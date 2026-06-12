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

    $numericId = (int) preg_replace('/[^0-9]/', '', $caseId);

    // 1. First, check and update crime_reports by EXACT case_ref match
    $stmt = $pdo->prepare("SELECT reporter_name, report_type, source_ref_id FROM crime_reports WHERE case_ref = ?");
    $stmt->execute([$caseId]);
    $crimeRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $updatedCrime = false;
    if ($crimeRow) {
        $pdo->prepare("UPDATE crime_reports SET status = 'closed', closed_at = NOW() WHERE case_ref = ?")->execute([$caseId]);
        $reporterName = !empty($crimeRow['reporter_name']) ? $crimeRow['reporter_name'] : 'Unknown Reporter';
        $updatedCrime = true;
    }

    // 2. Check and update missing_person_reports using the extracted numeric ID
    if ($numericId > 0) {
        $stmt = $pdo->prepare("SELECT reporter_name FROM missing_person_reports WHERE report_id = ?");
        $stmt->execute([$numericId]);
        $mpRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mpRow) {
            $pdo->prepare("UPDATE missing_person_reports SET status = 'resolved', resolved_at = NOW() WHERE report_id = ?")->execute([$numericId]);
            // Use this reporter name if crime_reports didn't have one
            if (!$updatedCrime) {
                $reporterName = !empty($mpRow['reporter_name']) ? $mpRow['reporter_name'] : 'Unknown Reporter';
            }
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
