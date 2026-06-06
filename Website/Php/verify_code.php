<?php
require_once "db.php";
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$code = trim($_POST['code'] ?? '');

if (empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Email and code are required']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND code = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email, $code]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
        exit();
    }

    if (strtotime($reset['expires_at']) < time()) {
        echo json_encode(['success' => false, 'error' => 'Verification code has expired']);
        exit();
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
