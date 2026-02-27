<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

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

function normalizeStatus(string $status): string {
    $status = strtolower(trim($status));
    return $status === '' ? 'open' : $status;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!tableExists($pdo, 'missing_person_reports')) {
        throw new RuntimeException('Missing reports table not found');
    }

    if (!columnExists($pdo, 'missing_person_reports', 'resolved_at')) {
        $pdo->exec("ALTER TABLE missing_person_reports ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER status");
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $reportId = (int)($payload['report_id'] ?? 0);
    $action = strtolower(trim((string)($payload['action'] ?? '')));

    if ($reportId <= 0) {
        throw new RuntimeException('Invalid report id');
    }

    $allowedActions = [
        'reject' => 'closed',
        'make_report' => 'under_review',
    ];

    if (!isset($allowedActions[$action])) {
        throw new RuntimeException('Invalid action');
    }

    $rowStmt = $pdo->prepare('SELECT report_id, status FROM missing_person_reports WHERE report_id = :id LIMIT 1');
    $rowStmt->execute([':id' => $reportId]);
    $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException('Report not found');
    }

    $currentStatus = normalizeStatus((string)($row['status'] ?? 'open'));
    $actionableStatuses = ['open', 'active', 'pending', 'searching'];
    if (!in_array($currentStatus, $actionableStatuses, true)) {
        echo json_encode([
            'success' => false,
            'already_processed' => true,
            'status' => $currentStatus,
            'error' => 'This report is already processed',
        ]);
        exit;
    }

    $targetStatus = $allowedActions[$action];

    if ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = :status, resolved_at = NOW() WHERE report_id = :id AND LOWER(COALESCE(status, 'open')) IN ('open','active','pending','searching')");
        $upd->execute([':status' => $targetStatus, ':id' => $reportId]);
    } else {
        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = :status, resolved_at = NULL WHERE report_id = :id AND LOWER(COALESCE(status, 'open')) IN ('open','active','pending','searching')");
        $upd->execute([':status' => $targetStatus, ':id' => $reportId]);
    }

    if ($upd->rowCount() < 1) {
        $fresh = $pdo->prepare('SELECT status FROM missing_person_reports WHERE report_id = :id LIMIT 1');
        $fresh->execute([':id' => $reportId]);
        $freshStatus = normalizeStatus((string)($fresh->fetchColumn() ?: 'open'));

        echo json_encode([
            'success' => false,
            'already_processed' => true,
            'status' => $freshStatus,
            'error' => 'This report is already processed',
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'report_id' => $reportId,
        'status' => $targetStatus,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
