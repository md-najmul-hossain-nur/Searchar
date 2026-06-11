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
$reason = trim((string)($_POST['reason'] ?? ''));

try {
    $stmt = $pdo->prepare('SELECT full_name, city FROM policemen WHERE police_id = :id LIMIT 1');
    $stmt->execute(['id' => $policeId]);
    $police = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $policeName = (string)($police['full_name'] ?? 'Police Officer');
    $station = (string)($police['city'] ?? '');

    $needle = '%"police_id":' . $policeId . '%';
    $check = $pdo->prepare("SELECT notification_id FROM user_notifications WHERE recipient_entity = 'admin' AND title = 'Broadcast Request' AND is_read = 0 AND meta_json LIKE :needle ORDER BY notification_id DESC LIMIT 1");
    $check->execute(['needle' => $needle]);
    if ($check->fetchColumn()) {
        respond(['success' => true, 'status' => 'pending', 'message' => 'Request already pending.']);
    }

    $meta = json_encode([
        'type' => 'broadcast_request',
        'police_id' => $policeId,
        'police_name' => $policeName,
        'station' => $station,
        'status' => 'pending',
        'request_reason' => $reason
    ]);

    $message = sprintf('Broadcast request from %s (%s).', $policeName, $station !== '' ? $station : 'Station not set');
    if ($reason !== '') {
        $message .= ' Reason: ' . $reason;
    }

    $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read) VALUES (:entity, :rid, :title, :message, :meta, :level, 0)');
    $ins->execute([
        'entity' => 'admin',
        'rid' => 0,
        'title' => 'Broadcast Request',
        'message' => $message,
        'meta' => $meta,
        'level' => 'info'
    ]);

    respond(['success' => true, 'status' => 'pending', 'message' => 'Request sent. Waiting for admin approval...']);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to submit request: ' . $e->getMessage()], 500);
}
