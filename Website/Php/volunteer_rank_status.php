<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower((string)$_SESSION['role']) !== 'volunteer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$volunteerId = (int)$_SESSION['user_id'];

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

function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function maybeInsertCertificateUnlockNotification(PDO $pdo, int $recipientId, string $rank): void {
    $title = 'Admin Congratulations';
    $message = sprintf('Congratulations! Your %s certificate is unlocked.', $rank);

    $check = $pdo->prepare("SELECT notification_id
                           FROM user_notifications
                           WHERE recipient_entity IN ('volunteer', 'volunteers')
                             AND recipient_id = :rid
                             AND title = :title
                             AND message = :message
                           LIMIT 1");
    $check->execute([
        ':rid' => $recipientId,
        ':title' => $title,
        ':message' => $message,
    ]);

    if ($check->fetchColumn()) {
        return;
    }

    $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read) VALUES (:entity, :rid, :title, :message, :level, 0)');
    $ins->execute([
        ':entity' => 'volunteer',
        ':rid' => $recipientId,
        ':title' => $title,
        ':message' => $message,
        ':level' => 'info',
    ]);
}

function pointsToRank(int $points): array {
    if ($points >= 1000) {
        return ['rank' => 'Platinum Responder', 'next_rank' => null, 'next_points' => null];
    }
    if ($points >= 700) {
        return ['rank' => 'Gold Responder', 'next_rank' => 'Platinum Responder', 'next_points' => 1000];
    }
    if ($points >= 380) {
        return ['rank' => 'Silver Responder', 'next_rank' => 'Gold Responder', 'next_points' => 700];
    }
    return ['rank' => 'Bronze Volunteer', 'next_rank' => 'Silver Responder', 'next_points' => 380];
}

try {
    $completed = 0;
    $accepted = 0;
    $busy = 0;
    $autoClosedByPolice = 0;

    if (tableExists($pdo, 'volunteer_missions')) {
        $hasResponse = columnExists($pdo, 'volunteer_missions', 'response_status');
        $responseExpr = $hasResponse ? 'LOWER(COALESCE(response_status,\'\'))' : "''";
        $stmt = $pdo->prepare("SELECT
            SUM(CASE WHEN LOWER(status) = 'completed' OR {$responseExpr} = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN LOWER(status) = 'accepted' OR {$responseExpr} = 'accepted' THEN 1 ELSE 0 END) AS accepted_count,
            SUM(CASE WHEN LOWER(status) = 'rejected_busy' OR {$responseExpr} = 'rejected_busy' THEN 1 ELSE 0 END) AS busy_count,
            SUM(CASE WHEN LOWER(status) = 'closed_by_police' OR {$responseExpr} = 'closed_by_police' THEN 1 ELSE 0 END) AS auto_closed_by_police_count
            FROM volunteer_missions
            WHERE volunteer_id = :vid");
        $stmt->execute([':vid' => $volunteerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $completed = (int)($row['completed_count'] ?? 0);
        $accepted = (int)($row['accepted_count'] ?? 0);
        $busy = (int)($row['busy_count'] ?? 0);
        $autoClosedByPolice = (int)($row['auto_closed_by_police_count'] ?? 0);
    }

    $points = ($completed * 20) + ($accepted * 10) + ($autoClosedByPolice * 2);
    $rankInfo = pointsToRank($points);

    $pointsToNext = null;
    if ($rankInfo['next_points'] !== null) {
        $pointsToNext = max(0, (int)$rankInfo['next_points'] - $points);
    }

    $certificateUnlocked = in_array($rankInfo['rank'], ['Silver Responder', 'Gold Responder', 'Platinum Responder'], true);

    if ($certificateUnlocked) {
        ensureNotificationsTable($pdo);
        maybeInsertCertificateUnlockNotification($pdo, $volunteerId, (string)$rankInfo['rank']);
    }

    echo json_encode([
        'success' => true,
        'rules' => [
            'accepted_mission_xp' => 10,
            'completed_mission_xp' => 20,
            'auto_closed_by_police_xp' => 2,
            'ranks' => [
                ['name' => 'Bronze Volunteer', 'min_points' => 100],
                ['name' => 'Silver Responder', 'min_points' => 380],
                ['name' => 'Gold Responder', 'min_points' => 700],
                ['name' => 'Platinum Responder', 'min_points' => 1000],
            ],
        ],
        'stats' => [
            'accepted_missions' => $accepted,
            'completed_missions' => $completed,
            'busy_missions' => $busy,
            'auto_closed_by_police_missions' => $autoClosedByPolice,
            'points' => $points,
            'rank' => $rankInfo['rank'],
            'next_rank' => $rankInfo['next_rank'],
            'points_to_next_rank' => $pointsToNext,
            'certificate_unlocked' => $certificateUnlocked,
        ],
    ]);
} catch (Throwable $e) {
    error_log('volunteer_rank_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load rank status']);
}
