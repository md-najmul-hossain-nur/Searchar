<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'] ?? '';
$postId = $data['post_id'] ?? '';

if (!$caseId || !$postId) {
    echo json_encode(['success' => false, 'error' => 'Missing case ID or post ID']);
    exit;
}

echo json_encode(['success' => true]);
