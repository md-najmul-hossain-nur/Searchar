<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
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

function normalizeRecipientEntity(string $authorRole): string {
    return match (strtolower($authorRole)) {
        'police' => 'policeman',
        'volunteer' => 'volunteer',
        'contributor', 'camera_contributor' => 'camera_contributor',
        default => 'user',
    };
}

function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }
}

$allowedActions = [
    'approve' => 'approved',
    'reject'  => 'rejected',
    'make_report' => 'reported',
];

try {
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $action = $_POST['action'] ?? '';

    if ($postId <= 0 || !isset($allowedActions[$action])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid post_id or action']);
        exit;
    }

    if (!tableExists($pdo, 'posts')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Posts table not found']);
        exit;
    }

    if (!columnExists($pdo, 'posts', 'status')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER share_anonymous");
    }
    if (!columnExists($pdo, 'posts', 'report_status')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN report_status VARCHAR(20) DEFAULT 'not_reported' AFTER status");
    }
    if (!columnExists($pdo, 'posts', 'reported_at')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN reported_at DATETIME DEFAULT NULL AFTER report_status");
    }

    $postStmt = $pdo->prepare('SELECT id, author_role, author_id, author_name, status, report_status FROM posts WHERE id = :id LIMIT 1');
    $postStmt->execute([':id' => $postId]);
    $postRow = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$postRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    $authorRole = (string)($postRow['author_role'] ?? 'user');
    $authorId = (int)($postRow['author_id'] ?? 0);
    $recipientEntity = normalizeRecipientEntity($authorRole);

    ensureNotificationsTable($pdo);

    $targetStatus = $allowedActions[$action];

    if ($action === 'make_report') {
        $stmt = $pdo->prepare("UPDATE posts SET report_status = 'reported', reported_at = NOW() WHERE id = :id AND LOWER(COALESCE(report_status, 'not_reported')) <> 'reported'");
        $stmt->execute([':id' => $postId]);

        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT report_status FROM posts WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $postId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Post not found']);
                exit;
            }

            echo json_encode([
                'success' => false,
                'error' => 'This post is already reported',
                'report_status' => $row['report_status'] ?? 'reported',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'status' => (string)($postRow['status'] ?? 'pending'),
            'report_status' => 'reported',
        ]);
        exit;
    }

    if ($action === 'reject') {
        // Delete only if still pending/null
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id AND (status IS NULL OR status = 'pending')");
        $stmt->execute([':id' => $postId]);

        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT status FROM posts WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $postId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Post not found']);
                exit;
            }
            echo json_encode([
                'success' => false,
                'error' => 'Post already decided',
                'status' => $row['status'] ?? 'pending',
            ]);
            exit;
        }

        if ($authorId > 0) {
            $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
            $notify->execute([
                ':entity' => $recipientEntity,
                ':rid' => $authorId,
                ':title' => 'Your post was rejected',
                ':message' => 'An admin rejected your post and it has been removed.',
                ':level' => 'warning',
                ':post_id' => $postId,
            ]);
        }

        echo json_encode([
            'success' => true,
            'status' => 'deleted',
            'deleted' => true,
        ]);
        exit;
    }

    // Approve path: update only if still pending/null
    $stmt = $pdo->prepare("UPDATE posts SET status = :status WHERE id = :id AND (status IS NULL OR status = 'pending')");
    $stmt->execute([':status' => $targetStatus, ':id' => $postId]);

    if ($stmt->rowCount() === 0) {
        // Check existing status to give a friendly message
        $chk = $pdo->prepare("SELECT status FROM posts WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $postId]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $current = $row['status'] ?? 'pending';
        echo json_encode([
            'success' => false,
            'error' => 'Post already decided',
            'status' => $current,
        ]);
        exit;
    }

    if ($authorId > 0) {
        $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
        $notify->execute([
            ':entity' => $recipientEntity,
            ':rid' => $authorId,
            ':title' => 'Your post was approved',
            ':message' => 'An admin approved your post.',
            ':level' => 'success',
            ':post_id' => $postId,
        ]);
    }

    echo json_encode([
        'success' => true,
        'status' => $targetStatus,
    ]);
} catch (Throwable $e) {
    error_log('admin_update_post_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update status',
    ]);
}
