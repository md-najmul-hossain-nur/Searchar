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

$userId = (int)$_SESSION['user_id'];
$role = strtolower(trim((string)$_SESSION['role']));

$convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
if ($convId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing conversation_id']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $convId]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$conv) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Conversation not found']);
        exit;
    }

    if (strtolower($role) !== 'admin' && (int)$conv['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    $upd = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = :cid AND sender_role <> :me');
    $upd->execute([':cid' => $convId, ':me' => $role === 'admin' ? 'admin' : $role]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark read']);
}
