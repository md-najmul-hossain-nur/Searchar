<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id']) || strtolower(trim((string)($_SESSION['role'] ?? ''))) !== 'police') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
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

function normalizeCaseRef(string $value): string {
    return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($value)) ?? '');
}

function parseCaseNo(string $caseNo): array {
    $trimmed = strtoupper(trim($caseNo));
    if (!preg_match('/^(MP|PT)-?(\d+)$/', $trimmed, $m)) {
        return [null, 0];
    }
    return [$m[1], (int)$m[2]];
}

function isCaseClosed(PDO $pdo, string $caseNo): bool {
    [$prefix, $rowId] = parseCaseNo($caseNo);
    if (!$prefix || $rowId <= 0) return false;

    if ($prefix === 'MP') {
        if (!tableExists($pdo, 'missing_person_reports')) return false;
        $stmt = $pdo->prepare('SELECT status FROM missing_person_reports WHERE report_id = :id LIMIT 1');
        $stmt->execute([':id' => $rowId]);
        $status = strtolower(trim((string)$stmt->fetchColumn()));
        return in_array($status, ['closed', 'resolved', 'found'], true);
    }

    if ($prefix === 'PT') {
        if (!tableExists($pdo, 'posts')) return false;
        $hasReportStatus = columnExists($pdo, 'posts', 'report_status');
        $hasStatus = columnExists($pdo, 'posts', 'status');

        $cols = 'id';
        if ($hasReportStatus) $cols .= ', report_status';
        if ($hasStatus) $cols .= ', status';

        $stmt = $pdo->prepare("SELECT {$cols} FROM posts WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $rowId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        $reportStatus = strtolower(trim((string)($row['report_status'] ?? '')));
        $status = strtolower(trim((string)($row['status'] ?? '')));

        if (in_array($reportStatus, ['closed', 'resolved'], true)) return true;
        if ($status === 'rejected') return true;
    }

    return false;
}

function ensureVolunteerMissionColumns(PDO $pdo): void {
    if (!tableExists($pdo, 'volunteer_missions')) return;

    if (!columnExists($pdo, 'volunteer_missions', 'response_status')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'case_ref')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN case_ref VARCHAR(80) DEFAULT NULL AFTER response_status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'completed_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $action = strtolower(trim((string)($payload['action'] ?? '')));

    if ($action === 'sync_published') {
        $cases = is_array($payload['cases'] ?? null) ? $payload['cases'] : [];
        $closedCaseNos = [];

        foreach ($cases as $item) {
            $caseNo = trim((string)($item['case_no'] ?? $item));
            if ($caseNo === '') continue;
            if (isCaseClosed($pdo, $caseNo)) {
                $closedCaseNos[] = $caseNo;
            }
        }

        echo json_encode([
            'success' => true,
            'closed_case_nos' => array_values(array_unique($closedCaseNos)),
        ]);
        exit;
    }

    if ($action === 'get_solved_cases') {
        $solved = [];
        
        if (tableExists($pdo, 'posts') && columnExists($pdo, 'posts', 'report_status')) {
            $stmt = $pdo->query("SELECT id, category, text, created_at, report_closed_at FROM posts WHERE LOWER(COALESCE(report_status,'not_reported')) = 'closed' ORDER BY report_closed_at DESC LIMIT 50");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                $categoryRaw = strtolower((string)($r['category'] ?? 'case'));
                $type = match ($categoryRaw) {
                    'missing_person' => 'Missing Person',
                    'criminal_found' => 'Criminal Found',
                    'disaster' => 'Disaster',
                    'mission' => 'Mission',
                    default => ucfirst(str_replace('_', ' ', $categoryRaw)),
                };
                
                $solvedAtStr = $r['report_closed_at'] ? (new DateTime((string)$r['report_closed_at']))->format('Y-m-d H:i') : '—';
                $solved[] = [
                    'case_no' => 'PT-' . str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT),
                    'type' => $type,
                    'details' => trim((string)$r['text']) ?: 'Case resolved',
                    'source' => 'Crime Reporting',
                    'published_at' => (new DateTime((string)$r['created_at']))->format('Y-m-d H:i'),
                    'solved_at' => $solvedAtStr,
                    'timestamp' => $r['report_closed_at'] ? strtotime((string)$r['report_closed_at']) : 0
                ];
            }
        }
        
        if (tableExists($pdo, 'missing_person_reports')) {
            $stmt = $pdo->query("SELECT report_id, full_name, last_seen_location, created_at, resolved_at FROM missing_person_reports WHERE LOWER(COALESCE(status,'open')) IN ('closed','resolved','found') ORDER BY resolved_at DESC LIMIT 50");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                $solvedAtStr = $r['resolved_at'] ? (new DateTime((string)$r['resolved_at']))->format('Y-m-d H:i') : '—';
                $solved[] = [
                    'case_no' => 'MP-' . str_pad((string)$r['report_id'], 4, '0', STR_PAD_LEFT),
                    'type' => 'Missing Person',
                    'details' => trim((string)$r['full_name']) . ' • Last seen: ' . trim((string)$r['last_seen_location']),
                    'source' => 'Missing Desk',
                    'published_at' => (new DateTime((string)$r['created_at']))->format('Y-m-d H:i'),
                    'solved_at' => $solvedAtStr,
                    'timestamp' => $r['resolved_at'] ? strtotime((string)$r['resolved_at']) : 0
                ];
            }
        }

        usort($solved, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        
        echo json_encode([
            'success' => true,
            'solved_cases' => array_slice($solved, 0, 100)
        ]);
        exit;
    }

    if ($action !== 'close_case') {
        throw new RuntimeException('Unsupported action');
    }

    $caseNo = trim((string)($payload['case_no'] ?? ''));
    if ($caseNo === '') {
        throw new RuntimeException('case_no is required');
    }

    [$prefix, $rowId] = parseCaseNo($caseNo);
    if (!$prefix || $rowId <= 0) {
        throw new RuntimeException('Invalid case reference');
    }

    if ($prefix === 'MP') {
        if (!tableExists($pdo, 'missing_person_reports')) {
            throw new RuntimeException('Missing reports table not found');
        }
        if (!columnExists($pdo, 'missing_person_reports', 'resolved_at')) {
            $pdo->exec("ALTER TABLE missing_person_reports ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER status");
        }

        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = 'closed', resolved_at = NOW() WHERE report_id = :id AND LOWER(COALESCE(status, 'open')) NOT IN ('closed','resolved','found')");
        $upd->execute([':id' => $rowId]);
    } else {
        if (!tableExists($pdo, 'posts')) {
            throw new RuntimeException('Posts table not found');
        }
        if (!columnExists($pdo, 'posts', 'report_status')) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN report_status VARCHAR(20) DEFAULT 'not_reported' AFTER status");
        }
        if (!columnExists($pdo, 'posts', 'report_closed_at')) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN report_closed_at DATETIME DEFAULT NULL AFTER reported_at");
        }

        $upd = $pdo->prepare("UPDATE posts SET report_status = 'closed', report_closed_at = NOW() WHERE id = :id AND LOWER(COALESCE(report_status, 'not_reported')) <> 'closed'");
        $upd->execute([':id' => $rowId]);
    }

    if (tableExists($pdo, 'crime_reports') && columnExists($pdo, 'crime_reports', 'status')) {
        $srcType = ($prefix === 'MP') ? 'missing_person' : 'post';
        $updCrime = $pdo->prepare("UPDATE crime_reports SET status = 'closed', updated_at = NOW() WHERE source_type = :st AND source_ref_id = :id AND LOWER(COALESCE(status, 'new')) <> 'closed'");
        $updCrime->execute([':st' => $srcType, ':id' => $rowId]);
    }

    ensureVolunteerMissionColumns($pdo);
    ensureNotificationsTable($pdo);

    $missionRows = [];
    if (tableExists($pdo, 'volunteer_missions') && columnExists($pdo, 'volunteer_missions', 'case_ref')) {
        $normRef = normalizeCaseRef($caseNo);
        $find = $pdo->prepare("SELECT mission_id, volunteer_id, mission_title FROM volunteer_missions WHERE UPPER(REPLACE(REPLACE(TRIM(COALESCE(case_ref,'')),'-',''),' ','')) = :ref AND LOWER(COALESCE(status,'assigned')) NOT IN ('completed','rejected_busy','closed_by_police')");
        $find->execute([':ref' => $normRef]);
        $missionRows = $find->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($missionRows) {
            $updMission = $pdo->prepare("UPDATE volunteer_missions SET status = 'closed_by_police', response_status = 'closed_by_police', completed_at = NOW() WHERE mission_id = :mid");
            foreach ($missionRows as $mr) {
                $missionId = (int)($mr['mission_id'] ?? 0);
                if ($missionId <= 0) continue;
                $updMission->execute([':mid' => $missionId]);
            }
        }
    }

    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, NULL)');
    $smsSent = 0;
    $reducedXpPerMission = 2;
    foreach ($missionRows as $mr) {
        $volunteerId = (int)($mr['volunteer_id'] ?? 0);
        if ($volunteerId <= 0) continue;
        $missionTitle = trim((string)($mr['mission_title'] ?? 'Assigned Mission'));

        $notify->execute([
            ':entity' => 'volunteer',
            ':rid' => $volunteerId,
            ':title' => 'Mission Auto-Closed (Reduced XP)',
            ':message' => "Case {$caseNo} has been closed by police. Mission '{$missionTitle}' is auto-closed. No further action required. You received reduced XP (+{$reducedXpPerMission}).",
            ':level' => 'info',
        ]);
        $smsSent++;
    }

    echo json_encode([
        'success' => true,
        'case_no' => $caseNo,
        'missions_auto_closed' => count($missionRows),
        'reduced_xp_per_mission' => $reducedXpPerMission,
        'reduced_xp_total' => count($missionRows) * $reducedXpPerMission,
        'sms_notifications_sent' => $smsSent,
    ]);
} catch (Throwable $e) {
    error_log('police_case_resolution error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
