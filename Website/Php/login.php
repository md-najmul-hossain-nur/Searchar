<?php
session_start();
require_once "../Php/db.php";

// Get form data
$role = trim((string)($_POST['role'] ?? ''));
$login_input = trim((string)($_POST['emailOrPhone'] ?? ''));
$password = (string)($_POST['password'] ?? '');

function buildPhoneCandidates(string $value): array {
    $candidates = [];
    $clean = trim($value);
    if ($clean !== '') {
        $candidates[] = $clean;
    }

    $digits = preg_replace('/\D+/', '', $clean);
    if ($digits !== '') {
        $candidates[] = $digits;

        if (substr($digits, 0, 3) === '880' && strlen($digits) === 13) {
            $candidates[] = '0' . substr($digits, 3);
        }

        if (substr($digits, 0, 2) === '01' && strlen($digits) === 11) {
            $candidates[] = '880' . substr($digits, 1);
            $candidates[] = '+880' . substr($digits, 1);
        }
    }

    return array_values(array_unique(array_filter($candidates, static fn($x) => $x !== '')));
}

// Check for empty fields
if (empty($role) || empty($login_input) || empty($password)) {
    header('Location: ../Html/login.html?error=empty');
    exit();
}

// Map roles to table info
$adminEmail = 'mnajmulhossainnur@gmail.com';
$adminPhone = '01743094595';
$adminPassword = '12345678';

$roleTableMap = [
    'admin'       => ['table' => null, 'id_col' => null, 'home' => '../Html/Admin.html'],
    'user'        => ['table' => 'users', 'id_col' => 'user_id', 'home' => '../Html/User_Home.php'],
    'police'      => ['table' => 'policemen', 'id_col' => 'police_id', 'home' => '../Html/Policeman_Home.php'],
    'volunteer'   => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'home' => '../Html/Volunteer_Home.php'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'home' => '../Html/Camera_Contribution_Home.php']
];

// Validate role
if (!isset($roleTableMap[$role])) {
    header('Location: ../Html/login.html?error=role');
    exit();
}

$table = $roleTableMap[$role]['table'];
$id_col = $roleTableMap[$role]['id_col'];
$home_page = $roleTableMap[$role]['home'];

// Handle admin without DB lookup
if ($role === 'admin') {
    $adminPhoneCandidates = buildPhoneCandidates($login_input);
    $loginOk = (
        strcasecmp($login_input, $adminEmail) === 0 ||
        in_array($adminPhone, $adminPhoneCandidates, true)
    ) && $password === $adminPassword;

    if ($loginOk) {
        $_SESSION['user_id'] = 0;
        $_SESSION['role'] = 'admin';
        header("Location: $home_page?login=success");
        exit();
    }

    header('Location: ../Html/login.html?error=wrong_password');
    exit();
}

try {
    // Simple login flow: role অনুযায়ী table থেকে email/phone match
    $phoneCandidates = buildPhoneCandidates($login_input);
    $sql = "SELECT * FROM `{$table}` WHERE LOWER(email) = LOWER(?)";
    $params = [$login_input];

    if (!empty($phoneCandidates)) {
        $placeholders = implode(',', array_fill(0, count($phoneCandidates), '?'));
        $sql .= " OR mobile IN ({$placeholders})";
        $params = array_merge($params, $phoneCandidates);
    }

    $sql .= ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // No account found
        header('Location: ../Html/login.html?error=no_account');
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        header('Location: ../Html/login.html?error=wrong_password');
        exit();
    }

    // Successful login
    $_SESSION['user_id'] = $user[$id_col];
    $_SESSION['role'] = $role;
    header("Location: $home_page?login=success");
    exit();

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: ../Html/login.html?error=db');
    exit();
}
?>
