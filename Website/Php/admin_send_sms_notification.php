<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdminSession = ($sessionRole === 'admin');

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$isAdminPanelRef = false;
if ($referer !== '') {
    $isAdminPanelRef = (
        stripos($referer, '/Website/Html/Admin.html') !== false ||
        stripos($referer, '/Website/Html/Admin.php') !== false
    );
}

if (!$isAdminSession && !$isAdminPanelRef) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$entity = strtolower(trim((string)($payload['entity'] ?? '')));
$recipientId = (int)($payload['id'] ?? 0);
$message = trim((string)($payload['message'] ?? ''));

if ($recipientId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid recipient id']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}

if (mb_strlen($message) > 220) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message too long (max 220 chars)']);
    exit;
}

$entityMap = [
    'users' => 'users',
    'volunteers' => 'volunteers',
    'camera_contributors' => 'camera_contributors',
    'policemen' => 'policemen',
];

if (!isset($entityMap[$entity])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported recipient entity']);
    exit;
}

$recipientEntity = $entityMap[$entity];

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

    $insert = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, NULL)');
    $insert->execute([
        ':entity' => $recipientEntity,
        ':rid' => $recipientId,
        ':title' => 'SMS from Admin',
        ':message' => $message,
        ':level' => 'warning',
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('admin_send_sms_notification error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not send SMS notification']);
}
