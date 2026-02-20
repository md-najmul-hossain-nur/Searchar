<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $volunteerId = (int)($payload['volunteer_id'] ?? 0);
    $title = trim((string)($payload['title'] ?? ''));
    $details = trim((string)($payload['details'] ?? ''));
    $missionLocation = trim((string)($payload['mission_location'] ?? ''));

    if ($volunteerId <= 0) {
        throw new RuntimeException('Invalid volunteer id');
    }
    if ($title === '') {
        throw new RuntimeException('Mission title is required');
    }

    $check = $pdo->prepare('SELECT volunteer_id, full_name, name FROM volunteers WHERE volunteer_id = :id LIMIT 1');
    $check->execute([':id' => $volunteerId]);
    $volunteer = $check->fetch(PDO::FETCH_ASSOC);
    if (!$volunteer) {
        throw new RuntimeException('Volunteer not found');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_missions (
        mission_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        volunteer_id INT UNSIGNED NOT NULL,
        mission_title VARCHAR(190) NOT NULL,
        mission_details TEXT DEFAULT NULL,
        mission_location VARCHAR(255) DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'assigned',
        assigned_by VARCHAR(100) NOT NULL DEFAULT 'admin',
        assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (mission_id),
        INDEX idx_vm_volunteer (volunteer_id),
        INDEX idx_vm_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ins = $pdo->prepare('INSERT INTO volunteer_missions (volunteer_id, mission_title, mission_details, mission_location, status, assigned_by) VALUES (:volunteer_id, :title, :details, :mission_location, :status, :assigned_by)');
    $ins->execute([
        ':volunteer_id' => $volunteerId,
        ':title' => $title,
        ':details' => $details !== '' ? $details : null,
        ':mission_location' => $missionLocation !== '' ? $missionLocation : null,
        ':status' => 'assigned',
        ':assigned_by' => 'admin',
    ]);

    $notificationMessage = 'New mission assigned: ' . $title;
    if ($missionLocation !== '') {
        $notificationMessage .= ' | Location: ' . $missionLocation;
    }
    if ($details !== '') {
        $notificationMessage .= ' | Details: ' . $details;
    }

    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :recipient_id, :title, :message, :level)');
    $notify->execute([
        ':entity' => 'volunteer',
        ':recipient_id' => $volunteerId,
        ':title' => 'Mission Assigned',
        ':message' => $notificationMessage,
        ':level' => 'info',
    ]);

    echo json_encode([
        'success' => true,
        'mission_id' => (int)$pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
