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
    if ($status === '') return 'open';
    return $status;
}

function formatDuration(int $hours): string {
    if ($hours < 0) $hours = 0;
    $days = intdiv($hours, 24);
    $remHours = $hours % 24;
    return sprintf('%02dd %02dh', $days, $remHours);
}

try {
    if (!tableExists($pdo, 'missing_person_reports')) {
        echo json_encode([
            'success' => true,
            'summary' => [
                'total_active_cases' => 0,
                'resolved_cases_month' => 0,
                'avg_resolution_time' => '00d 00h',
            ],
            'rows' => [],
        ]);
        exit;
    }

    $hasResolvedAt = columnExists($pdo, 'missing_person_reports', 'resolved_at');

    $selectCols = $hasResolvedAt
        ? 'report_id, full_name, age, gender, last_seen_location, last_seen_time, status, reporter_name, created_at, resolved_at'
        : 'report_id, full_name, age, gender, last_seen_location, last_seen_time, status, reporter_name, created_at, NULL AS resolved_at';

    $stmt = $pdo->query("SELECT {$selectCols} FROM missing_person_reports ORDER BY created_at DESC LIMIT 1000");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $activeStatuses = ['open', 'active', 'pending', 'searching'];
    $resolvedStatuses = ['resolved', 'closed', 'found'];

    $totalActive = 0;
    $resolvedThisMonth = 0;
    $resolutionHours = [];

    $now = new DateTime();
    $monthStart = new DateTime($now->format('Y-m-01 00:00:00'));

    $payloadRows = [];
    foreach ($rows as $row) {
        $status = normalizeStatus((string)($row['status'] ?? 'open'));
        $createdAtRaw = (string)($row['created_at'] ?? '');
        $resolvedAtRaw = (string)($row['resolved_at'] ?? '');

        if (in_array($status, $activeStatuses, true)) {
            $totalActive++;
        }

        $resolvedDate = null;
        if ($resolvedAtRaw !== '') {
            try {
                $resolvedDate = new DateTime($resolvedAtRaw);
            } catch (Throwable $e) {
                $resolvedDate = null;
            }
        }

        if (in_array($status, $resolvedStatuses, true)) {
            $comparisonDate = $resolvedDate;
            if (!$comparisonDate && $createdAtRaw !== '') {
                try {
                    $comparisonDate = new DateTime($createdAtRaw);
                } catch (Throwable $e) {
                    $comparisonDate = null;
                }
            }

            if ($comparisonDate && $comparisonDate >= $monthStart) {
                $resolvedThisMonth++;
            }

            if ($createdAtRaw !== '') {
                try {
                    $createdDate = new DateTime($createdAtRaw);
                    $endDate = $resolvedDate ?: $now;
                    $diffSeconds = max(0, $endDate->getTimestamp() - $createdDate->getTimestamp());
                    $resolutionHours[] = (int)floor($diffSeconds / 3600);
                } catch (Throwable $e) {
                }
            }
        }

        $payloadRows[] = [
            'report_id' => (int)($row['report_id'] ?? 0),
            'full_name' => (string)($row['full_name'] ?? ''),
            'age' => (int)($row['age'] ?? 0),
            'gender' => (string)($row['gender'] ?? ''),
            'last_seen_location' => (string)($row['last_seen_location'] ?? ''),
            'last_seen_time' => (string)($row['last_seen_time'] ?? ''),
            'status' => $status,
            'reporter_name' => (string)($row['reporter_name'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }

    $avgHours = 0;
    if (count($resolutionHours) > 0) {
        $avgHours = (int)round(array_sum($resolutionHours) / count($resolutionHours));
    }

    echo json_encode([
        'success' => true,
        'summary' => [
            'total_active_cases' => $totalActive,
            'resolved_cases_month' => $resolvedThisMonth,
            'avg_resolution_time' => formatDuration($avgHours),
        ],
        'rows' => $payloadRows,
    ]);
} catch (Throwable $e) {
    error_log('fetch_missing_reports_admin error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch missing person reports',
    ]);
}
