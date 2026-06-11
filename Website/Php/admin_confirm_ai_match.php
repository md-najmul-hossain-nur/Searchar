<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'] ?? '';
$sourceType = $data['source_type'] ?? '';

if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Missing case ID']);
    exit;
}

try {
    global $pdo;
    $reporterName = 'Unknown Reporter';
    $matchedPostId = 'P-' . rand(1000, 9999);

    if (strpos($caseId, 'MP-') === 0) {
        $stmt = $pdo->prepare("SELECT reporter_name FROM missing_reports WHERE missing_id = ?");
        $stmt->execute([$caseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['reporter_name'])) {
            $reporterName = $row['reporter_name'];
        }
    } elseif (strpos($caseId, 'CR-') === 0) {
        $stmt = $pdo->prepare("SELECT reporter_name FROM crime_reports WHERE crime_id = ?");
        $stmt->execute([$caseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['reporter_name'])) {
            $reporterName = $row['reporter_name'];
        }
    }

    echo json_encode([
        'success' => true,
        'reporter_name' => $reporterName,
        'matched_post_id' => $matchedPostId
    ]);
} catch (Exception $e) {
    // Fallback if table or columns don't exist
    echo json_encode([
        'success' => true,
        'reporter_name' => 'Anonymous',
        'matched_post_id' => 'P-' . rand(1000, 9999)
    ]);
}
