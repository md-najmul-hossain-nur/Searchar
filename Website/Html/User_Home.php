<?php


declare(strict_types=1);
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../Php/db.php'; // adjust path if necessary

// User home must only allow authenticated normal users
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit();
}

$role = 'user';
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
  $sql = "SELECT {$id_col}, full_name, email, mobile, nid_number, profile_photo, bio, cover_photo, date_of_birth, gender, street, city, country, latitude, longitude
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

  function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
      notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      recipient_entity VARCHAR(60) NOT NULL,
      recipient_id INT UNSIGNED NOT NULL,
      title VARCHAR(190) NOT NULL,
      message TEXT NOT NULL,
      level VARCHAR(30) NOT NULL DEFAULT 'info',
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (notification_id),
      INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  }

  function buildUserImagePath(?string $fileName, string $demoFallback): string {
    $file = trim((string)($fileName ?? ''));
    if ($file !== '') {
      return '../uploads/user/' . $file;
    }
    return $demoFallback;
  }

  function isUserProfileComplete(array $row): bool {
    $required = ['date_of_birth', 'gender', 'city', 'country'];
    foreach ($required as $key) {
      if (trim((string)($row[$key] ?? '')) === '') {
        return false;
      }
    }

    $street = trim((string)($row['street'] ?? ''));
    $lat = trim((string)($row['latitude'] ?? ''));
    $lng = trim((string)($row['longitude'] ?? ''));

    // Treat address as complete if street exists OR map coordinates are saved.
    return $street !== '' || ($lat !== '' && $lng !== '');
  }

  $demoProfilePath = '../Images/demo_pic/profile.jpg';
  $demoCoverPath = '../Images/demo_pic/cover.jpg';

  $profileImagePath = buildUserImagePath((string)($user['profile_photo'] ?? ''), $demoProfilePath);
  $coverImagePath = buildUserImagePath((string)($user['cover_photo'] ?? ''), $demoCoverPath);

  $missingProfileParts = [];
  if (trim((string)($user['date_of_birth'] ?? '')) === '') $missingProfileParts[] = 'date of birth';
  if (trim((string)($user['gender'] ?? '')) === '') $missingProfileParts[] = 'gender';
  if (trim((string)($user['street'] ?? '')) === '' && (trim((string)($user['latitude'] ?? '')) === '' || trim((string)($user['longitude'] ?? '')) === '')) $missingProfileParts[] = 'street address or map location';
  if (trim((string)($user['city'] ?? '')) === '') $missingProfileParts[] = 'city';
  if (trim((string)($user['country'] ?? '')) === '') $missingProfileParts[] = 'country';

  $isProfileIncomplete = !isUserProfileComplete($user);
  $profileMissingLabel = implode(', ', $missingProfileParts);

  $volunteerProfileMissingParts = [];
  if (trim((string)($user['full_name'] ?? '')) === '') $volunteerProfileMissingParts[] = 'full name';
  if (trim((string)($user['email'] ?? '')) === '') $volunteerProfileMissingParts[] = 'email';
  if (trim((string)($user['mobile'] ?? '')) === '') $volunteerProfileMissingParts[] = 'mobile';
  if (trim((string)($user['nid_number'] ?? '')) === '') $volunteerProfileMissingParts[] = 'NID number';
  if (trim((string)($user['date_of_birth'] ?? '')) === '') $volunteerProfileMissingParts[] = 'date of birth';
  if (trim((string)($user['gender'] ?? '')) === '') $volunteerProfileMissingParts[] = 'gender';
  if (trim((string)($user['street'] ?? '')) === '' && (trim((string)($user['latitude'] ?? '')) === '' || trim((string)($user['longitude'] ?? '')) === '')) $volunteerProfileMissingParts[] = 'street address or map location';
  if (trim((string)($user['city'] ?? '')) === '') $volunteerProfileMissingParts[] = 'city';
  if (trim((string)($user['country'] ?? '')) === '') $volunteerProfileMissingParts[] = 'country';

  $isVolunteerProfileReady = count($volunteerProfileMissingParts) === 0;
  $volunteerProfileMissingLabel = implode(', ', $volunteerProfileMissingParts);
  $volunteerMobileDisplay = trim((string)($user['mobile'] ?? ''));
  if ($volunteerMobileDisplay === '') {
    $volunteerMobileDisplay = 'Not added yet. Complete profile first.';
  }

  try {
    ensureNotificationsTable($pdo);

    if ($isProfileIncomplete) {
      $existsReminder = $pdo->prepare("SELECT notification_id
        FROM user_notifications
        WHERE recipient_entity IN ('user', 'users')
          AND recipient_id = :id
          AND title = 'Admin Reminder'
          AND message LIKE '%complete your profile%'
        LIMIT 1");
      $existsReminder->execute([':id' => $user_id]);

      if (!$existsReminder->fetchColumn()) {
        $insReminder = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read) VALUES (:entity, :id, :title, :message, :level, 0)');
        $insReminder->execute([
          ':entity' => 'user',
          ':id' => $user_id,
          ':title' => 'Admin Reminder',
          ':message' => 'Please complete your profile from Edit Profile. Missing: ' . $profileMissingLabel . '. (Profile and cover photo are recommended.)',
          ':level' => 'warning',
        ]);
      }
    } else {
      $deleteReminder = $pdo->prepare("DELETE FROM user_notifications
        WHERE recipient_entity IN ('user', 'users')
          AND recipient_id = :id
          AND title = 'Admin Reminder'
          AND message LIKE '%complete your profile%'");
      $deleteReminder->execute([':id' => $user_id]);

      $existsThanks = $pdo->prepare("SELECT notification_id
        FROM user_notifications
        WHERE recipient_entity IN ('user', 'users')
          AND recipient_id = :id
          AND title = 'Admin Thanks'
        LIMIT 1");
      $existsThanks->execute([':id' => $user_id]);

      if (!$existsThanks->fetchColumn()) {
        $insThanks = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read) VALUES (:entity, :id, :title, :message, :level, 0)');
        $insThanks->execute([
          ':entity' => 'user',
          ':id' => $user_id,
          ':title' => 'Admin Thanks',
          ':message' => 'Thanks for completing your profile. Your account is now fully ready for emergency reporting.',
          ':level' => 'info',
        ]);
      }
    }
  } catch (Throwable $e) {
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

function normalizeBrokenUtf8(?string $text): string {
  $value = (string)($text ?? '');
  if ($value === '') {
    return '';
  }

  // Fix common mojibake when UTF-8 bytes were decoded as latin1/cp1252.
  if (preg_match('/Ã°Å¸|Ãƒ.|Ã¢.|Ã¯Â¸|Ã‚./u', $value) !== 1) {
    return $value;
  }

  $fixed = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
  return is_string($fixed) && $fixed !== '' ? $fixed : $value;
}

function isPlaceholderBio(string $text): bool {
  $normalized = strtolower(trim($text));
  if ($normalized === '') {
    return true;
  }

  // Match old and new placeholder variants regardless of emoji/mojibake.
  return str_contains($normalized, 'add your bio in your profile so everyone knows a little about you')
    || str_contains($normalized, 'tell people a little about yourself by adding a bio in your profile');
}

function ensureVolunteerApplicationsTable(PDO $pdo): void {
  $pdo->exec("CREATE TABLE IF NOT EXISTS volunteer_applications (
    application_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    mobile VARCHAR(30) DEFAULT NULL,
    nid_number VARCHAR(100) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    country VARCHAR(120) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    reviewed_by VARCHAR(100) DEFAULT NULL,
    review_note VARCHAR(255) DEFAULT NULL,
    volunteer_id INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (application_id),
    UNIQUE KEY uq_volunteer_application_user (user_id),
    KEY idx_volunteer_application_status (status),
    KEY idx_volunteer_application_created (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$bioRaw = (string)($user['bio'] ?? '');
$bioText = trim(normalizeBrokenUtf8($bioRaw));
if (isPlaceholderBio($bioText)) {
  $bioText = '';
}

// Defensive fallback: re-fetch bio directly from users table if first row returned empty bio.
if ($bioText === '' && $user_id > 0) {
  try {
    $bioStmt = $pdo->prepare('SELECT bio FROM users WHERE user_id = :id LIMIT 1');
    $bioStmt->execute(['id' => $user_id]);
    $bioDb = (string)($bioStmt->fetchColumn() ?: '');
    $bioText = trim(normalizeBrokenUtf8($bioDb));
    if (isPlaceholderBio($bioText)) {
      $bioText = '';
    }
  } catch (Throwable $e) {
    // Keep existing fallback text path.
  }
}

$volunteerApplyStatus = 'not_applied';
$volunteerApplyNote = '';
$volunteerCanSwitch = false;
$volunteerStatusText = 'Not applied yet';

try {
  ensureVolunteerApplicationsTable($pdo);

  $lookupEmail = trim((string)($user['email'] ?? ''));
  $lookupMobile = trim((string)($user['mobile'] ?? ''));
  $lookupNid = trim((string)($user['nid_number'] ?? ''));

  if ($lookupEmail !== '' || $lookupMobile !== '' || $lookupNid !== '') {
    $volExists = $pdo->prepare('SELECT volunteer_id FROM volunteers WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1');
    $volExists->execute([
      ':email' => $lookupEmail,
      ':mobile' => $lookupMobile,
      ':nid' => $lookupNid,
    ]);
    $existingVolunteerId = (int)($volExists->fetchColumn() ?: 0);
    if ($existingVolunteerId > 0) {
      $volunteerApplyStatus = 'approved';
      $volunteerCanSwitch = true;
      $volunteerStatusText = 'Approved';
    }
  }

  if (!$volunteerCanSwitch) {
    $appStmt = $pdo->prepare('SELECT status, review_note FROM volunteer_applications WHERE user_id = :uid LIMIT 1');
    $appStmt->execute([':uid' => $user_id]);
    $appRow = $appStmt->fetch(PDO::FETCH_ASSOC);
    if ($appRow) {
      $status = strtolower(trim((string)($appRow['status'] ?? 'pending')));
      if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $volunteerApplyStatus = $status;
      }
      $volunteerApplyNote = trim((string)($appRow['review_note'] ?? ''));
      if ($volunteerApplyStatus === 'approved') {
        $volunteerCanSwitch = true;
      }
    }

    if ($volunteerApplyStatus === 'approved') {
      $volunteerStatusText = 'Approved';
    } elseif ($volunteerApplyStatus === 'pending') {
      $volunteerStatusText = 'Pending admin approval';
    } elseif ($volunteerApplyStatus === 'rejected') {
      $volunteerStatusText = 'Rejected (you can apply again)';
    }
  }
} catch (Throwable $e) {
  $volunteerApplyStatus = 'not_applied';
  $volunteerCanSwitch = false;
  $volunteerStatusText = 'Not applied yet';
}

$showComboVolunteerBadge = ($volunteerCanSwitch || $volunteerApplyStatus === 'approved');

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
  if (strtolower(trim($authorRole)) === 'admin') {
    return '../Images/businessman.gif';
  }

  static $roleMap = [
    'user' => ['table' => 'users', 'id_col' => 'user_id'],
    'police' => ['table' => 'policemen', 'id_col' => 'police_id'],
    'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id'],
    'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id'],
  ];
  static $cache = [];

  $cacheKey = $authorRole . ':' . $authorId;
  if (isset($cache[$cacheKey])) {
    return $cache[$cacheKey];
  }

  if (!isset($roleMap[$authorRole]) || $authorId <= 0) {
    return $cache[$cacheKey] = '../Images/demo_pic/profile.jpg';
  }

  $table = $roleMap[$authorRole]['table'];
  $idCol = $roleMap[$authorRole]['id_col'];

  try {
    $stmt = $pdo->prepare("SELECT profile_photo FROM {$table} WHERE {$idCol} = :id LIMIT 1");
    $stmt->execute(['id' => $authorId]);
    $photo = (string)($stmt->fetchColumn() ?: '');
    if ($photo !== '') {
      return $cache[$cacheKey] = '../uploads/user/' . e($photo);
    }
  } catch (Exception $e) {
    // fall through to default image
  }

  return $cache[$cacheKey] = '../Images/demo_pic/profile.jpg';
}

$posts = [];
try {
  $hasMediaJson = false;
  $hasStatus = false;
  $hasShareAnonymous = false;

  $mediaJsonCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'media_json'");
  if ($mediaJsonCol && $mediaJsonCol->fetch(PDO::FETCH_ASSOC)) {
    $hasMediaJson = true;
  }

  $statusCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
  if ($statusCol && $statusCol->fetch(PDO::FETCH_ASSOC)) {
    $hasStatus = true;
  }

  $shareAnonCol = $pdo->query("SHOW COLUMNS FROM posts LIKE 'share_anonymous'");
  if ($shareAnonCol && $shareAnonCol->fetch(PDO::FETCH_ASSOC)) {
    $hasShareAnonymous = true;
  }

  $selectCols = "id, author_role, author_id, author_name, category, text, media_path, media_type, created_at";
  if ($hasMediaJson) {
    $selectCols .= ", media_json";
  }
  if ($hasShareAnonymous) {
    $selectCols .= ", share_anonymous";
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

// Now render a minimal HTML page â€” integrate this into your full template as needed.
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
  <link rel="stylesheet" href="../css/User_Home.css?v=20260406e">

</head>
<body data-current-user-name="<?= e($user['full_name'] ?? 'User') ?>" data-profile-incomplete="<?= $isProfileIncomplete ? '1' : '0' ?>" data-profile-missing="<?= e($profileMissingLabel) ?>" data-volunteer-ready="<?= $isVolunteerProfileReady ? '1' : '0' ?>" data-volunteer-missing="<?= e($volunteerProfileMissingLabel) ?>">
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




    <div class="container">
    <!-- Left Sidebar -->
    <div class="sidebar-left">
      <div class="profile-card">
<img src="<?= e($coverImagePath) ?>"
  class="cover" alt="Cover Photo" onerror="this.onerror=null;this.src='../Images/default-cover.gif';">
         <!-- Profile image dynamic from DB -->
 <img src="<?= e($profileImagePath) ?>"
     class="profile-pic" 
     alt="Profile Photo" onerror="this.onerror=null;this.src='../Images/demo_pic/profile.jpg';">
     <?php $user_id = (int)$user['user_id']; ?>
      <button class="edit-btn" title="Profile " onclick="location.href='../Html/User_profile.php?user_id=<?= $user_id ?>'">Profile</button>

<h3 class="profile-name-row">
  <span><?= e($user['full_name'] ?? 'â€”') ?></span>
  <?php if ($showComboVolunteerBadge): ?>
    <span class="profile-combo-badge" title="Volunteer Plus User">
      <img src="../Images/volunteering.gif" class="profile-volunteer-badge" alt="Volunteer Plus Badge" onerror="this.closest('.profile-combo-badge').style.display='none'">
      <span>Volunteer Plus</span>
    </span>
  <?php endif; ?>
</h3>
<?php $hasUserBio = ($bioText !== ''); ?>
<p class="user-bio<?= $hasUserBio ? '' : ' is-placeholder' ?>">
  <?= $hasUserBio
    ? e($bioText)
  : "Tell people a little about yourself by adding a bio in your profile." ?>
</p>
</div>
      
    <div class="page-like">
  <h4>Contribute to Save Lives</h4>
  <p class="contribution-text">Support emergency response efforts by donating to verified rescue and assistance programs.</p>

  <!-- Donation Button -->
    <button class="donate-btn" type="button" onclick="openDonationPopup()">Donate Now</button>

</div>

    <div id="donationModal" class="donation-modal" aria-hidden="true">
      <div class="donation-modal-content" role="dialog" aria-modal="true" aria-labelledby="donationModalTitle">
        <button type="button" class="donation-close" aria-label="Close" onclick="closeDonationPopup()">&times;</button>
        <h3 id="donationModalTitle">Support Emergency Rescue</h3>
        <p class="donation-subtitle">Your contribution helps verified rescue operations, medical aid, and missing-person response.</p>

        <div class="donation-payment-box">
          <p class="donation-payment-title">Send Donation To</p>
          <div class="donation-payment-row">
            <strong id="donationReceiverNumber">01743094595</strong>
            <button type="button" class="donation-copy-btn" id="copyDonationNumberBtn">Copy</button>
          </div>
          <p class="donation-payment-hint">Please send money first, then enter the Transaction ID below.</p>
        </div>

        <form id="donationForm" class="donation-form">
          <label for="donationName">Full Name</label>
          <input id="donationName" name="donation_name" type="text" placeholder="Enter your name" value="<?= e($user['full_name'] ?? '') ?>" required>

          <label for="donationPhone">Mobile Number</label>
          <input id="donationPhone" name="donation_phone" type="tel" placeholder="01XXXXXXXXX" required>

          <label for="donationAmount">Amount (BDT)</label>
          <input id="donationAmount" name="donation_amount" type="number" min="50" step="50" placeholder="e.g. 500" required>

          <label for="donationTxId">Transaction ID (TxID)</label>
          <input id="donationTxId" name="donation_tx_id" type="text" placeholder="Enter payment transaction ID" required>

          <div class="donation-quick-amounts" aria-label="Quick amount selection">
            <button type="button" data-amount="200">200 BDT</button>
            <button type="button" data-amount="500">500 BDT</button>
            <button type="button" data-amount="1000">1000 BDT</button>
          </div>

          <div class="donation-actions">
            <button type="button" class="donation-cancel" onclick="closeDonationPopup()">Cancel</button>
            <button type="submit" class="donation-submit">Proceed Donation</button>
          </div>
        </form>
      </div>
    </div>



<!-- Become a Volunteer Section -->
<div class="volunteer-section">
  <h4>Become a Volunteer</h4>
  <p>Join our community and help us make a real difference in emergency response.</p>
  <p class="volunteer-note">Get verified, receive mission alerts, and support people in critical moments.</p>
  <p class="volunteer-status volunteer-status-<?= e($volunteerApplyStatus) ?>">Status: <?= e($volunteerStatusText) ?></p>
  <?php if ($volunteerCanSwitch): ?>
    <button class="volunteer-btn" type="button" disabled>Volunteer Features Enabled In This User Account</button>
    <p class="volunteer-note">No need to switch mode. You will receive volunteer mission notifications and tasks here.</p>
  <?php elseif ($volunteerApplyStatus === 'pending'): ?>
    <button class="volunteer-btn" type="button" disabled>Pending Approval</button>
  <?php elseif (!$isVolunteerProfileReady): ?>
    <button class="volunteer-btn" type="button" onclick="window.location.href='../Html/User_Edit_profile.php'">Complete Profile First</button>
    <p class="volunteer-note">To apply as volunteer, first complete: <?= e($volunteerProfileMissingLabel ?: 'your profile details') ?>.</p>
  <?php else: ?>
    <button class="volunteer-btn" type="button" onclick="openVolunteerApplyModal()">Apply as Volunteer</button>
  <?php endif; ?>
  <?php if ($volunteerApplyStatus === 'rejected' && $volunteerApplyNote !== ''): ?>
    <p class="volunteer-review-note">Admin note: <?= e($volunteerApplyNote) ?></p>
  <?php endif; ?>

  <?php if ($volunteerCanSwitch): ?>
    <div id="comboMissionsPanel" class="combo-missions-panel">
      <h5>Your Volunteer Missions (Plus)</h5>
      <div id="comboRankCard" class="combo-rank-card">
        <h6>Volunteer Rank &amp; Missions</h6>
        <div class="combo-rank-top">
          <strong id="comboRankTitle">Bronze Volunteer</strong>
          <span id="comboRankXp">0 XP</span>
        </div>
        <div class="combo-rank-progress-row">
          <span id="comboRankProgressText">0%</span>
          <span id="comboRankNext">Next: Silver Responder</span>
        </div>
        <div class="combo-rank-progress-track" aria-hidden="true">
          <div id="comboRankProgressBar" class="combo-rank-progress-bar" style="width: 0%;"></div>
        </div>
        <p id="comboRankNeed" class="combo-rank-need">Need 380 XP</p>
        <div class="combo-rank-tiers" aria-hidden="true">
          <span>Bronze</span>
          <span>Silver</span>
          <span>Gold</span>
          <span>Platinum</span>
        </div>
        <p id="comboMissionStats" class="combo-mission-stats">Accepted 0 â€¢ Completed 0 â€¢ Busy 0</p>
        <p class="combo-xp-rules">+10 XP (Accept) â€¢ +20 XP (Complete) â€¢ +2 XP (Auto-close by Police)</p>
      </div>
      <button class="view-missions-btn" type="button" onclick="openMissionModal()">ðŸ“‹ View Missions</button>
      <div id="comboMissionsList" class="combo-missions-list">
        <p class="combo-missions-empty">Loading missions...</p>
      </div>

      <div id="comboCertificateUnlock" class="certificate-box hidden" aria-live="polite" data-volunteer-name="<?= e($user['full_name'] ?? 'Volunteer') ?>">
        <p id="comboCertificateMessage">ðŸŽ‰ Congratulations! Youâ€™ve reached <strong>Silver Responder</strong>! Certificate unlocked.</p>
        <div class="certificate-actions">
          <button id="comboViewCertificateBtn" class="view-certificate-btn" type="button">ðŸ… Certificate</button>
        </div>
      </div>

      <div id="volunteerMissionModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="missionModalTitle" tabindex="-1">
        <div class="modal-content">
          <button class="close" onclick="closeMissionModal()" aria-label="Close modal">&times;</button>
          <h3 id="missionModalTitle">ðŸ§­ Missions for Your Rank</h3>

          <ul class="mission-list">
            <li id="mission-assigned-single" class="mission-step">
              <strong>ðŸš¨ Admin Assigned Cases</strong><br>
              These are the missions assigned by admin for your volunteer rank.
              <div id="mission-assigned-list" class="mission-assigned-list"></div>
              <p id="mission-assigned-empty" class="mission-history-empty">No assigned mission right now.</p>
            </li>

            <li id="mission-proof-single" class="mission-step" data-step="single">
              <strong>ðŸ“¤ Mission Proof Submission</strong><br>
              Upload one proof file (image/video/pdf) for your assigned mission.<br><br>
              <label>
                Submit Proof:
                <input id="mission-proof-file" type="file" accept="image/*,video/*,application/pdf" />
              </label><br><br>
              <div id="mission-proof-preview" class="mission-preview-box"></div>
              <p id="mission-proof-status" class="mission-status-note"></p>
              <button type="button" class="submit-proof-btn" data-mission-proof-submit="1">âœ… Submit Proof</button>
            </li>

            <li id="mission-history-single" class="mission-step">
              <strong>ðŸ—‚ Completed Missions History</strong><br>
              See your previously completed missions below.
              <div id="mission-history-list" class="mission-history-list"></div>
              <p id="mission-history-empty" class="mission-history-empty">No completed mission yet.</p>
            </li>
          </ul>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div id="volunteerApplyModal" class="volunteer-apply-modal" style="display:none;" aria-hidden="true" data-profile-ready="<?= $isVolunteerProfileReady ? '1' : '0' ?>" data-profile-missing="<?= e($volunteerProfileMissingLabel) ?>">
  <div class="volunteer-apply-modal-content">
    <button type="button" class="volunteer-apply-close" onclick="closeVolunteerApplyModal()">&times;</button>
    <h3>Volunteer Application</h3>
    <p class="volunteer-apply-subtitle">Submit your request from this user account. Admin will review and approve.</p>

    <div class="volunteer-apply-user-info">
      <p><strong>Name:</strong> <?= e($user['full_name'] ?? '') ?></p>
      <p><strong>Email:</strong> <?= e($user['email'] ?? '') ?></p>
      <p><strong>Mobile:</strong> <?= e($volunteerMobileDisplay) ?></p>
    </div>

    <?php if (!$isVolunteerProfileReady): ?>
      <p class="volunteer-review-note">Complete profile first: <?= e($volunteerProfileMissingLabel ?: 'required details') ?>.</p>
    <?php endif; ?>

    <label for="volunteerApplyNote">Why do you want to volunteer? (optional)</label>
    <textarea id="volunteerApplyNote" rows="4" placeholder="Write a short note for admin review"></textarea>

    <div class="volunteer-apply-actions">
      <button type="button" class="volunteer-apply-cancel" onclick="closeVolunteerApplyModal()">Cancel</button>
      <button type="button" class="volunteer-apply-submit" onclick="submitVolunteerApplication()" <?= $isVolunteerProfileReady ? '' : 'disabled' ?>>Submit Application</button>
    </div>
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
<button id="find-hospitals" style="padding:8px 15px;background:#f05454;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">Show Nearby Hospitals</button>
<button id="find-fire" style="padding:8px 15px;background:#ff7f11;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:5px;">Show Fire Stations</button>
<button id="find-police" style="padding:8px 15px;background:#0077b6;color:white;border:none;border-radius:6px;cursor:pointer;margin-bottom:10px;">Show Police Stations</button>

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

<!-- âœ… Popup Modal -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    
    <!-- Close Button -->
    <span class="post-modal-close" onclick="closeModal()">&times;</span>

    <!-- Title -->
    <div class="post-modal-head">
      <h2 class="post-modal-title">Share Your Mood</h2>
      <p class="post-modal-subtitle">Upload photos or a video and post instantly</p>
    </div>

    <!-- âœ… Facebook Toggle -->
    <div class="facebook-toggle">
      <label class="facebook-toggle-switch">
        <input type="checkbox" id="facebookShareToggle">
        <span class="facebook-toggle-slider">
          <i class="fab fa-facebook"></i>
        </span>
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

    <!-- âœ… Textarea -->
    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>

    <!-- âœ… Post Preview (Auto-filled from clicked post) -->
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

    <!-- âœ… Media Upload Buttons -->
    <div class="post-media-options">
      <label>
        <input type="file" id="imageUpload" accept="image/*" multiple hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('imageUpload').click()">ðŸ“· Photo</button>
      </label>
      <label>
        <input type="file" id="videoUpload" accept="video/*" hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('videoUpload').click()">ðŸŽ¥ Video</button>
      </label>
    </div>
    <p class="post-media-hint">You can select up to 5 photos in one post.</p>


    <!-- âœ… Media Preview (optional preview for uploaded file) -->
    <div id="mediaPreview" class="post-media-preview"></div>

    <!-- âœ… Action Buttons -->
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
      $isAnonymous = (int)($post['share_anonymous'] ?? 0) === 1;
      $displayAuthorName = $isAnonymous ? 'Anonymous' : $postAuthorName;
      $displayAuthorPhoto = $isAnonymous ? '../Images/anonymously.gif' : $authorPhoto;
    ?>
    <div class="post" id="post-<?= $postId ?>" data-post-id="<?= $postId ?>" data-category="<?= e($postCategory) ?>" data-status="<?= e((string)($post['status'] ?? 'approved')) ?>" data-share-anonymous="<?= $isAnonymous ? '1' : '0' ?>">
      <div class="post-header">
        <img src="<?= e($displayAuthorPhoto) ?>" alt="Author Photo" onerror="this.onerror=null;this.src='../Images/demo_pic/profile.jpg';">
        <div>
          <h5><?= e($displayAuthorName) ?></h5>
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
        <video class="post-video" controls controlsList="nodownload nofullscreen noplaybackrate" disablePictureInPicture oncontextmenu="return false;" preload="metadata">
          <source src="<?= e($postMediaUrl) ?>" type="video/mp4">
          Your browser does not support video.
        </video>
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
<?php else: ?>
  <div class="post">
    <p>No published posts yet. Create a post and it will appear here.</p>
  </div>
<?php endif; ?>

  <!-- Only ONE post block should exist, not repeated. Comment modules should be closed properly and IDs/classes should be unique per post. Here is a cleaned-up, non-repeated example of a single post: -->

<div class="post" id="post-1" data-post-id="1" data-category="mission" style="display:none;">
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
  <h4>Missing Person Help Desk</h4>
  <p class="helpdesk-subtitle">Quickly report a missing person and share verified details with responders.</p>
  <div class="helpdesk-highlights">
    <span>Fast Report</span>
    <span>Photo Support</span>
    <span>Secure Data</span>
  </div>
  <button type="button" onclick="openMissingForm()">
    <img src="../Images/search.gif" alt="Love Icon" class="love-image" />
  </button>
  <p class="helpdesk-cta">Tap the icon to open the form</p>
</div>

     <div class="advert">
  <h4>Advertisement</h4>
  <div class="ad-ticker" aria-hidden="true">
    <div class="ad-ticker-track">Special Offer | City CCTV Bundle | First Aid Bootcamp | Community Safety Partner</div>
  </div>

  <article class="ad-card ad-card-primary">
    <small>Sponsored</small>
    <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_f8ba3ae7.jpg" alt="Camera plan" class="ad-thumb">
    <h5 class="ad-title-animate">Secure Home Camera Plan</h5>
    <p>Protect your area with live monitoring and instant alerts.</p>
    <a href="#!">Learn More</a>
  </article>

  <article class="ad-card">
    <small>Partner Offer</small>
    <img src="../Images/WhatsApp Image 2025-07-31 at 12.44.00_b3223d89.jpg" alt="Training offer" class="ad-thumb ad-thumb-small">
    <h5 class="ad-title-animate delay">Emergency First Aid Training</h5>
    <p>Join the weekend session and earn a verified safety badge.</p>
    <a href="#!">Book Seat</a>
  </article>
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


<!-- Missing Person Report Popup -->
<div id="missingFormModal" class="missing-modal">
  <div class="missing-modal-content">
    <span class="missing-close" onclick="closeMissingForm()">&times;</span>
    <h2>Missing Person Information Form</h2>

    <form id="missingForm" action="../Php/save_missing_person.php" method="POST" enctype="multipart/form-data">
      <!-- Section 1: Personal Details -->
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

      <!-- Section 2: Last Seen Info -->
      <h3>Last Seen Information</h3>
      <label>Last Seen Date</label>
      <input type="date" name="last_seen_date" required>
      
      <label>Last Seen Location</label>
      <input type="text" name="last_seen_location" placeholder="E.g., Dhanmondi 27, Dhaka" required>

      <label>Approximate Time</label>
      <input type="text" name="last_seen_time" placeholder="E.g., 6:30 PM">

      <!-- Section 3: Health -->
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

      <!-- Section 4: Reporter Contact -->
      <h3>Your Contact Details</h3>
      <label>Your Name</label>
      <input type="text" name="reporter_name" required>
      
      <label>Mobile Number</label>
      <input type="tel" name="reporter_mobile" required>

      <label>Relationship with Missing Person</label>
      <input type="text" name="relationship" placeholder="E.g., Father / Sister / Friend">

      <!-- Section 5: Consent -->
      <h3>Consent</h3>
      <label>
        <input type="checkbox" name="consent" value="1" required> I give permission to share this data publicly.
      </label>
      
      <div class="modal-actions">
        <button type="button" onclick="closeMissingForm()" class="cancel-btn">Cancel</button>
        <button type="submit" class="submit-btn">Submit Report</button>
      </div>
    </form>
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

<button type="button" id="messengerFab" class="messenger-fab" aria-label="Open Messenger" title="Messenger">
  <i class="fa fa-comments" aria-hidden="true"></i>
</button>
<div id="messengerBackdrop" class="messenger-backdrop" aria-hidden="true"></div>
<aside id="messengerDrawer" class="messenger-drawer" aria-hidden="true">
  <div class="messenger-drawer-header">
    <h3>Messenger</h3>
    <button type="button" id="messengerClose" class="messenger-close" aria-label="Close">&times;</button>
  </div>
  <div class="messenger-layout">
    <aside class="messenger-list">
      <div class="messenger-list-title">All</div>
      <input type="text" class="messenger-search" placeholder="Search" aria-label="Search chats">
      <div class="messenger-contact">
        <div class="avatar">
          <img src="../Images/businessman.gif" alt="Admin Logo" class="admin-avatar-img" onerror="this.onerror=null;this.src='../Images/demo_pic/profile.jpg';">
        </div>
        <div>
          <strong>Admin Desk</strong>
          <small>Announcements and updates</small>
        </div>
      </div>
    </aside>

    <section class="messenger-chat">
      <div class="messenger-chat-top">
        <div class="avatar online">
          <img src="../Images/businessman.gif" alt="Admin Logo" class="admin-avatar-img" onerror="this.onerror=null;this.src='../Images/demo_pic/profile.jpg';">
        </div>
        <div>
          <strong>Admin Desk</strong>
          <small>Active now</small>
        </div>
      </div>
      <div class="messenger-chat-feed">
        <p class="messenger-bubble support">Hi, this is Admin Desk. How can we help you today?</p>
      </div>
      <div class="messenger-composer">
        <input id="messengerInput" class="messenger-input" type="text" placeholder="Type a message..." autocomplete="off">
        <button type="button" class="messenger-send" aria-label="Send">
          <i class="fa fa-paper-plane" aria-hidden="true"></i>
        </button>
      </div>
    </section>
  </div>
</aside>

       <script>
         (function () {
           const params = new URLSearchParams(window.location.search);
           const status = params.get('missing_report');
           const msg = params.get('msg');
           if (!status) return;

           if (msg) {
             alert(msg);
           } else if (status === 'success') {
             alert('Missing person report submitted successfully.');
           } else if (status === 'error') {
             alert('Could not submit missing person report. Please try again.');
           }

           params.delete('missing_report');
           params.delete('msg');
           const cleanUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
           window.history.replaceState({}, document.title, cleanUrl);
         })();
       </script>
       <script>
         (function () {
           const body = document.body;
           if (!body || body.getAttribute('data-profile-incomplete') !== '1') {
             return;
           }

           const todayKey = 'searcharProfileReminderSeen_' + new Date().toISOString().slice(0, 10);
           if (localStorage.getItem(todayKey) === '1') {
             return;
           }

           const missing = body.getAttribute('data-profile-missing') || 'profile details';
           alert('Admin Reminder: Please complete your profile from Edit Profile. Missing: ' + missing + '.');
           localStorage.setItem(todayKey, '1');
         })();
       </script>
       <script>
         (function () {
           const params = new URLSearchParams(window.location.search);
           const status = params.get('volunteer_switch');
           if (!status) return;

           const messages = {
             unauthorized: 'Please login as user first to switch role.',
             invalid_user: 'User account is invalid. Please login again.',
             user_not_found: 'User account was not found.',
             profile_incomplete: 'Please add email, mobile, and NID in your profile before switching.',
             not_approved: 'Volunteer mode is not available yet. Wait for admin approval.',
             error: 'Could not switch to volunteer mode right now.'
           };

           if (messages[status]) {
             alert(messages[status]);
           }

           params.delete('volunteer_switch');
           const cleanUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
           window.history.replaceState({}, document.title, cleanUrl);
         })();
       </script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
      <script src="../javascrpit/User_Home.js?v=20260406b"></script>
      <script src="../javascrpit/post_interactions_shared.js?v=20260406d"></script>
    </body>

</html>
