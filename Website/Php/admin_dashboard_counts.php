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

try {
    $users = safeCount($pdo, 'users');
    $policemen = safeCount($pdo, 'policemen');
    $volunteers = safeCount($pdo, 'volunteers');
    $cameras = safeCount($pdo, 'camera_contributors');
    $traffic = safeCount($pdo, 'traffic_logs'); // expects a traffic_logs table with created_at

    $userTrend = windowCounts($pdo, 'users', 'created_at');
    $cameraTrend = windowCounts($pdo, 'camera_contributors', 'created_at');
    $trafficTrend = windowCounts($pdo, 'traffic_logs', 'created_at');

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
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load counts']);
}
