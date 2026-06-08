<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdminSession = ($sessionRole === 'admin');

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$isAdminPanelRef = false;
if ($referer !== '') {
    $isAdminPanelRef = (
        stripos($referer, '/Website/Html/Admin.html') !== false ||
        stripos($referer, '/Website/Html/Admin.php') !== false
    );
}

if (!$isAdminSession && !$isAdminPanelRef) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT MONTH(closed_at) as m, COUNT(*) as c
        FROM crime_reports
        WHERE status = 'closed'
          AND description LIKE '%[Closed by Admin AI%'
          AND YEAR(closed_at) = 2026
        GROUP BY MONTH(closed_at)
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data2026 = array_fill(0, 12, 0);
    foreach ($rows as $r) {
        if ($r['m']) {
            $data2026[(int)$r['m'] - 1] = (int)$r['c'];
        }
    }

    echo json_encode([
        'success' => true,
        'data_2026' => $data2026
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
