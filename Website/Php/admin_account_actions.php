<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name LIMIT 1");
    $stmt->execute([':name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $action = (string)($payload['action'] ?? '');
    $entity = (string)($payload['entity'] ?? '');
    $id = (int)($payload['id'] ?? 0);
    $message = trim((string)($payload['message'] ?? ''));

    $entityMap = [
        'users' => ['table' => 'users', 'id_col' => 'user_id', 'role' => 'user'],
        'volunteers' => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'role' => 'volunteer'],
        'camera_contributors' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'role' => 'camera_contributor'],
        'policemen' => ['table' => 'policemen', 'id_col' => 'police_id', 'role' => 'policeman'],
    ];

    if (!isset($entityMap[$entity])) {
        throw new RuntimeException('Unsupported entity');
    }
    if ($id <= 0) {
        throw new RuntimeException('Invalid id');
    }

    // ensure helper tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS signup_blacklist (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        entity VARCHAR(60) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        mobile VARCHAR(30) DEFAULT NULL,
        nid_number VARCHAR(100) DEFAULT NULL,
        reason VARCHAR(255) DEFAULT NULL,
        blocked_by VARCHAR(100) DEFAULT 'admin',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_blacklist_email (email),
        INDEX idx_blacklist_mobile (mobile),
        INDEX idx_blacklist_nid (nid_number)
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

    $table = $entityMap[$entity]['table'];
    $idCol = $entityMap[$entity]['id_col'];
    $role = $entityMap[$entity]['role'];

    $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE `{$idCol}` = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Record not found');
    }

    if ($action === 'warn') {
        $title = 'Admin Warning';
        $body = $message !== '' ? $message : 'Admin warning: Please review your account activity.';

        $exists = $pdo->prepare("SELECT notification_id FROM user_notifications WHERE recipient_entity = :entity AND recipient_id = :id AND level = 'warning' AND title = 'Admin Warning' LIMIT 1");
        $exists->execute([
            ':entity' => $role,
            ':id' => $id,
        ]);
        if ($exists->fetchColumn()) {
            echo json_encode(['success' => true, 'already_warned' => true]);
            exit;
        }

        $ins = $pdo->prepare("INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :id, :title, :message, 'warning')");
        $ins->execute([
            ':entity' => $role,
            ':id' => $id,
            ':title' => $title,
            ':message' => $body,
        ]);

        echo json_encode(['success' => true, 'already_warned' => false]);
        exit;
    }

    if ($action === 'delete') {
        $email = isset($row['email']) ? (string)$row['email'] : null;
        $mobile = isset($row['mobile']) ? (string)$row['mobile'] : null;
        $nid = isset($row['nid_number']) ? (string)$row['nid_number'] : null;

        $pdo->beginTransaction();

        $blk = $pdo->prepare("INSERT INTO signup_blacklist (entity, email, mobile, nid_number, reason, blocked_by)
                              VALUES (:entity, :email, :mobile, :nid, 'Deleted by admin', 'admin')");
        $blk->execute([
            ':entity' => $entity,
            ':email' => $email,
            ':mobile' => $mobile,
            ':nid' => $nid,
        ]);

        if ($entity === 'users') {
            if (tableExists($pdo, 'volunteer_applications')) {
                $delCombo = $pdo->prepare("DELETE FROM volunteer_applications WHERE user_id = :uid");
                $delCombo->execute([':uid' => $id]);
            }

            if (tableExists($pdo, 'user_combo_roles')) {
                $delComboRole = $pdo->prepare("DELETE FROM user_combo_roles WHERE user_id = :uid");
                $delComboRole->execute([':uid' => $id]);
            }
        }

        $del = $pdo->prepare("DELETE FROM `{$table}` WHERE `{$idCol}` = :id LIMIT 1");
        $del->execute([':id' => $id]);

        $pdo->commit();

        echo json_encode(['success' => true]);
        exit;
    }

    throw new RuntimeException('Unsupported action');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
