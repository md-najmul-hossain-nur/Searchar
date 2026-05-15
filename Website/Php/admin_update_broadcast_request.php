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
$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdminSession = ($sessionRole === 'admin');

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$isAdminPanelRef = false;
if ($referer !== '') {
    $isAdminPanelRef = (
        stripos($referer, '/Website/Html/Admin.html') !== false ||
        stripos($referer, '/Website/Html/Admin.php') !== false ||
        stripos($referer, '/Html/Admin.html') !== false ||
        stripos($referer, '/Html/Admin.php') !== false
    );
}

if (!$isAdminSession && !$isAdminPanelRef) {
    respond(['success' => false, 'error' => 'Unauthorized'], 403);
}

$requestId = (int)($_POST['request_id'] ?? 0);
$action = strtolower(trim((string)($_POST['action'] ?? '')));
$reason = trim((string)($_POST['reason'] ?? ''));
if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    respond(['success' => false, 'error' => 'Invalid request'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT notification_id, message, meta_json FROM user_notifications WHERE notification_id = :id AND recipient_entity = 'admin' AND title = 'Broadcast Request' LIMIT 1");
    $stmt->execute(['id' => $requestId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        respond(['success' => false, 'error' => 'Request not found'], 404);
    }

    $metaRaw = (string)($row['meta_json'] ?? '');
    $meta = json_decode($metaRaw, true);
    $meta = is_array($meta) ? $meta : [];

    $policeId = (int)($meta['police_id'] ?? 0);
    $policeName = (string)($meta['police_name'] ?? 'Police Officer');
    $station = (string)($meta['station'] ?? '');

    $nextStatus = $action === 'approve' ? 'approved' : 'rejected';
    $meta['status'] = $nextStatus;
    $meta['actioned_at'] = date('Y-m-d H:i:s');
    if ($reason !== '') {
        $meta['reason'] = $reason;
    }

    $upd = $pdo->prepare('UPDATE user_notifications SET is_read = 1, meta_json = :meta WHERE notification_id = :id');
    $upd->execute(['meta' => json_encode($meta), 'id' => $requestId]);

    if ($policeId > 0) {
        $title = 'Broadcast Approval';
        $msg = $nextStatus === 'approved'
            ? sprintf('Your broadcast request was approved. You can join the broadcast desk now.')
            : sprintf('Your broadcast request was rejected by admin.');
        if ($reason !== '') {
            $msg .= ' Reason: ' . $reason;
        }

        $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read) VALUES (:entity, :rid, :title, :message, :meta, :level, 0)');
        $ins->execute([
            'entity' => 'police',
            'rid' => $policeId,
            'title' => $title,
            'message' => $msg,
            'meta' => json_encode([
                'type' => 'broadcast_request',
                'status' => $nextStatus,
                'reason' => $reason,
                'request_id' => $requestId,
                'police_id' => $policeId,
                'police_name' => $policeName,
                'station' => $station
            ]),
            'level' => $nextStatus === 'approved' ? 'success' : 'warning'
        ]);
    }

    respond(['success' => true, 'status' => $nextStatus]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to update request'], 500);
}
