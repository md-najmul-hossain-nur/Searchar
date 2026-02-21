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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = strtolower(trim((string)$_SESSION['role']));

function recipientEntitiesForRole(string $role): array {
    return match ($role) {
        'user' => ['user', 'users'],
        'police' => ['police', 'policeman', 'policemen'],
        'volunteer' => ['volunteer', 'volunteers'],
        'contributor' => ['contributor', 'camera_contributor', 'camera_contributors'],
        default => ['user'],
    };
}

try {
    $entities = recipientEntitiesForRole($role);
    $entityPlaceholders = implode(', ', array_fill(0, count($entities), '?'));

    $sql = "UPDATE user_notifications
            SET is_read = 1
            WHERE ((recipient_entity IN ({$entityPlaceholders}) AND recipient_id IN (?, 0))
                   OR (recipient_entity IN ('all', 'broadcast') AND recipient_id IN (0, ?)))";

    $stmt = $pdo->prepare($sql);
    $params = array_merge($entities, [$userId, $userId]);
    $stmt->execute($params);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('mark_all_notifications_read error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark all notifications read']);
}
