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
$postId = $data['post_id'] ?? '';

if (!$caseId || !$postId) {
    echo json_encode(['success' => false, 'error' => 'Missing case ID or post ID']);
    exit;
}

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
    // For demo purposes, sending the thank you mail to the test email
    $mail->addAddress('najmulhosainnur555@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = "Thank you for your help!";
    $mail->Body    = "Hello,<br><br>Thank you so much for your post (Post ID: {$postId}). It was a match and helped us resolve Case #{$caseId}!<br><br>We really appreciate your contribution.<br><br>Best regards,<br>Searchar Team";

    $mail->send();
} catch (Exception $e) {
    // Ignore error so UI still works
}

echo json_encode(['success' => true]);
?>
