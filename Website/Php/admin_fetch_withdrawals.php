<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    // Find table name
    $candidates = ['withdrawal_requests', 'withdraw_requests'];
    $tableName = null;
    foreach ($candidates as $t) {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
        $stmt->execute([':t' => $t]);
        if ($stmt->fetchColumn()) {
            $tableName = $t;
            break;
        }
    }

    if ($tableName === null) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $txIdSelect = "";
    $stmtCheck = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = 'tx_id' LIMIT 1");
    $stmtCheck->execute([':t' => $tableName]);
    if ($stmtCheck->fetchColumn()) {
        $txIdSelect = ", r.tx_id";
    }

    // Return recent requests with contributor name where possible
    $sql = "SELECT r.* {$txIdSelect},
                   COALESCE(c.full_name, u.full_name, '') AS contributor_name
            FROM `{$tableName}` r
            LEFT JOIN camera_contributors c ON c.camera_id = r.contributor_id
            LEFT JOIN users u ON u.user_id = r.contributor_id
            ORDER BY r.created_at DESC
            LIMIT 100";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
