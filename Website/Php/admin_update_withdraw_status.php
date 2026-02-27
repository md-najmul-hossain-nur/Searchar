<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function failResponse(int $statusCode, string $message): void {
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failResponse(405, 'Method not allowed');
}

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    failResponse(400, 'Invalid JSON payload');
}

$action = strtolower(trim((string)($data['action'] ?? '')));
if ($action !== 'approve' && $action !== 'reject') {
    failResponse(422, 'Invalid action');
}
$newStatus = $action === 'approve' ? 'approved' : 'rejected';

try {
    if (!tableExists($pdo, 'withdraw_requests')) {
        failResponse(404, 'Withdraw requests table not found');
    }

    $idColumn = null;
    if (columnExists($pdo, 'withdraw_requests', 'withdraw_id')) {
        $idColumn = 'withdraw_id';
    } elseif (columnExists($pdo, 'withdraw_requests', 'id')) {
        $idColumn = 'id';
    }

    $params = [':new_status' => $newStatus];
    $where = '';

    $requestId = (int)($data['request_id'] ?? 0);
    if ($idColumn !== null && $requestId > 0) {
        $where = "{$idColumn} = :request_id";
        $params[':request_id'] = $requestId;
    } else {
        $requesterName = trim((string)($data['requester_name'] ?? ''));
        $amount = (float)($data['amount'] ?? 0);
        $requestDate = trim((string)($data['request_date'] ?? ''));

        if ($requesterName === '' || $requestDate === '') {
            failResponse(422, 'Insufficient request identity data');
        }

        $where = 'requester_name = :requester_name AND amount = :amount AND request_date = :request_date';
        $params[':requester_name'] = $requesterName;
        $params[':amount'] = $amount;
        $params[':request_date'] = $requestDate;
    }

    $updatedAtSet = '';
    if (columnExists($pdo, 'withdraw_requests', 'updated_at')) {
        $updatedAtSet = ', updated_at = NOW()';
    }

    $sql = "UPDATE withdraw_requests
            SET status = :new_status{$updatedAtSet}
            WHERE {$where}
              AND LOWER(COALESCE(status, 'pending')) = 'pending'
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() < 1) {
        failResponse(409, 'Request already processed or not found');
    }

    echo json_encode([
        'success' => true,
        'status' => $newStatus,
        'message' => 'Withdraw request updated'
    ]);
} catch (Throwable $e) {
    failResponse(500, 'Failed to update withdraw request');
}
