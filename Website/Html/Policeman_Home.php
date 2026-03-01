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
  $sql = "SELECT police_id AS id, full_name, email, mobile, profile_photo, cover_photo, bio, badge_id, designation, station FROM policemen WHERE police_id = :id LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['id' => $user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Main CSS -->
  <link rel="stylesheet" href="../css/Policman_Home.css?=2">
  <link rel="stylesheet" href="../css/notifications_shared.css">
  
</head>
<body>
 <header class="navbar" style="display:flex; align-items:center; justify-content:space-between; padding:10px; position:fixed; top:0; left:0; right:0; z-index:2000; background:#fff;">
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
  <div class="container" style="margin-top:104px; padding:20px;">
    <div class="sidebar-left">
      <div class="profile-card">
        <img src="<?= isset($user['cover_photo']) ? '../uploads/police/' . e($user['cover_photo']) : '../Images/default-cover.gif' ?>" class="cover">
        <img src="<?= isset($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" class="profile-pic">
        <button class="edit-btn" title="Edit Profile" onclick="location.href='../Html/Policeman_profile.php'">
          <img src="../Images/pencil.gif" alt="Edit" />
        </button>

        <h3><?= e($user['full_name'] ?? '—') ?></h3>
        <p class="user-bio"><?= !empty($user['bio']) ? e($user['bio']) : 'Any one can join with us.' ?></p>
      </div>

     



<!-- Broadcast Request Section -->
<div class="broadcast-section">
  <h4>Broadcast Request</h4>
  <p>Request admin approval to start a broadcast.</p>
  
  <!-- Request Button -->
  <button id="requestBroadcastBtn" class="broadcast-request-btn">Request Broadcast</button>

  <!-- Status Message -->
  <p id="broadcastStatus"></p>

  <!-- Broadcast Link (Hidden until approved) -->
  <div id="broadcastLink" style="display:none;">
    <a href="../Html/BroadCast.html" target="_blank" class="broadcast-btn">Join Broadcast</a>
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
<button id="find-hospitals" style="padding:8px 15px;background:#f05454;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">🏥 Show Nearby Hospitals</button>
<button id="find-fire" style="padding:8px 15px;background:#ff7f11;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">🚒 Show Fire Stations</button>
<button id="find-police" style="padding:8px 15px;background:#0077b6;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:10px;">👮 Show Police Stations</button>

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

<div class="post static-demo-post" id="post-1" data-post-id="1" data-category="mission" style="display:none;">
  <div class="post-header">
    <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_f8ba3ae7.jpg">
    <div>
      <h5>Merry Watson</h5>
      <small>20 min ago</small>
    </div>
  </div>
  <p>Many desktop publishing packages and web page editors now use Lorem Ipsum...</p>
  <img src="../Images/demo.jpg" class="post-img">

  <div class="post-actions">
    <span class="like-btn"><i class="fa fa-heart"></i> 201 Likes</span>
    <span class="comment-btn"><i class="fa fa-comment"></i> 41</span>
    <span class="share-btn"><i class="fa fa-share"></i> 7</span>
  </div>

<section class="comment-module" style="display:none;">

<!-- Comment Input Area (Top) -->
  <div class="comment-input-area">
  <div class="comment-editor" contenteditable="true" data-placeholder="Write a comment..."></div>
<button class="comment-send-btn">
  <img src="../Images/send.png" alt="Send">
</button>
</div>


  <!-- Heading below input -->
  <h4 class="comments-title">All Comments</h4>

  <ul>
    
    <!-- First Comment -->
    <li>
      
      <div class="comment">
        <div class="comment-img">
          <img src="https://rvs-comment-module.vercel.app/Assets/User Avatar.png" alt="">
        </div>
        <div class="comment-content">
          <div class="comment-details">
            <h4 class="comment-name">Adamsdavid</h4>
            <span class="comment-log">20 hours ago</span>
          </div>
          <div class="comment-desc">
            <p>I genuinely think that Codewell's community is AMAZING...</p>
          </div>
          <div class="comment-data">
            <div class="comment-likes">
              <div class="comment-likes-up">
                <img src="../Images/like.gif" alt="">
                <span>2</span>
              </div>
              <div class="comment-likes-down">
                <img src="../Images/dislike.gif" alt="">
              </div>
            </div>
            <div class="comment-reply">
              <a href="#!">Reply</a>
            </div>
            <div class="comment-report">
              <a href="#!">Report</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Nested Replies to First Comment -->
      <ul>
        <li>
          <div class="comment">
            <div class="comment-img">
              <img src="https://rvs-comment-module.vercel.app/Assets/User Avatar-1.png" alt="">
            </div>
            <div class="comment-content">
              <div class="comment-details">
                <h4 class="comment-name">saramay</h4>
                <span class="comment-log">16 hours ago</span>
              </div>
              <div class="comment-desc">
                <p>I agree. I've been coding really well (pun intended) ever since I started practicing on their templates hehe.</p>
              </div>
              <div class="comment-data">
                <div class="comment-likes">
                  <div class="comment-likes-up">
                <img src="../Images/like.gif" alt="">
                    <span>5</span>
                  </div>
                  <div class="comment-likes-down">
                <img src="../Images/dislike.gif" alt="">
                  </div>
                </div>
                <div class="comment-reply">
                  <a href="#!">Reply</a>
                </div>
                <div class="comment-report">
                  <a href="#!">Report</a>
                </div>
              </div>
            </div>
          </div>

          <!-- Nested Reply to Second Comment -->
          <ul>
            <li>
              <div class="comment">
                <div class="comment-img">
                  <img src="https://rvs-comment-module.vercel.app/Assets/User Avatar-2.png" alt="">
                </div>
                <div class="comment-content">
                  <div class="comment-details">
                    <h4 class="comment-name">Jessica21</h4>
                    <span class="comment-log">14 hours ago</span>
                  </div>
                  <div class="comment-desc">
                    <p>Okay, this comment wins.</p>
                  </div>
                  <div class="comment-data">
                    <div class="comment-likes">
                      <div class="comment-likes-up">
                <img src="../Images/like.gif" alt="">
                        <span>5</span>
                      </div>
                      <div class="comment-likes-down">
                <img src="../Images/dislike.gif" alt="">
                      </div>
                    </div>
                    <div class="comment-reply">
                      <a href="#!">Reply</a>
                    </div>
                    <div class="comment-report">
                      <a href="#!">Report</a>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </li>
      </ul>
    </li>

    <!-- Second Top-level Comment -->
    <li>
      <div class="comment">
        <div class="comment-img">
          <img src="https://rvs-comment-module.vercel.app/Assets/User Avatar-3.png" alt="">
        </div>
        <div class="comment-content">
          <div class="comment-details">
            <h4 class="comment-name">andrew231</h4>
            <span class="comment-log">20 hours ago</span>
          </div>
          <div class="comment-desc">
            <p>Thanks for making this, super helpful.</p>
          </div>
          <div class="comment-data">
            <div class="comment-likes">
              <div class="comment-likes-up">
                <img src="../Images/like.gif" alt="">
                <span>2</span>
              </div>
              <div class="comment-likes-down">
                <img src="../Images/dislike.gif" alt="">
              </div>
            </div>
            <div class="comment-reply">
              <a href="#!">Reply</a>
            </div>
            <div class="comment-report">
              <a href="#!">Report</a>
            </div>
          </div>
        </div>
      </div>
    </li>

    <!-- Third Top-level Comment -->
    <li>
      <div class="comment">
        <div class="comment-img">
          <img src="https://rvs-comment-module.vercel.app/Assets/User Avatar-4.png" alt="">
        </div>
        <div class="comment-content">
          <div class="comment-details">
            <h4 class="comment-name">maria_k</h4>
            <span class="comment-log">18 hours ago</span>
          </div>
          <div class="comment-desc">
            <p>This platform really helped me improve my coding skills.</p>
          </div>
          <div class="comment-data">
            <div class="comment-likes">
              <div class="comment-likes-up">
                <img src="../Images/like.gif" alt="">
                <span>4</span>
              </div>
              <div class="comment-likes-down">
                <img src="../Images/dislike.gif" alt="">
              </div>
            </div>
            <div class="comment-reply">
              <a href="#!">Reply</a>
            </div>
            <div class="comment-report">
              <a href="#!">Report</a>
            </div>
          </div>
        </div>
      </div>
    </li>

  </ul>
</section>

</div>

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

      <div class="find-love-simple">
        <h4>Missing Person Investigation Desk</h4>
        <p class="helpdesk-subtitle">Collect verified clues quickly and report suspected sightings for fast police action.</p>
        <button type="button" onclick="openMissingForm()">
          <img src="../Images/search.gif" alt="Investigation Icon" class="love-image" />
        </button>
        <p class="helpdesk-cta">Tap to open investigation form</p>
      </div>

    <div class="advert">
  <h4>Advertisement</h4>

  <div class="advert-slider">
    <div class="advert-track">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_f8ba3ae7.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_b3223d89.jpg">

      <!-- Repeat same images -->
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_f8ba3ae7.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.01_fac5108b.jpg">
      <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_b3223d89.jpg">
    </div>
  </div>
</div>

<!-- Missing Person Investigation Popup -->
<div id="missingFormModal" class="missing-modal">
  <div class="missing-modal-content">
    <span class="missing-close" onclick="closeMissingForm()">&times;</span>
    <h2>Police Missing Person Investigation Form</h2>

    <form id="missingForm" action="../Php/save_missing_person.php" method="POST" enctype="multipart/form-data">
      <h3>Personal Details</h3>
      <label>Full Name</label>
      <input type="text" name="full_name" required>

      <label>Nickname / Alias</label>
      <input type="text" name="nickname">

      <label>Gender</label>
      <select name="gender" required>
        <option value="">Select Gender</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
        <option value="Other">Other</option>
      </select>

      <label>Age</label>
      <input type="number" name="age" min="1" required>

      <label>Physical Description (Height / Dress / Marks)</label>
      <input type="text" name="physical_description" placeholder="E.g., 5'6, blue shirt, scar on left hand">

      <label>Photo Upload</label>
      <input type="file" id="personPhotoInput" name="person_photo" accept="image/*" required>
      <div id="personPhotoPreviewWrap" class="person-photo-preview-wrap" style="display:none;">
        <p class="person-photo-preview-title">Photo Preview</p>
        <img id="personPhotoPreview" class="person-photo-preview" src="" alt="Missing Person Photo Preview">
      </div>

      <h3>Last Seen Information</h3>
      <label>Last Seen Date</label>
      <input type="date" name="last_seen_date" required>

      <label>Last Seen Location</label>
      <input type="text" name="last_seen_location" placeholder="E.g., Dhanmondi 27, Dhaka" required>

      <label>Approximate Time</label>
      <input type="text" name="last_seen_time" placeholder="E.g., 6:30 PM">

      <h3>Health & Mental Condition</h3>
      <label>Mental Condition</label>
      <select name="mental_condition">
        <option value="Stable">Stable</option>
        <option value="Depression">Depression</option>
        <option value="Autism">Autism</option>
        <option value="Memory Loss">Memory Loss</option>
      </select>

      <label>Medical Notes</label>
      <input type="text" name="medical_notes" placeholder="E.g., Needs regular medicine">

      <h3>Officer / Reporter Contact</h3>
      <label>Reporting Officer Name</label>
      <input type="text" name="reporter_name" required>

      <label>Official Contact Number</label>
      <input type="tel" name="reporter_mobile" required>

      <label>Source Relation</label>
      <input type="text" name="relationship" placeholder="E.g., Witness / Family / Field Team">

      <h3>Consent</h3>
      <label>
        <input type="checkbox" name="consent" value="1" required> I confirm this information is verified for investigation use.
      </label>

      <div class="modal-actions">
        <button type="button" onclick="closeMissingForm()" class="cancel-btn">Cancel</button>
        <button type="submit" class="submit-btn">Submit Investigation Report</button>
      </div>
    </form>
  </div>
</div>

 <style>.advert {
  border: 1px solid #eee;
  padding: 10px;
  border-radius: 12px;
  background: #fff;
  margin-top: 15px;
}

.advert-slider {
  overflow: hidden;
  width: 100%;
}

.advert-track {
  display: flex;
  width: max-content;
}

.advert-track img {
  width: 150px;
  height: 100px;
  object-fit: cover;
  margin-right: 10px;
  border-radius: 10px;
}
</style>
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
<div class="Calender-section">
  <div class="calendar-container">
    <h2 class="calendar-title">Calendar</h2>
    <p class="calendar-info">Select a date to add your events. Use the arrows to navigate months.</p>
  </div>
  <div class="calendar-header">
    <button id="prevMonth">&lt;</button>
    <h2 id="monthYear"></h2>
    <button id="nextMonth">&gt;</button>
  </div>

  <div class="calendar-weekdays"></div>  <!-- Add this -->

  <div class="calendar-grid" id="calendarGrid"></div>
</div>


<!-- Event Modal -->
<div id="myEventModal" class="my-event-modal" style="display:none;">
  <div class="event-modal-content">
    <span id="closeMyModal" style="cursor:pointer;float:right;font-size:20px;">&times;</span>
    <h3>Add Event</h3>
    <p id="selectedDateText"></p>
    <input type="text" id="eventInput" placeholder="Enter event here" style="width: 100%; padding: 8px; margin: 10px 0;">
    <button id="saveEventBtn" style="background:#f05454; color:white; border:none; padding:8px 20px; border-radius:25px; cursor:pointer;">Save</button>
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
      <script src="../javascrpit/Policeman_Home.js?v=callbtn1"></script>
      <script src="../javascrpit/post_interactions_shared.js"></script>
      <script src="../javascrpit/notifications_shared.js"></script>

</html>