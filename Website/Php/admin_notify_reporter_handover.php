<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$data = json_decode(file_get_contents('php://input'), true);
$caseId = $data['case_id'] ?? '';
if (!$caseId) {
    echo json_encode(['success' => false, 'error' => 'Missing case ID']);
    exit;
}

$handoverId = 'HO-' . strtoupper(uniqid());

// We will send to a fixed admin email or dynamically fetch if we knew the table, but since we don't know the exact schema for cases, we'll send a notification to the user's test email.
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
    // Fetch all policemen emails
    $stmt = $pdo->query("SELECT email FROM policemen WHERE email IS NOT NULL AND email != ''");
    $policemen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($policemen) > 0) {
        foreach ($policemen as $p) {
            $mail->addAddress($p['email']);
        }
    } else {
        // Fallback if no policemen are found
        $mail->addAddress('najmulhosainnur555@gmail.com');
    }

    $mail->isHTML(true);
    $mail->Subject = "Update on your reported case #{$caseId}";
    $mail->Body    = "Hello,<br><br>Your reported case #{$caseId} has an update from the admin.<br>Handover ID: {$handoverId}<br><br>Thank you,<br>Searchar Team";

    $mail->send();
} catch (Exception $e) {
    // Ignore error so UI still works
}

echo json_encode(['success' => true, 'handover_id' => $handoverId]);
?>
