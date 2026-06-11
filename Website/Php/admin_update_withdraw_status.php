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
    // support both common table names used by different endpoints
    $tableCandidates = ['withdrawal_requests', 'withdraw_requests'];
    $tableName = null;
    foreach ($tableCandidates as $t) {
        if (tableExists($pdo, $t)) {
            $tableName = $t;
            break;
        }
    }

    if ($tableName === null) {
        failResponse(404, 'Withdraw requests table not found');
    }

    $idColumn = null;
    if (columnExists($pdo, $tableName, 'withdraw_id')) {
        $idColumn = 'withdraw_id';
    } elseif (columnExists($pdo, $tableName, 'id')) {
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

    try {
        // perform select-for-update then update within a transaction so we can create a notification
        $pdo->beginTransaction();

        $selectSql = "SELECT * FROM `{$tableName}` WHERE {$where} LIMIT 1 FOR UPDATE";
        $selectStmt = $pdo->prepare($selectSql);
        // execute select with only the WHERE params (exclude :new_status)
        $selectParams = $params;
        if (array_key_exists(':new_status', $selectParams)) unset($selectParams[':new_status']);
        $selectStmt->execute($selectParams);
        $row = $selectStmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $pdo->rollBack();
            failResponse(409, 'Request already processed or not found');
        }

        $currentStatus = strtolower(trim((string)($row['status'] ?? 'pending')));
        if ($currentStatus !== 'pending') {
            $pdo->rollBack();
            failResponse(409, 'Request already processed or not found');
        }

        $updatedAtSet = '';
        if (columnExists($pdo, $tableName, 'updated_at')) {
            $updatedAtSet = ', updated_at = NOW()';
        } elseif (columnExists($pdo, $tableName, 'processed_at')) {
            // some schemas use processed_at
            $updatedAtSet = ', processed_at = NOW()';
        }

        $txIdSet = '';
        if ($newStatus === 'approved' && isset($data['tx_id']) && columnExists($pdo, $tableName, 'tx_id')) {
            $txIdSet = ', tx_id = :tx_id';
            $params[':tx_id'] = trim((string)$data['tx_id']);
        }

        $updateSql = "UPDATE `{$tableName}`
                        SET status = :new_status{$updatedAtSet}{$txIdSet}
                        WHERE {$where}
                            AND LOWER(COALESCE(status, 'pending')) = 'pending'
                        LIMIT 1";

        $updateStmt = $pdo->prepare($updateSql);
        // update requires :new_status, :tx_id (optional), and the where params
        $updateStmt->execute($params);

        if ($updateStmt->rowCount() < 1) {
            $pdo->rollBack();
            failResponse(409, 'Request already processed or not found');
        }

        // Insert a user notification for the contributor (if contributor_id exists)
        $contributorId = isset($row['contributor_id']) ? (int)$row['contributor_id'] : 0;
        $amountVal = isset($row['amount']) ? $row['amount'] : null;
        if ($contributorId > 0 && tableExists($pdo, 'user_notifications')) {
            $title = ($newStatus === 'approved') ? 'Admin: Withdrawal Approved' : 'Admin: Withdrawal Update';
            $message = ($newStatus === 'approved') ? "Your withdrawal request of ৳{$amountVal} has been approved." : "Your withdrawal request of ৳{$amountVal} has been rejected.";
            $meta = json_encode(['request_id' => $row[$idColumn] ?? $requestId, 'amount' => $amountVal, 'status' => $newStatus]);
            $level = ($newStatus === 'approved') ? 'success' : 'warning';

            $notifStmt = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read) VALUES (:entity, :rid, :title, :message, :meta, :level, 0)');
            $notifStmt->execute([
                ':entity' => 'contributor',
                ':rid' => $contributorId,
                ':title' => $title,
                ':message' => $message,
                ':meta' => $meta,
                ':level' => $level
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'status' => $newStatus,
            'message' => 'Withdraw request updated'
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = 'Failed to update withdraw request';
        $dbg = $e->getMessage();
        failResponse(500, $msg . ': ' . $dbg);
    }
} catch (Throwable $e) {
    failResponse(500, 'Failed to update withdraw request');
}
