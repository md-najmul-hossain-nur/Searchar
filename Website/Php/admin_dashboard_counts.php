<?php
// Returns aggregate counts for dashboard cards

declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
        $stmt->execute([':t' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function safeCount(PDO $pdo, string $table): int {
    if (!tableExists($pdo, $table)) return 0;
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function windowCounts(PDO $pdo, string $table, string $dateCol): array {
    // current 7 days and the previous 7 days window for trend
    if (!tableExists($pdo, $table)) {
        return ['current' => 0, 'previous' => 0, 'pct' => 0.0];
    }
    try {
        $currentStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$dateCol}` >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $currentStmt->execute();
        $current = (int)($currentStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $prevStmt = $pdo->prepare("SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$dateCol}` >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND `{$dateCol}` < DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $prevStmt->execute();
        $previous = (int)($prevStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $pct = $previous > 0 ? (($current - $previous) / $previous) * 100.0 : ($current > 0 ? 100.0 : 0.0);
        return ['current' => $current, 'previous' => $previous, 'pct' => round($pct, 2)];
    } catch (Throwable $e) {
        return ['current' => 0, 'previous' => 0, 'pct' => 0.0];
    }
}

function calculateAiPerformance(PDO $pdo): array {
    if (!tableExists($pdo, 'crime_reports')) {
        return ['current_perf' => 0.0, 'trend' => 0.0];
    }
    try {
        // Overall
        $totalStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports");
        $totalReports = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $aiStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports WHERE status = 'closed' AND description LIKE '%[Closed by Admin AI%'");
        $aiClosed = (int)($aiStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $currentPerf = $totalReports > 0 ? ($aiClosed / $totalReports) * 100 : 0;

        // Last month vs This month
        $lmTotalStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        $lmTotal = (int)($lmTotalStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $lmAiStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports WHERE status = 'closed' AND description LIKE '%[Closed by Admin AI%' AND MONTH(closed_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(closed_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        $lmAi = (int)($lmAiStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $lmPerf = $lmTotal > 0 ? ($lmAi / $lmTotal) * 100 : 0;
        
        $tmTotalStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $tmTotal = (int)($tmTotalStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $tmAiStmt = $pdo->query("SELECT COUNT(*) as c FROM crime_reports WHERE status = 'closed' AND description LIKE '%[Closed by Admin AI%' AND MONTH(closed_at) = MONTH(CURRENT_DATE()) AND YEAR(closed_at) = YEAR(CURRENT_DATE())");
        $tmAi = (int)($tmAiStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        $tmPerf = $tmTotal > 0 ? ($tmAi / $tmTotal) * 100 : 0;

        $trend = $tmPerf - $lmPerf; // Difference in percentage points
        return ['current_perf' => round($currentPerf, 2), 'trend' => round($trend, 2)];
    } catch (Throwable $e) {
        return ['current_perf' => 0.0, 'trend' => 0.0];
    }
}

try {
    $users = safeCount($pdo, 'users');
    $policemen = safeCount($pdo, 'policemen');
    $volunteers = safeCount($pdo, 'volunteers');
    $cameras = safeCount($pdo, 'camera_contributors');
    $traffic = safeCount($pdo, 'traffic_logs'); // expects a traffic_logs table with created_at

    $userTrend = windowCounts($pdo, 'users', 'created_at');
    $cameraTrend = windowCounts($pdo, 'camera_contributors', 'created_at');
    $trafficTrend = windowCounts($pdo, 'traffic_logs', 'created_at');
    $aiPerformance = calculateAiPerformance($pdo);

    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'policemen' => $policemen,
            'volunteers' => $volunteers,
            'cameras' => $cameras,
            'traffic' => $traffic,
            'userTrend' => $userTrend,
            'cameraTrend' => $cameraTrend,
            'trafficTrend' => $trafficTrend,
            'aiPerformance' => $aiPerformance,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load counts']);
}
