<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
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

function parseResponseState(string $message): array {
    $text = strtolower($message);
    if (str_contains($text, '[response: accepted]')) {
        return ['status' => 'accepted', 'response_status' => 'accepted'];
    }
    if (str_contains($text, '[response: rejected_busy]')) {
        return ['status' => 'rejected_busy', 'response_status' => 'rejected_busy'];
    }
    return ['status' => 'assigned', 'response_status' => 'pending'];
}

function repairAutoCompletedProofMissions(PDO $pdo): void {
    if (!tableExists($pdo, 'volunteer_missions')) {
        return;
    }

    $hasProofFile = columnExists($pdo, 'volunteer_missions', 'proof_file');
    $hasProofSubmittedAt = columnExists($pdo, 'volunteer_missions', 'proof_submitted_at');
    $hasCompletedAt = columnExists($pdo, 'volunteer_missions', 'completed_at');
    $hasResponseStatus = columnExists($pdo, 'volunteer_missions', 'response_status');

    if (!$hasProofFile || !$hasProofSubmittedAt || !$hasCompletedAt || !$hasResponseStatus) {
        return;
    }

    $pdo->exec("UPDATE volunteer_missions
        SET status = 'accepted', response_status = 'accepted', completed_at = NULL
        WHERE LOWER(COALESCE(status,'')) = 'completed'
          AND LOWER(COALESCE(response_status,'')) = 'completed'
          AND proof_file IS NOT NULL
          AND proof_file <> ''
          AND proof_submitted_at IS NOT NULL
          AND completed_at IS NOT NULL
          AND completed_at = proof_submitted_at");
}

function syncMissionsFromNotifications(PDO $pdo): void {
    if (!tableExists($pdo, 'user_notifications') || !tableExists($pdo, 'volunteer_missions')) {
        return;
    }

    $hasResponseStatus = columnExists($pdo, 'volunteer_missions', 'response_status');
    $hasCaseRef = columnExists($pdo, 'volunteer_missions', 'case_ref');
    $hasSourceNotificationId = columnExists($pdo, 'volunteer_missions', 'source_notification_id');
    $hasProofFile = columnExists($pdo, 'volunteer_missions', 'proof_file');
    $hasProofSubmittedAt = columnExists($pdo, 'volunteer_missions', 'proof_submitted_at');
    $hasCompletedAt = columnExists($pdo, 'volunteer_missions', 'completed_at');

    if (!$hasResponseStatus) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!$hasCaseRef) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN case_ref VARCHAR(80) DEFAULT NULL AFTER response_status");
    }
    if (!$hasSourceNotificationId) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN source_notification_id INT UNSIGNED DEFAULT NULL AFTER case_ref");
    }
    if (!$hasProofFile) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_file VARCHAR(255) DEFAULT NULL AFTER source_notification_id");
    }
    if (!$hasProofSubmittedAt) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN proof_submitted_at DATETIME DEFAULT NULL AFTER proof_file");
    }
    if (!$hasCompletedAt) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }

    $sel = $pdo->query("SELECT notification_id, recipient_id, title, message, meta_json, created_at
                        FROM user_notifications
                        WHERE recipient_entity IN ('volunteer','volunteers')
                          AND LOWER(title) LIKE '%assignment%'
                        ORDER BY notification_id DESC
                        LIMIT 500");
    $rows = $sel->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$rows) {
        return;
    }

    $checkBySource = $pdo->prepare('SELECT mission_id FROM volunteer_missions WHERE source_notification_id = :nid LIMIT 1');
    $checkById = $pdo->prepare('SELECT mission_id FROM volunteer_missions WHERE mission_id = :mid LIMIT 1');
    $updById = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status, source_notification_id = COALESCE(source_notification_id, :nid) WHERE mission_id = :mid AND LOWER(COALESCE(status,\'assigned\')) <> \'completed\' AND LOWER(COALESCE(response_status,\'pending\')) <> \'completed\' LIMIT 1');
    $updBySource = $pdo->prepare('UPDATE volunteer_missions SET status = :status, response_status = :response_status WHERE source_notification_id = :nid AND LOWER(COALESCE(status,\'assigned\')) <> \'completed\' AND LOWER(COALESCE(response_status,\'pending\')) <> \'completed\' LIMIT 1');
    $ins = $pdo->prepare('INSERT INTO volunteer_missions (volunteer_id, mission_title, mission_details, mission_location, status, response_status, case_ref, source_notification_id, assigned_by, assigned_at) VALUES (:volunteer_id, :mission_title, :mission_details, :mission_location, :status, :response_status, :case_ref, :source_notification_id, :assigned_by, :assigned_at)');

    foreach ($rows as $r) {
        $nid = (int)($r['notification_id'] ?? 0);
        if ($nid <= 0) {
            continue;
        }

        $meta = [];
        $metaRaw = (string)($r['meta_json'] ?? '');
        if ($metaRaw !== '') {
            $decoded = json_decode($metaRaw, true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }

        $state = parseResponseState((string)($r['message'] ?? ''));
        $missionTitle = trim((string)($meta['mission_label'] ?? $r['title'] ?? 'Assigned Mission'));
        $missionDetails = trim((string)($meta['mission_note'] ?? $r['message'] ?? ''));
        $missionLocation = trim((string)($meta['landmark'] ?? ''));
        $caseRef = trim((string)($meta['case_id'] ?? ''));
        $metaMissionId = (int)($meta['mission_id'] ?? 0);

        if ($metaMissionId > 0) {
            $checkById->execute([':mid' => $metaMissionId]);
            if ($checkById->fetchColumn()) {
                $updById->execute([
                    ':status' => $state['status'],
                    ':response_status' => $state['response_status'],
                    ':nid' => $nid,
                    ':mid' => $metaMissionId,
                ]);
                continue;
            }
        }

        $checkBySource->execute([':nid' => $nid]);
        if ($checkBySource->fetchColumn()) {
            $updBySource->execute([
                ':status' => $state['status'],
                ':response_status' => $state['response_status'],
                ':nid' => $nid,
            ]);
            continue;
        }

        $ins->execute([
            ':volunteer_id' => (int)($r['recipient_id'] ?? 0),
            ':mission_title' => $missionTitle !== '' ? $missionTitle : 'Assigned Mission',
            ':mission_details' => $missionDetails !== '' ? $missionDetails : null,
            ':mission_location' => $missionLocation !== '' ? $missionLocation : null,
            ':status' => $state['status'],
            ':response_status' => $state['response_status'],
            ':case_ref' => $caseRef !== '' ? $caseRef : null,
            ':source_notification_id' => $nid,
            ':assigned_by' => 'admin',
            ':assigned_at' => (string)($r['created_at'] ?? date('Y-m-d H:i:s')),
        ]);
    }
}

try {
    syncMissionsFromNotifications($pdo);
    repairAutoCompletedProofMissions($pdo);

    $donations = [];
    if (tableExists($pdo, 'donations')) {
        $hasSenderMobile = columnExists($pdo, 'donations', 'sender_mobile');
        $hasTxId = columnExists($pdo, 'donations', 'tx_id');
        $hasReceiverNumber = columnExists($pdo, 'donations', 'receiver_number');
        $hasDonorEmail = columnExists($pdo, 'donations', 'donor_email');

        $selectParts = ['donor_name', 'amount', 'date', 'anonymous', 'message'];
        $selectParts[] = $hasSenderMobile ? 'sender_mobile' : 'NULL AS sender_mobile';
        $selectParts[] = $hasTxId ? 'tx_id' : 'NULL AS tx_id';
        $selectParts[] = $hasReceiverNumber ? 'receiver_number' : 'NULL AS receiver_number';
        $selectParts[] = $hasDonorEmail ? 'donor_email' : 'NULL AS donor_email';

        $stmt = $pdo->query('SELECT ' . implode(', ', $selectParts) . ' FROM donations ORDER BY date DESC LIMIT 100');
        $donations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $broadcasts = [];
    if (tableExists($pdo, 'user_notifications')) {
        $stmt = $pdo->query("SELECT title, message, recipient_entity, created_at FROM user_notifications WHERE recipient_entity IN ('broadcast','all') ORDER BY created_at DESC LIMIT 100");
        $broadcasts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $missions = [];
    if (tableExists($pdo, 'volunteer_missions')) {
        $hasResponseStatus = columnExists($pdo, 'volunteer_missions', 'response_status');
        $hasCaseRef = columnExists($pdo, 'volunteer_missions', 'case_ref');
        $hasProofFile = columnExists($pdo, 'volunteer_missions', 'proof_file');
        $hasProofSubmittedAt = columnExists($pdo, 'volunteer_missions', 'proof_submitted_at');
        $hasCompletedAt = columnExists($pdo, 'volunteer_missions', 'completed_at');

        $responseExpr = $hasResponseStatus ? 'vm.response_status' : 'NULL';
        $caseExpr = $hasCaseRef ? 'vm.case_ref' : 'NULL';
        $proofExpr = $hasProofFile ? 'vm.proof_file' : 'NULL';
        $proofAtExpr = $hasProofSubmittedAt ? 'vm.proof_submitted_at' : 'NULL';
        $completedExpr = $hasCompletedAt ? 'vm.completed_at' : 'NULL';

        $stmt = $pdo->query("SELECT vm.mission_id, vm.volunteer_id, vm.mission_title, vm.mission_location, vm.status, {$responseExpr} AS response_status, {$caseExpr} AS case_ref, {$proofExpr} AS proof_file, {$proofAtExpr} AS proof_submitted_at, {$completedExpr} AS completed_at, vm.assigned_at, vm.mission_details,
        COALESCE(v.full_name, vu.full_name, CONCAT('Volunteer #', vm.volunteer_id)) AS volunteer_name,
        CASE
            WHEN COALESCE(v.profile_photo, '') <> '' THEN v.profile_photo
            WHEN COALESCE(vu.profile_photo, '') <> '' THEN vu.profile_photo
            ELSE ''
        END AS profile_photo,
        CASE
            WHEN COALESCE(v.profile_photo, '') <> '' THEN 'volunteers'
            WHEN COALESCE(vu.profile_photo, '') <> '' THEN 'users'
            ELSE ''
        END AS profile_photo_entity,
        COALESCE(vmcount.total_points, 0) AS volunteer_points,
        CASE
            WHEN COALESCE(vmcount.total_points, 0) >= 1000 THEN 'Platinum Responder'
            WHEN COALESCE(vmcount.total_points, 0) >= 700 THEN 'Gold Responder'
            WHEN COALESCE(vmcount.total_points, 0) >= 380 THEN 'Silver Responder'
            ELSE 'Bronze Volunteer'
        END AS volunteer_rank
        FROM volunteer_missions vm
        LEFT JOIN volunteers v ON v.volunteer_id = vm.volunteer_id
        LEFT JOIN (
            SELECT volunteer_id, MAX(application_id) AS latest_application_id
            FROM volunteer_applications
            WHERE LOWER(COALESCE(status, 'pending')) = 'approved'
            GROUP BY volunteer_id
        ) va_latest ON va_latest.volunteer_id = vm.volunteer_id
        LEFT JOIN volunteer_applications va ON va.application_id = va_latest.latest_application_id
        LEFT JOIN users vu ON vu.user_id = va.user_id
        LEFT JOIN (
            SELECT volunteer_id,
                   SUM(CASE
                       WHEN LOWER(status) = 'completed' OR LOWER(COALESCE(response_status,'')) = 'completed' THEN 20
                       WHEN LOWER(status) = 'accepted' OR LOWER(COALESCE(response_status,'')) = 'accepted' THEN 10
                       ELSE 0
                   END) AS total_points,
                   SUM(CASE WHEN LOWER(status) = 'completed' OR LOWER(COALESCE(response_status,'')) = 'completed' THEN 1 ELSE 0 END) AS completed_count
            FROM volunteer_missions
            GROUP BY volunteer_id
        ) vmcount ON vmcount.volunteer_id = vm.volunteer_id
        ORDER BY vm.assigned_at DESC LIMIT 150");
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $withdraws = [];
    $withdrawTable = null;
    foreach (['withdrawal_requests', 'withdraw_requests'] as $t) {
        if (tableExists($pdo, $t)) {
            $withdrawTable = $t;
            break;
        }
    }

    if ($withdrawTable) {
        $idColumn = null;
        if (columnExists($pdo, $withdrawTable, 'withdraw_id')) {
            $idColumn = 'withdraw_id';
        } elseif (columnExists($pdo, $withdrawTable, 'id')) {
            $idColumn = 'id';
        }

        $dateColumn = 'request_date';
        if (!columnExists($pdo, $withdrawTable, 'request_date') && columnExists($pdo, $withdrawTable, 'created_at')) {
            $dateColumn = 'created_at';
        }

        $hasRequesterName = columnExists($pdo, $withdrawTable, 'requester_name');
        $hasContributorId = columnExists($pdo, $withdrawTable, 'contributor_id');
        $hasTxId = columnExists($pdo, $withdrawTable, 'tx_id');

        $selectParts = [];
        if ($idColumn !== null) {
            $selectParts[] = "w.{$idColumn} AS request_id";
        }
        
        if ($hasRequesterName) {
            $selectParts[] = 'w.requester_name';
        } elseif ($hasContributorId && tableExists($pdo, 'camera_contributors')) {
            $selectParts[] = 'COALESCE(cc.full_name, CONCAT("Contributor #", w.contributor_id)) AS requester_name';
        } else {
            $selectParts[] = '"Unknown" AS requester_name';
        }
        
        $selectParts[] = 'w.amount';
        $selectParts[] = 'w.status';
        $selectParts[] = "w.{$dateColumn} AS request_date";
        
        if ($hasTxId) {
            $selectParts[] = 'w.tx_id';
        } else {
            $selectParts[] = 'NULL AS tx_id';
        }

        $join = '';
        if (!$hasRequesterName && $hasContributorId && tableExists($pdo, 'camera_contributors')) {
            $join = ' LEFT JOIN camera_contributors cc ON cc.contributor_id = w.contributor_id ';
        }

        $sql = 'SELECT ' . implode(', ', $selectParts) . " FROM {$withdrawTable} w {$join} ORDER BY w.{$dateColumn} DESC LIMIT 100";
        $stmt = $pdo->query($sql);
        $withdraws = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    echo json_encode([
        'success' => true,
        'donations' => $donations,
        'broadcasts' => $broadcasts,
        'missions' => $missions,
        'withdraws' => $withdraws,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load admin sections',
    ]);
}
