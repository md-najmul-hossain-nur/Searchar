<?php
require_once "db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit();
}

try {
    // Check if email exists in any table
    $tables = ['users', 'policemen', 'volunteers', 'camera_contributors', 'admins'];
    $found = false;
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Email not found in our system']);
        exit();
    }

    // Generate 6 digit code
    $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Invalidate old codes
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

    // Insert new code
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $code, $expiresAt]);

    // Send email using PHPMailer
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
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Searchar Password Reset Code';
        $mail->Body    = "Your password reset code is: <b>$code</b><br><br>It will expire in 15 minutes.";
        $mail->AltBody = "Your password reset code is: $code\n\nIt will expire in 15 minutes.";

        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Reset code sent to email']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
