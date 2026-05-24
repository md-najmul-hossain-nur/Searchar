<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'contributor' || empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    $tableCandidates = ['withdrawal_requests', 'withdraw_requests'];
    $tableName = null;
    foreach ($tableCandidates as $t) {
        if (tableExists($pdo, $t)) {
            $tableName = $t;
            break;
        }
    }

    if ($tableName === null) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $sql = "SELECT id, method, amount, status, created_at
            FROM `{$tableName}`
            WHERE contributor_id = :cid
            ORDER BY created_at DESC
            LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
