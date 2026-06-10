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
$matchImg = trim((string)($payload['match_img'] ?? ''));
$matchDetails = trim((string)($payload['match_details'] ?? ''));

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
        $mail->Password   = 'qblyvkftdzkuujni';
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
    $reporterUserId = null;
    $reporterName = 'Unknown Reporter';
    $reporterEmail = null;

    if ($prefix === 'MP') {
        $stmt = $pdo->prepare("SELECT reporter_user_id, reporter_name FROM missing_person_reports WHERE report_id = :id");
        $stmt->execute([':id' => $rowId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reporterUserId = $row['reporter_user_id'];
            $reporterName = $row['reporter_name'];
        }
    } else {
        $stmt = $pdo->prepare("SELECT author_id, author_name FROM posts WHERE id = :id");
        $stmt->execute([':id' => $rowId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $reporterUserId = $row['author_id']; 
            $reporterName = $row['author_name'];
        }
    }

    if (!$reporterUserId) {
        echo json_encode(['success' => false, 'error' => 'Original reporter not found for this case.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $reporterUserId]);
    $uRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($uRow && !empty($uRow['email'])) {
        $reporterEmail = $uRow['email'];
    }

    $handoverId = 'HO-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :rid, :title, :message, :level)');

    $messageTxt = "We found the person you are looking for! Please bring your ID card for verification. Handover ID: $handoverId";
    if ($matchDetails !== '') {
        $messageTxt .= "\n\nMatch Details: $matchDetails";
    } else {
        $messageTxt .= "\n\nThey were successfully located by our team.";
    }

    $notify->execute([
        ':entity' => 'users',
        ':rid' => $reporterUserId,
        ':title' => 'Verification Required',
        ':message' => $messageTxt,
        ':level' => 'warning'
    ]);

    if ($reporterEmail) {
        $emailHtmlBody = "Hello $reporterName,<br><br>We have successfully found the person you were looking for.<br>";
        if ($matchDetails !== '') {
            $emailHtmlBody .= "<br><b>Match Details:</b> $matchDetails<br>";
        } else {
            $emailHtmlBody .= "<br>They were successfully located by our team.<br>";
        }
        if ($matchImg !== '') {
            $emailHtmlBody .= "<br><img src='$matchImg' style='max-width:300px; border-radius:8px;'><br>";
        }
        $emailHtmlBody .= "<br>Please bring your ID to verify and complete the handover.<br><br><b>Your Handover ID:</b> $handoverId<br><br>Thank you,<br>Searchar Team";

        $emailAltBody = "Hello $reporterName,\n\nWe have successfully found the person you were looking for.\n";
        if ($matchDetails !== '') {
            $emailAltBody .= "Match Details: $matchDetails\n";
        } else {
            $emailAltBody .= "They were successfully located by our team.\n";
        }
        $emailAltBody .= "Please bring your ID to verify and complete the handover.\n\nYour Handover ID: $handoverId\n\nThank you,\nSearchar Team";

        sendEmailViaPHPMailer(
            $reporterEmail,
            "Verification Required - Case $caseNo",
            $emailHtmlBody,
            $emailAltBody
        );
    }

    echo json_encode([
        'success' => true,
        'handover_id' => $handoverId
    ]);
} catch (Throwable $e) {
    error_log('admin_notify_reporter_handover error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
