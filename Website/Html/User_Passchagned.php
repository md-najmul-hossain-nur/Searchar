<?php
session_start();
require_once __DIR__ . '/../Php/db.php'; // Make sure this sets $pdo

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];
$js_alert = ''; // Will hold JS code for alert + redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $js_alert = "alert('User not found!');";
    } elseif (!password_verify($current_password, $user['password_hash'])) {
        $js_alert = "alert('Current password is incorrect!');";
    } elseif ($new_password !== $confirm_password) {
        $js_alert = "alert('New password and confirm password do not match!');";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update->execute([$hashed_password, $user_id]);

        // Success alert + redirect
        $js_alert = "alert('Password updated successfully!'); window.location.href = '../Html/User_profile.php';";
    }
}
?>

<style>
  .error-msg {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 5px solid #f5c6cb;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
  }

  .success-msg {
    background-color: #d4edda;
    color: #155724;
    border-left: 5px solid #c3e6cb;
    padding: 10px;
    margin: 10px 0;
    border-radius: 5px;
  }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password - Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../css/User_Passchagned.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>
  <div class="bubble-background"></div>

  <main class="edit-passchanged-container">
    <div class="password-form-box">
      <div class="password-change-box">
        <img src="../Images/password.gif" alt="Change Password" class="password-change-img">
        <h2>Change Your Password</h2>

        <div class="back-button-container">
          <a href="../Html/User_profile.php" class="back-btn">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
          <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
          <div class="success-msg"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

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

  <script src="../javascript/User_Passchagned.js"></script>
  <?php if (!empty($js_alert)): ?>
<script>
    <?= $js_alert ?>
</script>
<?php endif; ?>

</body>
</html>
