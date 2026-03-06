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

    $fullName = trim((string)($payload['full_name'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $mobile = trim((string)($payload['mobile'] ?? ''));
    $nidNumber = trim((string)($payload['nid_number'] ?? ''));
    $dob = trim((string)($payload['date_of_birth'] ?? ''));
    $gender = trim((string)($payload['gender'] ?? ''));
    $badgeId = trim((string)($payload['badge_id'] ?? ''));
    $designation = trim((string)($payload['designation'] ?? ''));
    $station = trim((string)($payload['station'] ?? ''));
    $city = trim((string)($payload['city'] ?? ''));
    $country = trim((string)($payload['country'] ?? ''));
    $password = (string)($payload['password'] ?? '');

    if ($fullName === '' || $email === '' || $mobile === '' || $nidNumber === '' || $dob === '' || $gender === '' || $badgeId === '' || $designation === '' || $station === '' || $password === '') {
        throw new RuntimeException('Missing required fields');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email format');
    }

    if (strlen($password) < 6) {
        throw new RuntimeException('Password must be at least 6 characters');
    }

    // Blocked account check (email/mobile)
    $blkExists = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'signup_blacklist' LIMIT 1");
    $blkExists->execute();
    if ($blkExists->fetchColumn()) {
        $blk = $pdo->prepare("SELECT 1 FROM signup_blacklist WHERE email = :email OR mobile = :mobile LIMIT 1");
        $blk->execute([':email' => $email, ':mobile' => $mobile]);
        if ($blk->fetch()) {
            throw new RuntimeException('This Email/Mobile has been blocked by admin.');
        }
    }

    $exists = $pdo->prepare('SELECT 1 FROM policemen WHERE email = :email OR mobile = :mobile OR nid_number = :nid OR badge_id = :badge LIMIT 1');
    $exists->execute([
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid' => $nidNumber,
        ':badge' => $badgeId,
    ]);
    if ($exists->fetch()) {
        throw new RuntimeException('Email, Mobile, NID or Badge ID already exists');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Required by schema; admin quick-add uses placeholders when files are not uploaded
    $nidPhoto = 'admin_manual_nid_placeholder.png';

    $stmt = $pdo->prepare('INSERT INTO policemen
        (full_name, email, mobile, nid_number, nid_photo, profile_photo, cover_photo, date_of_birth, gender, street, city, postal_code, country, latitude, longitude, password_hash, badge_id, designation, station)
        VALUES
        (:full_name, :email, :mobile, :nid_number, :nid_photo, NULL, NULL, :dob, :gender, NULL, :city, NULL, :country, NULL, NULL, :password_hash, :badge_id, :designation, :station)');

    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid_number' => $nidNumber,
        ':nid_photo' => $nidPhoto,
        ':dob' => $dob,
        ':gender' => $gender,
        ':city' => $city !== '' ? $city : null,
        ':country' => $country !== '' ? $country : null,
        ':password_hash' => $passwordHash,
        ':badge_id' => $badgeId,
        ':designation' => $designation,
        ':station' => $station,
    ]);

    echo json_encode(['success' => true, 'police_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
