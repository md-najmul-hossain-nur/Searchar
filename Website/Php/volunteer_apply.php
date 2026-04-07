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

function buildVolunteerMissingProfileParts(array $user): array {
    $missing = [];

    if (trim((string)($user['full_name'] ?? '')) === '') $missing[] = 'full name';
    if (trim((string)($user['email'] ?? '')) === '') $missing[] = 'email';
    if (trim((string)($user['mobile'] ?? '')) === '') $missing[] = 'mobile';
    if (trim((string)($user['nid_number'] ?? '')) === '') $missing[] = 'NID number';
    if (trim((string)($user['date_of_birth'] ?? '')) === '') $missing[] = 'date of birth';
    if (trim((string)($user['gender'] ?? '')) === '') $missing[] = 'gender';

    $street = trim((string)($user['street'] ?? ''));
    $lat = trim((string)($user['latitude'] ?? ''));
    $lng = trim((string)($user['longitude'] ?? ''));
    if ($street === '' && ($lat === '' || $lng === '')) {
        $missing[] = 'street address or map location';
    }

    if (trim((string)($user['city'] ?? '')) === '') $missing[] = 'city';
    if (trim((string)($user['country'] ?? '')) === '') $missing[] = 'country';

    return $missing;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
        http_response_code(401);
        throw new RuntimeException('Please login as user first');
    }

    $userId = (int)$_SESSION['user_id'];
    if ($userId <= 0) {
        throw new RuntimeException('Invalid user session');
    }

    ensureVolunteerApplicationsTable($pdo);

    $userStmt = $pdo->prepare('SELECT user_id, full_name, email, mobile, nid_number, city, country, date_of_birth, gender, street, latitude, longitude FROM users WHERE user_id = :id LIMIT 1');
    $userStmt->execute([':id' => $userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        throw new RuntimeException('User account not found');
    }

    $fullName = trim((string)($user['full_name'] ?? ''));
    $email = trim((string)($user['email'] ?? ''));
    $mobile = trim((string)($user['mobile'] ?? ''));
    $nidNumber = trim((string)($user['nid_number'] ?? ''));
    $city = trim((string)($user['city'] ?? ''));
    $country = trim((string)($user['country'] ?? ''));

    $missingParts = buildVolunteerMissingProfileParts($user);
    if (count($missingParts) > 0) {
        throw new RuntimeException('Please complete your profile first before volunteer apply. Missing: ' . implode(', ', $missingParts) . '.');
    }

    $input = json_decode(file_get_contents('php://input') ?: '', true);
    $note = '';
    if (is_array($input)) {
        $note = trim((string)($input['note'] ?? ''));
    }

    $volStmt = $pdo->prepare('SELECT volunteer_id FROM volunteers WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1');
    $volStmt->execute([
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid' => $nidNumber,
    ]);
    $volunteerId = (int)($volStmt->fetchColumn() ?: 0);

    if ($volunteerId > 0) {
        $approvedUpsert = $pdo->prepare("INSERT INTO volunteer_applications
            (user_id, full_name, email, mobile, nid_number, city, country, note, status, volunteer_id, reviewed_by, review_note, reviewed_at)
            VALUES (:user_id, :full_name, :email, :mobile, :nid_number, :city, :country, :note, 'approved', :volunteer_id, 'system', 'Already verified volunteer account found.', NOW())
            ON DUPLICATE KEY UPDATE
              full_name = VALUES(full_name),
              email = VALUES(email),
              mobile = VALUES(mobile),
              nid_number = VALUES(nid_number),
              city = VALUES(city),
              country = VALUES(country),
              note = VALUES(note),
              status = 'approved',
              volunteer_id = VALUES(volunteer_id),
              reviewed_by = 'system',
              review_note = 'Already verified volunteer account found.',
              reviewed_at = NOW()");
        $approvedUpsert->execute([
            ':user_id' => $userId,
            ':full_name' => $fullName,
            ':email' => $email,
            ':mobile' => $mobile,
            ':nid_number' => $nidNumber,
            ':city' => $city,
            ':country' => $country,
            ':note' => $note,
            ':volunteer_id' => $volunteerId,
        ]);

        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'volunteer_ready' => true,
            'message' => 'You are already approved as volunteer. You can switch to volunteer mode now.'
        ]);
        exit;
    }

    $upsert = $pdo->prepare("INSERT INTO volunteer_applications
      (user_id, full_name, email, mobile, nid_number, city, country, note, status, review_note, reviewed_by, reviewed_at)
      VALUES (:user_id, :full_name, :email, :mobile, :nid_number, :city, :country, :note, 'pending', NULL, NULL, NULL)
      ON DUPLICATE KEY UPDATE
        full_name = VALUES(full_name),
        email = VALUES(email),
        mobile = VALUES(mobile),
        nid_number = VALUES(nid_number),
        city = VALUES(city),
        country = VALUES(country),
        note = VALUES(note),
        status = IF(status = 'approved', 'approved', 'pending'),
        review_note = NULL,
        reviewed_by = NULL,
        reviewed_at = NULL");

    $upsert->execute([
        ':user_id' => $userId,
        ':full_name' => $fullName,
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid_number' => $nidNumber,
        ':city' => $city,
        ':country' => $country,
        ':note' => $note,
    ]);

    $statusStmt = $pdo->prepare('SELECT status FROM volunteer_applications WHERE user_id = :id LIMIT 1');
    $statusStmt->execute([':id' => $userId]);
    $status = strtolower(trim((string)($statusStmt->fetchColumn() ?: 'pending')));

    if ($status === 'approved') {
        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'volunteer_ready' => true,
            'message' => 'Your volunteer account is already approved.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'status' => 'pending',
        'volunteer_ready' => false,
        'message' => 'Volunteer application submitted. Admin approval is pending.'
    ]);
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
