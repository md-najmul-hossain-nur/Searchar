<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = strtolower(trim((string)$_SESSION['role']));
$userId = (int)$_SESSION['user_id'];
if ($role !== 'volunteer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only volunteers can respond']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$notificationId = (int)($payload['notification_id'] ?? 0);
$action = strtolower(trim((string)($payload['action'] ?? '')));

if ($notificationId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$responseTag = $action === 'accept' ? '[Response: accepted]' : '[Response: rejected_busy]';

try {
    $sel = $pdo->prepare('SELECT notification_id, message FROM user_notifications WHERE notification_id = :id AND recipient_entity IN ("volunteer", "volunteers") AND recipient_id = :rid LIMIT 1');
    $sel->execute([':id' => $notificationId, ':rid' => $userId]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment notification not found']);
        exit;
    }

    $message = (string)($row['message'] ?? '');
    if (!str_contains(strtolower($message), '[response: accepted]') && !str_contains(strtolower($message), '[response: rejected_busy]')) {
        $message = trim($message . ' ' . $responseTag);
    } else {
        $message = preg_replace('/\[Response:\s*(accepted|rejected_busy)\]/i', $responseTag, $message) ?: $message;
    }

    $upd = $pdo->prepare('UPDATE user_notifications SET message = :message, is_read = 1 WHERE notification_id = :id AND recipient_id = :rid');
    $upd->execute([
        ':message' => $message,
        ':id' => $notificationId,
        ':rid' => $userId,
    ]);

    echo json_encode(['success' => true, 'action' => $action]);
} catch (Throwable $e) {
    error_log('volunteer_assignment_response error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update assignment response']);
}
