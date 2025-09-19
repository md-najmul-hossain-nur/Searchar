<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Main CSS -->
  <link rel="stylesheet" href="../css/Volunteer_Passchanged.css">
</head>
<body>
  <!-- Navbar -->
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
          <!-- Back Button -->
      <div class="back-button-container">
        <a href="../Html/Volunteer_profile.html" class="back-btn">← Back</a>
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
</main>


           <script src="../javascrpit/Volunteer_Passchanged.js"></script>
