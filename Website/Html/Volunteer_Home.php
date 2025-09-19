<?php
declare(strict_types=1);
session_start();
require_once "../Php/db.php";

// Check if user is logged in as volunteer
if (
    empty($_SESSION['role']) || 
    $_SESSION['role'] !== 'volunteer' || 
    empty($_SESSION['user_id'])
) {
    header('Location: ../Html/login.html?error=session');
    exit();
}

$volunteer_id = (int)$_SESSION['user_id'];

try {
    // FIX: Removed trailing comma and added more columns if you need
    $stmt = $pdo->prepare("SELECT volunteer_id, full_name,  profile_photo, cover_photo, bio                       
                           FROM volunteers WHERE volunteer_id = :id LIMIT 1");
    $stmt->execute(['id' => $volunteer_id]);
    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: ../Html/login.html?error=db');
    exit();
}

if (!$volunteer) {
    session_unset();
    session_destroy();
    header('Location: ../Html/login.html?error=no_user');
    exit();
}

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
  <link rel="stylesheet" href="../css/Volunteer_Home.css">
  <style>
    .main-section { display:none; }
    .main-section.active { display:block; }
  </style>
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
    <!-- Cover Photo -->
    <img src="<?= !empty($volunteer['cover_photo']) ? '../uploads/volunteer/' . e($volunteer['cover_photo']) : '../Images/cover_default.jpg' ?>" 
         alt="Cover" class="cover">

    <!-- Profile Photo -->
    <img src="<?= !empty($volunteer['profile_photo']) ? '../uploads/volunteer/' . e($volunteer['profile_photo']) : '../Images/default_profile.png' ?>" 
         class="profile-pic" 
         alt="Profile">

    <!-- Edit button as image icon -->
    <button class="edit-btn" title="Edit Profile" 
        onclick="location.href='../Html/Volunteer_profile.php?user_id=<?= e($volunteer_id); ?>'">
        <img src="../Images/pencil.gif" alt="Edit" />
    </button>

    <!-- Volunteer name and icon -->
    <h2>
        <?= e($volunteer['full_name'] ?? 'User Name') ?>
        <img src="../Images/volunteer.gif" alt="User Icon" class="user-icon">
    </h2>

    <!-- Bio -->
    <p class="user-bio">
        <?= !empty($volunteer['bio']) ? e($volunteer['bio']) : '💬 Add your bio in your profile so everyone knows a little about you' ?>
    </p>
</div>
    
 <!-- 🎖️ Volunteer Missions Panel -->
<div class="Volunteer-rank">
  <h2>Volunteer Rank & Missions</h2>

  <div class="volunteer-rank-box">
    <h3>🎖️ Real-Life Missions</h3>

    <div class="rank-info">
      <p>🔰 <strong>Rank:</strong> Bronze Volunteer</p>
      <p>⭐ <strong>Points:</strong> 120 XP</p>
      <p>🎯 <strong>Next Rank:</strong> Silver Responder (Needs 200 XP)</p>
    </div>

    <button class="view-missions-btn" onclick="openMissionModal()">📋 View Missions</button>

    <!-- Certificate Display -->
    <div id="certificate-unlock" class="certificate-box hidden" aria-live="polite">
      <p>🎉 Congratulations! You’ve reached <strong>Silver Responder</strong>!</p>
      <button class="view-certificate-btn">🏅 View & Download Certificate</button>
    </div>
  </div>
</div>

<!-- 🧭 Volunteer Mission Modal -->
<div id="volunteerMissionModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="missionModalTitle" tabindex="-1">
  <div class="modal-content">
    <button class="close" onclick="closeMissionModal()" aria-label="Close modal">&times;</button>
    <h3 id="missionModalTitle">🧭 Missions for Your Rank</h3>

    <ul class="mission-list">
      <li>
        <strong>📍 Locate a missing person</strong><br>
        🎯 Verify alert visually<br>
        🎁 50 XP<br><br>
        <label>
          📤 Submit Photo/Video:
          <input type="file" accept="image/*,video/*" />
        </label><br><br>
        <button class="submit-proof-btn">✅ Submit Proof</button>
      </li>

      <li>
        <strong>👮 Assist police with report</strong><br>
        🎯 Identify suspects<br>
        🎁 40 XP<br><br>
        <label>
          📤 Submit Confirmation:
          <input type="file" accept="image/*,application/pdf" />
        </label><br><br>
        <button class="submit-proof-btn">✅ Submit Proof</button>
      </li>
    </ul>
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
       <script src="../javascrpit/Volunteer_Home.js"></script>

</html>