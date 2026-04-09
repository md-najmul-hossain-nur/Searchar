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

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
}

function ensurePostReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL,
        post_author_role VARCHAR(50) DEFAULT NULL,
        post_author_id INT UNSIGNED DEFAULT NULL,
        post_author_name VARCHAR(255) DEFAULT NULL,
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
        KEY idx_post_reports_post (post_id),
        KEY idx_post_reports_reporter (reporter_role, reporter_id),
        KEY idx_post_reports_status (status),
        KEY idx_post_reports_created (created_at)
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

    $role = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $reporterId = (int)($_SESSION['user_id'] ?? 0);
    if ($role === '' || $reporterId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please login first']);
        exit;
    }

    $postId = (int)($payload['post_id'] ?? 0);
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

    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid post']);
        exit;
    }

    if (!in_array($reportCategory, $allowedCategories, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report category']);
        exit;
    }

    if (!tableExists($pdo, 'posts')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Posts table not found']);
        exit;
    }

    ensurePostReportsTable($pdo);

    $postStmt = $pdo->prepare("SELECT id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, created_at FROM posts WHERE id = :id LIMIT 1");
    $postStmt->execute([':id' => $postId]);
    $postRow = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$postRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    [$tableName, $idCol] = resolveRoleMeta($role);
    $reporterName = 'Unknown';
    if ($tableName !== '' && $idCol !== '') {
        $nameStmt = $pdo->prepare("SELECT full_name FROM {$tableName} WHERE {$idCol} = :id LIMIT 1");
        $nameStmt->execute([':id' => $reporterId]);
        $reporterName = trim((string)($nameStmt->fetchColumn() ?: '')) ?: 'Unknown';
    }

    $dupStmt = $pdo->prepare("SELECT report_id FROM post_reports WHERE post_id = :post_id AND reporter_role = :role AND reporter_id = :rid AND status IN ('pending', 'under_review') ORDER BY report_id DESC LIMIT 1");
    $dupStmt->execute([
        ':post_id' => $postId,
        ':role' => $role,
        ':rid' => $reporterId
    ]);
    $existingId = (int)($dupStmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        echo json_encode([
            'success' => true,
            'already_exists' => true,
            'report_id' => $existingId,
            'message' => 'You already reported this post. Admin will review it.'
        ]);
        exit;
    }

    $insertStmt = $pdo->prepare("INSERT INTO post_reports (post_id, post_author_role, post_author_id, post_author_name, reporter_role, reporter_id, reporter_name, report_category, report_details) VALUES (:post_id, :post_author_role, :post_author_id, :post_author_name, :reporter_role, :reporter_id, :reporter_name, :report_category, :report_details)");
    $insertStmt->execute([
        ':post_id' => $postId,
        ':post_author_role' => (string)($postRow['author_role'] ?? ''),
        ':post_author_id' => (int)($postRow['author_id'] ?? 0),
        ':post_author_name' => (string)($postRow['author_name'] ?? ''),
        ':reporter_role' => $role,
        ':reporter_id' => $reporterId,
        ':reporter_name' => $reporterName,
        ':report_category' => $reportCategory,
        ':report_details' => $reportDetails !== '' ? $reportDetails : null,
    ]);

    if (tableExists($pdo, 'posts')) {
        if (!columnExists($pdo, 'posts', 'report_status')) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN report_status VARCHAR(20) DEFAULT 'not_reported'");
        }
        if (!columnExists($pdo, 'posts', 'reported_at')) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN reported_at DATETIME DEFAULT NULL");
        }
        $markStmt = $pdo->prepare("UPDATE posts SET report_status = 'reported', reported_at = COALESCE(reported_at, NOW()) WHERE id = :id");
        $markStmt->execute([':id' => $postId]);
    }

    echo json_encode([
        'success' => true,
        'report_id' => (int)$pdo->lastInsertId(),
        'reporter_name' => $reporterName,
    ]);
} catch (Throwable $e) {
    error_log('submit_post_report error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to submit report'
    ]);
}
