<?php
session_start();
require_once __DIR__ . '/../Php/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT full_name, email, profile_photo, cover_photo,date_of_birth, gender,street,email, city, country, bio FROM users WHERE user_id=:id LIMIT 1");
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
    <!-- Birthday -->
    <li>
        <span class="icon">&#127874;</span> <!-- 🎂 cake icon -->
        <?= !empty($user['date_of_birth']) ? e($user['date_of_birth']) : 'No birthday provided' ?>
    </li>

    <!-- Gender -->
    <li>
        <span class="icon">&#9794;&#9792;</span> <!-- ⚥ gender icon -->
        <?= !empty($user['gender']) ? ucfirst(e($user['gender'])) : 'Gender not specified' ?>
    </li>

    <!-- Email -->
    <li>
        <span class="icon">&#9993;</span> <!-- ✉️ envelope icon -->
        <?= !empty($user['email']) ? e($user['email']) : 'No email provided' ?>
    </li>

    <!-- Street / Address -->
    <li>
        <span class="icon">&#127968;</span> <!-- 🏠 house icon -->
        <?= !empty($user['street']) ? e($user['street']) : 'No street provided' ?>
    </li>

    <!-- City / Country -->
    <li>
        <span class="icon">&#127758;</span> <!-- 🌎 globe icon -->
        <?= !empty($user['city']) ? e($user['city']) : 'No city provided' ?>, 
        <?= !empty($user['country']) ? e($user['country']) : 'No country provided' ?>
    </li>
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
      </div><div class="notifications">
  <div class="redzone">
  <h4>Red Zone Alerts</h4>
  <ul>
    <li><span>Badda: Fire risk</span><span>Today</span></li>
    <li><span>Kuril: Accident zone</span><span>1 hr ago</span></li>
    <li><span>Gulshan-2: Snatching alert</span><span>Yesterday</span></li>
    <li><span>Rampura: Traffic heavy</span><span>30 min ago</span></li>
  </ul>

  <button class="redzone-btn"
    onclick="window.location.href='../Html/RedZone.html';">
    Open Red Zone Map
  </button>
</div>
</div>
<style>.redzone {
  border: 1px solid #ffd4d4;
  background: linear-gradient(135deg, #fff7f7, #ffecec);
  padding: 14px;
  border-radius: 12px;
  margin-top: 12px;
}

.redzone h4 {
  margin-bottom: 10px;
  color: #c0392b;
  font-weight: 700;
}

.redzone ul {
  list-style: none;
  padding: 0;
  margin: 0 0 10px 0;
}

.redzone ul li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  border-left: 4px solid #e74c3c;
  padding: 8px 10px;
  border-radius: 8px;
  margin-bottom: 6px;
  font-size: 14px;
}

.redzone ul li span:last-child {
  font-size: 12px;
  color: #888;
}

.redzone-btn {
  width: 100%;
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  transition: 0.3s;
}

.redzone-btn:hover {
  background: #c0392b;
}
</style>
      
    </div>
  </div>
</body>
       <script src="../javascrpit/User_profile.js"></script>

</html>