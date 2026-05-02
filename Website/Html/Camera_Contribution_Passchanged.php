<?php
session_start();
require_once __DIR__ . '/../Php/db.php'; // sets $pdo

// Ensure user is logged in
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'contributor' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$js_alert = ''; // Hold JS for alert + redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM camera_contributors WHERE camera_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $js_alert = "alert('User not found!');";
    } elseif (!password_verify($current_password, $user['password_hash'])) {
        $js_alert = "alert('Current password is incorrect!');";
    } elseif ($new_password !== $confirm_password) {
        $js_alert = "alert('New password and confirm password do not match!');";
    } elseif (strlen($new_password) < 6) {
        $js_alert = "alert('New password must be at least 6 characters!');";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE camera_contributors SET password_hash = ? WHERE camera_id = ?");
        $update->execute([$hashed_password, $user_id]);

        $js_alert = "alert('Password updated successfully!'); window.location.href = '../Html/Camera_Contribution_profile.php';";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - Camera Contributor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../css/Camera_Contribution_Passchanged.css?v=20260405bg">
  <link rel="stylesheet" href="../css/button_theme_shared.css?v=20260503a">
</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>
  <main class="edit-passchanged-container">
    <div class="password-form-box">
      <div class="password-change-box">
        <img src="../Images/password.gif" alt="Change Password" class="password-change-img">
        <h2>Change Your Password</h2>

        <div class="back-button-container">
          <a href="../Html/Camera_Contribution_profile.php" class="back-btn">Ã¢â€ Â Back</a>
        </div>

        <form action="" method="POST">
          <div class="form-group">
            <label for="current-password">Current Password:</label>
            <input type="password" id="current-password" name="current_password" required>
          </div>
          
          <div class="form-group">
            <label for="new-password">New Password:</label>
            <input type="password" id="new-password" name="new_password" required>
          </div>
          
          <div class="form-group">
            <label for="confirm-password">Confirm New Password:</label>
            <input type="password" id="confirm-password" name="confirm_password" required>
          </div>
          
          <button type="submit" class="btn">Update Password</button>
        </form>
      </div>
    </div>
  </main>

  <script src="../javascrpit/Camera_Contribution_Passchanged.js?v=20260405bg"></script>
  <?php if (!empty($js_alert)): ?>
  <script>
    <?= $js_alert ?>
  </script>
  <?php endif; ?>
</body>
</html>

