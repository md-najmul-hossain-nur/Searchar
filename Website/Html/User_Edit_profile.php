<?php
require_once __DIR__ . '/../Php/db.php'; // Make sure $pdo is set
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Helper to escape output
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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

function isProfileComplete(array $row): bool {
  $required = ['date_of_birth', 'gender', 'city', 'country'];
  foreach ($required as $key) {
    if (trim((string)($row[$key] ?? '')) === '') {
      return false;
    }
  }

  $street = trim((string)($row['street'] ?? ''));
  $lat = trim((string)($row['latitude'] ?? ''));
  $lng = trim((string)($row['longitude'] ?? ''));

  return $street !== '' || ($lat !== '' && $lng !== '');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $beforeStmt = $pdo->prepare("SELECT profile_photo, cover_photo, date_of_birth, gender, street, city, country, latitude, longitude FROM users WHERE user_id = ? LIMIT 1");
  $beforeStmt->execute([$user_id]);
  $beforeRow = $beforeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
  $wasComplete = isProfileComplete($beforeRow);

    $full_name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
  $date_of_birth = trim((string)($_POST['date_of_birth'] ?? ''));
  $gender = trim((string)($_POST['gender'] ?? ''));
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
$street  = trim((string)($_POST['street'] ?? ''));
$city    = trim((string)($_POST['city'] ?? ''));
$postal  = trim((string)($_POST['postal'] ?? ''));
$country = trim((string)($_POST['country'] ?? ''));

    // Profile photo upload
    if (!empty($_FILES['profilePhoto']['name']) && $_FILES['profilePhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
        $profile_photo_name = 'profile_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profilePhoto']['tmp_name'], "../uploads/user/$profile_photo_name");
    } else {
      $profile_photo_name = $_POST['current_profile'] ?? null;
    }

    // Cover photo upload
    if (!empty($_FILES['coverPhoto']['name']) && $_FILES['coverPhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['coverPhoto']['name'], PATHINFO_EXTENSION);
        $cover_photo_name = 'cover_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['coverPhoto']['tmp_name'], "../uploads/user/$cover_photo_name");
    } else {
      $cover_photo_name = $_POST['current_cover'] ?? null;
    }



// Update user in DB including address
$stmt = $pdo->prepare("UPDATE users 
  SET full_name = ?, email = ?, mobile = ?, bio = ?, profile_photo = ?, cover_photo = ?, date_of_birth = ?, gender = ?, latitude = ?, longitude = ?, street = ?, city = ?, postal_code = ?, country = ?
    WHERE user_id = ?");
$stmt->execute([
    $full_name,
    $email,
    $mobile,
    $bio,
    $profile_photo_name,
    $cover_photo_name,
    $date_of_birth !== '' ? $date_of_birth : null,
    $gender !== '' ? $gender : null,
    $latitude,
    $longitude,
    $street,
    $city,
    $postal,
    $country,
    $user_id
]);

    $afterProfile = [
      'profile_photo' => $profile_photo_name,
      'cover_photo' => $cover_photo_name,
      'date_of_birth' => $date_of_birth,
      'gender' => $gender,
      'street' => $street,
      'city' => $city,
      'country' => $country,
      'latitude' => $latitude,
      'longitude' => $longitude,
    ];
    $isNowComplete = isProfileComplete($afterProfile);

    if ($isNowComplete) {
      ensureNotificationsTable($pdo);
      $deleteReminder = $pdo->prepare("DELETE FROM user_notifications
        WHERE recipient_entity IN ('user', 'users')
          AND recipient_id = :id
          AND title = 'Admin Reminder'
          AND message LIKE '%complete your profile%'");
      $deleteReminder->execute([':id' => $user_id]);

      $existsThanks = $pdo->prepare("SELECT notification_id FROM user_notifications WHERE recipient_entity IN ('user', 'users') AND recipient_id = :id AND title = 'Admin Thanks' LIMIT 1");
      $existsThanks->execute([':id' => $user_id]);
      if (!$existsThanks->fetchColumn()) {
        $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read) VALUES (:entity, :id, :title, :message, :level, 0)');
        $notify->execute([
          ':entity' => 'user',
          ':id' => $user_id,
          ':title' => 'Admin Thanks',
          ':message' => 'Thanks for completing your profile. Your account is now fully ready.',
          ':level' => 'info',
        ]);
      }
    }


    // âœ… Redirect to profile page after successful save
    header("Location: ../Html/User_profile.php");
    exit;
}

// Fetch user data to prefill form
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
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
  <link rel="stylesheet" href="../css/User_Edit_profile.css?v=20260405bg">
</head>
<body>
  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>

  <!-- Main Content -->
  <main class="edit-profile-container">
    <div class="edit-profile-header-vertical">
      <img src="../Images/edit-profile.gif" alt="Edit Icon" class="edit-profile-icon-vertical">
      <h2>EDIT YOUR PROFILE</h2>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
      <a href="../Html/User_profile.php" class="back-btn">â† Back</a>
    </div>

    <form class="edit-profile-form" method="POST" enctype="multipart/form-data">

      <!-- Hidden for current images -->
      <input type="hidden" name="current_profile" value="<?= e($user['profile_photo']) ?>">
      <input type="hidden" name="current_cover" value="<?= e($user['cover_photo']) ?>">

      <!-- Cover Photo -->
      <div class="cover-photo-section">
        <label for="coverPhoto" class="cover-label">Cover Photo</label>
        <div class="cover-preview">
          <img id="coverPreview" src="<?= !empty($user['cover_photo']) ? '../uploads/user/' . e($user['cover_photo']) : '../Images/demo_pic/cover.jpg' ?>" alt="Cover Photo Preview" onerror="this.onerror=null;this.src='../Images/default-cover.gif';">
        </div>
        <input type="file" id="coverPhoto" name="coverPhoto" accept="image/*" onchange="previewImage(event, 'coverPreview')">
      </div>

      <!-- Profile Photo -->
      <div class="profile-photo-section">
        <label for="profilePhoto" class="profile-label">Profile Picture</label>
        <div class="profile-preview">
          <img id="profilePreview" src="<?= !empty($user['profile_photo']) ? '../uploads/user/' . e($user['profile_photo']) : '../Images/demo_pic/profile.jpg' ?>" alt="Profile Picture Preview" onerror="this.onerror=null;this.src='../Images/demo_pic/profile.jpg';">
        </div>
        <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*" onchange="previewImage(event, 'profilePreview')">
      </div>

      <!-- Bio -->
      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" placeholder="Write something about yourself..." maxlength="250"><?= e($user['bio'] ?? '') ?></textarea>

      <!-- Name -->
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="Enter your name" value="<?= e($user['full_name']) ?>" required>

      <!-- Email -->
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= e($user['email']) ?>" required>

      <!-- Phone -->
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= e($user['mobile']) ?>" pattern="[0-9]{10,15}" required>
<!-- Date of birth & Gender -->
      <label for="date_of_birth">Date of Birth</label>
      <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($user['date_of_birth'] ?? '') ?>">

      <label for="gender">Gender</label>
      <select id="gender" name="gender">
        <option value="" <?= empty($user['gender']) ? 'selected' : '' ?>>Prefer not to say</option>
        <option value="male" <?= (isset($user['gender']) && $user['gender']==='male') ? 'selected' : '' ?>>Male</option>
        <option value="female" <?= (isset($user['gender']) && $user['gender']==='female') ? 'selected' : '' ?>>Female</option>
        <option value="other" <?= (isset($user['gender']) && $user['gender']==='other') ? 'selected' : '' ?>>Other</option>
      </select>
      <!-- Map / Location -->
      <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
        Please select your location by clicking the <b>Select location from map</b> button below.
      </p>
      <label for="street">Street Address</label>
      <input type="text" id="street" name="street" value="<?= e($user['street']) ?>" >
      <label for="city">City</label>
      <input type="text" id="city" name="city" value="<?= e($user['city']) ?>" >
      <label for="postal">Postal Code</label>
      <input type="text" id="postal" name="postal" value="<?= e($user['postal_code']) ?>" >
      <label for="country">Country</label>
      <input type="text" id="country" name="country" value="<?= e($user['country']) ?>" >

      <input type="hidden" id="latitude" name="latitude" value="<?= e($user['latitude']) ?>">
      <input type="hidden" id="longitude" name="longitude" value="<?= e($user['longitude']) ?>">

      <!-- Map Select Button -->
      <button type="button" class="map-select-btn" onclick="selectLocationFromMap()">Select location from map</button>

      <!-- Map Modal -->
<div id="mapModal" class="map-modal" style="display:none;">
  <div class="map-modal-content">
    <span class="map-close" onclick="closeMapModal()">&times;</span>
    <div id="map" style="width:100%;height:320px;"></div>
    <button id="currentLocationBtn" type="button" onclick="getCurrentLocation()">Use Current Location</button>
    <button id="saveLocationBtn" type="button" onclick="saveMapLocation()">Save Location</button>
  </div>
</div>

      <!-- Action Buttons -->
      <div class="profile-actions">
        <button type="button" class="cancel-btn" onclick="window.location.href='../Html/User_profile.php'">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </main>

  <!-- JavaScript -->
  <script src="../javascrpit/User_Edit_profile.js?v=20260405bg"></script>
  <script>
let map, marker, selectedLatLng;

// Open the map modal and initialize the map
function selectLocationFromMap() {
  document.getElementById('mapModal').style.display = 'flex';
  initMap();
}

// Close the map modal
function closeMapModal() {
  document.getElementById('mapModal').style.display = 'none';
}

// Initialize Leaflet map
function initMap() {
  if (!map) {
    map = L.map('map').setView([23.8103, 90.4125], 13); // Dhaka default
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Click to set marker
    map.on('click', function(e) {
      setMarker(e.latlng);
    });
  }
  map.invalidateSize();
}

// Set or move marker
function setMarker(latlng) {
  selectedLatLng = latlng;
  if (marker) {
    marker.setLatLng(latlng);
  } else {
    marker = L.marker(latlng).addTo(map);
  }
}

// Get user's current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(pos) {
      const latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      map.setView([latlng.lat, latlng.lng], 16);
      setMarker(latlng);
    }, function(err) {
      alert('Failed to get current location: ' + err.message);
    });
  } else {
    alert('Geolocation not supported by your browser');
  }
}

// Save location and fill form fields
function saveMapLocation() {
  if (!selectedLatLng) {
    alert('Please select a location on the map!');
    return;
  }

  // Fill hidden latitude/longitude fields
  document.getElementById('latitude').value = selectedLatLng.lat;
  document.getElementById('longitude').value = selectedLatLng.lng;

  // Reverse geocode using Nominatim
  fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${selectedLatLng.lat}&lon=${selectedLatLng.lng}`, {
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'SearcharApp/1.0' // Avoid Nominatim blocking
      }
    })
    .then(response => response.json())
    .then(data => {
      document.getElementById('street').value = data.address.road || data.address.neighbourhood || '';
      document.getElementById('city').value = data.address.city || data.address.town || data.address.village || '';
      document.getElementById('postal').value = data.address.postcode || '';
      document.getElementById('country').value = data.address.country || '';
      closeMapModal();
    })
    .catch(err => {
      console.error('Reverse geocoding error:', err);
      alert('Failed to get address from coordinates. You can manually fill it.');
      closeMapModal();
    });
}

  </script>
<style>/* === Date of Birth & Gender Section === */
.edit-profile-form input[type="date"],
.edit-profile-form select#gender {
  width: 100%;
  padding: 0.54em 1.05em;
  margin-bottom: 1.15em;
  border: 1.6px solid #dbeafe;
  border-radius: 7px;
  font-size: 1em;
  background: #fafdff;
  color: #314060;
  transition: border 0.18s, box-shadow 0.18s, background 0.18s;
  box-sizing: border-box;
  outline: none;
  appearance: none;
}

/* On hover */
.edit-profile-form input[type="date"]:hover,
.edit-profile-form select#gender:hover {
  background: #fff0f0;
}

/* On focus */
.edit-profile-form input[type="date"]:focus,
.edit-profile-form select#gender:focus {
  border-color: #f05454;
  box-shadow: 0 0 0 2px #ffeaea;
}

/* Custom dropdown arrow for select */
.edit-profile-form select#gender {
  background-image: url('data:image/svg+xml;utf8,<svg fill="%23425a78" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 18px;
  cursor: pointer;
}

/* Label styling (keeps consistent with your form) */
.edit-profile-form label[for="date_of_birth"],
.edit-profile-form label[for="gender"] {
  display: block;
  margin-bottom: 0.50em;
  color: #425a78;
  font-size: 1.01em;
  font-weight: 500;
  letter-spacing: 0.01em;
}
</style>

</body>
</html>

