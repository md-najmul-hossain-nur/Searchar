<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function ensureVolunteerApplicationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_applications (
        application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        mobile VARCHAR(30) DEFAULT NULL,
        nid_number VARCHAR(100) DEFAULT NULL,
        city VARCHAR(120) DEFAULT NULL,
        country VARCHAR(120) DEFAULT NULL,
        note TEXT DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        reviewed_by VARCHAR(100) DEFAULT NULL,
        review_note VARCHAR(255) DEFAULT NULL,
        volunteer_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        reviewed_at DATETIME DEFAULT NULL,
        PRIMARY KEY (application_id),
        UNIQUE KEY uq_volunteer_application_user (user_id),
        KEY idx_volunteer_application_status (status),
        KEY idx_volunteer_application_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensureNotificationTable(PDO $pdo): void {
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
}

function notifyUser(PDO $pdo, int $userId, string $title, string $message, string $level = 'info'): void {
    $stmt = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read) VALUES (\'user\', :rid, :title, :message, :level, 0)');
    $stmt->execute([
        ':rid' => $userId,
        ':title' => $title,
        ':message' => $message,
        ':level' => $level,
    ]);
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    $sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
    $isAdminSession = ($sessionRole === 'admin');
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    $fromAdminPage = stripos($referer, '/Html/Admin.html') !== false;
    if (!$isAdminSession && !$fromAdminPage) {
        http_response_code(403);
        throw new RuntimeException('Admin access required');
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $applicationId = (int)($payload['application_id'] ?? 0);
    $action = strtolower(trim((string)($payload['action'] ?? '')));
    $reviewNote = trim((string)($payload['review_note'] ?? ''));

    if ($applicationId <= 0) {
        throw new RuntimeException('Invalid application id');
    }
    if (!in_array($action, ['approve', 'reject'], true)) {
        throw new RuntimeException('Invalid action');
    }

    ensureVolunteerApplicationsTable($pdo);
    ensureNotificationTable($pdo);

    $appStmt = $pdo->prepare('SELECT * FROM volunteer_applications WHERE application_id = :id LIMIT 1');
    $appStmt->execute([':id' => $applicationId]);
    $application = $appStmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        throw new RuntimeException('Application not found');
    }

    $userId = (int)($application['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new RuntimeException('Invalid applicant');
    }

    $adminLabel = trim((string)($_SESSION['email'] ?? 'admin')) ?: 'admin';

    if ($action === 'reject') {
        $note = $reviewNote !== '' ? $reviewNote : 'Your volunteer application needs updates. Please review your profile and apply again.';
        $rejectStmt = $pdo->prepare("UPDATE volunteer_applications
            SET status = 'rejected', reviewed_by = :reviewed_by, review_note = :review_note, reviewed_at = NOW()
            WHERE application_id = :id");
        $rejectStmt->execute([
            ':reviewed_by' => $adminLabel,
            ':review_note' => $note,
            ':id' => $applicationId,
        ]);

        notifyUser(
            $pdo,
            $userId,
            'Volunteer Application Update',
            'Your volunteer application was reviewed: ' . $note,
            'warning'
        );

        echo json_encode([
            'success' => true,
            'status' => 'rejected',
            'message' => 'Application rejected successfully.'
        ]);
        exit;
    }

    $pdo->beginTransaction();

    $userStmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new RuntimeException('Source user not found');
    }

    $fullName = trim((string)($user['full_name'] ?? ''));
    $email = trim((string)($user['email'] ?? ''));
    $mobile = trim((string)($user['mobile'] ?? ''));
    $nidNumber = trim((string)($application['nid_number'] ?? ''));

    if ($fullName === '' || $email === '' || $mobile === '') {
        throw new RuntimeException('User must have full name, email, and mobile for volunteer approval.');
    }

    $findVol = $pdo->prepare('SELECT volunteer_id FROM volunteers WHERE LOWER(email) = LOWER(:email) OR mobile = :mobile LIMIT 1');
    $findVol->execute([
        ':email' => $email,
        ':mobile' => $mobile,
    ]);
    $volunteerId = (int)($findVol->fetchColumn() ?: 0);

    if ($volunteerId <= 0) {
        $insertVol = $pdo->prepare("INSERT INTO volunteers
          (full_name, email, mobile, nid_number, nid_photo, profile_photo, cover_photo, bio, date_of_birth, gender, street, city, postal_code, country, latitude, longitude, password_hash, occupation, availability)
          VALUES
          (:full_name, :email, :mobile, :nid_number, :nid_photo, :profile_photo, :cover_photo, :bio, :date_of_birth, :gender, :street, :city, :postal_code, :country, :latitude, :longitude, :password_hash, :occupation, :availability)");

        $passwordHash = trim((string)($user['password_hash'] ?? ''));
        if ($passwordHash === '') {
            $passwordHash = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
        }

        if ($nidNumber === '') {
            $nidNumber = 'VOL_' . $userId . '_' . date('YmdHis');
        }

        $nidPhoto = 'missing_nid_photo.png';

        $insertVol->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':mobile' => $mobile,
            ':nid_number' => $nidNumber,
            ':nid_photo' => $nidPhoto,
            ':profile_photo' => trim((string)($user['profile_photo'] ?? '')),
            ':cover_photo' => trim((string)($user['cover_photo'] ?? '')),
            ':bio' => trim((string)($user['bio'] ?? '')),
            ':date_of_birth' => ($user['date_of_birth'] ?? null) ?: null,
            ':gender' => ($user['gender'] ?? null) ?: null,
            ':street' => ($user['street'] ?? null) ?: null,
            ':city' => ($user['city'] ?? null) ?: null,
            ':postal_code' => ($user['postal_code'] ?? null) ?: null,
            ':country' => ($user['country'] ?? null) ?: null,
            ':latitude' => ($user['latitude'] ?? null) ?: null,
            ':longitude' => ($user['longitude'] ?? null) ?: null,
            ':password_hash' => $passwordHash,
            ':occupation' => 'Community Volunteer',
            ':availability' => 'Flexible',
        ]);

        $volunteerId = (int)$pdo->lastInsertId();
    }

    $approveStmt = $pdo->prepare("UPDATE volunteer_applications
        SET status = 'approved', volunteer_id = :volunteer_id, reviewed_by = :reviewed_by, review_note = :review_note, reviewed_at = NOW()
        WHERE application_id = :id");
    $approveStmt->execute([
        ':volunteer_id' => $volunteerId,
        ':reviewed_by' => $adminLabel,
        ':review_note' => ($reviewNote !== '' ? $reviewNote : 'Approved by admin.'),
        ':id' => $applicationId,
    ]);

    notifyUser(
        $pdo,
        $userId,
        'Volunteer Application Approved',
        'Congratulations! Your volunteer application is approved. You can now switch to volunteer mode from User Home.',
        'info'
    );

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'status' => 'approved',
        'volunteer_id' => $volunteerId,
        'message' => 'Application approved successfully.'
    ]);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
