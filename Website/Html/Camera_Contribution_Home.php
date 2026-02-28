<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../Php/db.php';

// Require a logged-in user id. This page specifically displays camera contributor data,
// so always fetch from `camera_contributors` using the session `user_id`.
if (empty($_SESSION['user_id'])) {
  header('Location: ../Html/login.html');
  exit();
}

$user_id = (int) $_SESSION['user_id'];

try {
  $sql = "SELECT camera_id AS id, full_name, email, mobile, profile_photo, cover_photo, bio
      FROM camera_contributors WHERE camera_id = :id LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['id' => $user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
  // If DB fails, redirect to login to avoid showing sensitive errors.
  header('Location: ../Html/login.html?error=db');
  exit();
}

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function timeAgo(?string $datetime): string {
  if (!$datetime) return 'Just now';
  try {
    $created = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $created->getTimestamp();
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' day ago';
    return $created->format('d M Y');
  } catch (Exception $e) {
    return 'Just now';
  }
}

function getAuthorPhoto(PDO $pdo, string $authorRole, int $authorId): string {
  static $roleMap = [
    'user' => ['table' => 'users', 'id_col' => 'user_id', 'folder' => 'user'],
    'police' => ['table' => 'policemen', 'id_col' => 'police_id', 'folder' => 'police'],
    'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'folder' => 'volunteer'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'folder' => 'camera'],
  ];
  static $cache = [];

  $cacheKey = $authorRole . ':' . $authorId;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  if (!isset($roleMap[$authorRole]) || $authorId <= 0) {
    return $cache[$cacheKey] = '../Images/default_profile.png';
  }

  $table = $roleMap[$authorRole]['table'];
  $idCol = $roleMap[$authorRole]['id_col'];
  $folder = $roleMap[$authorRole]['folder'];

  try {
    $stmt = $pdo->prepare("SELECT profile_photo FROM {$table} WHERE {$idCol} = :id LIMIT 1");
    $stmt->execute(['id' => $authorId]);
    $photo = (string)($stmt->fetchColumn() ?: '');
    if ($photo !== '') {
      return $cache[$cacheKey] = '../uploads/' . $folder . '/' . e($photo);
    }
  } catch (Exception $e) {
  }

  return $cache[$cacheKey] = '../Images/default_profile.png';
}

$posts = [];
try {
  $hasMediaJson = false;
  $hasStatus = false;

  $mediaJsonCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'media_json'");
  if ($mediaJsonCol && $mediaJsonCol->fetch(PDO::FETCH_ASSOC)) {
    $hasMediaJson = true;
  }

  $statusCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
  if ($statusCol && $statusCol->fetch(PDO::FETCH_ASSOC)) {
    $hasStatus = true;
  }

  $selectCols = "id, author_role, author_id, author_name, category, text, media_path, media_type, created_at";
  if ($hasMediaJson) {
    $selectCols .= ", media_json";
  }
  if ($hasStatus) {
    $selectCols .= ", status";
  }

  $whereClause = $hasStatus ? "WHERE status = 'approved'" : '';
  $postStmt = $pdo->query("SELECT {$selectCols} FROM posts {$whereClause} ORDER BY id DESC LIMIT 50");
  $posts = $postStmt ? $postStmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
  $posts = [];
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
  <link rel="stylesheet" href="../css/Camera_Contribution_Home.css">
  <link rel="stylesheet" href="../css/notifications_shared.css">
   <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>

</head>
<body>
 <header class="navbar" style="display:flex; align-items:center; justify-content:space-between; padding:10px; position:sticky; top:0; z-index:2000; background:#fff;">
  <!-- Left: Logo -->
  <div class="navbar-logo">
    <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo" />
  </div>
  
  <!-- Right: Email + Logout -->
  <div style="display:flex; align-items:center; gap:10px; margin-right:40px;">
    <span><?= e($user['email'] ?? 'Guest') ?></span>
    <button class="navbar-donate" onclick="window.location.href='../Php/logout.php';" style="display:flex; align-items:center; gap:5px;">
      LOG OUT
      <img src="../Images/import.gif" alt="Gift" style="height:1.5em; border-radius:6px;">
    </button>
  </div>
</header>
  <?php /* user data loaded above */ ?>
  <div class="container">
    <!-- Left Sidebar -->
    <div class="sidebar-left">
      <div class="profile-card">
        <img src="<?= isset($user['cover_photo']) ? '../uploads/camera/' . e($user['cover_photo']) : '../Images/WhatsApp Image 2025-07-31 at 12.44.00_0c691462.jpg' ?>" class="cover">
        <img src="<?= isset($user['profile_photo']) ? '../uploads/camera/' . e($user['profile_photo']) : '../Images/WhatsApp Image 2025-07-31 at 12.44.00_b3223d89.jpg' ?>" class="profile-pic">
        <!-- Edit button as image icon -->
        <button class="edit-btn" title="Edit Profile" onclick="location.href='../Html/Camera_Contribution_profile.php'">
  <img src="../Images/pencil.gif" alt="Edit" />
</button>

        <h3><?= e($user['full_name'] ?? '—') ?></h3>
        <p class="user-bio">
          <?= !empty($user['bio']) ? e($user['bio']) : 'Any one can join with us.' ?></p>
      </div>
      
      
     <!-- Streamer Info Section -->
<div class="money-withdrawal">
  <h2>Withdraw Your Stream Earnings</h2>
  <p>You’ve earned money by helping the community through live CCTV streaming. You can withdraw your balance anytime using your preferred method.</p>

  <ul class="streamer-info">
    <li><strong>Name:</strong> Erik Jhonson</li>
    <li><strong>Total Streams:</strong> 45</li>
    <li><strong>Total Earned:</strong> $1000</li>
    <li><strong>Available Balance:</strong> $1000</li>
    <li><strong>Pending Transactions:</strong> $0</li>
    <li><strong>Last Withdrawal Date:</strong> July 24, 2025</li>
  </ul>

  <button id="openWithdrawBtn" class="withdraw-btn">Withdraw Now</button>
</div>

<!-- Withdrawal Modal -->
<div id="withdrawModal" class="withdrawal-modal" style="display:none;">
  <div class="withdrawal-modal-content">
    <span id="closeModalBtn" class="withdrawal-close">&times;</span>
    <h3>Withdrawal Form</h3>
    <form id="withdrawForm" class="withdrawal-form">
      <label for="method">Withdrawal Method:</label>
      <select id="method" name="method" required>
        <option value="">Select Method</option>
        <option value="bkash">bKash</option>
        <option value="nagad">Nagad</option>
        <option value="bank">Bank Transfer</option>
        <option value="paypal">PayPal</option>
      </select>

      <label for="accountNumber">Account Number:</label>
      <input type="text" id="accountNumber" name="accountNumber" placeholder="Enter your account number" required>

      <label for="amount">Amount to Withdraw:</label>
      <input type="number" id="amount" name="amount" min="5" max="1000" placeholder="Enter amount" required>

      <button type="submit" class="confirm-btn">Confirm Withdrawal</button>
    </form>

    <!-- Transaction History -->
    <h4>Transaction History</h4>
    <table class="transaction-table" border="1" cellpadding="8" cellspacing="0">
      <thead>
        <tr>
          <th>Date</th>
          <th>Method</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>July 20, 2025</td>
          <td>bKash</td>
          <td>$200</td>
          <td>Completed</td>
        </tr>
        <tr>
          <td>July 15, 2025</td>
          <td>PayPal</td>
          <td>$300</td>
          <td>Pending</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>




<!-- Hospital Section -->
<div class="hospital-section">
  <!-- Header -->
  <h2 style="text-align: center; color: #333; margin-bottom: 15px; font-weight: 700;">
    Emergency Services Locator
  </h2>
  <!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<!-- Routing Machine CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

<!-- Buttons -->
<button id="find-hospitals" class="emergency-btn hospital">🏥 Show Nearby Hospitals</button>
<button id="find-fire" class="emergency-btn fire">🚒 Show Fire Stations</button>
<button id="find-police" class="emergency-btn police">👮 Show Police Stations</button>

<!-- Map Container -->
<div id="emergency-map" style="height: 400px; border-radius: 8px; border: 2px solid #000; width: 100%; max-width: 100%; overflow: hidden; box-sizing: border-box; position: relative; z-index: 0;"></div>

<!-- JS Libraries -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

</div>
    </div>

      <!-- Main Feed -->
    <div class="main-feed">
      <!-- Post Box -->
      <div class="post-box" onclick="openModal()">
        <img src="../Images/post.gif" class="user">
        <input type="text" placeholder="What's on your mind?" readonly>
      </div>

<!-- ✅ Popup Modal -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    
    <!-- Close Button -->
    <span class="post-modal-close" onclick="closeModal()">&times;</span>

    <!-- Title -->
    <h2 class="post-modal-title">Share Your Mood</h2>

    <!-- ✅ Facebook Toggle -->
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="facebookShareToggle">
        <span class="facebook-toggle-slider">
          <i class="fab fa-facebook"></i>
        </span>
      </label>
      <span class="facebook-toggle-label">Share to Facebook</span>
    </div>
<!-- ✅ Anonymous Mode Toggle -->
<div class="facebook-toggle">
  <label class="facebook-toggle-switch">
    <input type="checkbox" id="anonToggle">
    <span class="facebook-toggle-slider">
      <i class="fas fa-user-secret"></i> <!-- example icon -->
    </span>
  </label>
  <span class="facebook-toggle-label">Post Anonymously</span>
</div>
    <!-- Category Label -->
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

    <!-- ✅ Textarea -->
    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>

    <!-- ✅ Post Preview (Auto-filled from clicked post) -->
    <div class="post-modal-preview">
      <p id="sharedPostText" class="preview-text"></p>
      <img id="sharedPostImage" class="preview-img" src="" alt="" />
    </div>

    <!-- ✅ Media Upload Buttons -->
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


    <!-- ✅ Media Preview (optional preview for uploaded file) -->
    <div id="mediaPreview" class="post-media-preview"></div>

    <!-- ✅ Action Buttons -->
    <div class="post-modal-actions">
      <button class="post-cancel-btn" onclick="closeModal()">Cancel</button>
      <button class="post-submit-btn" onclick="createPost()">Post</button>
    </div>
    
  </div>
</div>

<div class="filter-bar-section">
  <p class="filter-bar-title">Filter by Category:</p>
  <nav class="post-filter-bar" aria-label="Post Category Filters">
    <button class="filter-btn active" type="button" onclick="filterPosts('all')">All</button>
    <button class="filter-btn" type="button" onclick="filterPosts('mission')">
      <img src="../Images/mission-icon.gif" alt="Mission Icon" class="filter-icon" /> Mission Person
    </button>
    <button class="filter-btn" type="button" onclick="filterPosts('disaster')">
      <img src="../Images/disaster-icon.gif" alt="Disaster Icon" class="filter-icon" /> Disaster
    </button>
  </nav>
</div>

<?php if (!empty($posts)): ?>
  <?php foreach ($posts as $post): ?>
    <?php
      $postId = (int)($post['id'] ?? 0);
      $postCategory = (string)($post['category'] ?? 'general');
      $postAuthorName = (string)($post['author_name'] ?? 'Unknown User');
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
      $authorRole = (string)($post['author_role'] ?? '');
      $authorId = (int)($post['author_id'] ?? 0);
      $authorPhoto = getAuthorPhoto($pdo, $authorRole, $authorId);
    ?>
    <div class="post" id="post-<?= $postId ?>" data-post-id="<?= $postId ?>" data-category="<?= e($postCategory) ?>" data-status="<?= e((string)($post['status'] ?? 'approved')) ?>">
      <div class="post-header">
        <img src="<?= $authorPhoto ?>" alt="Author Photo">
        <div>
          <h5><?= e($postAuthorName) ?></h5>
          <small class="post-time" data-created-at="<?= e((string)($post['created_at'] ?? '')) ?>"><?= e(timeAgo((string)($post['created_at'] ?? ''))) ?></small>
        </div>
      </div>

      <?php if ($postText !== ''): ?>
        <p><?= nl2br(e($postText)) ?></p>
      <?php endif; ?>

      <?php if (!empty($postImageUrls)): ?>
        <?php if (count($postImageUrls) === 1): ?>
          <img src="<?= e($postImageUrls[0]) ?>" class="post-img" alt="Post Image">
        <?php else: ?>
          <div class="post-image-grid">
            <?php foreach ($postImageUrls as $imgUrl): ?>
              <img src="<?= e($imgUrl) ?>" class="post-grid-img" alt="Post Image">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php elseif ($postMediaUrl !== '' && $postMediaType === 'video'): ?>
        <video class="post-video" controls preload="metadata">
          <source src="<?= e($postMediaUrl) ?>" type="video/mp4">
          Your browser does not support video.
        </video>
      <?php endif; ?>

      <div class="post-actions">
        <span class="like-btn"><i class="fa fa-heart"></i> Like</span>
        <span class="comment-btn"><i class="fa fa-comment"></i> Comment</span>
        <span class="share-btn"><i class="fa fa-share"></i> Share</span>
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

    <!-- Right Sidebar -->
    <div class="sidebar-right">
      <div class="notifications notifications-card">
        <div class="notifications-top">
          <h4>Recent Notifications</h4>
          <button type="button" id="notificationsSeeMore" class="notifications-see-more">See more</button>
        </div>
        <ul id="recentNotificationsList" class="notifications-list">
          <li class="notifications-empty">Loading notifications...</li>
        </ul>
      </div>

     <div class="advert">
  <h4>Advertisement</h4>
  <div class="advert-slider">
    <div class="advert-track">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_f8ba3ae7.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_b3223d89.jpg">
    </div>
  </div>
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

<!-- Camera Contributor Panel -->
<div class="camera-contributor-panel">
  <h4>Camera Contributor Panel</h4>
  
  <button id="startFeedBtn" class="camera-btn">
    📷 Start Live / Upload Recorded Feed
  </button>
  <button class="camera-btn" onclick="window.location.href='../Html/Camera_Contribution_Feed.php';">
  👀 View Feed
</button>

</div>

<!-- Feed Form Popup -->
<div id="camFeedFormModal" class="cam-form-popup">
  <div class="cam-form-content">
    <span class="cam-form-close">&times;</span>
    <h3>Upload or Start Live Feed</h3>
    <p class="cam-form-subtitle">Add one by one, your camera name will auto increment (Camera 1, Camera 2, ...).</p>

    <form id="camFeedForm">
      <!-- Live / Recorded Selection -->
      <label>
        <input type="radio" name="feedType" value="live" required checked>
        Start Live Feed
      </label>
      <label>
        <input type="radio" name="feedType" value="recorded" required>
        Upload Recorded Video
      </label>

      <div class="cam-auto-label-box">
        Camera Name: <strong id="camAutoLabel">Camera 1</strong>
      </div>

      <!-- URL input for Live -->
      <div id="camLiveInputSection">
        <label>
          Stream Access Type
          <select id="camStreamScope" name="stream_scope">
            <option value="private">Private (owner permission)</option>
            <option value="public">Public (open URL)</option>
          </select>
        </label>
        <input type="url" id="camLiveURL" name="live_url" placeholder="Enter live video URL (MP4/HLS)">
        <div class="cam-live-help">
          <h5>Where to get a real Live CCTV URL?</h5>
          <ul>
            <li><strong>Private stream:</strong> collect URL from your own camera/NVR dashboard and keep owner permission.</li>
            <li><strong>Public stream:</strong> ask provider for public playback URL (HLS <code>.m3u8</code> preferred).</li>
            <li>Without owner permission, private CCTV access is not allowed.</li>
          </ul>
          <div class="cam-live-help-links">
            <a href="https://www.earthcam.com/" target="_blank" rel="noopener">EarthCam</a>
            <a href="https://www.skylinewebcams.com/" target="_blank" rel="noopener">Skyline Webcams</a>
            <a href="https://www.livebeaches.com/" target="_blank" rel="noopener">Live Beaches</a>
          </div>
          <small>Tip: Public mode blocks local/private network URLs; Private mode allows owner-authorized internal URLs.</small>
        </div>
      </div>

      <!-- File Upload Section for Recorded -->
      <div id="camUploadSection">
        <input type="file" accept="video/*" id="camFileInput" name="recorded_video">
        <small class="cam-form-hint">Long recorded footage is supported (depending on server upload limit).</small>
        <div id="camRecordedPreviewWrap" class="cam-recorded-preview-wrap" style="display:none;">
          <video id="camRecordedPreview" class="cam-recorded-preview" controls preload="metadata"></video>
        </div>
      </div>

      <label>
        Estimated Duration
        <select name="streaming_hours">
          <option value="30min">Up to 30 min</option>
          <option value="1to2h">1-2 hours</option>
          <option value="2to6h">2-6 hours</option>
          <option value="6to12h">6-12 hours</option>
          <option value="12to24h">12-24 hours</option>
          <option value="24plus">24+ hours</option>
        </select>
      </label>

      <label class="cam-owner-consent">
        <input type="checkbox" id="camPermissionConfirm" name="permission_confirmed" required>
        I confirm this CCTV/live stream is mine or I have owner permission.
      </label>

      <button type="submit" class="cam-submit-btn">
        Add CCTV Feed
      </button>
    </form>

    <div class="cam-pricing-rules">
      <h4>Feed Payment Rule</h4>
      <p><strong>Recorded Feed:</strong> ৳60 per hour</p>
      <p><strong>Live Feed:</strong> ৳100 per hour</p>
      <small>Live feed pays more because real-time monitoring demand is higher.</small>
    </div>
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
       <script src="../javascrpit/Camera_Contribution_Home.js"></script>
       <script src="../javascrpit/notifications_shared.js"></script>

</html>