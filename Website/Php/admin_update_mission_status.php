<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
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

    if (!columnExists($pdo, 'user_notifications', 'meta_json')) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN meta_json TEXT NULL AFTER message");
    }
    if (!columnExists($pdo, 'user_notifications', 'target_post_id')) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$missionId = (int)($payload['mission_id'] ?? 0);
$action = strtolower(trim((string)($payload['action'] ?? '')));

$allowed = ['accept', 'busy', 'complete'];
if ($missionId <= 0 || !in_array($action, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$statusMap = [
    'accept' => ['status' => 'accepted', 'response_status' => 'accepted'],
    'busy' => ['status' => 'rejected_busy', 'response_status' => 'rejected_busy'],
    'complete' => ['status' => 'completed', 'response_status' => 'completed'],
];

$target = $statusMap[$action];

try {
    if (!tableExists($pdo, 'volunteer_missions')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Volunteer missions table not found']);
        exit;
    }

    if (!columnExists($pdo, 'volunteer_missions', 'response_status')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'completed_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }

    $selMission = $pdo->prepare('SELECT mission_id, volunteer_id, mission_title, status, response_status, proof_file FROM volunteer_missions WHERE mission_id = :mid LIMIT 1');
    $selMission->execute([':mid' => $missionId]);
    $mission = $selMission->fetch(PDO::FETCH_ASSOC);
    if (!$mission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mission not found']);
        exit;
    }

    if ($action === 'complete') {
        $proofFile = trim((string)($mission['proof_file'] ?? ''));
        if ($proofFile === '') {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Proof is required before marking mission complete'
            ]);
            exit;
        }
    }

    $setCompleted = $action === 'complete' ? ', completed_at = NOW()' : '';
    $sql = "UPDATE volunteer_missions SET status = :status, response_status = :response_status{$setCompleted} WHERE mission_id = :mid LIMIT 1";
    $upd = $pdo->prepare($sql);
    $upd->execute([
        ':status' => $target['status'],
        ':response_status' => $target['response_status'],
        ':mid' => $missionId,
    ]);

    if ($upd->rowCount() < 1) {
        $currentStatus = strtolower((string)($mission['status'] ?? ''));
        $currentResponse = strtolower((string)($mission['response_status'] ?? ''));
        if ($currentStatus === strtolower($target['status']) && $currentResponse === strtolower($target['response_status'])) {
            echo json_encode([
                'success' => true,
                'mission_id' => $missionId,
                'status' => $target['status'],
                'response_status' => $target['response_status'],
                'already_updated' => true,
            ]);
            exit;
        }

        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Mission status update failed']);
        exit;
    }

    if ($action === 'complete') {
        ensureNotificationsTable($pdo);

        $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, meta_json) VALUES (:entity, :rid, :title, :message, :level, 0, :meta_json)');
        $notify->execute([
            ':entity' => 'volunteer',
            ':rid' => (int)($mission['volunteer_id'] ?? 0),
            ':title' => 'Mission Completed & XP Added',
            ':message' => 'Thanks for your service! Admin reviewed and marked your mission as complete. You earned +20 XP for "' . (string)($mission['mission_title'] ?? 'Mission') . '".',
            ':level' => 'success',
            ':meta_json' => json_encode([
                'mission_id' => $missionId,
                'event' => 'admin_mark_complete',
                'xp' => 20,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    echo json_encode([
        'success' => true,
        'mission_id' => $missionId,
        'status' => $target['status'],
        'response_status' => $target['response_status'],
    ]);
} catch (Throwable $e) {
    error_log('admin_update_mission_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update mission status']);
}
