<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

try {
    $raw = file_get_contents('php://input');
    $payload = is_string($raw) && trim($raw) !== '' ? (json_decode($raw, true) ?: []) : [];
    if (!$payload) $payload = $_POST;

    $reportId = (int)($payload['report_id'] ?? 0);
    $reportSource = strtolower(trim((string)($payload['report_source'] ?? 'post')));
    $action = strtolower(trim((string)($payload['action'] ?? '')));
    $note = trim((string)($payload['note'] ?? ''));

    $allowed = [
        'mark_reviewed' => 'under_review',
        'resolve' => 'resolved',
        'dismiss' => 'dismissed'
    ];

    if ($reportId <= 0 || !isset($allowed[$action])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report/action']);
        exit;
    }

    if (!in_array($reportSource, ['post', 'comment'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid report source']);
        exit;
    }

    $targetTable = $reportSource === 'comment' ? 'comment_reports' : 'post_reports';

    if (!tableExists($pdo, $targetTable)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Report table not found']);
        exit;
    }

    $newStatus = $allowed[$action];

    $stmt = $pdo->prepare("UPDATE {$targetTable} SET status = :status, admin_action_note = :note, actioned_at = NOW() WHERE report_id = :id");
    $stmt->execute([
        ':status' => $newStatus,
        ':note' => $note !== '' ? $note : null,
        ':id' => $reportId,
    ]);

    if ($stmt->rowCount() <= 0) {
        $chk = $pdo->prepare("SELECT status FROM {$targetTable} WHERE report_id = :id LIMIT 1");
        $chk->execute([':id' => $reportId]);
        $current = $chk->fetchColumn();
        if ($current === false) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Report not found']);
            exit;
        }
        echo json_encode(['success' => true, 'status' => (string)$current, 'unchanged' => true]);
        exit;
    }

    echo json_encode(['success' => true, 'status' => $newStatus]);
} catch (Throwable $e) {
    error_log('admin_update_post_report_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update report status'
    ]);
}
