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

  function hourlyRateByCameraCount(int $cameraCount): int {
    if ($cameraCount <= 1) return 20;
    if ($cameraCount === 2) return 15;
    if ($cameraCount === 3) return 10;
    return 5;
  }

  function truncateText(string $value, int $limit = 120): string {
    $value = trim($value);
    if (mb_strlen($value) <= $limit) return $value;
    return mb_substr($value, 0, $limit - 3) . '...';
  }

  function accruedSeconds(array $feed): int {
    $acc = (int)($feed['accumulated_seconds'] ?? 0);
    $isActive = (int)($feed['is_active'] ?? 0) === 1;
    if (!$isActive) {
      return max(0, $acc);
    }

    $startRaw = (string)($feed['active_started_at'] ?? '');
    if ($startRaw === '') {
      $startRaw = (string)($feed['created_at'] ?? '');
    }
    $startTs = strtotime($startRaw ?: 'now');
    if (!$startTs) {
      return max(0, $acc);
    }
    return max(0, $acc + max(0, time() - $startTs));
  }

$user = [];
$feedPosts = [];
$cctvFeeds = [];

try {
    $stmt = $pdo->prepare("SELECT camera_id, full_name, email, profile_photo, cover_photo, city, street, country, camera_type, created_at FROM camera_contributors WHERE camera_id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $pdo->exec("CREATE TABLE IF NOT EXISTS camera_cctv_feeds (
      feed_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      camera_id INT UNSIGNED NOT NULL,
      feed_label VARCHAR(150) NOT NULL,
      feed_type VARCHAR(20) NOT NULL DEFAULT 'live',
      stream_scope VARCHAR(20) NOT NULL DEFAULT 'private',
      live_url VARCHAR(1200) DEFAULT NULL,
      video_path VARCHAR(600) DEFAULT NULL,
      camera_location VARCHAR(255) DEFAULT NULL,
      streaming_hours VARCHAR(80) DEFAULT 'continuous',
      allow_ai_detection TINYINT(1) NOT NULL DEFAULT 0,
      allow_public_viewing TINYINT(1) NOT NULL DEFAULT 0,
      ai_alerts_to_volunteers TINYINT(1) NOT NULL DEFAULT 0,
      accumulated_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
      active_started_at DATETIME DEFAULT NULL,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (feed_id),
      KEY idx_camera_active (camera_id, is_active),
      KEY idx_camera_created (camera_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $scopeCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'stream_scope'");
    if (!$scopeCol || !$scopeCol->fetch(PDO::FETCH_ASSOC)) {
      $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN stream_scope VARCHAR(20) NOT NULL DEFAULT 'private' AFTER feed_type");
    }

    $accCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'accumulated_seconds'");
    if (!$accCol || !$accCol->fetch(PDO::FETCH_ASSOC)) {
      $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN accumulated_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ai_alerts_to_volunteers");
    }

    $startedCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'active_started_at'");
    if (!$startedCol || !$startedCol->fetch(PDO::FETCH_ASSOC)) {
      $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN active_started_at DATETIME DEFAULT NULL AFTER accumulated_seconds");
    }

    $cctvStmt = $pdo->prepare("SELECT feed_id, feed_label, feed_type, stream_scope, live_url, video_path, camera_location, streaming_hours, allow_ai_detection, allow_public_viewing, ai_alerts_to_volunteers, accumulated_seconds, active_started_at, is_active, created_at FROM camera_cctv_feeds WHERE camera_id = :camera_id ORDER BY feed_id DESC LIMIT 40");
    $cctvStmt->execute(['camera_id' => $userId]);
    $cctvFeeds = $cctvStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

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

$profilePhoto = !empty($user['profile_photo']) ? '../uploads/camera/' . e($user['profile_photo']) : '../Images/demo_pic/profile.jpg';
$coverPhoto = !empty($user['cover_photo']) ? '../uploads/camera/' . e($user['cover_photo']) : '../Images/cover_default.jpg';
$displayName = !empty($user['full_name']) ? (string)$user['full_name'] : 'Camera Contributor';
$emailText = !empty($user['email']) ? (string)$user['email'] : 'Guest';
$profileLocationParts = array_filter([
  (string)($user['city'] ?? ''),
  (string)($user['street'] ?? ''),
  (string)($user['country'] ?? ''),
]);
$cameraLocation = !empty($profileLocationParts) ? implode(', ', $profileLocationParts) : 'Unknown Location';
$cameraLocation = truncateText($cameraLocation, 140);
$streamType = 'Live Stream';

$totalFeeds = count($cctvFeeds);
$liveReadyCount = 0;
$totalEarnings = 0.0;
$runningHourlyRate = 0;
foreach ($cctvFeeds as $feed) {
  if ((int)($feed['is_active'] ?? 0) === 1) {
    $liveReadyCount++;
  }
}

$ratePerCamera = hourlyRateByCameraCount($liveReadyCount);

foreach ($cctvFeeds as $feed) {
  $isActive = (int)($feed['is_active'] ?? 0) === 1;
  $mediaType = (string)($feed['feed_type'] ?? 'live');
  $hasStream = false;
  if ($mediaType === 'live') {
    $hasStream = trim((string)($feed['live_url'] ?? '')) !== '';
  } else {
    $hasStream = trim((string)($feed['video_path'] ?? '')) !== '';
  }
  $rate = ($isActive && $hasStream) ? $ratePerCamera : 0;
  $earned = (accruedSeconds($feed) / 3600) * $rate;
  $totalEarnings += $earned;
}

$runningHourlyRate = $liveReadyCount * $ratePerCamera;

$nextPayoutSeconds = null;
foreach ($cctvFeeds as $feed) {
  if ((int)($feed['is_active'] ?? 0) !== 1) {
    continue;
  }
  $acc = accruedSeconds($feed);
  $remaining = 1800 - ($acc % 1800);
  if ($remaining === 1800) {
    $remaining = 0;
  }
  if ($nextPayoutSeconds === null || $remaining < $nextPayoutSeconds) {
    $nextPayoutSeconds = $remaining;
  }
}

if ($liveReadyCount === 0) {
  $nextPayoutSeconds = null;
}

$streamType = $liveReadyCount > 0 ? 'Live Stream' : 'Recorded Feed';

$latestFeed = $cctvFeeds[0] ?? null;
$latestMedia = '';
$latestMediaType = '';
$latestText = '';
if ($latestFeed) {
  $latestMediaType = (string)($latestFeed['feed_type'] ?? '');
  $latestText = (string)($latestFeed['feed_label'] ?? '');
  $raw = (string)($latestFeed['video_path'] ?? '');
  if ($latestMediaType === 'recorded' && $raw !== '') {
    $latestMedia = '../' . ltrim($raw, '/');
  } elseif ($latestMediaType === 'live') {
    $latestMedia = (string)($latestFeed['live_url'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Searchar - Camera Contribution Feed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Private camera feed dashboard for camera contributors.">
  <link rel="icon" type="image/png" href="../Images/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/Camera_Contribution_Feed.css">
  <link rel="stylesheet" href="../css/button_theme_shared.css?v=20260503a">
</head>
<body>
  <nav class="navbar" role="navigation" aria-label="Main Navigation">
    <div class="navbar-logo">
      <a href="../Html/Camera_Contribution_Home.php">
        <img src="../Images/logo.png" alt="SearchAR Logo" class="navbar-logo-img" id="logo" />
      </a>
    </div>
    <div class="navbar-user-actions">
      <button class="navbar-donate" type="button" onclick="window.location.href='../Php/logout.php';">
        LOG OUT
        <img src="../Images/import.gif" alt="Logout" class="logout-gif">
      </button>
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
          <span aria-hidden="true">&larr;</span> Back
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
            <span>&#2547;<?= e(number_format($totalEarnings, 2)) ?> <small>Total Earnings</small></span>
            <span>&#2547;<?= (int)$runningHourlyRate ?>/hr <small>Running Rate</small></span>
          </div>
        </div>
      </div>
      <div class="user-side">
        <span class="side-title">Location</span>
        <span class="side-value"><?= e($cameraLocation) ?></span>
        <span class="side-meta"><?= e($streamType) ?></span>
      </div>
    </section>

    <section class="payout-panel" aria-label="Payout summary">
      <div class="payout-head">
        <h2>Stream Earnings Summary</h2>
        <p>Key payout and stream details are separated for quick reading.</p>
      </div>
      <div class="payout-grid">
        <div class="payout-card">
          <span class="payout-label">Next payout in</span>
          <span class="payout-value" id="payoutCountdown" data-next-payout="<?= e($nextPayoutSeconds !== null ? (string)$nextPayoutSeconds : '') ?>">--:--</span>
        </div>
        <div class="payout-card">
          <span class="payout-label">Stream type</span>
          <span class="payout-value"><?= e($streamType) ?></span>
        </div>
        <div class="payout-card">
          <span class="payout-label">Running rate</span>
          <span class="payout-value">&#2547;<?= (int)$runningHourlyRate ?>/hr</span>
        </div>
      </div>
    </section>

    <section class="cameras" aria-label="Your CCTV sources">
      <?php if (empty($cctvFeeds)): ?>
        <div class="empty-state">
          <h3>No CCTV source yet</h3>
          <p>Add camera source from Camera Home (Start Live / Upload Recorded Feed).</p>
        </div>
      <?php else: ?>
        <?php foreach ($cctvFeeds as $feed): ?>
          <?php
            $mediaType = (string)($feed['feed_type'] ?? 'live');
            $mediaPath = (string)($feed['video_path'] ?? '');
            $mediaUrl = $mediaPath !== '' ? ('../' . ltrim($mediaPath, '/')) : '';
            $liveUrl = (string)($feed['live_url'] ?? '');
            $scope = (string)($feed['stream_scope'] ?? 'private');
            $isActive = (int)($feed['is_active'] ?? 0) === 1;
            $statusText = $isActive ? 'Active' : 'Closed';
            $statusClass = $isActive ? 'status-approved' : 'status-rejected';
            $isActive = (int)($feed['is_active'] ?? 0) === 1;
            $hasStream = $mediaType === 'live' ? ($liveUrl !== '') : ($mediaPath !== '');
            $rate = ($isActive && $hasStream) ? $ratePerCamera : 0;
            $earned = (accruedSeconds($feed) / 3600) * $rate;
          ?>
          <article class="camera-card">
            <?php if ($mediaType === 'recorded' && $mediaUrl !== ''): ?>
              <div class="camera-video-wrap">
                <video class="camera-video" controls preload="metadata">
                  <source src="<?= e($mediaUrl) ?>" type="video/mp4">
                </video>
              </div>
            <?php elseif ($mediaType === 'live' && $liveUrl !== ''): ?>
              <div class="camera-video-wrap">
                <video class="camera-video live-video" controls muted playsinline preload="metadata" data-live-src="<?= e($liveUrl) ?>" title="<?= e((string)($feed['feed_label'] ?? 'Live CCTV')) ?>"></video>
              </div>
            <?php else: ?>
              <img src="<?= $coverPhoto ?>" alt="Camera cover" class="camera-img">
            <?php endif; ?>

            <span class="feed-status <?= e($statusClass) ?>"><?= e($statusText) ?></span>

            <div class="camera-info">
              <span class="camera-title"><?= e((string)($feed['feed_label'] ?? 'CCTV Feed')) ?></span>
              <span class="camera-time"><i class="fa-regular fa-clock"></i> <?= e(timeAgo((string)($feed['created_at'] ?? ''))) ?></span>
              <p class="camera-caption"><?= e((string)($feed['camera_location'] ?? 'Location not set')) ?></p>
              <p class="camera-earning">Earned: &#2547;<?= e(number_format($earned, 2)) ?> &middot; Rate: <?= ($isActive && $hasStream) ? ('&#2547;' . (int)$rate . '/hr') : ($isActive ? 'No stream' : 'Paused') ?> &middot; <?= e(ucfirst($scope)) ?></p>
              <div class="feed-controls">
                <form method="post" action="../Php/camera_cctv_feeds.php">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="feed_id" value="<?= (int)($feed['feed_id'] ?? 0) ?>">
                  <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                  <input type="hidden" name="return_to" value="../Html/Camera_Contribution_Feed.php">
                  <button type="submit" class="feed-action-btn"><?= $isActive ? 'Close CCTV' : 'Reopen CCTV' ?></button>
                </form>
                <form method="post" action="../Php/camera_cctv_feeds.php" onsubmit="return confirm('Remove this CCTV source?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="feed_id" value="<?= (int)($feed['feed_id'] ?? 0) ?>">
                  <input type="hidden" name="return_to" value="../Html/Camera_Contribution_Feed.php">
                  <button type="submit" class="feed-action-btn danger">Remove</button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/hls.js@1"></script>
  <script src="../javascrpit/Camera_Contribution_Feed.js"></script>
</body>
</html>


