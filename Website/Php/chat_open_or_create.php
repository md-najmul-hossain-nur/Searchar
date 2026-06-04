<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function canonicalRole(string $role): string {
    $r = strtolower(trim($role));
    return match ($r) {
        'user', 'users' => 'user',
        'police', 'policeman', 'policemen' => 'police',
        'volunteer', 'volunteers' => 'volunteer',
        'contributor', 'camera_contributor', 'camera_contributors', 'cameraman', 'camera_man' => 'contributor',
        default => $r !== '' ? $r : 'user',
    };
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = canonicalRole((string)$_SESSION['role']);

try {
    if ($role === 'admin') {
        echo json_encode(['success' => true, 'conversation' => null]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM conversations WHERE user_id = :uid AND role = :role LIMIT 1');
    $stmt->execute([':uid' => $userId, ':role' => $role]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        $ins = $pdo->prepare('INSERT INTO conversations (user_id, role, last_message_at) VALUES (:uid, :role, NOW())');
        $ins->execute([':uid' => $userId, ':role' => $role]);
        $conversationId = (int)$pdo->lastInsertId();
        $stmt->execute([':uid' => $userId, ':role' => $role]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            $conversation = ['id' => $conversationId, 'user_id' => $userId, 'role' => $role, 'last_message_at' => null];
        }
    }

    echo json_encode(['success' => true, 'conversation' => $conversation]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to initialize conversation']);
}
