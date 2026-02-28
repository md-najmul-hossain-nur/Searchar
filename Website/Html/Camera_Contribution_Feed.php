<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../Php/db.php';

if (empty($_SESSION['role']) || !in_array((string)$_SESSION['role'], ['contributor', 'camera_contributor', 'camera'], true) || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

$userId = (int)$_SESSION['user_id'];

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function timeAgo(?string $dateTime): string {
    if (!$dateTime) return 'Just now';
    $ts = strtotime($dateTime);
    if (!$ts) return 'Just now';
    $diff = time() - $ts;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day ago';
    return date('d M Y', $ts);
}

$user = [];
$feedPosts = [];

try {
    $stmt = $pdo->prepare("SELECT camera_id, full_name, profile_photo, cover_photo, camera_location, camera_type, stream_type, created_at FROM camera_contributors WHERE camera_id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $mediaJsonCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'media_json'");
    $hasMediaJson = (bool)($mediaJsonCol && $mediaJsonCol->fetch(PDO::FETCH_ASSOC));

    $statusCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
    $hasStatus = (bool)($statusCol && $statusCol->fetch(PDO::FETCH_ASSOC));

    $selectCols = "id, category, text, media_path, media_type, created_at";
    if ($hasMediaJson) $selectCols .= ", media_json";
    if ($hasStatus) $selectCols .= ", status";

    $postStmt = $pdo->prepare("SELECT {$selectCols} FROM posts WHERE author_id = :author_id AND author_role IN ('contributor', 'camera_contributor', 'camera') ORDER BY id DESC LIMIT 30");
    $postStmt->execute(['author_id' => $userId]);
    $feedPosts = $postStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $feedPosts = [];
}

$profilePhoto = !empty($user['profile_photo']) ? '../uploads/camera/' . e($user['profile_photo']) : '../Images/default_profile.png';
$coverPhoto = !empty($user['cover_photo']) ? '../uploads/camera/' . e($user['cover_photo']) : '../Images/cover_default.jpg';
$displayName = !empty($user['full_name']) ? (string)$user['full_name'] : 'Camera Contributor';
$cameraLocation = !empty($user['camera_location']) ? (string)$user['camera_location'] : 'Unknown Location';
$cameraType = !empty($user['camera_type']) ? (string)$user['camera_type'] : 'Standard Camera';
$streamType = !empty($user['stream_type']) ? (string)$user['stream_type'] : 'Live Stream';

$totalFeeds = count($feedPosts);
$liveReadyCount = 0;
foreach ($feedPosts as $post) {
    $mediaPath = (string)($post['media_path'] ?? '');
    if ($mediaPath !== '') $liveReadyCount++;
}

$latestFeed = $feedPosts[0] ?? null;
$latestMedia = '';
$latestMediaType = '';
$latestText = '';
if ($latestFeed) {
    $latestMediaType = (string)($latestFeed['media_type'] ?? '');
    $latestText = (string)($latestFeed['text'] ?? '');
    $raw = (string)($latestFeed['media_path'] ?? '');
    if ($raw !== '') {
        $latestMedia = '../' . ltrim($raw, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SearchAR - Camera Contribution Feed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Private camera feed dashboard for camera contributors.">
  <link rel="icon" type="image/png" href="../Images/favicon.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/Camera_Contribution_Feed.css">
</head>
<body>
  <a href="#main" class="skip-link">Skip to main content</a>

  <nav class="navbar" role="navigation" aria-label="Main Navigation">
    <div class="navbar-logo">
      <a href="../Html/Camera_Contribution_Home.php">
        <img src="../Images/logo.png" alt="SearchAR Logo" class="navbar-logo-img" id="logo" />
      </a>
    </div>
  </nav>

  <main class="container" id="main">
    <header>
      <div class="header-top">
        <div>
          <h1 class="main-title">My Camera Feed</h1>
          <p class="subtitle">Only you can see this page and your own camera feed data.</p>
        </div>
        <button class="back-btn" aria-label="Go back" onclick="window.location.href='../Html/Camera_Contribution_Home.php'">
          <span aria-hidden="true">←</span> Back
        </button>
      </div>
    </header>

    <section class="user-card" aria-label="Your contribution summary">
      <div class="user-info">
        <img src="<?= $profilePhoto ?>" alt="<?= e($displayName) ?> profile photo" class="user-img">
        <div>
          <span class="user-name"><?= e($displayName) ?></span>
          <span class="user-role">Camera Contributor</span>
          <div class="user-stats">
            <span><?= (int)$totalFeeds ?> <small>Total Feeds</small></span>
            <span><?= (int)$liveReadyCount ?> <small>Live Ready</small></span>
            <span><?= e($cameraType) ?> <small>Camera Type</small></span>
          </div>
        </div>
      </div>
      <div class="user-side">
        <span class="side-title">Location</span>
        <span class="side-value"><?= e($cameraLocation) ?></span>
        <span class="side-meta"><?= e($streamType) ?></span>
      </div>
    </section>

    <section class="cameras" aria-label="Your feed posts">
      <?php if (empty($feedPosts)): ?>
        <div class="empty-state">
          <h3>No feed posts yet</h3>
          <p>Your uploaded camera posts will appear here.</p>
        </div>
      <?php else: ?>
        <?php foreach ($feedPosts as $post): ?>
          <?php
            $mediaType = (string)($post['media_type'] ?? '');
            $mediaPath = (string)($post['media_path'] ?? '');
            $mediaUrl = $mediaPath !== '' ? ('../' . ltrim($mediaPath, '/')) : '';
            $status = (string)($post['status'] ?? 'pending');
            $statusClass = strtolower($status) === 'approved' ? 'status-approved' : (strtolower($status) === 'rejected' ? 'status-rejected' : 'status-pending');
          ?>
          <article class="camera-card">
            <?php if ($mediaUrl !== '' && $mediaType === 'video'): ?>
              <div class="camera-video-wrap">
                <video class="camera-video" controls preload="metadata">
                  <source src="<?= e($mediaUrl) ?>" type="video/mp4">
                </video>
              </div>
            <?php elseif ($mediaUrl !== ''): ?>
              <img src="<?= e($mediaUrl) ?>" alt="Feed media" class="camera-img">
            <?php else: ?>
              <img src="<?= $coverPhoto ?>" alt="Camera cover" class="camera-img">
            <?php endif; ?>

            <span class="feed-status <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span>

            <div class="camera-info">
              <span class="camera-title"><?= e(ucfirst((string)($post['category'] ?? 'general'))) ?> Feed</span>
              <span class="camera-time"><i class="fa-regular fa-clock"></i> <?= e(timeAgo((string)($post['created_at'] ?? ''))) ?></span>
              <?php if (!empty($post['text'])): ?>
                <p class="camera-caption"><?= nl2br(e((string)$post['text'])) ?></p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="broadcast" aria-label="Latest feed broadcast">
      <div class="broadcast-video">
        <div class="video-header">
          <span class="broadcast-title">Latest Feed Preview</span>
        </div>

        <?php if ($latestMedia !== '' && $latestMediaType === 'video'): ?>
          <div class="video-player">
            <video class="video-preview" controls preload="metadata">
              <source src="<?= e($latestMedia) ?>" type="video/mp4">
            </video>
          </div>
        <?php elseif ($latestMedia !== ''): ?>
          <div class="video-player">
            <img src="<?= e($latestMedia) ?>" alt="Latest feed" class="video-img">
          </div>
        <?php else: ?>
          <div class="video-player no-preview">No media preview available</div>
        <?php endif; ?>

        <div class="ai-title">Feed Summary</div>
        <button class="secondary-btn" type="button" aria-label="Latest post info">
          <span><?= $latestFeed ? e(timeAgo((string)($latestFeed['created_at'] ?? ''))) : 'No post yet' ?></span>
          <span class="badge"><?= (int)$totalFeeds ?></span>
        </button>
        <?php if ($latestText !== ''): ?>
          <p class="latest-caption"><?= nl2br(e($latestText)) ?></p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="../javascrpit/Camera_Contribution_Feed.js"></script>
</body>
</html>
