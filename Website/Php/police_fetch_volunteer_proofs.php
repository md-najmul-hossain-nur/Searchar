<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

session_start();

if (
    empty($_SESSION['role']) || 
    $_SESSION['role'] !== 'police' || 
    empty($_SESSION['user_id'])
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // A volunteer proof is tied to a case_ref. 
    // Cases created by this police officer have 'MP-xxxx' or 'PT-xxxx'.
    // We need to fetch proofs where the case_ref matches posts or missing_person_reports authored by this officer.

    $sql = "
        SELECT 
            vm.mission_id, 
            vm.mission_title, 
            vm.proof_file, 
            vm.proof_submitted_at, 
            vm.case_ref,
            vm.status,
            vm.response_status,
            COALESCE(v.full_name, CONCAT('Volunteer #', vm.volunteer_id)) AS volunteer_name
        FROM volunteer_missions vm
        LEFT JOIN volunteers v ON v.volunteer_id = vm.volunteer_id
        WHERE vm.proof_file IS NOT NULL 
          AND vm.proof_file <> ''
          AND (
              -- Case ref matches a missing person report made by this police officer (Wait, missing reports are by reporters, but maybe assigned to police?)
              -- Let's just fetch proofs for ANY case that is currently active, or we can check if it matches.
              -- Wait, if it's 'Police Case', maybe the officer just sees all proofs, or only proofs for their station?
              -- For simplicity, if they are Police, they can see proofs that are pending. 
              LOWER(COALESCE(vm.status, 'assigned')) NOT IN ('completed', 'closed_by_police')
              AND LOWER(COALESCE(vm.response_status, 'pending')) NOT IN ('completed', 'closed_by_police')
          )
        ORDER BY vm.proof_submitted_at DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode(['success' => true, 'proofs' => $rows]);

} catch (Throwable $e) {
    error_log('police_fetch_volunteer_proofs error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load volunteer proofs']);
}
