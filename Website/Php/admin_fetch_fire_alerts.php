<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

try {
    $sql = "SELECT 
                f.alert_id,
                f.feed_id,
                f.confidence,
                f.snapshot_url,
                f.status,
                f.created_at,
                c.feed_label,
                c.camera_location
            FROM fire_alerts f
            LEFT JOIN camera_cctv_feeds c ON f.feed_id = c.feed_id
            ORDER BY f.created_at DESC";
            
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'rows' => $rows,
    ]);
} catch (Throwable $e) {
    error_log('admin_fetch_fire_alerts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch fire alerts'
    ]);
}
