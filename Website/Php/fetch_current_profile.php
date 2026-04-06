<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/db.php';

function canonicalRole(string $role): string {
    $r = strtolower(trim($role));
    return match ($r) {
        'user', 'users' => 'user',
        'police', 'policeman', 'policemen' => 'police',
        'volunteer', 'volunteers' => 'volunteer',
        'contributor', 'camera_contributor', 'camera_contributors', 'camera', 'cameraman', 'camera_man' => 'contributor',
        default => 'user',
    };
}

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = canonicalRole((string)$_SESSION['role']);
$userId = (int)$_SESSION['user_id'];

$roleMap = [
    'user' => [
        'table' => 'users',
        'id_col' => 'user_id',
        'upload_folder' => 'user',
        'profile_page' => '../Html/User_profile.php',
    ],
    'police' => [
        'table' => 'policemen',
        'id_col' => 'police_id',
        'upload_folder' => 'police',
        'profile_page' => '../Html/Policeman_profile.php',
    ],
    'volunteer' => [
        'table' => 'volunteers',
        'id_col' => 'volunteer_id',
        'upload_folder' => 'volunteer',
        'profile_page' => '../Html/Volunteer_profile.php',
    ],
    'contributor' => [
        'table' => 'camera_contributors',
        'id_col' => 'camera_id',
        'upload_folder' => 'camera',
        'profile_page' => '../Html/Camera_Contribution_profile.php',
    ],
];

$cfg = $roleMap[$role] ?? $roleMap['user'];

try {
    $sql = "SELECT full_name, email, bio, profile_photo, cover_photo FROM {$cfg['table']} WHERE {$cfg['id_col']} = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Profile not found']);
        exit;
    }

    $photoRaw = trim((string)($row['profile_photo'] ?? ''));
    $coverRaw = trim((string)($row['cover_photo'] ?? ''));

    $profilePhoto = $photoRaw !== ''
        ? ('../uploads/' . $cfg['upload_folder'] . '/' . $photoRaw)
        : '../Images/default-profile.gif';

    $coverPhoto = $coverRaw !== ''
        ? ('../uploads/' . $cfg['upload_folder'] . '/' . $coverRaw)
        : '../Images/cover_default.jpg';

    echo json_encode([
        'success' => true,
        'data' => [
            'role' => $role,
            'full_name' => (string)($row['full_name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'bio' => (string)($row['bio'] ?? ''),
            'profile_photo' => $profilePhoto,
            'cover_photo' => $coverPhoto,
            'profile_page' => (string)$cfg['profile_page'],
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not load profile']);
}
