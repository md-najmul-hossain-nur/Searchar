<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$postId = (int)($payload['post_id'] ?? 0);
$actionType = strtolower(trim((string)($payload['action_type'] ?? '')));

if ($postId <= 0 || !in_array($actionType, ['like', 'comment', 'share'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$actorRole = (string)$_SESSION['role'];
$actorId = (int)$_SESSION['user_id'];

function fetchActorName(PDO $pdo, string $role, int $id): string {
    $roleMap = [
        'user' => ['table' => 'users', 'id_col' => 'user_id'],
        'police' => ['table' => 'policemen', 'id_col' => 'police_id'],
        'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id'],
        'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id'],
    ];

    if (!isset($roleMap[$role])) {
        return 'Someone';
    }

    $table = $roleMap[$role]['table'];
    $idCol = $roleMap[$role]['id_col'];
    $stmt = $pdo->prepare("SELECT full_name FROM {$table} WHERE {$idCol} = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $name = $stmt->fetchColumn();
    return $name ? (string)$name : 'Someone';
}

function normalizeRecipientEntity(string $authorRole): string {
    return match ($authorRole) {
        'user' => 'user',
        'police' => 'policeman',
        'volunteer' => 'volunteer',
        'contributor' => 'camera_contributor',
        default => 'user',
    };
}

try {
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

    $postStmt = $pdo->prepare('SELECT id, author_role, author_id, author_name FROM posts WHERE id = :id LIMIT 1');
    $postStmt->execute([':id' => $postId]);
    $post = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        echo json_encode(['success' => true]);
        exit;
    }

    $authorRole = (string)($post['author_role'] ?? '');
    $authorId = (int)($post['author_id'] ?? 0);

    if ($authorId <= 0 || ($authorRole === $actorRole && $authorId === $actorId)) {
        echo json_encode(['success' => true]);
        exit;
    }

    $actorName = fetchActorName($pdo, $actorRole, $actorId);

    $title = match ($actionType) {
        'like' => 'Someone liked your post',
        'comment' => 'New comment on your post',
        'share' => 'Your post was shared',
        default => 'Post activity',
    };

    $message = match ($actionType) {
        'like' => $actorName . ' liked your post.',
        'comment' => $actorName . ' commented on your post.',
        'share' => $actorName . ' shared your post.',
        default => $actorName . ' interacted with your post.',
    };

    $recipientEntity = normalizeRecipientEntity($authorRole);

    $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
    $ins->execute([
        ':entity' => $recipientEntity,
        ':rid' => $authorId,
        ':title' => $title,
        ':message' => $message,
        ':level' => 'info',
        ':post_id' => $postId,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('notify_post_interaction error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create interaction notification']);
}
