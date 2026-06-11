<?php
session_start();
require_once "../Php/db.php";

// Get form data
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
if (empty($login_input) || empty($password)) {
    header('Location: ../Html/login.html?error=empty');
    exit();
}

// Hardcoded admin checks removed; handled by roleTableMap below.

$roleTableMap = [
    'admin'       => ['table' => 'admins', 'id_col' => 'admin_id', 'home' => '../Html/Admin.html'],
    'user'        => ['table' => 'users', 'id_col' => 'user_id', 'home' => '../Html/User_Home.php'],
    'police'      => ['table' => 'policemen', 'id_col' => 'police_id', 'home' => '../Html/Policeman_Home.php'],
    'volunteer'   => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'home' => '../Html/Volunteer_Home.php'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'home' => '../Html/Camera_Contribution_Home.php']
];

try {
    $phoneCandidates = buildPhoneCandidates($login_input);
    $foundUser = null;
    $foundRole = null;

    foreach ($roleTableMap as $roleKey => $info) {
        $table = $info['table'];
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

        if ($user) {
            $foundUser = $user;
            $foundRole = $roleKey;
            break;
        }
    }

    if (!$foundUser) {
        // No account found in any table
        header('Location: ../Html/login.html?error=no_account');
        exit();
    }

    // Verify password
    $storedHash = $foundUser['password_hash'] ?? '';
    if (!is_string($storedHash) || $storedHash === '') {
        header('Location: ../Html/login.html?error=wrong_password');
        exit();
    }

    if (!password_verify($password, $storedHash)) {
        header('Location: ../Html/login.html?error=wrong_password');
        exit();
    }

    // Successful login
    $_SESSION['user_id'] = $foundUser[$roleTableMap[$foundRole]['id_col']];
    $_SESSION['role'] = $foundRole;
    
    if (!isset($_SESSION['active_roles']) || !is_array($_SESSION['active_roles'])) {
        $_SESSION['active_roles'] = [];
    }
    $_SESSION['active_roles'][$foundRole] = $_SESSION['user_id'];

    if ($foundRole === 'admin') {
        $_SESSION['admin_role'] = 'admin';
    }
    $home_page = $roleTableMap[$foundRole]['home'];
    header("Location: $home_page?login=success");
    exit();

} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: ../Html/login.html?error=db');
    exit();
}
?>
