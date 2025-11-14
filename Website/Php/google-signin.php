<?php
// google-signin.php
// Accepts POST JSON { credential: ID_TOKEN, role: 'user' }
// Verifies Google ID token via tokeninfo endpoint, upserts user, sets session.
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php'; // provides $pdo
session_start();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$idToken = $data['credential'] ?? null;
$role = $data['role'] ?? 'user';

// Normalize role values coming from the client to match DB enum values
$roleMap = [
    'user' => 'user',
    'police' => 'policeman',
    'policeman' => 'policeman',
    'volunteer' => 'volunteer',
    'contributor' => 'camera_contributor',
    'camera_contributor' => 'camera_contributor'
];
$role = $roleMap[$role] ?? 'user';

// Helper: ensure column exists (adds column if missing)
function ensure_column_exists(PDO $pdo, $columnName, $definition) {
    $dbName = $pdo->query('select database()')->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :col');
    $stmt->execute([':db'=>$dbName, ':table'=>'auth_users', ':col'=>$columnName]);
    $exists = (bool)$stmt->fetchColumn();
    if (!$exists) {
        // Try to add the column
        $pdo->exec("ALTER TABLE auth_users ADD COLUMN $columnName $definition");
    }
}

// Ensure provider_id and basic user columns exist (matching your schema)
try {
    ensure_column_exists($pdo, 'provider_id', "VARCHAR(255) DEFAULT NULL");
    ensure_column_exists($pdo, 'full_name', "VARCHAR(100) DEFAULT NULL");
    ensure_column_exists($pdo, 'email', "VARCHAR(255) DEFAULT NULL");
    ensure_column_exists($pdo, 'provider', "ENUM('local','google','facebook') DEFAULT 'local'");
    ensure_column_exists($pdo, 'role', "ENUM('user','policeman','volunteer','camera_contributor') DEFAULT 'user'");
    ensure_column_exists($pdo, 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
    // ignore; if ALTER TABLE is not permitted we'll return a DB error later when inserting
}

if (!$idToken) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing id_token']);
    exit;
}

// Verify token with Google tokeninfo endpoint (sufficient for dev/test)
$tokenInfo = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
if ($tokenInfo === false) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Failed to verify token']);
    exit;
}
$tokenInfo = json_decode($tokenInfo, true);
if (!isset($tokenInfo['sub'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid ID token']);
    exit;
}

$googleId = $tokenInfo['sub'];
$email = $tokenInfo['email'] ?? null;
$fullName = $tokenInfo['name'] ?? null;

// Try to find existing user by provider_id (google) or email
try {
    $stmt = $pdo->prepare("SELECT * FROM auth_users WHERE (provider = 'google' AND provider_id = :pid) OR email = :email LIMIT 1");
    $stmt->execute([':pid' => $googleId, ':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update existing (preserve role if already set)
        $currentRole = $user['role'] ?? $role;
        $authId = $user['auth_id'] ?? $user['id'] ?? null;
        $update = $pdo->prepare('UPDATE auth_users SET full_name = :full_name, email = :email, provider = :provider, provider_id = :pid, role = :role WHERE auth_id = :auth_id');
        $update->execute([':full_name'=>$fullName, ':email'=>$email, ':provider'=>'google', ':pid'=>$googleId, ':role'=>$currentRole, ':auth_id'=>$authId]);
    } else {
        // Insert new user using your schema
        $insert = $pdo->prepare('INSERT INTO auth_users (email, full_name, provider, role, provider_id, created_at) VALUES (:email, :full_name, :provider, :role, :pid, NOW())');
        $insert->execute([':email'=>$email, ':full_name'=>$fullName, ':provider'=>'google', ':role'=>$role, ':pid'=>$googleId]);
        $userId = $pdo->lastInsertId();
        // Depending on schema the PK column may be auth_id
        $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE auth_id = :id LIMIT 1');
        $stmt->execute([':id'=>$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'Database error: '.$ex->getMessage()]);
    exit;
}

// Refresh user data from DB (in case of update)
$stmt = $pdo->prepare("SELECT * FROM auth_users WHERE (provider = 'google' AND provider_id = :pid) OR email = :email LIMIT 1");
$stmt->execute([':pid' => $googleId, ':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set session
$_SESSION['auth_user'] = $user;

// Determine role and decide where to send the user
$effectiveRole = $user['role'] ?? $role;
$emailToCheck = $user['email'] ?? $email;

$map = [
    'user' => ['table'=>'users','home'=>'../Html/User_Home.php','edit'=>'../Html/User_Edit_profile.php'],
    'policeman' => ['table'=>'policemen','home'=>'../Html/Policeman_Home.php','edit'=>'../Html/Policeman_Edit_profile.php'],
    'volunteer' => ['table'=>'volunteers','home'=>'../Html/Volunteer_Home.php','edit'=>'../Html/Volunteer_Edit_profile.php'],
    'camera_contributor' => ['table'=>'camera_contributors','home'=>'../Html/Camera_Contribution_Home.php','edit'=>'../Html/Camera_Contribution_Edit_profile.php']
];

$response = ['success'=>true, 'role'=>$effectiveRole];

if (isset($map[$effectiveRole])) {
    $t = $map[$effectiveRole]['table'];
    $home = $map[$effectiveRole]['home'];
    $edit = $map[$effectiveRole]['edit'];

    try {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE email = :email LIMIT 1");
        $chk->execute([':email'=>$emailToCheck]);
        $exists = (bool)$chk->fetchColumn();
        if ($exists) {
            $response['action'] = 'home';
            $response['redirect'] = $home;
        } else {
            $response['action'] = 'edit';
            $response['redirect'] = $edit;
        }
    } catch (Exception $ex) {
        // If role-specific table doesn't exist or another error, fall back to home
        $response['action'] = 'home';
        $response['redirect'] = $map['user']['home'];
    }
} else {
    // Unknown role - default to generic home
    $response['action'] = 'home';
    $response['redirect'] = '../Html/Index.html';
}

echo json_encode($response);
exit;
