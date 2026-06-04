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

$convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
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

    // permission: admin can access all, others only their own
    if (strtolower($role) !== 'admin' && (int)$conv['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }

    $msgStmt = $pdo->prepare('SELECT id, sender_role, sender_id, receiver_role, receiver_id, COALESCE(content, message) AS content, is_read, created_at FROM messages WHERE conversation_id = :cid ORDER BY created_at ASC');
    $msgStmt->execute([':cid' => $convId]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch messages']);
}
