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
        'contributor', 'camera_contributor', 'camera_contributors' => 'contributor',
        default => $r !== '' ? $r : 'user',
    };
}

function roleLookupConfig(string $role): ?array {
    return match ($role) {
        'user' => ['table' => 'users', 'id_col' => 'user_id', 'name_col' => 'full_name'],
        'police' => ['table' => 'policemen', 'id_col' => 'police_id', 'name_col' => 'full_name'],
        'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'name_col' => 'full_name'],
        'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'name_col' => 'full_name'],
        default => null,
    };
}

function lookupDisplayName(PDO $pdo, string $role, int $id): string {
    if ($role === 'admin') {
        return 'Admin Desk';
    }

    $cfg = roleLookupConfig($role);
    if (!$cfg || $id <= 0) {
        return ucfirst($role ?: 'User');
    }

    try {
        $stmt = $pdo->prepare("SELECT {$cfg['name_col']} FROM {$cfg['table']} WHERE {$cfg['id_col']} = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $name = trim((string)$stmt->fetchColumn());
        return $name !== '' ? $name : ucfirst($role);
    } catch (Throwable $e) {
        return ucfirst($role);
    }
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
        $stmt = $pdo->query("SELECT c.id, c.user_id, c.role, c.last_message_at, c.updated_at,
            (SELECT COUNT(1) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_role <> 'admin') AS unread_count,
            (SELECT COALESCE(m.content, m.message) FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message
            FROM conversations c ORDER BY c.last_message_at DESC, c.created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['display_name'] = lookupDisplayName($pdo, (string)($row['role'] ?? 'user'), (int)($row['user_id'] ?? 0));
            $row['role'] = (string)($row['role'] ?? 'user');
        }
        echo json_encode(['success' => true, 'rows' => $rows]);
        exit;
    }

    // regular user: return their conversation (one per user)
    $stmt = $pdo->prepare('SELECT id, user_id, role, last_message_at, updated_at,
        (SELECT COUNT(1) FROM messages m WHERE m.conversation_id = conversations.id AND m.is_read = 0 AND m.sender_role <> :role) AS unread_count,
        (SELECT COALESCE(m.content, m.message) FROM messages m WHERE m.conversation_id = conversations.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_message
        FROM conversations WHERE user_id = :uid AND role = :role LIMIT 1');
    $stmt->execute([':uid' => $userId, ':role' => $role]);
    $conv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conv) {
        // nothing yet
        echo json_encode(['success' => true, 'rows' => []]);
        exit;
    }

    $conv['display_name'] = 'Admin Desk';

    $msgStmt = $pdo->prepare('SELECT id, sender_role, sender_id, receiver_role, receiver_id, COALESCE(content, message) AS content, is_read, created_at FROM messages WHERE conversation_id = :cid ORDER BY created_at ASC');
    $msgStmt->execute([':cid' => $conv['id']]);
    $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'conversation' => $conv, 'messages' => $messages]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch conversations']);
}
