<?php
session_start();
require_once "../Php/db.php";

// Get form data
$role = $_POST['role'] ?? '';
$login_input = $_POST['emailOrPhone'] ?? '';
$password = $_POST['password'] ?? '';

// Check for empty fields
if (empty($role) || empty($login_input) || empty($password)) {
    header('Location: ../Html/login.html?error=empty');
    exit();
}

// Map roles to table info
$roleTableMap = [
    'user'        => ['table' => 'users', 'id_col' => 'user_id', 'home' => '../Html/User_Home.php'],
    'police'      => ['table' => 'policemen', 'id_col' => 'police_id', 'home' => '../Html/Policeman_Home.html'],
    'volunteer'   => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'home' => '../Html/Volunteer_Home.html'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'home' => '../Html/Camera_Contribution_Home.html']
];

// Validate role
if (!isset($roleTableMap[$role])) {
    header('Location: ../Html/login.html?error=role');
    exit();
}

$table = $roleTableMap[$role]['table'];
$id_col = $roleTableMap[$role]['id_col'];
$home_page = $roleTableMap[$role]['home'];

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE email = :login OR mobile = :login");
    $stmt->execute(['login' => $login_input]);
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

} catch (PDOException $e) {
    header('Location: ../Html/login.html?error=db');
    exit();
}
?>
