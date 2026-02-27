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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

$assignments = is_array($payload['assignments'] ?? null) ? $payload['assignments'] : [];
$context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

if (!$assignments) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No assignments provided']);
    exit;
}

$caseId = trim((string)($context['case_id'] ?? ''));
$landmark = trim((string)($context['landmark'] ?? ''));
$missionType = trim((string)($context['mission_type'] ?? 'locate_verify'));
$missionLabel = trim((string)($context['mission_label'] ?? 'Assigned Mission'));
$missionNote = trim((string)($context['mission_note'] ?? 'Please review and respond.'));
$media = is_array($context['media'] ?? null) ? $context['media'] : [];

$safeMedia = [];
foreach ($media as $m) {
    if (!is_array($m)) continue;
    $safeMedia[] = [
        'type' => trim((string)($m['type'] ?? 'media')),
        'url' => trim((string)($m['url'] ?? '')),
        'hash' => trim((string)($m['hash'] ?? '')),
    ];
}

$title = 'New Crime Assignment • ' . $missionLabel;
$messageBase = 'You have been assigned to a crime case';
if ($caseId !== '') {
    $messageBase .= ' (' . $caseId . ')';
}
if ($landmark !== '') {
    $messageBase .= ' near ' . $landmark;
}
$messageBase .= '. ' . $missionNote;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        meta_json TEXT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }

    $hasMeta = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'meta_json' LIMIT 1")->fetchColumn();
    if (!$hasMeta) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN meta_json TEXT NULL AFTER message");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_missions (
        mission_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        volunteer_id INT UNSIGNED NOT NULL,
        mission_title VARCHAR(190) NOT NULL,
        mission_details TEXT DEFAULT NULL,
        mission_location VARCHAR(255) DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'assigned',
        response_status VARCHAR(30) NOT NULL DEFAULT 'pending',
        case_ref VARCHAR(80) DEFAULT NULL,
        source_notification_id INT UNSIGNED DEFAULT NULL,
        proof_file VARCHAR(255) DEFAULT NULL,
        proof_submitted_at DATETIME DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        assigned_by VARCHAR(100) NOT NULL DEFAULT 'admin',
        assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (mission_id),
        INDEX idx_vm_volunteer (volunteer_id),
        INDEX idx_vm_status (status),
        INDEX idx_vm_response (response_status),
        INDEX idx_vm_source_notification (source_notification_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!columnExists($pdo, 'volunteer_missions', 'response_status')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'case_ref')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN case_ref VARCHAR(80) DEFAULT NULL AFTER response_status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'source_notification_id')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN source_notification_id INT UNSIGNED DEFAULT NULL AFTER case_ref");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'proof_file')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_file VARCHAR(255) DEFAULT NULL AFTER source_notification_id");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'proof_submitted_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_submitted_at DATETIME DEFAULT NULL AFTER proof_file");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'completed_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }

    $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read) VALUES (:entity, :rid, :title, :message, :meta_json, :level, 0)');
    $insMission = $pdo->prepare('INSERT INTO volunteer_missions (volunteer_id, mission_title, mission_details, mission_location, status, response_status, case_ref, assigned_by) VALUES (:volunteer_id, :mission_title, :mission_details, :mission_location, :status, :response_status, :case_ref, :assigned_by)');
    $updMissionSource = $pdo->prepare('UPDATE volunteer_missions SET source_notification_id = :nid WHERE mission_id = :mid LIMIT 1');

    $count = 0;
    foreach ($assignments as $a) {
        $entity = strtolower(trim((string)($a['recipient_entity'] ?? '')));
        $rid = (int)($a['recipient_id'] ?? 0);

        if (!in_array($entity, ['volunteer', 'camera_contributor', 'contributor', 'camera_contributors'], true)) {
            continue;
        }
        if ($rid <= 0) {
            continue;
        }

        $normalizedEntity = match ($entity) {
            'contributor', 'camera_contributors' => 'camera_contributor',
            default => $entity,
        };

        $missionId = null;
        if ($normalizedEntity === 'volunteer') {
            $insMission->execute([
                ':volunteer_id' => $rid,
                ':mission_title' => $missionLabel,
                ':mission_details' => $missionNote !== '' ? $missionNote : null,
                ':mission_location' => $landmark !== '' ? $landmark : null,
                ':status' => 'assigned',
                ':response_status' => 'pending',
                ':case_ref' => $caseId !== '' ? $caseId : null,
                ':assigned_by' => 'admin',
            ]);
            $missionId = (int)$pdo->lastInsertId();
        }

        $metaJson = json_encode([
            'case_id' => $caseId,
            'landmark' => $landmark,
            'mission_type' => $missionType,
            'mission_label' => $missionLabel,
            'mission_note' => $missionNote,
            'mission_id' => $missionId,
            'media' => $safeMedia,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ins->execute([
            ':entity' => $normalizedEntity,
            ':rid' => $rid,
            ':title' => $title,
            ':message' => $messageBase,
            ':meta_json' => $metaJson,
            ':level' => 'info',
        ]);

        if ($missionId) {
            $notificationId = (int)$pdo->lastInsertId();
            $updMissionSource->execute([
                ':nid' => $notificationId,
                ':mid' => $missionId,
            ]);
        }

        $count++;
    }

    echo json_encode(['success' => true, 'inserted' => $count]);
} catch (Throwable $e) {
    error_log('admin_notify_assignments error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create assignment notifications']);
}
