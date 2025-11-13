<?php
// facebook-signin.php
// Accepts POST JSON { access_token: FB_ACCESS_TOKEN, role: 'user' }
// Verifies token with Facebook Graph API, upserts user, sets session.
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

$accessToken = $data['access_token'] ?? null;
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

// Ensure facebook_id column exists (non-blocking)
try {
    ensure_column_exists($pdo, 'facebook_id', "VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // ignore - we'll report errors later if the DB operation fails
}

if (!$accessToken) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing access_token']);
    exit;
}

// Verify token and fetch profile from Graph API
$fields = 'id,name,email,picture.width(400).height(400)';
$url = 'https://graph.facebook.com/me?fields=' . urlencode($fields) . '&access_token=' . urlencode($accessToken);
$resp = @file_get_contents($url);
if ($resp === false) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Failed to verify token with Facebook']);
    exit;
}

$profile = json_decode($resp, true);
if (!isset($profile['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid Facebook token']);
    exit;
}

$fbId = $profile['id'];
$email = $profile['email'] ?? null;
$name = $profile['name'] ?? null;
$picture = null;
if (isset($profile['picture']) && isset($profile['picture']['data']) && isset($profile['picture']['data']['url'])) {
    $picture = $profile['picture']['data']['url'];
}

// Try to find existing user by facebook_id or email
try {
    $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE facebook_id = :fid OR email = :email LIMIT 1');
    $stmt->execute([':fid' => $fbId, ':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update existing (preserve existing role if present)
        $currentRole = $user['role'] ?? $role;
        $update = $pdo->prepare('UPDATE auth_users SET name = :name, email = :email, picture = :picture, facebook_id = :fid, role = :role, updated_at = NOW() WHERE id = :id');
        $update->execute([':name'=>$name, ':email'=>$email, ':picture'=>$picture, ':fid'=>$fbId, ':role'=>$currentRole, ':id'=>$user['id']]);
    } else {
        // Insert new user
        $insert = $pdo->prepare('INSERT INTO auth_users (auth0_id, name, email, picture, role, facebook_id, created_at, updated_at) VALUES (:auth0_id, :name, :email, :picture, :role, :fid, NOW(), NOW())');
        $auth0_id = 'facebook|' . $fbId;
        $insert->execute([':auth0_id'=>$auth0_id, ':name'=>$name, ':email'=>$email, ':picture'=>$picture, ':role'=>$role, ':fid'=>$fbId]);
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE id = :id LIMIT 1');
        $stmt->execute([':id'=>$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Refresh user data from DB
    $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE facebook_id = :fid OR email = :email LIMIT 1');
    $stmt->execute([':fid' => $fbId, ':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'Database error: '.$ex->getMessage()]);
    exit;
}

// Set session
$_SESSION['auth_user'] = $user;

echo json_encode(['success' => true, 'role' => $user['role'] ?? $role]);
exit;
