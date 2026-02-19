<?php
// Returns monthly camera_contributor counts grouped by year
// Response: { success: true, data: { "2026": [..12..], "2027": [..12..], ... } }

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->query("SELECT YEAR(created_at) AS y, MONTH(created_at) AS m, COUNT(*) AS c FROM camera_contributors GROUP BY y, m ORDER BY y, m");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $y = (string)$row['y'];
        $m = (int)$row['m'];
        $c = (int)$row['c'];
        if (!isset($data[$y])) {
            $data[$y] = array_fill(0, 12, 0);
        }
        // month is 1-based
        if ($m >= 1 && $m <= 12) {
            $data[$y][$m - 1] = $c;
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
