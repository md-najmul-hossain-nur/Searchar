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

function profileTimeAgo(?string $dateTime): string {
  if (empty($dateTime)) {
    return 'Just now';
  }
  $ts = strtotime($dateTime);
  if (!$ts) {
    return 'Just now';
  }
  $diff = time() - $ts;
  if ($diff < 60) return 'Just now';
  if ($diff < 3600) return floor($diff / 60) . ' min ago';
  if ($diff < 86400) return floor($diff / 3600) . ' hour ago';
  if ($diff < 604800) return floor($diff / 86400) . ' day ago';
  return date('M d, Y', $ts);
}

// 6. Fallback images and bio
$profile_pic = !empty($volunteer['profile_photo']) ? '../uploads/volunteer/' . e($volunteer['profile_photo']) : '../Images/default_profile.png';
$cover_pic   = !empty($volunteer['cover_photo'])   ? '../uploads/volunteer/' . e($volunteer['cover_photo'])   : '../Images/cover_default.jpg';
$bio_text    = !empty($volunteer['bio']) ? e($volunteer['bio']) : "💬 Bio not added yet. Go to <a href='../Html/Volunteer_Edit_profile.html'>edit profile</a> to add your bio!";

$profilePosts = [];
try {
  $hasStatus = false;
  $hasMediaJson = false;

  $statusCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
  if ($statusCol && $statusCol->fetch(PDO::FETCH_ASSOC)) {
    $hasStatus = true;
  }

  $mediaJsonCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'media_json'");
  if ($mediaJsonCol && $mediaJsonCol->fetch(PDO::FETCH_ASSOC)) {
    $hasMediaJson = true;
  }

  $selectCols = "id, author_name, text, media_path, media_type, category, created_at";
  if ($hasMediaJson) {
    $selectCols .= ", media_json";
  }
  if ($hasStatus) {
    $selectCols .= ", status";
  }

  $statusWhere = $hasStatus ? " AND status = 'approved'" : '';
  $postStmt = $pdo->prepare("SELECT {$selectCols} FROM posts WHERE author_id = :author_id AND author_role = 'volunteer'{$statusWhere} ORDER BY id DESC LIMIT 50");
  $postStmt->execute(['author_id' => $volunteer_id]);
  $profilePosts = $postStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $profilePosts = [];
}
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
    <link rel="stylesheet" href="../css/notifications_shared.css">

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

  <div id="post-feed">
    <?php if (empty($profilePosts)): ?>
      <div class="card post">
        <p>No posts yet. Share your first update.</p>
      </div>
    <?php else: ?>
      <?php foreach ($profilePosts as $post): ?>
        <?php
          $postAuthorName = (string)($post['author_name'] ?? ($volunteer['full_name'] ?? 'Volunteer Name'));
          $postText = (string)($post['text'] ?? '');
          $postMediaType = (string)($post['media_type'] ?? '');
          $postMediaPath = (string)($post['media_path'] ?? '');
          $postMediaUrl = $postMediaPath !== '' ? ('../' . ltrim($postMediaPath, '/')) : '';
          $postMediaJson = isset($post['media_json']) ? (string)$post['media_json'] : '';
          $postImageUrls = [];
          if ($postMediaJson !== '') {
              $decodedImages = json_decode($postMediaJson, true);
              if (is_array($decodedImages)) {
                  foreach ($decodedImages as $imgPath) {
                      if (is_string($imgPath) && trim($imgPath) !== '') {
                          $postImageUrls[] = '../' . ltrim($imgPath, '/');
                      }
                  }
              }
          }
          if (empty($postImageUrls) && $postMediaType === 'image' && $postMediaUrl !== '') {
              $postImageUrls[] = $postMediaUrl;
          }
          $postStatus = isset($post['status']) ? (string)$post['status'] : '';
        ?>
        <div class="card post" data-post-id="<?= (int)($post['id'] ?? 0) ?>">
          <div class="post-header">
            <img class="mini-profile" src="<?= !empty($volunteer['profile_photo']) ? '../uploads/volunteer/' . e($volunteer['profile_photo']) : '../Images/default_profile.png' ?>" alt="Profile">
            <div>
              <div class="username"><?= e($postAuthorName) ?></div>
              <div class="post-time"><?= e(profileTimeAgo((string)($post['created_at'] ?? ''))) ?></div>
              <?php if ($postStatus !== ''): ?>
                <div class="post-time">Status: <?= e(ucfirst($postStatus)) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($postText !== ''): ?>
            <p><?= nl2br(e($postText)) ?></p>
          <?php endif; ?>

          <?php foreach ($postImageUrls as $imgUrl): ?>
            <div class="post-image">
              <img src="<?= e($imgUrl) ?>" alt="Post Image">
            </div>
          <?php endforeach; ?>

          <?php if ($postMediaUrl !== '' && $postMediaType === 'video'): ?>
            <div class="post-image">
              <video controls style="width:100%; border-radius:8px;">
                <source src="<?= e($postMediaUrl) ?>" type="video/mp4">
              </video>
            </div>
          <?php endif; ?>

          <div class="post-actions">
            <span class="like-btn"><i class="fa fa-heart"></i> Like</span>
            <span class="comment-btn"><i class="fa fa-comment"></i> Comment</span>
          </div>
          <section class="comment-module" style="display:none;">
            <div class="comment-input-area">
              <div class="comment-editor" contenteditable="true" data-placeholder="Write a comment..."></div>
              <button class="comment-send-btn">
                <img src="../Images/send.png" alt="Send">
              </button>
            </div>
            <h4 class="comments-title">All Comments</h4>
            <ul></ul>
          </section>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Popup Modal and right panel copied from template -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    <span class="post-modal-close" onclick="closeModal()">&times;</span>
    <div class="post-modal-head">
      <h2 class="post-modal-title">Share Your Mood</h2>
      <p class="post-modal-subtitle">Upload photos or a video and post instantly</p>
    </div>
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="facebookShareToggle">
        <span class="facebook-toggle-slider"><i class="fab fa-facebook"></i></span>
      </label>
      <span class="facebook-toggle-label">Share to Facebook</span>
    </div>
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="anonymousShareToggle">
        <span class="facebook-toggle-slider">
          <i class="fa-solid fa-user-secret"></i>
        </span>
      </label>
      <span class="facebook-toggle-label">Share Anonymously</span>
    </div>

    <p class="category-label">Select Category:</p>
    <div class="category-toggle">
      <label class="category-option">
        <input type="radio" name="category" value="mission" checked>
        <img src="../Images/mission-icon.gif" alt="Mission Icon" class="category-icon" />
        Mission Person
      </label>
      <label class="category-option">
        <input type="radio" name="category" value="disaster">
        <img src="../Images/disaster-icon.gif" alt="Disaster Icon" class="category-icon" />
        Disaster
      </label>
    </div>

    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>
    <div class="post-modal-preview">
      <div id="sharedPostMeta" class="preview-meta">
        <img id="sharedPostAuthorImage" class="preview-meta-avatar" src="" alt="Author" />
        <div class="preview-meta-text">
          <h5 id="sharedPostAuthorName"></h5>
          <small id="sharedPostTime"></small>
        </div>
      </div>
      <p id="sharedPostText" class="preview-text"></p>
      <img id="sharedPostImage" class="preview-img" src="" alt="" />
      <video id="sharedPostVideo" class="preview-video" src="" controls controlsList="nodownload nofullscreen noplaybackrate" disablePictureInPicture oncontextmenu="return false;"></video>
    </div>
    <!-- ✅ Media Upload Buttons -->
    <div class="post-media-options">
      <label>
        <input type="file" id="imageUpload" accept="image/*" multiple hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('imageUpload').click()">📷 Photo</button>
      </label>
      <label>
        <input type="file" id="videoUpload" accept="video/*" hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('videoUpload').click()">🎥 Video</button>
      </label>
    </div>

    <p class="post-media-hint">You can select up to 5 photos in one post.</p>

    <!-- ✅ Media Preview -->
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
      <div class="card notifications notifications-card">
        <div class="notifications-top">
          <h3>Recent Notifications</h3>
          <button type="button" id="notificationsSeeMore" class="notifications-see-more">See more</button>
        </div>
        <div class="divider"></div>
        <ul id="recentNotificationsList" class="notifications-list">
          <li class="notifications-empty">Loading notifications...</li>
        </ul>
      </div>
      <div class="notifications">
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

<div id="notificationsDrawerBackdrop" class="notifications-drawer-backdrop"></div>
<aside id="notificationsDrawer" class="notifications-drawer" aria-hidden="true">
  <div class="notifications-drawer-header">
    <h3>All Notifications</h3>
    <button type="button" id="notificationsDrawerClose" class="notifications-drawer-close">&times;</button>
  </div>
  <div id="allNotificationsList" class="notifications-drawer-list">
    <div class="notifications-empty">No notifications yet.</div>
  </div>
  <div class="notifications-drawer-footer"></div>
</aside>
</body>
  <script src="../javascrpit/Volunteer_profile.js"></script>
  <script src="../javascrpit/post_interactions_shared.js?v=20260301"></script>
       <script src="../javascrpit/notifications_shared.js"></script>

</html>