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

try {
    $stmt = $pdo->prepare("SELECT notification_id, message, meta_json, created_at, is_read FROM user_notifications WHERE recipient_entity = 'admin' AND title = 'Broadcast Request' ORDER BY notification_id DESC LIMIT 200");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $out = [];
    foreach ($rows as $row) {
        $metaRaw = (string)($row['meta_json'] ?? '');
        $meta = json_decode($metaRaw, true);
        $meta = is_array($meta) ? $meta : [];

        $status = (string)($meta['status'] ?? '');
        if ($status === '') {
            $status = (int)($row['is_read'] ?? 0) === 1 ? 'approved' : 'pending';
        }

        $out[] = [
            'request_id' => (int)($row['notification_id'] ?? 0),
            'police_id' => (int)($meta['police_id'] ?? 0),
            'police_name' => (string)($meta['police_name'] ?? 'Police Officer'),
            'station' => (string)($meta['station'] ?? ''),
            'status' => $status,
            'reason' => (string)($meta['reason'] ?? ''),
            'request_reason' => (string)($meta['request_reason'] ?? ''),
            'message' => (string)($row['message'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? '')
        ];
    }

    respond(['success' => true, 'rows' => $out]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to load broadcast requests'], 500);
}
