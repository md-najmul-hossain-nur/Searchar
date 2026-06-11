<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

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
