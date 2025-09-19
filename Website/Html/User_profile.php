<?php
session_start();
require_once __DIR__ . '/../Php/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT full_name, email, profile_photo, cover_photo, street,email, city, country, bio FROM users WHERE user_id=:id LIMIT 1");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Fallback images
$profile_pic = !empty($user['profile_photo']) ? '../uploads/user/' . $user['profile_photo'] : '../Images/default_profile.png';
$cover_pic = !empty($user['cover_photo']) ? '../uploads/user/' . $user['cover_photo'] : '../Images/default_cover.jpg';
$bio_text = !empty($user['bio']) ? e($user['bio']) : "💬 Bio not added yet. Go to <a href='../Html/User_Edit_profile.html'>edit profile</a> to add your bio!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Main CSS -->
  <link rel="stylesheet" href="../css/User_profile.css">

</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
    <a href="../Html/User_Home.php">
        <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo" />
    </a>
</div>

  </header>
  <!-- Cover & Profile Photo -->
<div class="cover-photo">
    <img src="<?= !empty($user['cover_photo']) ? '../uploads/user/' . e($user['cover_photo']) : '../Images/cover_default.jpg' ?>" 
         alt="Cover" class="cover-img">
    <div class="profile-pic-container">
        <img src="<?= !empty($user['profile_photo']) ? '../uploads/user/' . e($user['profile_photo']) : '../Images/default_profile.png' ?>" 
             class="profile-pic" 
             alt="Profile">
    </div>


  <div class="main-content">
   <div class="left-panel">
  <div class="card user-info" style="position: relative;">
        <button class="edit-btn" title="Edit Profile" onclick="location.href='../Html/User_Edit_profile.php?user_id=<?= $user_id ?>'">
      <img src="../Images/pencil.gif" alt="Edit" />
    </button>
     <h2><?= e($user['full_name'] ?? 'User Name') ?></h2>
            <div class="divider"></div>
            
            <p class="user-bio">
                <?= !empty($user['bio']) ? e($user['bio']) : ' 💬 Add your bio in your profile so everyone knows a little about you' ?>
            </p>

            <ul class="info-list">
<li><span class="icon">&#128187;</span> <?= !empty($user['email']) ? e($user['email']) : 'No email provided' ?></li>
                <li><span class="icon">&#127968;</span> <?= e($user['street'] ?? 'Address') ?></li>
               <li><span class="icon">&#127758;</span> <?= e($user['city'] ?? 'City') ?>, <?= e($user['country'] ?? 'Country') ?></li>

            </ul>

   
  </div>
   <!-- New Password Change Section -->
    <div class="password-change-section">
      <h3>Password Change</h3>
      <p>For your account security, please change your password regularly.</p>
      <button class="change-pass-btn" onclick="location.href='../Html/User_Passchagned.php?user_id=<?= $user_id ?>'">Change Password</button>
    </div>
</div>

<div class="center-panel">
  <div class="card share-box">
    <img class="mini-profile" src="../Images/post.gif" alt="Profile">
    <input type="text" placeholder="What's on your mind?" onclick="openModal()">
  </div>

  <div class="card post">
    <div class="post-header">
        <img src="<?= !empty($user['profile_photo']) ? '../uploads/user/' . e($user['profile_photo']) : '../Images/default_profile.png' ?>" 
             class="mini-profile"
             alt="Profile">
      
      <div>
     <h2><?= e($user['full_name'] ?? 'User Name') ?></h2>
        <div class="post-time">35 min ago</div>
      </div>
    </div>
    <p>Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text.</p>
    <div class="post-image">
      <img src="https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=700&q=80" alt="Post Image">
    </div>
  </div>
</div>

<!-- Popup Modal -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    <span class="post-modal-close" onclick="closeModal()">&times;</span>
    <h2 class="post-modal-title">Share Your Mood</h2>

    <!-- Facebook Toggle -->
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="facebookShareToggle">
        <span class="facebook-toggle-slider">
          <i class="fab fa-facebook"></i>
        </span>
      </label>
      <span class="facebook-toggle-label">Share to Facebook</span>
    </div>

    <!-- Textarea -->
    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>

    <!-- Media Options -->
    <div class="post-media-options">
      <label>
        <input type="file" id="imageUpload" accept="image/*" hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('imageUpload').click()">📷 Photo</button>
      </label>
      <label>
        <input type="file" id="videoUpload" accept="video/*" hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('videoUpload').click()">🎥 Video</button>
      </label>
    </div>

    <!-- Media Preview -->
    <div id="mediaPreview" class="post-media-preview"></div>

    <!-- Actions -->
    <div class="post-modal-actions">
      <button class="post-cancel-btn" onclick="closeModal()">Cancel</button>
      <button class="post-submit-btn" onclick="createPost()">Post</button>
    </div>
  </div>
</div>
    <div class="right-panel">
      <div class="card notifications">
        <h3>Recent Notifications</h3>
        <div class="divider"></div>
        <div class="notification">
          <img class="mini-profile" src="https://randomuser.me/api/portraits/men/22.jpg" alt="User">
          <div>
            <div class="notification-text">Any one can join with us if you want</div>
            <div class="notification-time">5 Min Ago</div>
          </div>
        </div>
        <div class="notification">
          <img class="mini-profile" src="https://randomuser.me/api/portraits/women/34.jpg" alt="User">
          <div>
            <div class="notification-text">Any one can join with us if you want</div>
            <div class="notification-time">10 Min Ago</div>
          </div>
        </div>
        <div class="notification">
          <img class="mini-profile" src="https://randomuser.me/api/portraits/men/23.jpg" alt="User">
          <div>
            <div class="notification-text">Any one can join with us if you want</div>
            <div class="notification-time">18 Min Ago</div>
          </div>
        </div>
        <div class="notification">
          <img class="mini-profile" src="https://randomuser.me/api/portraits/men/25.jpg" alt="User">
          <div>
            <div class="notification-text">Any one can join with us if you want</div>
            <div class="notification-time">20 Min Ago</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
       <script src="../javascrpit/User_profile.js"></script>

</html>