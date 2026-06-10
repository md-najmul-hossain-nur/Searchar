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

$police_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$missionId = (int)($data['mission_id'] ?? 0);
$action = (string)($data['action'] ?? '');

if ($missionId <= 0 || !in_array($action, ['accept_close', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT mission_title, case_ref, volunteer_id, status FROM volunteer_missions WHERE mission_id = :mid LIMIT 1 FOR UPDATE");
    $stmt->execute([':mid' => $missionId]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Mission not found']);
        exit;
    }

    if ($action === 'accept_close') {
        // Mark mission as completed by police
        $upd = $pdo->prepare("UPDATE volunteer_missions SET status = 'completed', response_status = 'completed', completed_at = NOW() WHERE mission_id = :mid");
        $upd->execute([':mid' => $missionId]);

        // Auto-close the associated case if it's a Missing Person or Post case
        $caseRef = strtoupper(trim((string)$mission['case_ref']));
        if (str_starts_with($caseRef, 'MP')) {
            // Missing person report
            $reportId = (int)preg_replace('/[^0-9]/', '', $caseRef);
            if ($reportId > 0) {
                $pdo->prepare("UPDATE missing_person_reports SET status = 'resolved' WHERE report_id = :rid")
                    ->execute([':rid' => $reportId]);
            }
        } elseif (str_starts_with($caseRef, 'PT')) {
            // Post case
            $postId = (int)preg_replace('/[^0-9]/', '', $caseRef);
            if ($postId > 0) {
                // If there's a status column, set it. Otherwise maybe report_status.
                $pdo->prepare("UPDATE posts SET status = 'resolved' WHERE id = :pid")
                    ->execute([':pid' => $postId]);
            }
        }

        // Notify Volunteer
        $volunteerId = (int)$mission['volunteer_id'];
        if ($volunteerId > 0) {
            $msg = sprintf("Your proof for mission '%s' was verified by the police and the case was closed. Great job!", $mission['mission_title']);
            $meta = json_encode(['type' => 'mission_verified', 'mission_id' => $missionId]);
            $pdo->prepare("INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json) VALUES ('volunteer', :vid, 'Proof Verified', :msg, :meta)")
                ->execute([':vid' => $volunteerId, ':msg' => $msg, ':meta' => $meta]);
        }
        
    } elseif ($action === 'reject') {
        $upd = $pdo->prepare("UPDATE volunteer_missions SET status = 'assigned', response_status = 'assigned', proof_file = NULL, proof_submitted_at = NULL WHERE mission_id = :mid");
        $upd->execute([':mid' => $missionId]);

        // Notify Volunteer
        $volunteerId = (int)$mission['volunteer_id'];
        if ($volunteerId > 0) {
            $msg = sprintf("Your proof for mission '%s' was rejected by the police. Please resubmit clear proof.", $mission['mission_title']);
            $meta = json_encode(['type' => 'mission_proof_rejected', 'mission_id' => $missionId]);
            $pdo->prepare("INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json) VALUES ('volunteer', :vid, 'Proof Rejected', :msg, :meta)")
                ->execute([':vid' => $volunteerId, ':msg' => $msg, ':meta' => $meta]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('police_verify_proof error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process proof']);
}
