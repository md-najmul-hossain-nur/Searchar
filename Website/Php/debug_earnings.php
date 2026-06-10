<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
session_start();
$userId = (int)($_SESSION['user_id'] ?? 0);
$role = (string)($_SESSION['role'] ?? '');
header('Content-Type: application/json; charset=utf-8');
if ($userId <= 0) {
    echo json_encode(['success'=>false,'error'=>'no session']);
    exit;
}
$feedsStmt = $pdo->prepare('SELECT * FROM camera_cctv_feeds WHERE camera_id = :cid');
$feedsStmt->execute([':cid'=>$userId]);
$feeds = $feedsStmt->fetchAll(PDO::FETCH_ASSOC);
// Compute earnings as in original script
function computeAccruedSeconds(array $row): int {
    $acc = (int)($row['accumulated_seconds'] ?? 0);
    $isActive = (int)($row['is_active'] ?? 0) === 1;
    if (!$isActive) return max(0,$acc);
    $startRaw = (string)($row['active_started_at'] ?? '');
    if ($startRaw==='') $startRaw = (string)($row['created_at'] ?? '');
    $ts = strtotime($startRaw);
    return max(0,$acc + (time() - ($ts?:time())));
}
$totalEarned = 0.0;
foreach ($feeds as $f) {
    $accrued = computeAccruedSeconds($f);
    $totalEarned += ($accrued/3600)*40;
}

echo json_encode(['success'=>true,'feeds'=>$feeds,'totalEarned'=>round($totalEarned,2)]);
?>
