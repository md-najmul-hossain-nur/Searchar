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

function normalizeRole(string $value): string {
    $raw = strtolower(trim($value));
    if ($raw === '') return 'User';
    if (str_contains($raw, 'camera')) return 'Camera Contribution';
    if (str_contains($raw, 'police')) return 'Policeman';
    if (str_contains($raw, 'volunteer')) return 'Volunteer';
    if ($raw === 'user') return 'User';
    return ucfirst($raw);
}

function normalizeDate(string $value): string {
    $value = trim($value);
    if ($value === '') return date('Y-m-d H:i:s');
    $timestamp = strtotime($value);
    if ($timestamp === false) return date('Y-m-d H:i:s');
    return date('Y-m-d H:i:s', $timestamp);
}

try {
    $queue = [];

    if (tableExists($pdo, 'posts')) {
        $hasStatus = columnExists($pdo, 'posts', 'status');
        $statusExpr = $hasStatus ? 'LOWER(COALESCE(status,\'pending\')) = \'pending\'' : '1=1';

        $sql = "SELECT id, author_name, author_role, category, created_at
                FROM posts
                WHERE {$statusExpr}
                ORDER BY created_at DESC, id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $postId = (int)($row['id'] ?? 0);
            $queue[] = [
                'type' => 'Post Approval',
                'submitted_by' => trim((string)($row['author_name'] ?? '')) ?: 'Unknown',
                'actor_role' => normalizeRole((string)($row['author_role'] ?? 'user')),
                'item_ref' => $postId > 0 ? ('PT-' . str_pad((string)$postId, 3, '0', STR_PAD_LEFT)) : '—',
                'item_label' => trim((string)($row['category'] ?? 'General')) . ' post',
                'status' => 'Pending',
                'submitted_at' => normalizeDate((string)($row['created_at'] ?? '')),
                'section' => 'post-control',
                'search_key' => $postId > 0 ? ('PT-' . str_pad((string)$postId, 3, '0', STR_PAD_LEFT)) : ''
            ];
        }
    }

    if (tableExists($pdo, 'post_reports')) {
        $sql = "SELECT report_id, report_category, reporter_name, reported_name, status, created_at
                FROM (
                    SELECT
                        report_id,
                        report_category,
                        reporter_name,
                        COALESCE(post_author_name, '') AS reported_name,
                        status,
                        created_at
                    FROM post_reports
                ) p
                WHERE LOWER(COALESCE(status,'pending')) IN ('pending','under_review')
                ORDER BY created_at DESC, report_id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $reportId = (int)($row['report_id'] ?? 0);
            $status = trim((string)($row['status'] ?? 'pending'));
            $reportedName = trim((string)($row['reported_name'] ?? '')) ?: 'Unknown user';
            $queue[] = [
                'type' => 'Post Report',
                'submitted_by' => trim((string)($row['reporter_name'] ?? '')) ?: 'Unknown',
                'actor_role' => 'Reporter',
                'item_ref' => $reportId > 0 ? ('PR-' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : '—',
                'item_label' => $reportedName . ' • ' . (trim((string)($row['report_category'] ?? 'Other')) ?: 'Other'),
                'status' => ucfirst(strtolower($status)),
                'submitted_at' => normalizeDate((string)($row['created_at'] ?? '')),
                'section' => 'reports',
                'search_key' => $reportId > 0 ? ('PR-' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : $reportedName
            ];
        }
    }

    if (tableExists($pdo, 'comment_reports')) {
        $sql = "SELECT report_id, report_category, reporter_name, comment_author_name, status, created_at
                FROM comment_reports
                WHERE LOWER(COALESCE(status,'pending')) IN ('pending','under_review')
                ORDER BY created_at DESC, report_id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $reportId = (int)($row['report_id'] ?? 0);
            $status = trim((string)($row['status'] ?? 'pending'));
            $reportedName = trim((string)($row['comment_author_name'] ?? '')) ?: 'Unknown user';
            $queue[] = [
                'type' => 'Comment Report',
                'submitted_by' => trim((string)($row['reporter_name'] ?? '')) ?: 'Unknown',
                'actor_role' => 'Reporter',
                'item_ref' => $reportId > 0 ? ('PR-' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : '—',
                'item_label' => $reportedName . ' • ' . (trim((string)($row['report_category'] ?? 'Other')) ?: 'Other'),
                'status' => ucfirst(strtolower($status)),
                'submitted_at' => normalizeDate((string)($row['created_at'] ?? '')),
                'section' => 'reports',
                'search_key' => $reportId > 0 ? ('PR-' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : $reportedName
            ];
        }
    }

    if (tableExists($pdo, 'missing_person_reports')) {
        $statusExpr = "LOWER(COALESCE(status,'open')) IN ('open','active','pending','searching')";
        $sql = "SELECT report_id, full_name, reporter_name, status, created_at
                FROM missing_person_reports
                WHERE {$statusExpr}
                ORDER BY created_at DESC, report_id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $reportId = (int)($row['report_id'] ?? 0);
            $status = trim((string)($row['status'] ?? 'open'));
            $missingName = trim((string)($row['full_name'] ?? '')) ?: 'Unknown person';
            $queue[] = [
                'type' => 'Missing Report',
                'submitted_by' => trim((string)($row['reporter_name'] ?? '')) ?: 'Unknown',
                'actor_role' => 'Reporter',
                'item_ref' => $reportId > 0 ? ('MP' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : '—',
                'item_label' => $missingName,
                'status' => ucfirst(strtolower($status)),
                'submitted_at' => normalizeDate((string)($row['created_at'] ?? '')),
                'section' => 'missing',
                'search_key' => $reportId > 0 ? ('MP' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT)) : ''
            ];
        }
    }

    if (tableExists($pdo, 'withdraw_requests')) {
        $requestIdColumn = null;
        if (columnExists($pdo, 'withdraw_requests', 'withdraw_id')) {
            $requestIdColumn = 'withdraw_id';
        } elseif (columnExists($pdo, 'withdraw_requests', 'id')) {
            $requestIdColumn = 'id';
        }

        $dateColumn = columnExists($pdo, 'withdraw_requests', 'request_date')
            ? 'request_date'
            : (columnExists($pdo, 'withdraw_requests', 'created_at') ? 'created_at' : null);

        if ($dateColumn !== null) {
            $idSelect = $requestIdColumn ? "{$requestIdColumn} AS request_id," : 'NULL AS request_id,';
            $sql = "SELECT {$idSelect} requester_name, amount, status, {$dateColumn} AS request_date
                    FROM withdraw_requests
                    WHERE LOWER(COALESCE(status,'pending')) = 'pending'
                    ORDER BY {$dateColumn} DESC
                    LIMIT 80";

            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $row) {
                $requestId = (int)($row['request_id'] ?? 0);
                $amount = (float)($row['amount'] ?? 0);
                $queue[] = [
                    'type' => 'Withdraw Request',
                    'submitted_by' => trim((string)($row['requester_name'] ?? '')) ?: 'Volunteer',
                    'actor_role' => 'Volunteer',
                    'item_ref' => $requestId > 0 ? ('WD-' . str_pad((string)$requestId, 3, '0', STR_PAD_LEFT)) : '—',
                    'item_label' => '৳' . number_format($amount, 2),
                    'status' => 'Pending',
                    'submitted_at' => normalizeDate((string)($row['request_date'] ?? '')),
                    'section' => 'withdraw',
                    'search_key' => trim((string)($row['requester_name'] ?? ''))
                ];
            }
        }
    }

    if (tableExists($pdo, 'volunteer_missions') && columnExists($pdo, 'volunteer_missions', 'proof_file')) {
        $hasResponseStatus = columnExists($pdo, 'volunteer_missions', 'response_status');
        $hasCaseRef = columnExists($pdo, 'volunteer_missions', 'case_ref');
        $hasAssignedAt = columnExists($pdo, 'volunteer_missions', 'assigned_at');

        $responseExpr = $hasResponseStatus ? 'LOWER(COALESCE(vm.response_status,\'pending\'))' : "'pending'";
        $caseExpr = $hasCaseRef ? 'vm.case_ref' : 'NULL';
        $timeExpr = $hasAssignedAt ? 'vm.assigned_at' : 'NOW()';

        $sql = "SELECT
                    vm.mission_id,
                    vm.mission_title,
                    vm.status,
                    {$responseExpr} AS response_status,
                    {$caseExpr} AS case_ref,
                    {$timeExpr} AS submitted_at,
                    COALESCE(v.full_name, CONCAT('Volunteer #', vm.volunteer_id)) AS volunteer_name
                FROM volunteer_missions vm
                LEFT JOIN volunteers v ON v.volunteer_id = vm.volunteer_id
                WHERE vm.proof_file IS NOT NULL
                  AND vm.proof_file <> ''
                                    AND LOWER(COALESCE(vm.status,'assigned')) NOT IN ('completed','rejected_busy','closed_by_police')
                                    AND {$responseExpr} NOT IN ('completed','rejected_busy','closed_by_police')
                ORDER BY {$timeExpr} DESC, vm.mission_id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $missionId = (int)($row['mission_id'] ?? 0);
            $caseRef = trim((string)($row['case_ref'] ?? ''));
            $title = trim((string)($row['mission_title'] ?? '')) ?: 'Mission Proof';
            $queue[] = [
                'type' => 'Mission Proof',
                'submitted_by' => trim((string)($row['volunteer_name'] ?? '')) ?: 'Volunteer',
                'actor_role' => 'Volunteer',
                'item_ref' => $caseRef !== '' ? $caseRef : ($missionId > 0 ? ('MS-' . str_pad((string)$missionId, 3, '0', STR_PAD_LEFT)) : '—'),
                'item_label' => $title,
                'status' => 'Needs verification',
                'submitted_at' => normalizeDate((string)($row['submitted_at'] ?? '')),
                'section' => 'volunteer',
                'search_key' => $caseRef !== '' ? $caseRef : $title
            ];
        }
    }

    if (tableExists($pdo, 'volunteer_applications')) {
        $sql = "SELECT application_id, full_name, email, mobile, status, created_at
                FROM volunteer_applications
                WHERE LOWER(COALESCE(status, 'pending')) = 'pending'
                ORDER BY created_at DESC, application_id DESC
                LIMIT 80";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $applicationId = (int)($row['application_id'] ?? 0);
            $fullName = trim((string)($row['full_name'] ?? '')) ?: 'Unknown user';
            $email = trim((string)($row['email'] ?? ''));
            $mobile = trim((string)($row['mobile'] ?? ''));
            $contactLabel = $mobile !== '' ? $mobile : ($email !== '' ? $email : 'No contact');

            $queue[] = [
                'type' => 'Volunteer Application',
                'submitted_by' => $fullName,
                'actor_role' => 'User',
                'item_ref' => $applicationId > 0 ? ('VA-' . str_pad((string)$applicationId, 3, '0', STR_PAD_LEFT)) : '—',
                'item_label' => $contactLabel,
                'status' => 'Pending',
                'submitted_at' => normalizeDate((string)($row['created_at'] ?? '')),
                'section' => 'volunteer',
                'search_key' => $contactLabel,
                'application_id' => $applicationId,
            ];
        }
    }

    usort($queue, static function (array $a, array $b): int {
        $ta = strtotime((string)($a['submitted_at'] ?? '')) ?: 0;
        $tb = strtotime((string)($b['submitted_at'] ?? '')) ?: 0;
        return $tb <=> $ta;
    });

    $queue = array_slice($queue, 0, 150);

    $summary = [
        'post_pending' => 0,
        'missing_pending' => 0,
        'withdraw_pending' => 0,
        'mission_proof_pending' => 0,
        'volunteer_pending' => 0,
        'report_pending' => 0,
        'chat_log_total' => 0,
        'total' => count($queue),
    ];

    if (tableExists($pdo, 'chatbot_logs')) {
        $chatCount = (int)$pdo->query('SELECT COUNT(*) FROM chatbot_logs')->fetchColumn();
        $summary['chat_log_total'] = $chatCount;
    }

    foreach ($queue as $row) {
        $type = strtolower((string)($row['type'] ?? ''));
        if ($type === 'post approval') $summary['post_pending']++;
        elseif ($type === 'missing report') $summary['missing_pending']++;
        elseif ($type === 'withdraw request') $summary['withdraw_pending']++;
        elseif ($type === 'mission proof') $summary['mission_proof_pending']++;
        elseif ($type === 'volunteer application') $summary['volunteer_pending']++;
        elseif ($type === 'post report' || $type === 'comment report') $summary['report_pending']++;
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'rows' => $queue,
    ]);
} catch (Throwable $e) {
    error_log('admin_fetch_action_queue error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load action queue',
    ]);
}
