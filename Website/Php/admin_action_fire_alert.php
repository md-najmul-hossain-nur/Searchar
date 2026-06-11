<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $alertId = (int)($_POST['alert_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($alertId <= 0 || !$action) {
        throw new Exception('Missing parameters');
    }

    $newStatus = '';
    switch ($action) {
        case 'dispatch_police':
            $newStatus = 'police_dispatched';
            break;
        case 'call_fire_station':
            $newStatus = 'fire_station_called';
            break;
        case 'notify_camera_man':
            $newStatus = 'camera_man_notified';
            break;
        case 'dismiss':
            $newStatus = 'dismissed';
            break;
        default:
            throw new Exception('Unknown action');
    }

    $stmt = $pdo->prepare("UPDATE fire_alerts SET status = :status WHERE alert_id = :id");
    $stmt->execute([
        ':status' => $newStatus,
        ':id' => $alertId
    ]);

    // Send email to camera contributor if it's an emergency action
    if ($action !== 'dismiss') {
        $stmtInfo = $pdo->prepare("
            SELECT f.feed_label, f.camera_location, c.email, c.full_name
            FROM fire_alerts fa
            JOIN camera_cctv_feeds f ON fa.feed_id = f.feed_id
            JOIN camera_contributors c ON f.camera_id = c.camera_id
            WHERE fa.alert_id = :id
        ");
        $stmtInfo->execute([':id' => $alertId]);
        $cameraInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

        if ($cameraInfo && !empty($cameraInfo['email'])) {
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
                $mail->addAddress($cameraInfo['email']);

                $mail->isHTML(true);
                $mail->Subject = 'URGENT: Fire Alert Action Taken on Your Camera';
                
                $actionText = ucwords(str_replace('_', ' ', $newStatus));
                $mail->Body    = "Hello {$cameraInfo['full_name']},<br><br>
                An emergency fire alert was detected on your camera <b>{$cameraInfo['feed_label']}</b> located at <b>{$cameraInfo['camera_location']}</b>.<br><br>
                The Admin has taken the following action: <b>{$actionText}</b>.<br><br>
                Please check your premises immediately.<br><br>
                Stay safe,<br>Searchar Team";

                $mail->send();
            } catch (Exception $e) {
                error_log("Failed to send email to cameraman: {$mail->ErrorInfo}");
            }
        }
    }

    echo json_encode([
        'success' => true,
        'new_status' => $newStatus
    ]);
} catch (Throwable $e) {
    error_log('admin_action_fire_alert error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
