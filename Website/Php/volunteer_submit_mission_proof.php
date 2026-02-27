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

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = strtolower(trim((string)$_SESSION['role']));
$volunteerId = (int)$_SESSION['user_id'];
if ($role !== 'volunteer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only volunteers can submit mission proof']);
    exit;
}

$missionId = (int)($_POST['mission_id'] ?? 0);
$notificationId = (int)($_POST['notification_id'] ?? 0);

if (!isset($_FILES['proof_file']) || !is_array($_FILES['proof_file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Proof file is required']);
    exit;
}

$file = $_FILES['proof_file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Failed to upload proof file']);
    exit;
}

$maxBytes = 20 * 1024 * 1024;
if ((int)$file['size'] <= 0 || (int)$file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Proof file size must be between 1B and 20MB']);
    exit;
}

$origName = (string)($file['name'] ?? 'proof');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'mov', 'pdf'];
if ($ext === '' || !in_array($ext, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported proof file type']);
    exit;
}

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
    if (!columnExists($pdo, 'volunteer_missions', 'proof_file')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_file VARCHAR(255) DEFAULT NULL AFTER source_notification_id");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'proof_submitted_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_submitted_at DATETIME DEFAULT NULL AFTER proof_file");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'completed_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }

    if ($missionId <= 0 && $notificationId > 0) {
        $find = $pdo->prepare('SELECT mission_id FROM volunteer_missions WHERE source_notification_id = :nid AND volunteer_id = :vid ORDER BY mission_id DESC LIMIT 1');
        $find->execute([':nid' => $notificationId, ':vid' => $volunteerId]);
        $missionId = (int)$find->fetchColumn();

        if ($missionId <= 0) {
            $metaSel = $pdo->prepare('SELECT meta_json FROM user_notifications WHERE notification_id = :nid AND recipient_id = :vid LIMIT 1');
            $metaSel->execute([':nid' => $notificationId, ':vid' => $volunteerId]);
            $metaRaw = (string)$metaSel->fetchColumn();
            if ($metaRaw !== '') {
                $meta = json_decode($metaRaw, true);
                $caseRef = is_array($meta) ? trim((string)($meta['case_id'] ?? '')) : '';
                if ($caseRef !== '') {
                    $findCase = $pdo->prepare('SELECT mission_id FROM volunteer_missions WHERE volunteer_id = :vid AND case_ref = :case_ref ORDER BY mission_id DESC LIMIT 1');
                    $findCase->execute([':vid' => $volunteerId, ':case_ref' => $caseRef]);
                    $missionId = (int)$findCase->fetchColumn();
                }
            }
        }
    }

    if ($missionId <= 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mission not found for this volunteer']);
        exit;
    }

    $check = $pdo->prepare('SELECT mission_id FROM volunteer_missions WHERE mission_id = :mid AND volunteer_id = :vid LIMIT 1');
    $check->execute([':mid' => $missionId, ':vid' => $volunteerId]);
    if (!$check->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not assigned to this mission']);
        exit;
    }

    $uploadsRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'mission_proofs';
    if (!is_dir($uploadsRoot)) {
        @mkdir($uploadsRoot, 0777, true);
    }

    $safeName = 'proof_' . $volunteerId . '_' . $missionId . '_' . time() . '.' . $ext;
    $fullPath = $uploadsRoot . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file((string)$file['tmp_name'], $fullPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Unable to save proof file']);
        exit;
    }

    $relativeProofPath = 'uploads/mission_proofs/' . $safeName;
    $upd = $pdo->prepare('UPDATE volunteer_missions SET proof_file = :proof_file, proof_submitted_at = NOW(), completed_at = NOW(), status = :status, response_status = :response_status WHERE mission_id = :mid AND volunteer_id = :vid LIMIT 1');
    $upd->execute([
        ':proof_file' => $relativeProofPath,
        ':status' => 'completed',
        ':response_status' => 'completed',
        ':mid' => $missionId,
        ':vid' => $volunteerId,
    ]);

    echo json_encode([
        'success' => true,
        'mission_id' => $missionId,
        'proof_file' => $relativeProofPath,
    ]);
} catch (Throwable $e) {
    error_log('volunteer_submit_mission_proof error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to submit mission proof']);
}
