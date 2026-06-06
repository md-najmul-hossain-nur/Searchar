<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin' || $_SESSION['admin_role'] !== 'main_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($fullName) || empty($email) || empty($mobile) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
}

try {
    if (isDuplicateContact($pdo, $email, $mobile)) {
        echo json_encode(['success' => false, 'error' => 'Email or mobile is already in use']);
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO admins (full_name, email, mobile, password_hash, role) VALUES (?, ?, ?, ?, 'sub_admin')");
    $stmt->execute([$fullName, $email, $mobile, $passwordHash]);

    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_type, details) VALUES (?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], 'Added Sub-Admin', "Added sub-admin: $email"]);

    echo json_encode(['success' => true, 'message' => 'Sub-admin added successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
