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

// Ensure google_id column exists (non-blocking)
try {
    ensure_column_exists($pdo, 'google_id', "VARCHAR(255) DEFAULT NULL");
} catch (Exception $e) {
    // ignore - we'll report errors later if the DB operation fails
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
$name = $tokenInfo['name'] ?? null;
$picture = $tokenInfo['picture'] ?? null;

// Try to find existing user by google_id or email
try {
    $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE google_id = :gid OR email = :email LIMIT 1');
    $stmt->execute([':gid' => $googleId, ':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Update existing (preserve role if already set)
        $currentRole = $user['role'] ?? $role;
        $update = $pdo->prepare('UPDATE auth_users SET name = :name, email = :email, picture = :picture, google_id = :gid, role = :role, updated_at = NOW() WHERE id = :id');
        $update->execute([':name'=>$name, ':email'=>$email, ':picture'=>$picture, ':gid'=>$googleId, ':role'=>$currentRole, ':id'=>$user['id']]);
    } else {
        // Insert new user
        $insert = $pdo->prepare('INSERT INTO auth_users (auth0_id, name, email, picture, role, google_id, created_at, updated_at) VALUES (:auth0_id, :name, :email, :picture, :role, :gid, NOW(), NOW())');
        // auth0_id not applicable here; use provider namespaced id
        $auth0_id = 'google|' . $googleId;
        $insert->execute([':auth0_id'=>$auth0_id, ':name'=>$name, ':email'=>$email, ':picture'=>$picture, ':role'=>$role, ':gid'=>$googleId]);
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT * FROM auth_users WHERE id = :id LIMIT 1');
        $stmt->execute([':id'=>$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'Database error: '.$ex->getMessage()]);
    exit;
}

// Refresh user data from DB (in case of update)
$stmt = $pdo->prepare('SELECT * FROM auth_users WHERE google_id = :gid OR email = :email LIMIT 1');
$stmt->execute([':gid' => $googleId, ':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set session
$_SESSION['auth_user'] = $user;

echo json_encode(['success' => true, 'role' => $user['role'] ?? $role]);
exit;
