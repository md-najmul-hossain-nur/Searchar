<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

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

function normalizeStatus(string $status): string {
    $status = strtolower(trim($status));
    return $status === '' ? 'open' : $status;
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
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }
}

function ensureCrimeReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crime_reports (
        crime_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        case_ref VARCHAR(80) NOT NULL,
        source_type VARCHAR(40) NOT NULL DEFAULT 'missing_person',
        source_ref_id BIGINT UNSIGNED DEFAULT NULL,
        report_type VARCHAR(60) NOT NULL DEFAULT 'missing_person',
        severity VARCHAR(20) NOT NULL DEFAULT 'high',
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        landmark VARCHAR(255) DEFAULT NULL,
        reporter_name VARCHAR(150) DEFAULT NULL,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        anon_token VARCHAR(80) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        media_path VARCHAR(255) DEFAULT NULL,
        media_json TEXT DEFAULT NULL,
        lat DECIMAL(10,7) DEFAULT NULL,
        lng DECIMAL(10,7) DEFAULT NULL,
        submitted_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        closed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (crime_id),
        UNIQUE KEY uq_crime_reports_case_ref (case_ref),
        KEY idx_crime_reports_status (status),
        KEY idx_crime_reports_source (source_type, source_ref_id),
        KEY idx_crime_reports_submitted (submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!tableExists($pdo, 'missing_person_reports')) {
        throw new RuntimeException('Missing reports table not found');
    }

    if (!columnExists($pdo, 'missing_person_reports', 'resolved_at')) {
        $pdo->exec("ALTER TABLE missing_person_reports ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER status");
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $reportId = (int)($payload['report_id'] ?? 0);
    $action = strtolower(trim((string)($payload['action'] ?? '')));

    if ($reportId <= 0) {
        throw new RuntimeException('Invalid report id');
    }

    $allowedActions = [
        'reject' => 'closed',
        'make_report' => 'under_review',
    ];

    if (!isset($allowedActions[$action])) {
        throw new RuntimeException('Invalid action');
    }

    $rowStmt = $pdo->prepare('SELECT report_id, full_name, reporter_name, last_seen_location, photo_filename, status, created_at FROM missing_person_reports WHERE report_id = :id LIMIT 1');
    $rowStmt->execute([':id' => $reportId]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException('Report not found');
    }

    $currentStatus = normalizeStatus((string)($row['status'] ?? 'open'));
    $actionableStatuses = ['open', 'active', 'pending', 'searching'];
    if (!in_array($currentStatus, $actionableStatuses, true)) {
        echo json_encode([
            'success' => false,
            'already_processed' => true,
            'status' => $currentStatus,
            'error' => 'This report is already processed',
        ]);
        exit;
    }

    $targetStatus = $allowedActions[$action];

    ensureCrimeReportsTable($pdo);

    if ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = :status, resolved_at = NOW() WHERE report_id = :id AND LOWER(COALESCE(status, 'open')) IN ('open','active','pending','searching')");
        $upd->execute([':status' => $targetStatus, ':id' => $reportId]);

        $caseRef = 'MP' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT);
        $closeCrime = $pdo->prepare("UPDATE crime_reports
            SET status = 'closed', closed_at = NOW(), updated_at = NOW()
            WHERE case_ref = :case_ref");
        $closeCrime->execute([':case_ref' => $caseRef]);
    } else {
        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = :status, resolved_at = NULL WHERE report_id = :id AND LOWER(COALESCE(status, 'open')) IN ('open','active','pending','searching')");
        $upd->execute([':status' => $targetStatus, ':id' => $reportId]);

        $caseRef = 'MP' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT);
        $landmark = trim((string)($row['last_seen_location'] ?? ''));
        $reporterName = trim((string)($row['reporter_name'] ?? ''));
                $photoFilename = trim((string)($row['photo_filename'] ?? ''));
                $mediaPath = $photoFilename !== '' ? ('uploads/missing_person/' . ltrim($photoFilename, '/')) : null;
                $mediaJson = $mediaPath ? json_encode([$mediaPath], JSON_UNESCAPED_SLASHES) : null;
        $createdAt = trim((string)($row['created_at'] ?? ''));
        if ($createdAt === '') {
            $createdAt = date('Y-m-d H:i:s');
        }

        $upsertCrime = $pdo->prepare("INSERT INTO crime_reports
                        (case_ref, source_type, source_ref_id, report_type, severity, status, landmark, reporter_name, anonymous, description, media_path, media_json, submitted_at, updated_at, lat, lng)
            VALUES
                        (:case_ref, 'missing_person', :source_ref_id, 'missing_person', 'high', 'new', :landmark, :reporter_name, 0, :description, :media_path, :media_json, :submitted_at, NOW(), :lat, :lng)
            ON DUPLICATE KEY UPDATE
              source_ref_id = VALUES(source_ref_id),
              report_type = VALUES(report_type),
              severity = VALUES(severity),
              status = 'new',
              landmark = VALUES(landmark),
              reporter_name = VALUES(reporter_name),
              anonymous = 0,
              description = VALUES(description),
                            media_path = VALUES(media_path),
                            media_json = VALUES(media_json),
              submitted_at = VALUES(submitted_at),
              updated_at = NOW(),
              closed_at = NULL");

        $upsertCrime->execute([
            ':case_ref' => $caseRef,
            ':source_ref_id' => $reportId,
            ':landmark' => $landmark !== '' ? $landmark : 'Unknown location',
            ':reporter_name' => $reporterName !== '' ? $reporterName : 'Unknown',
            ':description' => 'Escalated from Missing Persons',
            ':media_path' => $mediaPath,
            ':media_json' => $mediaJson,
            ':submitted_at' => $createdAt,
            ':lat' => 23.8103,
            ':lng' => 90.4125,
        ]);
    }

    if ($upd->rowCount() < 1) {
        $fresh = $pdo->prepare('SELECT status FROM missing_person_reports WHERE report_id = :id LIMIT 1');
        $fresh->execute([':id' => $reportId]);
        $freshStatus = normalizeStatus((string)($fresh->fetchColumn() ?: 'open'));

        echo json_encode([
            'success' => false,
            'already_processed' => true,
            'status' => $freshStatus,
            'error' => 'This report is already processed',
        ]);
        exit;
    }

    ensureNotificationsTable($pdo);
    $notifyPolice = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, NULL)');
    if ($action === 'reject') {
        $notifyPolice->execute([
            ':entity' => 'policeman',
            ':rid' => 0,
            ':title' => 'Case Closed by Admin',
            ':message' => 'Admin closed a missing person case. Live board will auto-sync to solved history.',
            ':level' => 'info',
        ]);
    } else {
        $notifyPolice->execute([
            ':entity' => 'policeman',
            ':rid' => 0,
            ':title' => 'Admin Missing Case Alert',
            ':message' => 'Admin escalated a missing person report for police review. Check All Cases.',
            ':level' => 'warning',
        ]);
    }

    echo json_encode([
        'success' => true,
        'report_id' => $reportId,
        'status' => $targetStatus,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
