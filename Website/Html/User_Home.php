<?php
// user_home.php
// Server-side user home page that validates session role + user_id
// Additionally checks session email (if present) matches DB record.
// Replace paths as needed and ensure ../Php/db.php defines $pdo (PDO instance).

declare(strict_types=1);
session_start();

require_once __DIR__ . '/../Php/db.php'; // adjust path if necessary

// If not authenticated, redirect to login
if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

$role = (string) $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

// Role => table mapping (whitelist)
$roleTableMap = [
    'user'        => ['table' => 'users', 'id_col' => 'user_id'],
    'police'      => ['table' => 'policemen', 'id_col' => 'police_id'],
    'volunteer'   => ['table' => 'volunteers', 'id_col' => 'volunteer_id'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id'],
];

if (!isset($roleTableMap[$role])) {
    // invalid role in session: destroy session and force login
    session_unset();
    session_destroy();
    header('Location: ../Html/login.html?error=invalid_role');
    exit();
}

$table = $roleTableMap[$role]['table'];
$id_col = $roleTableMap[$role]['id_col'];

try {
    // Fetch the user row by id. Use whitelist for table/column interpolation.
    $sql = "SELECT {$id_col}, full_name, email, mobile, profile_photo,bio, cover_photo, date_of_birth, gender, street, city, country
            FROM {$table} WHERE {$id_col} = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // On DB error, redirect to login (or show an error page)
    header('Location: ../Html/login.html?error=db');
    exit();
}

if (!$user) {
    // No user found for this session id -> logout
    session_unset();
    session_destroy();
    header('Location: ../Html/login.html?error=no_user');
    exit();
}

// Optional extra check: if session has email, ensure it matches DB record.
// This defends against session tampering where role/user_id pair is inconsistent with email.
if (!empty($_SESSION['email'])) {
    $sessionEmail = (string) $_SESSION['email'];
    if (strcasecmp($sessionEmail, (string)$user['email']) !== 0) {
        // mismatch: destroy session and force login
        session_unset();
        session_destroy();
        header('Location: ../Html/login.html?error=email_mismatch');
        exit();
    }
}

// compute age from date_of_birth if available
$age = null;
if (!empty($user['date_of_birth'])) {
    try {
        $dob = new DateTime($user['date_of_birth']);
        $age = (new DateTime())->diff($dob)->y;
    } catch (Exception $e) {
        $age = null;
    }
}

// safe output helper
function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Now render a minimal HTML page — integrate this into your full template as needed.
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
  <link rel="stylesheet" href="../css/User_Home.css">

</head>
<body>
<header class="navbar" style="display:flex; align-items:center; justify-content:space-between; padding:10px;">
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




    <div class="container">
    <!-- Left Sidebar -->
    <div class="sidebar-left">
      <div class="profile-card">
<img src="<?= isset($user['cover_photo']) 
              ? '../uploads/user/' . e($user['cover_photo']) 
              : '../Images/cover_default.jpg' ?>" 
       class="cover" alt="Cover Photo">
         <!-- Profile image dynamic from DB -->
 <img src="<?= isset($user['profile_photo']) 
            ? '../uploads/user/' . e($user['profile_photo']) 
            : '../Images/default_profile.png' ?>" 
     class="profile-pic" 
     alt="Profile Photo">
     <?php $user_id = (int)$user['user_id']; ?>
      <!-- Edit button as image icon -->
        <button class="edit-btn" title="Edit Profile" onclick="location.href='../Html/User_profile.html?user_id=<?= $user_id ?>'">
  <img src="../Images/pencil.gif" alt="Edit" />
</button>

<h3><?= e($user['full_name'] ?? '—') ?></h3>
<p class="user-bio">
    <?= !empty($user['bio']) 
        ? e($user['bio']) 
        : "💬 Add your bio in your profile so everyone knows a little about you!" ?>
</p>


</div>
      
    <div class="page-like">
  <h4>Make a Contribution</h4>

  <!-- Donation Button -->
<button class="donate-btn" onclick="window.location.href='../Html/Donated.Html'"> Donate Now</button>

</div>



<!-- Become a Volunteer Section -->
<div class="volunteer-section">
  <h4>Become a Volunteer</h4>
  <p>Join our community and help us make a difference!</p>
  <button class="volunteer-btn">Sign Up</button>
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
<div id="emergency-map" style="height: 400px; border-radius: 8px;"></div>

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
  <!-- Only ONE post block should exist, not repeated. Comment modules should be closed properly and IDs/classes should be unique per post. Here is a cleaned-up, non-repeated example of a single post: -->

<div class="post">
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
      <div class="notifications">
        <h4>Recent Notifications</h4>
        <ul>
          <li><img src="https://via.placeholder.com/30"> Any one can join... <span>5 min ago</span></li>
          <li><img src="https://via.placeholder.com/30"> Any one can join... <span>10 min ago</span></li>
          <li><img src="https://via.placeholder.com/30"> Any one can join... <span>18 min ago</span></li>
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

<div class="find-love-simple">
  <h4>Find Your Loved One</h4>
  <button type="button" onclick="openMissingForm()">
    <img src="../Images/search.gif" alt="Love Icon" class="love-image" />
  </button>
</div>

<!-- Missing Person Report Popup -->
<div id="missingFormModal" class="missing-modal">
  <div class="missing-modal-content">
    <span class="missing-close" onclick="closeMissingForm()">&times;</span>
    <h2>Missing Person Report Form</h2>

    <form id="missingForm">
      <!-- Section 1: Personal Details -->
      <h3>Personal Details</h3>
      <label>Full Name</label>
      <input type="text" required>
      
      <label>Nickname / Alias</label>
      <input type="text">

      <label>Gender</label>
      <select>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>

      <label>Age</label>
      <input type="number" min="1">

      <label>Photo Upload</label>
      <input type="file" accept="image/*" required>

      <!-- Section 2: Last Seen Info -->
      <h3>Last Seen Information</h3>
      <label>Last Seen Date</label>
      <input type="date">
      
      <label>Last Seen Location</label>
      <input type="text" placeholder="E.g., Dhanmondi 27, Dhaka">

      <!-- Section 3: Health -->
      <h3>Health & Mental Condition</h3>
      <label>Mental Condition</label>
      <select>
        <option>Stable</option>
        <option>Depression</option>
        <option>Autism</option>
        <option>Memory Loss</option>
      </select>

      <!-- Section 4: Reporter Contact -->
      <h3>Your Contact Details</h3>
      <label>Your Name</label>
      <input type="text" required>
      
      <label>Mobile Number</label>
      <input type="tel" required>

      <!-- Section 5: Consent -->
      <h3>Consent</h3>
      <label>
        <input type="checkbox" required> I give permission to share this data publicly.
      </label>
      
      <div class="modal-actions">
        <button type="button" onclick="closeMissingForm()" class="cancel-btn">Cancel</button>
        <button type="submit" class="submit-btn">Submit Report</button>
      </div>
    </form>
  </div>
</div>

<div class="group-chat-section">
  <h4>Group Chat</h4>
  <div class="chat-window" id="chatWindow">
    <!-- Messages will appear here -->
    <div class="message received">Welcome to the group chat!</div>
  </div>
 <div class="chat-input-area">
  <input type="text" id="chatInput" placeholder="Type your message..." />
  <img src="../Images/smile.png" alt="Emoji" class="chat-icon" />
<button id="sendBtn">
    <img src="../Images/send.png" alt="Send" class="send-icon" />
  </button></div>




    </body>
       <script src="../javascrpit/User_Home.js"></script>

</html>