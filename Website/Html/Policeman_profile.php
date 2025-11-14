<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../Php/db.php';

if (empty($_SESSION['user_id'])) {
  header('Location: ../Html/login.html');
  exit();
}

$user_id = (int) $_SESSION['user_id'];

try {
  $sql = "SELECT police_id AS id, full_name, email, mobile, profile_photo, cover_photo, bio, date_of_birth, gender, street, city, country, badge_id, designation, station FROM policemen WHERE police_id = :id LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['id' => $user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
  header('Location: ../Html/login.html?error=db');
  exit();
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Policeman Profile - Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/Policeman_profile.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
      <a href="../Html/Policeman_Home.php">
        <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo" />
      </a>
    </div>
  </header>

  <div class="cover-photo">
    <img src="<?= !empty($user['cover_photo']) ? '../uploads/police/' . e($user['cover_photo']) : '../Images/default-cover.gif' ?>" alt="Cover" class="cover-img">
    <div class="profile-pic-container">
      <img class="profile-pic" src="<?= !empty($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" alt="Profile">
    </div>
  <div class="main-content">
    <div class="left-panel">
  <div class="card user-info" style="position: relative;">
        <button class="edit-btn" title="Edit Profile" onclick="location.href='../Html/Policeman_Edit_profile.php?police_id=<?= $user_id ?>'">
      <img src="../Images/pencil.gif" alt="Edit" />
    </button>
     <h2><?= e($user['full_name'] ?? 'Officer Name') ?></h2>
            <div class="divider"></div>
            
            <p class="user-bio">
                <?= !empty($user['bio']) ? e($user['bio']) : ' 💬 Add your bio in your profile so colleagues know a little about you' ?>
            </p>

        <ul class="info-list">
    <!-- Badge ID -->
    <li>
        <span class="icon">🏷️</span>
        <?= !empty($user['badge_id']) ? e($user['badge_id']) : 'Badge not set' ?>
    </li>

    <!-- Designation -->
    <li>
        <span class="icon">💼</span>
        <?= !empty($user['designation']) ? e($user['designation']) : 'Designation not set' ?>
    </li>

    <!-- Station -->
    <li>
        <span class="icon">🏢</span>
        <?= !empty($user['station']) ? e($user['station']) : 'Station not set' ?>
    </li>

    <!-- Email -->
    <li>
        <span class="icon">✉️</span>
        <?= !empty($user['email']) ? e($user['email']) : 'No email provided' ?>
    </li>

    <!-- Street / Address -->
    <li>
        <span class="icon">🏠</span>
        <?= !empty($user['street']) ? e($user['street']) : 'No street provided' ?>
    </li>

    <!-- City / Country -->
    <li>
        <span class="icon">🌍</span>
        <?= !empty($user['city']) ? e($user['city']) : 'No city provided' ?>,
        <?= !empty($user['country']) ? e($user['country']) : 'No country provided' ?>
    </li>
</ul>

  </div>
   <!-- New Password Change Section -->
    <div class="password-change-section">
      <h3>Password Change</h3>
      <p>For your account security, please change your password regularly.</p>
      <button class="change-pass-btn" onclick="location.href='../Html/Policeman_Passchanged.php'">Change Password</button>
    </div>
</div>
    <div class="center-panel">
  <div class="card share-box">
    <img class="mini-profile" src="<?= !empty($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" alt="Profile">
    <input type="text" placeholder="What's on your mind?" onclick="openModal()">
  </div>

  <div class="card post">
    <div class="post-header">
      <img class="mini-profile" src="<?= !empty($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : 'https://randomuser.me/api/portraits/men/20.jpg' ?>" alt="Profile">
      <div>
        <div class="username"><?= e($user['full_name'] ?? '—') ?></div>
        <div class="post-time">35 min ago</div>
      </div>
    </div>
    <p>Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text.</p>
    <div class="post-image">
      <img src="https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=700&q=80" alt="Post Image">
    </div>
  </div>
</div>

<!-- Popup Modal and right panel copied from template -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    <span class="post-modal-close" onclick="closeModal()">&times;</span>
    <h2 class="post-modal-title">Share Your Mood</h2>
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="facebookShareToggle">
        <span class="facebook-toggle-slider"><i class="fab fa-facebook"></i></span>
      </label>
      <span class="facebook-toggle-label">Share to Facebook</span>
    </div>
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="anonToggle">
        <span class="facebook-toggle-slider">
          <i class="fas fa-user-secret"></i>
        </span>
      </label>
      <span class="facebook-toggle-label">Post Anonymously</span>
    </div>
    <p class="category-label">Select Category:</p>
    <div class="category-toggle">
      <label class="category-option">
        <input type="radio" name="category" value="mission" checked>
        <img src="../Images/mission-icon.gif" alt="Mission Icon" class="category-icon" /> Mission Person
      </label>
      <label class="category-option">
        <input type="radio" name="category" value="disaster">
        <img src="../Images/disaster-icon.gif" alt="Disaster Icon" class="category-icon" /> Disaster
      </label>
    </div>
    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>
    <div class="post-modal-preview">
      <p id="sharedPostText" class="preview-text"></p>
      <img id="sharedPostImage" class="preview-img" src="" alt="" />
    </div>
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
    <div id="mediaPreview" class="post-media-preview"></div>
    <div class="post-modal-actions">
      <button class="post-cancel-btn" onclick="closeModal()">Cancel</button>
      <button class="post-submit-btn" onclick="createPost()">Post</button>
    </div>
  </div>
</div>
<style>.category-label {
  text-align: left;
  font-weight: 600;
  font-size: 16px;
  color: #333;
  margin-bottom: 12px;
  font-family: 'Roboto', Arial, sans-serif;
}

.category-toggle {
  display: flex;
  justify-content: center;
  gap: 18px;
  margin-bottom: 18px;
  user-select: none;
  font-family: 'Roboto', Arial, sans-serif;
}

.category-option {
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 6px 18px; /* ↓ reduced height */
  border: 2px solid #1a73e8;
  border-radius: 16px; /* slightly smaller for balance */
  font-weight: 450;
  color: #f75c3c;
  transition: all 0.3s ease;
  user-select: none;
  min-width: 90px;
  justify-content: center;
  gap: 6px;
  background-color: white;
}

.category-icon {
  width: 24px; /* ↓ smaller icons */
  height: 24px;
  object-fit: contain;
}

/* Highlight selected label */
.category-option:has(input[type="radio"]:checked) {
  background-color: #cdb468;
  color: white;
  box-shadow: 0 0 6px rgba(26, 115, 232, 0.5);
  border-color: #f75c3c;
}

/* Hover effect */
.category-option:hover {
  background-color: rgba(26, 115, 232, 0.1);
  border-color: #1a73e8;
  color: #1a73e8;
}
</style>
    <div class="right-panel">
      <div class="card notifications">
        <h3>Recent Notifications</h3>
        <div class="divider"></div>
        <div class="notification">
          <img class="mini-profile" src="<?= !empty($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : 'https://randomuser.me/api/portraits/men/22.jpg' ?>" alt="User">
          <div>
            <div class="notification-text">Any one can join with us if you want</div>
            <div class="notification-time">5 Min Ago</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
<script src="../javascrpit/Policeman_profile.js"></script>
</html>
