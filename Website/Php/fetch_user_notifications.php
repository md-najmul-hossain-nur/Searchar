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

function recipientEntitiesForRole(string $role): array {
    return match ($role) {
        'user' => ['user', 'users'],
        'police' => ['police', 'policeman', 'policemen'],
        'volunteer' => ['volunteer', 'volunteers'],
        'contributor' => ['contributor', 'camera_contributor', 'camera_contributors'],
        default => ['user'],
    };
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function timeAgo(?string $datetime): string {
    if (!$datetime) return 'Just now';
    try {
        $created = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $created->getTimestamp();
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) {
            $minutes = (int)floor($diff / 60);
            return $minutes . ' min ago';
        }
        if ($diff < 86400) {
            $hours = (int)floor($diff / 3600);
            return $hours . ' hr ago';
        }
        if ($diff < 2592000) {
            $days = (int)floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        return $created->format('d M Y');
    } catch (Throwable $e) {
        return 'Just now';
    }
}

function detectSource(string $title, string $message): string {
    $hay = strtolower($title . ' ' . $message);
    if (str_contains($hay, 'admin')) return 'admin';
    if (str_contains($hay, 'police')) return 'police';
    if (str_contains($hay, 'sms')) return 'sms';
    if (str_contains($hay, 'comment')) return 'comment';
    if (str_contains($hay, 'like')) return 'like';
    if (str_contains($hay, 'share')) return 'share';
    return 'general';
}

try {
    $notifications = [];
    $seenNotificationKeys = [];
    $entities = recipientEntitiesForRole($role);
    $entityPlaceholders = implode(', ', array_fill(0, count($entities), '?'));

    if (tableExists($pdo, 'user_notifications')) {
        $hasTargetPost = columnExists($pdo, 'user_notifications', 'target_post_id');
        $hasMetaJson = columnExists($pdo, 'user_notifications', 'meta_json');
        $selectCols = "notification_id, title, message, level, is_read, created_at";
        if ($hasTargetPost) {
            $selectCols .= ", target_post_id";
        }
        if ($hasMetaJson) {
            $selectCols .= ", meta_json";
        }

        $sql = "SELECT {$selectCols}
            FROM user_notifications
            WHERE ((recipient_entity IN ({$entityPlaceholders}) AND recipient_id IN (?, 0))
                   OR (recipient_entity IN ('all', 'broadcast') AND recipient_id IN (0, ?)))
            ORDER BY created_at DESC
            LIMIT 250";

        $stmt = $pdo->prepare($sql);
        $params = array_merge($entities, [$userId, $userId]);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $title = trim((string)($row['title'] ?? 'Notification'));
            $message = trim((string)($row['message'] ?? ''));
            $level = strtolower((string)($row['level'] ?? 'info'));
            $source = detectSource($title, $message);

            $dedupeKey = strtolower($title . '|' . $message . '|' . $source . '|' . $level);
            if (isset($seenNotificationKeys[$dedupeKey])) {
                continue;
            }
            $seenNotificationKeys[$dedupeKey] = true;

            if ($source === 'admin' && $level === 'info') {
                $level = 'warning';
            }

            $notifications[] = [
                'id' => (int)($row['notification_id'] ?? 0),
                'title' => $title,
                'message' => $message,
                'source' => $source,
                'level' => $level,
                'is_read' => ((int)($row['is_read'] ?? 0)) === 1,
                'target_post_id' => isset($row['target_post_id']) && is_numeric($row['target_post_id']) ? (int)$row['target_post_id'] : null,
                'meta_json' => isset($row['meta_json']) ? (string)$row['meta_json'] : '',
                'created_at' => (string)($row['created_at'] ?? ''),
                'time_ago' => timeAgo((string)($row['created_at'] ?? '')),
            ];
        }
    }

    usort($notifications, static function (array $a, array $b): int {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });

    echo json_encode([
        'success' => true,
        'data' => $notifications,
    ]);
} catch (Throwable $e) {
    error_log('fetch_user_notifications error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch notifications']);
}
