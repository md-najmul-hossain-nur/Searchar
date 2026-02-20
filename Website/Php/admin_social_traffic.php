<?php
// Returns social traffic by referrer for the last 7 days

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
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

try {
    if (!tableExists($pdo, 'traffic_logs')) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

        // Expect traffic_logs table with columns: referrer (or source) and created_at
        $limitClause = isset($_GET['all']) ? '' : 'LIMIT 8';
        $sql = "SELECT COALESCE(NULLIF(TRIM(referrer), ''), 'Direct') AS source, COUNT(*) AS visitors
            FROM traffic_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY source
            ORDER BY visitors DESC
            $limitClause";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load social traffic']);
}
