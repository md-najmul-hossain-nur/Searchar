<?php
require_once __DIR__ . '/../Php/db.php';
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'volunteer' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal = trim($_POST['postal'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $occupation = trim($_POST['occupation'] ?? '');
    $availability = trim($_POST['availability'] ?? '');

    $profile_photo_name = $_POST['current_profile'] ?? null;
    $cover_photo_name = $_POST['current_cover'] ?? null;

    $uploadDir = __DIR__ . '/../uploads/volunteer';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!empty($_FILES['profilePhoto']['name']) && $_FILES['profilePhoto']['error'] === 0) {
        $tmp = $_FILES['profilePhoto']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true) && @getimagesize($tmp)) {
            $candidate = 'profile_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . '/' . $candidate)) {
                $profile_photo_name = $candidate;
            }
        }
    }

    if (!empty($_FILES['coverPhoto']['name']) && $_FILES['coverPhoto']['error'] === 0) {
        $tmp = $_FILES['coverPhoto']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['coverPhoto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true) && @getimagesize($tmp)) {
            $candidate = 'cover_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . '/' . $candidate)) {
                $cover_photo_name = $candidate;
            }
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE volunteers SET full_name = ?, email = ?, mobile = ?, bio = ?, profile_photo = ?, cover_photo = ?, date_of_birth = ?, gender = ?, street = ?, city = ?, postal_code = ?, country = ?, latitude = ?, longitude = ?, occupation = ?, availability = ? WHERE volunteer_id = ?'
    );
    $stmt->execute([
        $full_name,
        $email,
        $mobile,
        $bio,
        $profile_photo_name,
        $cover_photo_name,
        $date_of_birth,
        $gender,
        $street,
        $city,
        $postal,
        $country,
        $latitude,
        $longitude,
        $occupation,
        $availability,
        $user_id
    ]);

    header('Location: ../Html/Volunteer_profile.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM volunteers WHERE volunteer_id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: ../Html/login.html?error=no_user');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <link rel="stylesheet" href="../css/Volunteer_Edit_profile.css?v=20260405bg">
</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>
  <main class="edit-profile-container">
    <div class="edit-profile-header-vertical">
      <img src="../Images/edit-profile.gif" alt="Edit Icon" class="edit-profile-icon-vertical">
      <h2>EDIT YOUR PROFILE</h2>
    </div>

    <div class="back-button-container">
      <a href="../Html/Volunteer_profile.php" class="back-btn">← Back</a>
    </div>

    <form class="edit-profile-form" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="current_profile" value="<?= e($user['profile_photo'] ?? '') ?>">
      <input type="hidden" name="current_cover" value="<?= e($user['cover_photo'] ?? '') ?>">

      <div class="cover-photo-section">
        <label for="coverPhoto" class="cover-label">Cover Photo</label>
        <div class="cover-preview">
          <img id="coverPreview" src="<?= !empty($user['cover_photo']) ? '../uploads/volunteer/' . e($user['cover_photo']) : '../Images/default-cover.gif' ?>" alt="Cover Photo Preview">
        </div>
        <input type="file" id="coverPhoto" name="coverPhoto" accept="image/*" onchange="previewImage(event, 'coverPreview')">
      </div>

      <div class="profile-photo-section">
        <label for="profilePhoto" class="profile-label">Profile Picture</label>
        <div class="profile-preview">
          <img id="profilePreview" src="<?= !empty($user['profile_photo']) ? '../uploads/volunteer/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" alt="Profile Picture Preview">
        </div>
        <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*" onchange="previewImage(event, 'profilePreview')">
      </div>

      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" placeholder="Write something about yourself..." maxlength="250"><?= e($user['bio'] ?? '') ?></textarea>

      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" value="<?= e($user['full_name'] ?? '') ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($user['email'] ?? '') ?>" required>

      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone" value="<?= e($user['mobile'] ?? '') ?>" pattern="[0-9]{10,15}" required>

      <label for="date_of_birth">Date of Birth</label>
      <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($user['date_of_birth'] ?? '') ?>">

      <label for="gender">Gender</label>
      <select id="gender" name="gender">
        <option value="" <?= empty($user['gender']) ? 'selected' : '' ?>>Prefer not to say</option>
        <option value="male" <?= (isset($user['gender']) && $user['gender'] === 'male') ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= (isset($user['gender']) && $user['gender'] === 'female') ? 'selected' : '' ?>>Female</option>
        <option value="other" <?= (isset($user['gender']) && $user['gender'] === 'other') ? 'selected' : '' ?>>Other</option>
      </select>

      <label for="occupation">Occupation</label>
      <input type="text" id="occupation" name="occupation" value="<?= e($user['occupation'] ?? '') ?>">

      <label for="availability">Availability</label>
      <input type="text" id="availability" name="availability" value="<?= e($user['availability'] ?? '') ?>">

      <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
        Please select your location by clicking the <b>Select location from map</b> button below.
      </p>

      <label for="street">Street Address</label>
      <input type="text" id="street" name="street" value="<?= e($user['street'] ?? '') ?>">

      <label for="city">City</label>
      <input type="text" id="city" name="city" value="<?= e($user['city'] ?? '') ?>">

      <label for="postal">Postal Code</label>
      <input type="text" id="postal" name="postal" value="<?= e($user['postal_code'] ?? '') ?>">

      <label for="country">Country</label>
      <input type="text" id="country" name="country" value="<?= e($user['country'] ?? '') ?>">

      <input type="hidden" id="latitude" name="latitude" value="<?= e($user['latitude'] ?? '') ?>">
      <input type="hidden" id="longitude" name="longitude" value="<?= e($user['longitude'] ?? '') ?>">

      <button type="button" class="map-select-btn" onclick="selectLocationFromMap()">Select location from map</button>

      <div id="mapModal" class="map-modal" style="display:none;">
        <div class="map-modal-content">
          <span class="map-close" onclick="closeMapModal()">&times;</span>
          <div id="map" style="width:100%;height:320px;"></div>
          <button id="currentLocationBtn" type="button" onclick="getCurrentLocation()">Use Current Location</button>
          <button id="saveLocationBtn" type="button" onclick="saveMapLocation()">Save Location</button>
        </div>
      </div>

      <div class="profile-actions">
        <button type="button" class="cancel-btn" onclick="window.location.href='../Html/Volunteer_profile.php'">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </main>

  <script src="../javascrpit/Volunteer_Edit_profile.js?v=20260405bg"></script>
</body>
</html>
