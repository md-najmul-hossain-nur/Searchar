<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Since Python is calling this internally via requests.post(json={...}), we read from php://input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST; // fallback to standard POST
    }

    $feedId = (int)($input['feed_id'] ?? 0);
    $confidence = $input['confidence'] ?? '';
    $snapshotUrl = $input['snapshot_url'] ?? '';

    if ($feedId <= 0) {
        throw new Exception('Missing feed_id');
    }

    $stmt = $pdo->prepare("INSERT INTO fire_alerts (feed_id, confidence, snapshot_url, status) VALUES (:feed_id, :confidence, :snapshot_url, 'new')");
    $stmt->execute([
        ':feed_id' => $feedId,
        ':confidence' => $confidence,
        ':snapshot_url' => $snapshotUrl
    ]);

    echo json_encode([
        'success' => true,
        'alert_id' => $pdo->lastInsertId()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
