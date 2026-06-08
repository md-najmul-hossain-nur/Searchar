<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Validate Admin Access
$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdminSession = ($sessionRole === 'admin');

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$isAdminPanelRef = false;
if ($referer !== '') {
    $isAdminPanelRef = (
        stripos($referer, '/Website/Html/Admin.html') !== false ||
        stripos($referer, '/Website/Html/Admin.php') !== false
    );
}

if (!$isAdminSession && !$isAdminPanelRef) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$caseNo = trim((string)($payload['case_id'] ?? ''));
$sourceType = trim((string)($payload['source_type'] ?? ''));

if ($caseNo === '') {
    echo json_encode(['success' => false, 'error' => 'Case ID is required']);
    exit;
}

function parseCaseNoLocal(string $caseNo): array {
    $trimmed = strtoupper(trim($caseNo));
    if (!preg_match('/^(MP|PT)-?(\d+)$/', $trimmed, $m)) {
        return [null, 0];
    }
    return [$m[1], (int)$m[2]];
}

[$prefix, $rowId] = parseCaseNoLocal($caseNo);
if (!$prefix || $rowId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid case reference']);
    exit;
}

function sendEmailViaPHPMailer(string $toEmail, string $subject, string $htmlBody, string $altBody): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'searchar04@gmail.com';
        $mail->Password   = 'qbly vkft dzku ujni';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('searchar04@gmail.com', 'Searchar Admin');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $mail->ErrorInfo);
        return false;
    }
}

try {
    $pdo->beginTransaction();

    $reporterUserId = null;
    $reporterName = 'Unknown Reporter';
    $reporterEmail = null;

    // 1. Close the case
    if ($prefix === 'MP') {
        $stmt = $pdo->prepare("SELECT reporter_user_id, reporter_name FROM missing_person_reports WHERE report_id = :id");
        $stmt->execute([':id' => $rowId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reporterUserId = $row['reporter_user_id'];
            $reporterName = $row['reporter_name'];
        }

        $upd = $pdo->prepare("UPDATE missing_person_reports SET status = 'closed', resolved_at = NOW() WHERE report_id = :id");
        $upd->execute([':id' => $rowId]);
    } else {
        $stmt = $pdo->prepare("SELECT author_id, author_name FROM posts WHERE id = :id");
        $stmt->execute([':id' => $rowId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reporterUserId = $row['author_id']; // This would be the user ID
            $reporterName = $row['author_name'];
        }

        $upd = $pdo->prepare("UPDATE posts SET report_status = 'closed', report_closed_at = NOW() WHERE id = :id");
        $upd->execute([':id' => $rowId]);
    }

    $updCrime = $pdo->prepare("UPDATE crime_reports SET status = 'closed', closed_at = NOW(), description = CONCAT(COALESCE(description,''), '\n[Closed by Admin AI: Match Confirmed ($sourceType)]') WHERE case_ref = :ref");
    $updCrime->execute([':ref' => $caseNo]);

    // 2. Notifications Logic
    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :rid, :title, :message, :level)');

    // 3. Reporter handling
    if ($reporterUserId) {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $reporterUserId]);
        $uRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($uRow && !empty($uRow['email'])) {
            $reporterEmail = $uRow['email'];
        }

        // SMS to Reporter (simulated as a notification since we don't have real SMS gateway here, but using the same table structure)
        $notify->execute([
            ':entity' => 'users',
            ':rid' => $reporterUserId,
            ':title' => 'SMS: Case Solved',
            ':message' => "Your case $caseNo has been solved using AI.",
            ':level' => 'success'
        ]);

        // Website Notification for Donation
        $notify->execute([
            ':entity' => 'users',
            ':rid' => $reporterUserId,
            ':title' => 'Consider a Donation',
            ':message' => "We are glad your case was resolved! Please consider making a donation to support Searchar.",
            ':level' => 'info'
        ]);

        // Website Notification for Review
        $notify->execute([
            ':entity' => 'users',
            ':rid' => $reporterUserId,
            ':title' => 'Please give us a review',
            ':message' => "Your feedback helps us improve. Please leave a review for Searchar.",
            ':level' => 'info'
        ]);

        if ($reporterEmail) {
            sendEmailViaPHPMailer(
                $reporterEmail,
                "Case $caseNo Closed & Review Request",
                "Hello $reporterName,<br><br>Your case <b>$caseNo</b> has been solved using our AI system.<br>We would appreciate it if you could give us a review and consider donating.<br><br>Thank you,<br>Searchar Team",
                "Hello $reporterName,\n\nYour case $caseNo has been solved using our AI system.\nPlease consider leaving a review and donating.\n\nThank you,\nSearchar Team"
            );
        }
    }

    // 4. Volunteer handling
    $findMissions = $pdo->prepare("SELECT m.mission_id, m.volunteer_id, v.email, v.full_name 
                                   FROM volunteer_missions m 
                                   JOIN volunteers v ON m.volunteer_id = v.volunteer_id 
                                   WHERE UPPER(REPLACE(REPLACE(TRIM(COALESCE(m.case_ref,'')),'-',''),' ','')) = :ref 
                                     AND LOWER(COALESCE(m.status,'assigned')) NOT IN ('completed','rejected_busy','closed_by_police','closed_by_ai')");
    $findMissions->execute([':ref' => strtoupper(str_replace(['-',' '], '', $caseNo))]);
    $missions = $findMissions->fetchAll(PDO::FETCH_ASSOC);

    $updMission = $pdo->prepare("UPDATE volunteer_missions SET status = 'closed_by_ai', response_status = 'closed_by_ai', completed_at = NOW() WHERE mission_id = :mid");
    foreach ($missions as $m) {
        $updMission->execute([':mid' => $m['mission_id']]);

        $notify->execute([
            ':entity' => 'volunteers',
            ':rid' => $m['volunteer_id'],
            ':title' => 'Mission Auto-Closed (AI Match)',
            ':message' => "Case {$caseNo} was solved by Admin AI. Mission closed. You earned +5 XP for accepting.",
            ':level' => 'info'
        ]);

        if (!empty($m['email'])) {
            sendEmailViaPHPMailer(
                $m['email'],
                "Mission Update: Case $caseNo Solved",
                "Hello {$m['full_name']},<br><br>The case <b>$caseNo</b> you were assigned to has been solved by Admin AI.<br>You have received +5 XP.<br><br>Thank you for volunteering!",
                "Hello {$m['full_name']},\nThe case $caseNo has been solved by Admin AI. You have received +5 XP.\nThank you!"
            );
        }
    }

    // 5. Police handling (notify police in the case area)
    $stmtCase = $pdo->prepare("SELECT lat, lng, landmark FROM crime_reports WHERE case_ref = :ref LIMIT 1");
    $stmtCase->execute([':ref' => $caseNo]);
    $caseRow = $stmtCase->fetch(PDO::FETCH_ASSOC);

    $caseLat = (float)($caseRow['lat'] ?? 0);
    $caseLng = (float)($caseRow['lng'] ?? 0);
    $landmark = trim((string)($caseRow['landmark'] ?? ''));

    $policemen = [];
    if ($caseLat !== 0.0 && $caseLng !== 0.0) {
        $pStmt = $pdo->prepare("
            SELECT police_id, email, full_name,
            (6371 * acos(cos(radians(:lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians(:lng)) + sin(radians(:lat)) * sin(radians(latitude)))) AS distance
            FROM policemen
            HAVING distance <= 20 OR distance IS NULL
            ORDER BY distance ASC
        ");
        $pStmt->execute([':lat' => $caseLat, ':lng' => $caseLng]);
        $policemen = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } else if ($landmark !== '') {
        $pStmt = $pdo->prepare("SELECT police_id, email, full_name FROM policemen WHERE city LIKE :lm OR street LIKE :lm");
        $pStmt->execute([':lm' => '%' . $landmark . '%']);
        $policemen = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmtAll = $pdo->query("SELECT police_id, email, full_name FROM policemen");
        $policemen = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    }
    foreach ($policemen as $p) {
        $notify->execute([
            ':entity' => 'policemen',
            ':rid' => $p['police_id'],
            ':title' => 'Case Closed by Admin AI',
            ':message' => "Case {$caseNo} has been resolved automatically by Admin AI.",
            ':level' => 'info'
        ]);

        if (!empty($p['email'])) {
            sendEmailViaPHPMailer(
                $p['email'],
                "Case $caseNo Closed by Admin AI",
                "Officer {$p['full_name']},<br><br>Case <b>$caseNo</b> has been successfully resolved by the Admin AI.<br>No further action is required.",
                "Officer {$p['full_name']},\nCase $caseNo has been resolved by Admin AI.\nNo further action is required."
            );
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'AI Match Confirmed. Case auto-closed and notifications/emails dispatched.',
        'reporter_name' => $reporterName,
        'volunteer_name' => empty($missions) ? 'None' : implode(', ', array_column($missions, 'full_name')),
        'policeman_name' => empty($policemen) ? 'Pending Assignment' : 'Local Police',
        'matched_post_id' => $sourceType === 'Website Post' ? $rowId : null
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('admin_confirm_ai_match error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
