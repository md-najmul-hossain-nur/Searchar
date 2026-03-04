<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function ensureCommentReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        comment_id BIGINT UNSIGNED NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        comment_author_role VARCHAR(50) DEFAULT NULL,
        comment_author_id INT UNSIGNED DEFAULT NULL,
        comment_author_name VARCHAR(255) DEFAULT NULL,
        comment_text TEXT DEFAULT NULL,
        reporter_role VARCHAR(50) NOT NULL,
        reporter_id INT UNSIGNED NOT NULL,
        reporter_name VARCHAR(255) NOT NULL,
        report_category VARCHAR(80) NOT NULL,
        report_details TEXT DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        admin_action_note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actioned_at DATETIME DEFAULT NULL,
        PRIMARY KEY (report_id),
        KEY idx_comment_reports_comment (comment_id),
        KEY idx_comment_reports_post (post_id),
        KEY idx_comment_reports_status (status),
        KEY idx_comment_reports_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function resolveRoleMeta(string $role): array {
    return match (strtolower(trim($role))) {
        'user' => ['users', 'user_id'],
        'volunteer' => ['volunteers', 'volunteer_id'],
        'police', 'policeman' => ['policemen', 'police_id'],
        'contributor', 'camera_contributor' => ['camera_contributors', 'camera_id'],
        default => ['', ''],
    };
}

function canonicalRole(string $role): string {
    $raw = strtolower(trim($role));
    return match ($raw) {
        'user', 'users' => 'user',
        'police', 'policeman', 'policemen' => 'police',
        'volunteer', 'volunteers' => 'volunteer',
        'contributor', 'camera_contributor', 'camera_contributors' => 'contributor',
        default => 'user',
    };
}

try {
    $payload = [];
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $payload = $json;
        }
    }
    if (!$payload) {
        $payload = $_POST;
    }

    $role = canonicalRole((string)($_SESSION['role'] ?? ''));
    $reporterId = (int)($_SESSION['user_id'] ?? 0);
    if ($reporterId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please login first']);
        exit;
    }

    $postId = (int)($payload['post_id'] ?? 0);
    $commentId = (int)($payload['comment_id'] ?? 0);
    $reportCategory = trim((string)($payload['report_category'] ?? ''));
    $reportDetails = trim((string)($payload['report_details'] ?? ''));

    $allowedCategories = [
        'Spam or misleading',
        'Harassment or hate speech',
        'Violence or dangerous content',
        'Sexual or explicit content',
        'Fraud or scam',
        'Privacy violation',
        'Other'
    ];

    if ($postId <= 0 || $commentId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid post/comment']);
        exit;
    }

    if (!in_array($reportCategory, $allowedCategories, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report category']);
        exit;
    }

    if (!tableExists($pdo, 'post_comments')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comments table not found']);
        exit;
    }

    ensureCommentReportsTable($pdo);

    $commentStmt = $pdo->prepare("SELECT comment_id, post_id, actor_role, actor_id, comment_text FROM post_comments WHERE comment_id = :comment_id LIMIT 1");
    $commentStmt->execute([':comment_id' => $commentId]);
    $commentRow = $commentStmt->fetch(PDO::FETCH_ASSOC);
    if (!$commentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
        exit;
    }

    $commentPostId = (int)($commentRow['post_id'] ?? 0);
    if ($commentPostId !== $postId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Comment/post mismatch']);
        exit;
    }

    [$reporterTable, $reporterIdCol] = resolveRoleMeta($role);
    $reporterName = 'Unknown';
    if ($reporterTable !== '' && $reporterIdCol !== '') {
        $nameStmt = $pdo->prepare("SELECT full_name FROM {$reporterTable} WHERE {$reporterIdCol} = :id LIMIT 1");
        $nameStmt->execute([':id' => $reporterId]);
        $reporterName = trim((string)($nameStmt->fetchColumn() ?: '')) ?: 'Unknown';
    }

    $commentAuthorRole = canonicalRole((string)($commentRow['actor_role'] ?? 'user'));
    $commentAuthorId = (int)($commentRow['actor_id'] ?? 0);
    [$authorTable, $authorIdCol] = resolveRoleMeta($commentAuthorRole);
    $commentAuthorName = 'Unknown';
    if ($authorTable !== '' && $authorIdCol !== '' && $commentAuthorId > 0) {
        $authorStmt = $pdo->prepare("SELECT full_name FROM {$authorTable} WHERE {$authorIdCol} = :id LIMIT 1");
        $authorStmt->execute([':id' => $commentAuthorId]);
        $commentAuthorName = trim((string)($authorStmt->fetchColumn() ?: '')) ?: 'Unknown';
    }

    $dupStmt = $pdo->prepare("SELECT report_id FROM comment_reports WHERE comment_id = :comment_id AND reporter_role = :role AND reporter_id = :rid AND status IN ('pending', 'under_review') ORDER BY report_id DESC LIMIT 1");
    $dupStmt->execute([
        ':comment_id' => $commentId,
        ':role' => $role,
        ':rid' => $reporterId
    ]);
    $existingId = (int)($dupStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        echo json_encode([
            'success' => true,
            'already_exists' => true,
            'report_id' => $existingId,
            'message' => 'You already reported this comment/reply. Admin will review it.'
        ]);
        exit;
    }

    $insertStmt = $pdo->prepare("INSERT INTO comment_reports (comment_id, post_id, comment_author_role, comment_author_id, comment_author_name, comment_text, reporter_role, reporter_id, reporter_name, report_category, report_details) VALUES (:comment_id, :post_id, :comment_author_role, :comment_author_id, :comment_author_name, :comment_text, :reporter_role, :reporter_id, :reporter_name, :report_category, :report_details)");
    $insertStmt->execute([
        ':comment_id' => $commentId,
        ':post_id' => $postId,
        ':comment_author_role' => $commentAuthorRole,
        ':comment_author_id' => $commentAuthorId,
        ':comment_author_name' => $commentAuthorName,
        ':comment_text' => (string)($commentRow['comment_text'] ?? ''),
        ':reporter_role' => $role,
        ':reporter_id' => $reporterId,
        ':reporter_name' => $reporterName,
        ':report_category' => $reportCategory,
        ':report_details' => $reportDetails !== '' ? $reportDetails : null,
    ]);

    echo json_encode([
        'success' => true,
        'report_id' => (int)$pdo->lastInsertId(),
        'reporter_name' => $reporterName,
    ]);
} catch (Throwable $e) {
    error_log('submit_comment_report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit comment report'
    ]);
}
