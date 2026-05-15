<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    if (!tableExists($pdo, 'camera_cctv_feeds')) {
        respond(['success' => true, 'rows' => []]);
    }

    $sql = "
        SELECT
            f.feed_id,
            f.camera_id,
            f.feed_label,
            f.feed_type,
            f.video_path,
            f.live_url,
            f.camera_location,
            f.created_at,
            c.full_name AS cameraman_name,
            c.street,
            c.city,
            c.country
        FROM camera_cctv_feeds f
        LEFT JOIN camera_contributors c ON c.camera_id = f.camera_id
        ORDER BY f.feed_id DESC
        LIMIT 500
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

    respond(['success' => true, 'rows' => $rows]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to fetch camera videos'], 500);
}
