<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    // Fetch active CCTV feeds that might be relevant for AI scanning
    // For now we get all active ones.
    $sql = "SELECT feed_id, camera_id, feed_label, live_url, video_path FROM camera_cctv_feeds WHERE is_active = 1 AND is_deleted = 0";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    echo json_encode(['success' => true, 'feeds' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
