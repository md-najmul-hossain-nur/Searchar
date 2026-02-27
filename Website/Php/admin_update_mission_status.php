<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$missionId = (int)($payload['mission_id'] ?? 0);
$action = strtolower(trim((string)($payload['action'] ?? '')));

$allowed = ['accept', 'busy', 'complete'];
if ($missionId <= 0 || !in_array($action, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid payload']);
    exit;
}

$statusMap = [
    'accept' => ['status' => 'accepted', 'response_status' => 'accepted'],
    'busy' => ['status' => 'rejected_busy', 'response_status' => 'rejected_busy'],
    'complete' => ['status' => 'completed', 'response_status' => 'completed'],
];

$target = $statusMap[$action];

try {
    if (!columnExists($pdo, 'volunteer_missions', 'response_status')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN response_status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER status");
    }
    if (!columnExists($pdo, 'volunteer_missions', 'completed_at')) {
        $pdo->exec("ALTER TABLE volunteer_missions ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER proof_submitted_at");
    }

    $setCompleted = $action === 'complete' ? ', completed_at = NOW()' : '';
    $sql = "UPDATE volunteer_missions SET status = :status, response_status = :response_status{$setCompleted} WHERE mission_id = :mid LIMIT 1";
    $upd = $pdo->prepare($sql);
    $upd->execute([
        ':status' => $target['status'],
        ':response_status' => $target['response_status'],
        ':mid' => $missionId,
    ]);

    if ($upd->rowCount() < 1) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mission not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'mission_id' => $missionId,
        'status' => $target['status'],
        'response_status' => $target['response_status'],
    ]);
} catch (Throwable $e) {
    error_log('admin_update_mission_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update mission status']);
}
