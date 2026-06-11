<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'] ?? '';
if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Missing case ID']);
    exit;
}

$handoverId = 'HO-' . strtoupper(uniqid());
echo json_encode(['success' => true, 'handover_id' => $handoverId]);
