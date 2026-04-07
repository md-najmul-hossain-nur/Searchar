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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = strtolower(trim((string)$_SESSION['role']));
$userId = (int)$_SESSION['user_id'];

$volunteerId = 0;
if ($role === 'volunteer') {
    $volunteerId = $userId;
} elseif ($role === 'user') {
    if (!tableExists($pdo, 'volunteer_applications')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Volunteer profile not linked for this account']);
        exit;
    }

    $map = $pdo->prepare("SELECT volunteer_id
                          FROM volunteer_applications
                          WHERE user_id = :uid
                            AND LOWER(COALESCE(status, 'pending')) = 'approved'
                            AND volunteer_id IS NOT NULL
                            AND volunteer_id > 0
                          ORDER BY application_id DESC
                          LIMIT 1");
    $map->execute([':uid' => $userId]);
    $volunteerId = (int)($map->fetchColumn() ?: 0);

    if ($volunteerId <= 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No approved volunteer profile found for this user account']);
        exit;
    }
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only volunteer-enabled accounts can respond']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$notificationId = (int)($payload['notification_id'] ?? 0);
$missionIdPayload = (int)($payload['mission_id'] ?? 0);
$action = strtolower(trim((string)($payload['action'] ?? '')));

if (($notificationId <= 0 && $missionIdPayload <= 0) || !in_array($action, ['accept', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$responseTag = $action === 'accept' ? '[Response: accepted]' : '[Response: rejected_busy]';
$acceptedAt = '';

try {
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
        INDEX idx_vm_source_notification (source_notification_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!columnExists($pdo, 'volunteer_missions', 'response_status')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'source_notification_id')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN source_notification_id INT UNSIGNED DEFAULT NULL AFTER case_ref");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'case_ref')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN case_ref VARCHAR(80) DEFAULT NULL AFTER response_status");
    }

    $row = null;
    if ($notificationId > 0 && tableExists($pdo, 'user_notifications')) {
        $sel = $pdo->prepare('SELECT notification_id, message, meta_json FROM user_notifications WHERE notification_id = :id AND recipient_entity IN ("volunteer", "volunteers", "user", "users") AND recipient_id = :rid LIMIT 1');
        $sel->execute([':id' => $notificationId, ':rid' => $userId]);
        $row = $sel->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$row && $missionIdPayload <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment notification not found']);
        exit;
    }

    if ($action === 'reject') {
        $meta = [];
        $metaRaw = (string)($row['meta_json'] ?? '');
        if ($metaRaw !== '') {
            $decoded = json_decode($metaRaw, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }
        $missionId = $missionIdPayload > 0 ? $missionIdPayload : (int)($meta['mission_id'] ?? 0);
        $caseRef = trim((string)($meta['case_id'] ?? ''));

        $updated = 0;
        if ($missionId > 0) {
            $updMission = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE mission_id = :mid AND volunteer_id = :vid LIMIT 1');
            $updMission->execute([
                ':status' => 'rejected_busy',
                ':response_status' => 'rejected_busy',
                ':mid' => $missionId,
                ':vid' => $volunteerId,
            ]);
            $updated = $updMission->rowCount();
        } else {
            $updMission = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE source_notification_id = :nid AND volunteer_id = :vid LIMIT 1');
            $updMission->execute([
                ':status' => 'rejected_busy',
                ':response_status' => 'rejected_busy',
                ':nid' => $notificationId,
                ':vid' => $volunteerId,
            ]);
            $updated = $updMission->rowCount();
        }

        if ($updated < 1 && $caseRef !== '') {
            $updCase = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE volunteer_id = :vid AND case_ref = :case_ref ORDER BY mission_id DESC LIMIT 1');
            $updCase->execute([
                ':status' => 'rejected_busy',
                ':response_status' => 'rejected_busy',
                ':vid' => $volunteerId,
                ':case_ref' => $caseRef,
            ]);
        }

        if ($notificationId > 0 && $row) {
            $del = $pdo->prepare('DELETE FROM user_notifications WHERE notification_id = :id AND recipient_id = :rid LIMIT 1');
            $del->execute([
                ':id' => $notificationId,
                ':rid' => $userId,
            ]);
        }

        echo json_encode(['success' => true, 'action' => $action, 'deleted' => true]);
        exit;
    }

    $message = (string)($row['message'] ?? '');
    if ($row) {
        if (!str_contains(strtolower($message), '[response: accepted]') && !str_contains(strtolower($message), '[response: rejected_busy]')) {
            $message = trim($message . ' ' . $responseTag);
        } else {
            $message = preg_replace('/\[Response:\s*(accepted|rejected_busy)\]/i', $responseTag, $message) ?: $message;
        }
    }

    $acceptedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
    if ($row) {
        if (preg_match('/\[AcceptedAt:\s*[^\]]+\]/i', $message) === 1) {
            $message = preg_replace('/\[AcceptedAt:\s*[^\]]+\]/i', '[AcceptedAt: ' . $acceptedAt . ']', $message) ?: $message;
        } else {
            $message = trim($message . ' [AcceptedAt: ' . $acceptedAt . ']');
        }

        $upd = $pdo->prepare('UPDATE user_notifications SET message = :message, is_read = 1 WHERE notification_id = :id AND recipient_id = :rid');
        $upd->execute([
            ':message' => $message,
            ':id' => $notificationId,
            ':rid' => $userId,
        ]);
    }

    $meta = [];
    $metaRaw = (string)($row['meta_json'] ?? '');
    if ($metaRaw !== '') {
        $decoded = json_decode($metaRaw, true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }
    $missionId = $missionIdPayload > 0 ? $missionIdPayload : (int)($meta['mission_id'] ?? 0);
    $caseRef = trim((string)($meta['case_id'] ?? ''));
    $missionLabel = trim((string)($meta['mission_label'] ?? 'Assigned Mission'));
    $missionNote = trim((string)($meta['mission_note'] ?? ''));
    $landmark = trim((string)($meta['landmark'] ?? ''));

    $updated = 0;
    if ($missionId > 0) {
        $updMission = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE mission_id = :mid AND volunteer_id = :vid LIMIT 1');
        $updMission->execute([
            ':status' => 'accepted',
            ':response_status' => 'accepted',
            ':mid' => $missionId,
            ':vid' => $volunteerId,
        ]);
        $updated = $updMission->rowCount();
    } else {
        $updMission = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE source_notification_id = :nid AND volunteer_id = :vid LIMIT 1');
        $updMission->execute([
            ':status' => 'accepted',
            ':response_status' => 'accepted',
            ':nid' => $notificationId,
            ':vid' => $volunteerId,
        ]);
        $updated = $updMission->rowCount();
    }

    if ($updated < 1 && $caseRef !== '') {
        $updCase = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status, source_notification_id = COALESCE(source_notification_id, :nid) WHERE volunteer_id = :vid AND case_ref = :case_ref ORDER BY mission_id DESC LIMIT 1');
        $updCase->execute([
            ':status' => 'accepted',
            ':response_status' => 'accepted',
            ':nid' => $notificationId,
            ':vid' => $volunteerId,
            ':case_ref' => $caseRef,
        ]);
        $updated = $updCase->rowCount();
    }

    if ($updated < 1) {
        $insMission = $pdo->prepare('INSERT INTO volunteer_missions (volunteer_id, mission_title, mission_details, mission_location, status, response_status, case_ref, source_notification_id, assigned_by) VALUES (:volunteer_id, :mission_title, :mission_details, :mission_location, :status, :response_status, :case_ref, :source_notification_id, :assigned_by)');
        $insMission->execute([
            ':volunteer_id' => $volunteerId,
            ':mission_title' => $missionLabel !== '' ? $missionLabel : 'Assigned Mission',
            ':mission_details' => $missionNote !== '' ? $missionNote : null,
            ':mission_location' => $landmark !== '' ? $landmark : null,
            ':status' => 'accepted',
            ':response_status' => 'accepted',
            ':case_ref' => $caseRef !== '' ? $caseRef : null,
            ':source_notification_id' => $notificationId,
            ':assigned_by' => 'admin',
        ]);
    }

    echo json_encode(['success' => true, 'action' => $action, 'accepted_at' => $acceptedAt]);
} catch (Throwable $e) {
    error_log('volunteer_assignment_response error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update assignment response']);
}
