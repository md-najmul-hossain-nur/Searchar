<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../Php/db.php';

if (
  empty($_SESSION['role']) ||
  $_SESSION['role'] !== 'police' ||
  empty($_SESSION['user_id'])
) {
  header('Location: ../Html/login.html?error=session');
  exit();
}

$user_id = (int) $_SESSION['user_id'];

try {
  $sql = "SELECT police_id AS id, full_name, email, mobile, profile_photo, cover_photo, bio, badge_id, designation, station FROM policemen WHERE police_id = :id LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(['id' => $user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  header('Location: ../Html/login.html?error=db');
  exit();
}

if (!$user) {
  session_unset();
  session_destroy();
  header('Location: ../Html/login.html?error=no_user');
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

function formatDateTimeDisplay(?string $datetime): string {
  if (!$datetime) return '—';
  try {
    $dt = new DateTime($datetime);
    return $dt->format('Y-m-d H:i');
  } catch (Exception $e) {
    return (string)$datetime;
  }
}

function normalizeCaseRef(string $value): string {
  return strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($value)) ?? '');
}

function isLikelyDummyText(string $text): bool {
  $clean = strtolower(trim($text));
  if ($clean === '') return true;

  $dummyPatterns = [
    'lorem ipsum',
    'test post',
    'dummy',
    'sample',
    'asdf',
    'dfdsf',
    'qwerty',
    'demo data',
    'case details pending',
  ];

  foreach ($dummyPatterns as $pattern) {
    if (str_contains($clean, $pattern)) {
      return true;
    }
  }

  return false;
}

function getAuthorPhoto(PDO $pdo, string $authorRole, int $authorId): string {
  if (strtolower(trim($authorRole)) === 'admin') {
    return '../Images/businessman.gif';
  }

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
    return $cache[$cacheKey] = '../Images/demo_pic/profile.jpg';
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

  return $cache[$cacheKey] = '../Images/demo_pic/profile.jpg';
}

function tableExists(PDO $pdo, string $table): bool {
  $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1");
  $stmt->execute(['table' => $table]);
  return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column LIMIT 1");
  $stmt->execute(['table' => $table, 'column' => $column]);
  return (bool)$stmt->fetchColumn();
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

$allCases = [];
$caseCounts = ['post' => 0, 'missing' => 0];
try {
  $volunteerSolvedCaseRefs = [];
  if (tableExists($pdo, 'volunteer_missions') && columnExists($pdo, 'volunteer_missions', 'case_ref')) {
    $hasResponseStatus = columnExists($pdo, 'volunteer_missions', 'response_status');
    $statusExpr = $hasResponseStatus
      ? "(LOWER(COALESCE(status, 'assigned')) = 'completed' OR LOWER(COALESCE(response_status, 'pending')) = 'completed')"
      : "LOWER(COALESCE(status, 'assigned')) = 'completed'";

    $vmStmt = $pdo->query("SELECT case_ref FROM volunteer_missions WHERE {$statusExpr} AND TRIM(COALESCE(case_ref,'')) <> ''");
    $vmRows = $vmStmt ? $vmStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($vmRows as $vmRow) {
      $ref = normalizeCaseRef((string)($vmRow['case_ref'] ?? ''));
      if ($ref !== '') {
        $volunteerSolvedCaseRefs[$ref] = true;
      }
    }
  }

  if (tableExists($pdo, 'missing_person_reports')) {
    $missingStmt = $pdo->query("SELECT report_id, full_name, gender, age, last_seen_location, status, created_at, photo_filename, reporter_mobile, mental_condition, medical_notes FROM missing_person_reports WHERE LOWER(COALESCE(status, 'open')) = 'under_review' ORDER BY report_id DESC LIMIT 100");
    $missingRows = $missingStmt ? $missingStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($missingRows as $row) {
      $caseNo = 'MP-' . str_pad((string)((int)($row['report_id'] ?? 0)), 4, '0', STR_PAD_LEFT);
      if (isset($volunteerSolvedCaseRefs[normalizeCaseRef($caseNo)])) {
        continue;
      }

      $missingLabel = (string)($row['full_name'] ?? 'Unknown') . ' • Last seen: ' . (string)($row['last_seen_location'] ?? 'Unknown');
      if (isLikelyDummyText($missingLabel)) {
        continue;
      }

      $photoFile = trim((string)($row['photo_filename'] ?? ''));
      $imageUrl = $photoFile !== '' ? ('../uploads/missing_person/' . $photoFile) : '';
      $allCases[] = [
        'case_no' => $caseNo,
        'type' => 'Missing Person',
        'details' => $missingLabel,
        'status' => (string)($row['status'] ?? 'open'),
        'source' => 'Missing Desk',
        'source_key' => 'missing',
        'image_url' => $imageUrl,
        'contact_mobile' => (string)($row['reporter_mobile'] ?? ''),
        'missing_name' => (string)($row['full_name'] ?? ''),
        'extra_details' => trim((string)($row['gender'] ?? '') . ' • Age: ' . (string)($row['age'] ?? '') . ' • Mental: ' . (string)($row['mental_condition'] ?? '') . ' • Medical: ' . (string)($row['medical_notes'] ?? '')),
        'created_at' => (string)($row['created_at'] ?? ''),
      ];
      $caseCounts['missing'] += 1;
    }
  }

  if (tableExists($pdo, 'posts')) {
    $hasReportStatus = columnExists($pdo, 'posts', 'report_status');
    $hasStatus = columnExists($pdo, 'posts', 'status');

    $statusSelect = $hasStatus ? ', status' : ", 'approved' AS status";
    $reportSelect = $hasReportStatus ? ', report_status' : ", 'not_reported' AS report_status";

    $where = $hasReportStatus
      ? "WHERE LOWER(COALESCE(report_status,'not_reported')) = 'reported'"
      : 'WHERE 1 = 0';

    if ($hasStatus) {
      $where .= " AND LOWER(COALESCE(status,'approved')) = 'approved'";
    }

    $postStmt = $pdo->query("SELECT id, case_id, category, text, media_path, media_json, media_type, created_at{$statusSelect}{$reportSelect} FROM posts {$where} ORDER BY id DESC LIMIT 80");
    $postRows = $postStmt ? $postStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($postRows as $row) {
      $caseNo = 'PT-' . str_pad((string)((int)($row['id'] ?? 0)), 4, '0', STR_PAD_LEFT);
      if (isset($volunteerSolvedCaseRefs[normalizeCaseRef($caseNo)])) {
        continue;
      }

      $categoryRaw = strtolower((string)($row['category'] ?? 'case'));
      $type = match ($categoryRaw) {
        'missing_person' => 'Missing Person',
        'criminal_found' => 'Criminal Found',
        'disaster' => 'Disaster',
        'mission' => 'Mission',
        default => ucfirst(str_replace('_', ' ', $categoryRaw)),
      };

      $statusText = (string)($row['report_status'] ?? '');
      if ($statusText === '' || strtolower($statusText) === 'not_reported') {
        $statusText = (string)($row['status'] ?? 'approved');
      }

      $imageUrl = '';
      $mediaType = strtolower((string)($row['media_type'] ?? ''));
      $mediaPath = trim((string)($row['media_path'] ?? ''));
      $mediaJson = trim((string)($row['media_json'] ?? ''));

      if ($mediaJson !== '') {
        $decodedMedia = json_decode($mediaJson, true);
        if (is_array($decodedMedia)) {
          foreach ($decodedMedia as $mediaItem) {
            $path = trim((string)$mediaItem);
            if ($path === '') continue;
            if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $path)) {
              $imageUrl = '../' . ltrim($path, '/');
              break;
            }
          }
        }
      }

      if ($imageUrl === '' && $mediaPath !== '' && ($mediaType === 'image' || preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $mediaPath))) {
        $imageUrl = '../' . ltrim($mediaPath, '/');
      }

      $detailsText = trim((string)($row['text'] ?? 'Case details pending'));
      if (isLikelyDummyText($detailsText)) {
        continue;
      }

      $allCases[] = [
        'case_no' => $caseNo,
        'type' => $type,
        'details' => $detailsText,
        'status' => $statusText,
        'source' => 'Crime Reporting',
        'source_key' => 'post',
        'image_url' => $imageUrl,
        'contact_mobile' => '',
        'missing_name' => '',
        'extra_details' => '',
        'created_at' => (string)($row['created_at'] ?? ''),
      ];
      $caseCounts['post'] += 1;
    }
  }

  usort($allCases, function (array $a, array $b): int {
    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
  });

  if (count($allCases) > 120) {
    $allCases = array_slice($allCases, 0, 120);
  }
} catch (Exception $e) {
  $allCases = [];
  $caseCounts = ['post' => 0, 'missing' => 0];
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
  <link rel="stylesheet" href="../css/Policman_Home.css?v=20260410g">
  <link rel="stylesheet" href="../css/post_modal_shared.css?v=20260409a">
  <link rel="stylesheet" href="../css/profile_button_shared.css?v=20260410a">
  <link rel="stylesheet" href="../css/notifications_shared.css">
  <link rel="stylesheet" href="../css/messenger_shared.css?v=20260410c">
  <style>
    .all-cases-table-wrap {
      border: 1px solid #e6ebf4;
      border-radius: 12px;
      overflow: auto;
      background: #fff;
    }
    .all-cases-admin-table {
      width: 100%;
      min-width: 860px;
      border-collapse: collapse;
      font-size: 13px;
      color: #1f2937;
    }
    .all-cases-admin-table thead th {
      background: #f4f7fc;
      color: #1f3b64;
      font-weight: 800;
      text-align: left;
      padding: 10px 12px;
      border-bottom: 1px solid #dbe4f0;
      position: sticky;
      top: 0;
      z-index: 1;
    }
    .all-cases-admin-table tbody td {
      padding: 10px 12px;
      border-bottom: 1px solid #edf1f7;
      vertical-align: top;
      background: #fff;
    }
    .all-cases-admin-table tbody tr:nth-child(even) td {
      background: #fbfcff;
    }
    .all-cases-admin-table tbody tr:hover td {
      background: #f6f9ff;
    }
    .all-cases-case-id {
      font-weight: 800;
      color: #274690;
      letter-spacing: .2px;
    }
    .all-cases-type-chip {
      display: inline-block;
      border-radius: 0;
      background: transparent;
      color: #374151;
      padding: 0;
      font-size: 12px;
      font-weight: 700;
    }
    .all-cases-source-chip {
      display: inline-block;
      border-radius: 0;
      padding: 0;
      font-size: 12px;
      font-weight: 700;
      background: transparent;
      border: none;
      color: #374151;
    }
    .all-cases-source-chip.post {
      color: #374151;
    }
    .all-cases-source-chip.missing {
      color: #374151;
    }
    .all-cases-details-cell {
      max-width: 340px;
      white-space: normal;
      line-height: 1.35;
      color: #374151;
    }
    .all-cases-thumb {
      width: 78px;
      height: 58px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #d1d5db;
      display: block;
      margin-bottom: 6px;
      background: #f8fafc;
    }
    .all-cases-created {
      color: #4b5563;
      white-space: nowrap;
      font-weight: 600;
    }
    .all-cases-actions {
      white-space: nowrap;
    }
    .all-cases-action-btn {
      border: none;
      border-radius: 8px;
      padding: 6px 10px;
      font-weight: 700;
      cursor: pointer;
      margin-right: 6px;
    }
    .all-cases-action-btn.preview {
      background: #e0ecff;
      color: #1e3a8a;
    }
    .all-cases-action-btn.publish {
      background: #0f766e;
      color: #fff;
    }
    .case-section-card {
      margin-top: 14px;
      padding: 14px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      background: #ffffff;
      box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
    }
    .case-section-title {
      text-align: center;
      color: #1f2937;
      margin: 0 0 8px;
      font-weight: 800;
      font-size: 22px;
    }
    .case-section-desc {
      text-align: center;
      color: #4b5563;
      margin: 0 0 12px;
      font-size: 14px;
      line-height: 1.5;
    }
    .case-section-actions {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .case-section-btn {
      border: none;
      border-radius: 8px;
      cursor: pointer;
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      padding: 10px 12px;
      transition: transform .08s ease, opacity .15s ease;
    }
    .case-section-btn:hover {
      opacity: .95;
      transform: translateY(-1px);
    }
    .case-section-btn:active {
      transform: translateY(0);
    }
    .case-section-btn.view {
      background: #1f6feb;
    }
    .case-section-btn.history {
      background: #0f766e;
    }
    .case-preview-footer {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 14px;
    }
    .case-preview-btn {
      border: none;
      border-radius: 9px;
      padding: 9px 14px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .08s ease, opacity .15s ease;
    }
    .case-preview-btn:hover {
      opacity: .95;
      transform: translateY(-1px);
    }
    .case-preview-btn:active {
      transform: translateY(0);
    }
    .case-preview-btn.cancel {
      background: #eef2f7;
      color: #1f2937;
      border: 1px solid #dbe4ee;
    }
    .case-preview-btn.publish {
      background: linear-gradient(135deg, #0f766e, #0d9488);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(13, 148, 136, 0.24);
    }
    @media (max-width: 680px) {
      .case-section-actions {
        grid-template-columns: 1fr;
      }
      .case-preview-footer {
        flex-direction: column;
      }
      .case-preview-btn {
        width: 100%;
      }
    }
  </style>
  
  <link rel="stylesheet" href="../css/button_theme_shared.css?v=20260503a">
</head>
<body data-current-user-name="<?= e($user['full_name'] ?? 'Policeman') ?>">
 <header class="navbar" style="display:flex; align-items:center; justify-content:space-between; padding:10px; position:fixed; top:0; left:0; right:0; z-index:2000; background:#fff;">
  <!-- Left: Logo -->
  <div class="navbar-logo">
    <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo" />
  </div>
  
  <!-- Right: Logout -->
  <div style="display:flex; align-items:center; gap:10px; margin-right:40px;">
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
        <img src="<?= isset($user['profile_photo']) ? '../uploads/police/' . e($user['profile_photo']) : '../Images/demo_pic/profile.jpg' ?>" class="profile-pic">
        <button class="edit-btn" title="Profile Setting" onclick="location.href='../Html/Policeman_profile.php'">Profile</button>

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
    <a href="../Html/BroadCast.php" target="_blank" class="broadcast-btn">Join Broadcast</a>
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

<div class="hospital-section case-section-card">
  <h2 class="case-section-title">Investigation Cases</h2>
  <p class="case-section-desc">Track investigation cases in one place. Open all current cases or view solved case history.</p>
  <div class="case-section-actions">
    <button id="openAllCasesBtn" type="button" class="case-section-btn view">&#128193; View All Cases</button>
    <button id="openSolvedCasesBtn" type="button" class="case-section-btn history">&#9989; Solved Case History</button>
  </div>
</div>

<div id="allCasesModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.52); z-index:4000; align-items:center; justify-content:center; padding:16px;">
  <div style="width:min(1020px,96vw); max-height:88vh; overflow:auto; background:#fff; border-radius:12px; box-shadow:0 14px 32px rgba(0,0,0,.25); padding:14px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #e5e7eb; padding-bottom:8px;">
      <h3 style="margin:0; color:#1f2937;">All Cases</h3>
      <button type="button" id="closeAllCasesBtn" style="border:none; background:#f3f4f6; width:32px; height:32px; border-radius:7px; cursor:pointer; font-size:18px;">&times;</button>
    </div>

    <div style="overflow:auto;">
      <div style="display:flex; gap:8px; flex-wrap:wrap; margin:0 0 10px;">
        <span style="background:#e0f2fe; color:#075985; border:1px solid #bae6fd; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700;">Post Cases: <?= (int)$caseCounts['post'] ?></span>
        <span style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700;">Missing Cases: <?= (int)$caseCounts['missing'] ?></span>
        <span style="background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700;">Total: <?= count($allCases) ?></span>
      </div>
      <div style="display:flex; align-items:center; gap:14px; margin:0 0 10px; padding:8px 10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
        <label style="display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#075985; font-size:13px;">
          <input type="checkbox" id="caseFilterPost" checked>
          Post Cases
        </label>
        <label style="display:inline-flex; align-items:center; gap:6px; font-weight:700; color:#92400e; font-size:13px;">
          <input type="checkbox" id="caseFilterMissing" checked>
          Missing Cases
        </label>
      </div>
      <div class="all-cases-table-wrap">
      <table class="all-cases-admin-table">
        <thead>
          <tr>
            <th>Case No</th>
            <th>Type</th>
            <th>Details</th>
            <th>Source</th>
            <th>Created</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="allCasesTableBody">
          <?php if (empty($allCases)): ?>
            <tr><td colspan="6">No cases found right now.</td></tr>
          <?php else: ?>
            <?php foreach ($allCases as $caseRow): ?>
              <?php
                $sourceKey = (string)($caseRow['source_key'] ?? 'post');
                $displayType = $sourceKey === 'missing' ? 'Missing Person' : 'Post';
                $displaySource = $sourceKey === 'missing' ? 'Missing Person' : 'Post';
              ?>
              <tr data-case-source-key="<?= e($sourceKey) ?>">
                <td><span class="all-cases-case-id"><?= e((string)($caseRow['case_no'] ?? '—')) ?></span></td>
                <td><span class="all-cases-type-chip"><?= e($displayType) ?></span></td>
                <td class="all-cases-details-cell">
                  <?php if (!empty($caseRow['image_url'])): ?>
                    <img src="<?= e((string)$caseRow['image_url']) ?>" alt="Case image" class="all-cases-thumb">
                  <?php endif; ?>
                  <?= e((string)($caseRow['details'] ?? '—')) ?>
                </td>
                <td>
                  <span class="all-cases-type-chip">
                    <?= e($displaySource) ?>
                  </span>
                </td>
                <td class="all-cases-created"><?= e(formatDateTimeDisplay((string)($caseRow['created_at'] ?? ''))) ?></td>
                <td class="all-cases-actions">
                  <button type="button" class="all-cases-action-btn preview js-case-preview-btn"
                          onclick="openCasePreviewFromRow(this)"
                          data-case-no="<?= e((string)($caseRow['case_no'] ?? '—')) ?>"
                          data-case-type="<?= e($displayType) ?>"
                          data-case-details="<?= e((string)($caseRow['details'] ?? '—')) ?>"
                          data-case-status="<?= e((string)($caseRow['status'] ?? 'open')) ?>"
                          data-case-source="<?= e($displaySource) ?>"
                          data-case-created="<?= e((string)($caseRow['created_at'] ?? '')) ?>"
                          data-case-image="<?= e((string)($caseRow['image_url'] ?? '')) ?>"
                          data-case-contact="<?= e((string)($caseRow['contact_mobile'] ?? '')) ?>"
                          data-case-missing-name="<?= e((string)($caseRow['missing_name'] ?? '')) ?>"
                          data-case-extra="<?= e((string)($caseRow['extra_details'] ?? '')) ?>"
                    >Preview</button>
                  <button type="button" class="all-cases-action-btn publish js-case-publish-btn"
                      onclick="publishCaseFromRow(this)"
                          data-case-no="<?= e((string)($caseRow['case_no'] ?? '—')) ?>"
                        data-case-type="<?= e($displayType) ?>"
                          data-case-details="<?= e((string)($caseRow['details'] ?? '—')) ?>"
                          data-case-status="<?= e((string)($caseRow['status'] ?? 'open')) ?>"
                          data-case-source="<?= e($displaySource) ?>"
                          data-case-created="<?= e((string)($caseRow['created_at'] ?? '')) ?>"
                          data-case-image="<?= e((string)($caseRow['image_url'] ?? '')) ?>"
                          data-case-contact="<?= e((string)($caseRow['contact_mobile'] ?? '')) ?>"
                          data-case-missing-name="<?= e((string)($caseRow['missing_name'] ?? '')) ?>"
                          data-case-extra="<?= e((string)($caseRow['extra_details'] ?? '')) ?>"
                  >Publish</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
      <div id="allCasesFilterEmpty" style="display:none; margin-top:10px; padding:10px; border:1px dashed #cbd5e1; border-radius:8px; color:#64748b; font-weight:600;">No cases match selected filters.</div>
    </div>

    <div style="margin-top:14px; border-top:1px solid #e5e7eb; padding-top:12px;">
      <h4 style="margin:0 0 8px; color:#1f2937;">Live Published Board</h4>
      <p style="margin:0 0 10px; color:#4b5563; font-size:13px;">Published case updates appear here and auto-sync when admin closes a case.</p>
      <div id="livePublishedBoard" style="display:grid; gap:10px;"></div>
    </div>
  </div>
</div>

<div id="solvedCasesModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.52); z-index:4200; align-items:center; justify-content:center; padding:16px;">
  <div style="width:min(980px,96vw); max-height:88vh; overflow:auto; background:#fff; border-radius:12px; box-shadow:0 14px 32px rgba(0,0,0,.25); padding:14px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; border-bottom:1px solid #e5e7eb; padding-bottom:8px;">
      <h3 style="margin:0; color:#1f2937;">Solved Case History</h3>
      <button type="button" id="closeSolvedCasesBtn" style="border:none; background:#f3f4f6; width:32px; height:32px; border-radius:7px; cursor:pointer; font-size:18px;">&times;</button>
    </div>

    <div class="all-cases-table-wrap">
      <table class="all-cases-admin-table">
        <thead>
          <tr>
            <th>Case No</th>
            <th>Type</th>
            <th>Details</th>
            <th>Source</th>
            <th>Published At</th>
            <th>Solved At</th>
          </tr>
        </thead>
        <tbody id="solvedCasesTableBody">
          <tr><td colspan="6">No solved cases yet.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="casePreviewModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:4100; align-items:center; justify-content:center; padding:16px;">
  <div style="width:min(650px,95vw); background:#fff; border-radius:12px; box-shadow:0 12px 28px rgba(0,0,0,.24); overflow:hidden;">
    <div style="background:linear-gradient(90deg,#dc2626,#ef4444); color:#fff; padding:12px 14px; display:flex; justify-content:space-between; align-items:center;">
      <strong style="font-size:17px;">&#128226; Case Billboard Preview</strong>
      <button type="button" id="casePreviewClose" style="border:none; background:rgba(255,255,255,.2); color:#fff; width:32px; height:32px; border-radius:7px; cursor:pointer; font-size:18px;">&times;</button>
    </div>
    <div style="padding:14px;">
      <div style="border:1px solid #fca5a5; border-radius:10px; padding:12px; background:#fff5f5;">
        <div id="casePreviewAutoThumb" style="display:none; width:100%; height:170px; border-radius:8px; border:1px solid #fecaca; margin-bottom:8px; background:linear-gradient(135deg,#fee2e2,#fecaca); align-items:center; justify-content:center; color:#991b1b; font-weight:800; text-align:center; padding:10px;"></div>
        <img id="casePreviewImage" src="" alt="Case preview" style="display:none; width:100%; max-height:240px; object-fit:contain; background:#fff; border-radius:8px; border:1px solid #fecaca; margin-bottom:8px;">
        <h3 id="casePreviewTitle" style="margin:0 0 8px; color:#991b1b;">Case</h3>
        <p id="casePreviewDetail" style="margin:0 0 8px; color:#1f2937; font-weight:600;"></p>
        <div style="display:flex; gap:10px; flex-wrap:wrap; font-size:12px; color:#374151;">
          <span id="casePreviewContact"></span>
          <span id="casePreviewSource"></span>
        </div>
        <p id="casePreviewExtra" style="margin:8px 0 0; color:#374151; font-size:12px;"></p>
      </div>
      <div class="case-preview-footer">
        <button type="button" id="casePreviewCancel" class="case-preview-btn cancel">Cancel Preview</button>
        <button type="button" id="casePreviewPublish" class="case-preview-btn publish">Publish Case</button>
      </div>
    </div>
  </div>
</div>


    </div>

    <!-- Main Feed -->
    <div class="main-feed">
      <!-- Post Box -->
      <div class="post-box" onclick="openModal()">
        <img src="../Images/post.gif" class="user">
        <input type="text" placeholder="What's on your mind?" readonly>
      </div>

<!-- Popup Modal -->
<div id="postModal" class="post-modal">
  <div class="post-modal-content">
    
    <!-- Close Button -->
    <span class="post-modal-close" onclick="closeModal()">&times;</span>

    <!-- Title -->
    <div class="post-modal-head">
      <h2 class="post-modal-title">Share Your Mood</h2>
      <p class="post-modal-subtitle">Upload photos or a video and post instantly</p>
    </div>

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

    <!-- Textarea -->
    <textarea id="postText" class="post-modal-textarea" placeholder="Say Something..."></textarea>

    <!-- Post Preview (Auto-filled from clicked post) -->
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

    <!-- Media Upload Buttons -->
    <div class="post-media-options">
      <label>
        <input type="file" id="imageUpload" accept="image/*" multiple hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('imageUpload').click()">Photo</button>
      </label>
      <label>
        <input type="file" id="videoUpload" accept="video/*" hidden>
        <button type="button" class="post-media-btn" onclick="document.getElementById('videoUpload').click()">Video</button>
      </label>
    </div>
    <p class="post-media-hint">You can select up to 5 photos in one post.</p>


    <!-- Media Preview (optional preview for uploaded file) -->
    <div id="mediaPreview" class="post-media-preview"></div>

    <!-- Action Buttons -->
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
        <img src="<?= e($displayAuthorPhoto) ?>" alt="Author Photo">
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
        <video class="post-video" controls preload="metadata">
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
        <button type="button" class="investigation-open-btn" onclick="openMissingForm()" aria-label="Open investigation form" title="Open investigation form">Open Investigation Form</button>
        <p class="helpdesk-cta">Tap to open investigation form</p>
      </div>

    <div class="advert">
  <h4>Advertisement</h4>
  <div class="ad-ticker" aria-hidden="true">
    <div class="ad-ticker-track">Special Offer | City CCTV Bundle | First Aid Bootcamp | Community Safety Partner</div>
  </div>

  <article class="ad-card ad-card-primary">
    <small>Sponsored</small>
    <video src="../Video/DONATION PROMO.mp4" class="ad-thumb" autoplay muted loop playsinline controls></video>
    <h5 class="ad-title-animate">Support Our Cause</h5>
    <p>Your donation helps us find missing persons and support volunteers.</p>
    <a href="#!">Donate Now</a>
  </article>

  <article class="ad-card ad-card-slider">
    <small>Partner Offer</small>
    <div class="ad-slider-container">
      <div class="ad-slider-track">
        <img src="../Images/logo.png" alt="Searchar Logo">
        <img src="../Images/makeachange.jpg" alt="Light Seekers">
        <img src="../Images/together.jpg" alt="Together">
        <img src="../Images/resuce.jpg" alt="Rescue">
      </div>
    </div>
    <h5 class="ad-title-animate delay">Community Safety Campaign</h5>
    <p>Together we can make a difference. Learn how to assist in missing person searches and emergencies.</p>
    <a href="#!">Join Campaign</a>
  </article>
</div>

<!-- Missing Person Investigation Popup moved to page end to avoid stacking context issues -->
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



    <!-- Missing Person Investigation Popup moved here to avoid stacking context issues -->
    <div id="missingFormModal" class="missing-modal">
      <div class="missing-modal-content">
        <span class="missing-close" onclick="closeMissingForm()">&times;</span>
        <h2>Police Missing Person Investigation Form</h2>

        <form id="missingForm" action="../Php/save_missing_person.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="return_to" value="Policeman_Home.php">
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

    </body>
      <script src="../javascrpit/Policeman_Home.js?v=20260410e"></script>
      <script src="../javascrpit/post_interactions_shared.js?v=20260406d"></script>
      <script src="../javascrpit/notifications_shared.js"></script>

</html>


