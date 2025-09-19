<?php
session_start();
require_once __DIR__ . '/../Php/db.php';

// 1. Authentication check
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'volunteer' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

// 2. Get volunteer_id from GET or session
$volunteer_id = isset($_GET['volunteer_id']) ? (int)$_GET['volunteer_id'] : (int)$_SESSION['user_id'];

// 3. Security: Optionally restrict access to your own profile only
if ($volunteer_id !== (int)$_SESSION['user_id']) {
    header('Location: ../Html/login.html?error=unauthorized');
    exit();
}

// 4. Fetch volunteer data
$stmt = $pdo->prepare("SELECT full_name, email, profile_photo, cover_photo, date_of_birth, gender,occupation,availability, street, city, country, bio 
                       FROM volunteers WHERE volunteer_id=:id LIMIT 1");
$stmt->execute(['id' => $volunteer_id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer) {
    session_unset();
    session_destroy();
    header('Location: ../Html/login.html?error=no_user');
    exit();
}

// 5. Safe output helper
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// 6. Fallback images and bio
$profile_pic = !empty($volunteer['profile_photo']) ? '../uploads/volunteer/' . e($volunteer['profile_photo']) : '../Images/default_profile.png';
$cover_pic   = !empty($volunteer['cover_photo'])   ? '../uploads/volunteer/' . e($volunteer['cover_photo'])   : '../Images/cover_default.jpg';
$bio_text    = !empty($volunteer['bio']) ? e($volunteer['bio']) : "💬 Bio not added yet. Go to <a href='../Html/Volunteer_Edit_profile.html'>edit profile</a> to add your bio!";
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
  <link rel="stylesheet" href="../css/Volunteer_profile.css">

</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
    <a href="../Html/Volunteer_Home.php">
        <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo" />
    </a>
</div>
  </header>
  <div class="cover-photo">
    <!-- Cover Photo -->
    <img src="<?= !empty($volunteer['cover_photo']) ? '../uploads/volunteer/' . e($volunteer['cover_photo']) : '../Images/cover_default.jpg' ?>" 
         alt="Cover" class="cover-img">
    <div class="profile-pic-container">
 <!-- Profile Photo -->
    <img src="<?= !empty($volunteer['profile_photo']) ? '../uploads/volunteer/' . e($volunteer['profile_photo']) : '../Images/default_profile.png' ?>" 
         class="profile-pic" 
         alt="Profile">    </div>
  <div class="main-content">
    <div class="left-panel">
  <div class="card user-info" style="position: relative;">
        <!-- Edit button as image icon -->
<button class="edit-btn" title="Edit Profile" 
        onclick="location.href='../Html/Volunteer_Edit_profile.php?user_id=<?php echo $volunteer_id; ?>'">
    <img src="../Images/pencil.gif" alt="Edit" />
</button>
          <h2>
                <?= e($volunteer['full_name'] ?? 'Volunteer Name') ?>
            </h2>
    <div class="divider"></div>
     <!-- Bio -->
    <p class="user-bio">
        <?= !empty($volunteer['bio']) ? e($volunteer['bio']) : '💬 Add your bio in your profile so everyone knows a little about you' ?>
    </p>
  <ul class="info-list">
    <!-- Birthday -->
    <li>
        <span class="icon">&#127874;</span> <!-- 🎂 cake icon -->
        <?= !empty($volunteer['date_of_birth']) ? e($volunteer['date_of_birth']) : 'No birthday provided' ?>
    </li>

    <!-- Gender -->
    <li>
        <span class="icon">&#9794;&#9792;</span> <!-- ⚥ gender icon -->
        <?= !empty($volunteer['gender']) ? ucfirst(e($volunteer['gender'])) : 'Gender not specified' ?>
    </li>
    <li><span class="icon">&#128231;</span> <?= e($volunteer['email']) ?></li>
    <li><span class="icon">&#127968;</span> <?= e($volunteer['street']) ?>, <?= e($volunteer['city']) ?>, <?= e($volunteer['country']) ?></li>
    <li><span class="icon">&#128187;</span> <?= !empty($volunteer['occupation']) ? e($volunteer['occupation']) : 'Not specified' ?></li>
    <li><span class="icon">&#9200;</span> <?= !empty($volunteer['availability']) ? e($volunteer['availability']) : 'Not specified' ?></li>
</ul>

   
  </div>
   <!-- New Password Change Section -->
    <div class="password-change-section">
      <h3>Password Change</h3>
      <p>For your account security, please change your password regularly.</p>
      <button class="change-pass-btn" onclick="location.href='../Html/Volunteer_Passchanged.php?user_id=<?= $volunteer_id ?>'">Change Password</button>
    </div>
</div>
    <div class="center-panel">
  <div class="card share-box">
    <img class="mini-profile" src="../Images/post.gif" alt="Profile">
    <input type="text" placeholder="What's on your mind?"  onclick="openModal()">
  </div>

  <div class="card post">
    <div class="post-header">
    <img class="mini-profile" 
         src="<?= !empty($volunteer['profile_photo']) 
                ? '../uploads/volunteer/' . e($volunteer['profile_photo']) 
                : 'https://randomuser.me/api/portraits/men/20.jpg' ?>" 
         alt="Profile">
    <div>
        <div class="username"><?= e($volunteer['full_name'] ?? 'Unknown User') ?></div>
        <div class="post-time">
            <?php
            // If you have a created_at timestamp, format "x min ago"
            if (!empty($post['created_at'])) {
                $post_time = strtotime($post['created_at']);
                $diff = time() - $post_time;
                if ($diff < 3600) {
                    echo intval($diff / 60) . ' min ago';
                } elseif ($diff < 86400) {
                    echo intval($diff / 3600) . ' hour ago';
                } else {
                    echo date('M d, Y', $post_time);
                }
            } else {
                echo 'Just now';
            }
            ?>
        </div>
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
       <script src="../javascrpit/Volunteer_profile.js"></script>

</html>