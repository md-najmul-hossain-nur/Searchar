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
        'admin' => 'admin',
        default => $r !== '' ? $r : 'user',
    };
}

function roleConfig(string $role): ?array {
    return match ($role) {
        'user' => ['table' => 'users', 'id_col' => 'user_id', 'name_col' => 'full_name'],
        'police' => ['table' => 'policemen', 'id_col' => 'police_id', 'name_col' => 'full_name'],
        'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'name_col' => 'full_name'],
        'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'name_col' => 'full_name'],
        default => null,
    };
}

function displayName(PDO $pdo, string $role, int $id): string {
    if ($role === 'admin') return 'Admin Desk';
    $cfg = roleConfig($role);
    if (!$cfg || $id <= 0) return ucfirst($role ?: 'user');
    try {
        $stmt = $pdo->prepare("SELECT {$cfg['name_col']} FROM {$cfg['table']} WHERE {$cfg['id_col']} = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $name = trim((string)$stmt->fetchColumn());
        return $name !== '' ? $name : ucfirst($role);
    } catch (Throwable $e) {
        return ucfirst($role);
    }
}

function ensureConversation(PDO $pdo, int $userId, string $role): array {
    $stmt = $pdo->prepare('SELECT id, user_id, role, last_message_at FROM conversations WHERE user_id = :uid AND role = :role LIMIT 1');
    $stmt->execute([':uid' => $userId, ':role' => $role]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($conversation) {
        return $conversation;
    }

    $ins = $pdo->prepare('INSERT INTO conversations (user_id, role, last_message_at) VALUES (:uid, :role, NOW())');
    $ins->execute([':uid' => $userId, ':role' => $role]);
    return [
        'id' => (int)$pdo->lastInsertId(),
        'user_id' => $userId,
        'role' => $role,
        'last_message_at' => date('Y-m-d H:i:s'),
    ];
}

function broadcastChat(array $message): void {
    $payload = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return;
    }

    $secret = (string)($_ENV['CHAT_BROADCAST_SECRET'] ?? getenv('CHAT_BROADCAST_SECRET') ?: '');
    $headers = [
        'Content-Type: application/json',
    ];
    if ($secret !== '') {
        $headers[] = 'X-Internal-Secret: ' . $secret;
    }

    $ch = curl_init('http://127.0.0.1:3000/broadcast-chat');
    if (!$ch) return;
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 4,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sessionRole = canonicalRole((string)$_SESSION['role']);
$sessionUserId = (int)$_SESSION['user_id'];
$messageText = trim((string)($_POST['message'] ?? ''));
$conversationId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;

if ($messageText === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
    exit;
}

try {
    if ($sessionRole === 'admin') {
        if ($conversationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Conversation required']);
            exit;
        }

        $convStmt = $pdo->prepare('SELECT id, user_id, role FROM conversations WHERE id = :id LIMIT 1');
        $convStmt->execute([':id' => $conversationId]);
        $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conversation not found']);
            exit;
        }

        $senderRole = 'admin';
        $senderId = 0;
        $receiverRole = canonicalRole((string)($conversation['role'] ?? 'user'));
        $receiverId = (int)($conversation['user_id'] ?? 0);
    } else {
        $senderRole = $sessionRole;
        $senderId = $sessionUserId;
        $receiverRole = 'admin';
        $receiverId = 0;

        if ($conversationId <= 0) {
            $conversation = ensureConversation($pdo, $sessionUserId, $sessionRole);
            $conversationId = (int)$conversation['id'];
        } else {
            $convStmt = $pdo->prepare('SELECT id, user_id, role FROM conversations WHERE id = :id LIMIT 1');
            $convStmt->execute([':id' => $conversationId]);
            $conversation = $convStmt->fetch(PDO::FETCH_ASSOC);
            if (!$conversation || (int)$conversation['user_id'] !== $sessionUserId || canonicalRole((string)$conversation['role']) !== $sessionRole) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden']);
                exit;
            }
        }
    }

    $insert = $pdo->prepare('INSERT INTO messages (conversation_id, sender_role, sender_id, receiver_role, receiver_id, message, content, is_read, created_at) VALUES (:conversation_id, :sender_role, :sender_id, :receiver_role, :receiver_id, :message, :content, 0, NOW())');
    $insert->execute([
        ':conversation_id' => $conversationId,
        ':sender_role' => $senderRole,
        ':sender_id' => $senderId,
        ':receiver_role' => $receiverRole,
        ':receiver_id' => $receiverId,
        ':message' => $messageText,
        ':content' => $messageText,
    ]);

    $messageId = (int)$pdo->lastInsertId();
    $createdAt = $pdo->query('SELECT created_at FROM messages WHERE id = ' . (int)$messageId . ' LIMIT 1')->fetchColumn();

    $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = :id')->execute([':id' => $conversationId]);

    $payload = [
        'success' => true,
        'message' => [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_role' => $senderRole,
            'sender_id' => $senderId,
            'receiver_role' => $receiverRole,
            'receiver_id' => $receiverId,
            'content' => $messageText,
            'is_read' => 0,
            'created_at' => $createdAt ?: date('c'),
            'display_name' => displayName($pdo, $senderRole, $senderId),
        ],
    ];

    broadcastChat($payload['message']);

    echo json_encode($payload);
} catch (Throwable $e) {
    error_log('chat_send_message error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
