<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'police' || empty($_SESSION['user_id'])) {
    respond(['success' => false, 'error' => 'Unauthorized'], 403);
}

$policeId = (int) $_SESSION['user_id'];

try {
    $needle = '%"police_id":' . $policeId . '%';
    $stmt = $pdo->prepare("SELECT notification_id, message, meta_json, created_at FROM user_notifications WHERE recipient_entity = 'admin' AND title = 'Broadcast Request' AND meta_json LIKE :needle ORDER BY notification_id DESC LIMIT 1");
    $stmt->execute(['needle' => $needle]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        respond(['success' => true, 'status' => 'none']);
    }

    $metaRaw = (string)($row['meta_json'] ?? '');
    $meta = json_decode($metaRaw, true);
    $status = is_array($meta) ? (string)($meta['status'] ?? 'pending') : 'pending';
    $reason = is_array($meta) ? (string)($meta['reason'] ?? '') : '';

    respond([
        'success' => true,
        'status' => $status,
        'request_id' => (int)($row['notification_id'] ?? 0),
        'message' => (string)($row['message'] ?? ''),
        'reason' => $reason,
        'created_at' => (string)($row['created_at'] ?? '')
    ]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to fetch status'], 500);
}
