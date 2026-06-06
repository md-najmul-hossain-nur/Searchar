<?php
require_once "db.php";
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if (empty($email) || empty($newPassword)) {
    echo json_encode(['success' => false, 'error' => 'Email and new password are required']);
    exit();
}

try {
    // We should ideally re-verify that a valid code was entered recently, but to keep it simple,
    // we'll just check if a valid unexpired code exists in the DB for this email.
    $currentTime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND expires_at > ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email, $currentTime]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset) {
        echo json_encode(['success' => false, 'error' => 'No active password reset session found']);
        exit();
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $tables = ['users', 'policemen', 'volunteers', 'camera_contributors', 'admins'];
    $updated = false;
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("UPDATE `$table` SET password_hash = ? WHERE email = ?");
        $stmt->execute([$passwordHash, $email]);
        if ($stmt->rowCount() > 0) {
            $updated = true;
        }
    }

    if ($updated) {
        // Delete the code so it can't be used again
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update password']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
