<?php
require_once __DIR__ . '/../Php/db.php';
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'contributor' || empty($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;

    // Profile upload
    if (!empty($_FILES['profilePhoto']['name']) && $_FILES['profilePhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
        $profile_photo_name = 'profile_' . uniqid() . '.' . $ext;
        if (!is_dir(__DIR__ . '/../uploads/camera')) mkdir(__DIR__ . '/../uploads/camera', 0755, true);
        move_uploaded_file($_FILES['profilePhoto']['tmp_name'], __DIR__ . '/../uploads/camera/' . $profile_photo_name);
    } else {
        $profile_photo_name = $_POST['current_profile'] ?? null;
    }

    // Cover upload
    if (!empty($_FILES['coverPhoto']['name']) && $_FILES['coverPhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['coverPhoto']['name'], PATHINFO_EXTENSION);
        $cover_photo_name = 'cover_' . uniqid() . '.' . $ext;
        if (!is_dir(__DIR__ . '/../uploads/camera')) mkdir(__DIR__ . '/../uploads/camera', 0755, true);
        move_uploaded_file($_FILES['coverPhoto']['tmp_name'], __DIR__ . '/../uploads/camera/' . $cover_photo_name);
    } else {
        $cover_photo_name = $_POST['current_cover'] ?? null;
    }

    // Update camera_contributors
    $stmt = $pdo->prepare("UPDATE camera_contributors SET full_name = ?, email = ?, mobile = ?, bio = ?, profile_photo = ?, cover_photo = ?, date_of_birth = ?, gender = ?, street = ?, city = ?, country = ?, latitude = ?, longitude = ? WHERE camera_id = ?");
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
        $country,
        $latitude,
        $longitude,
        $user_id
    ]);

    header('Location: ../Html/Camera_Contribution_profile.php');
    exit;
}

// Fetch existing data
$stmt = $pdo->prepare('SELECT * FROM camera_contributors WHERE camera_id = ?');
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
  <title>Edit Profile - Camera Contributor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <link rel="stylesheet" href="../css/Camera_Contribution_Edit_profile.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>
  <div class="bubble-background"></div>

  <main class="edit-profile-container">
    <div class="edit-profile-header-vertical">
      <img src="../Images/edit-profile.gif" alt="Edit Icon" class="edit-profile-icon-vertical">
      <h2>EDIT YOUR PROFILE</h2>
    </div>

    <div class="back-button-container">
      <a href="../Html/Camera_Contribution_profile.php" class="back-btn">← Back</a>
    </div>

    <form class="edit-profile-form" method="POST" enctype="multipart/form-data">

      <input type="hidden" name="current_profile" value="<?= e($user['profile_photo'] ?? '') ?>">
      <input type="hidden" name="current_cover" value="<?= e($user['cover_photo'] ?? '') ?>">

      <!-- Cover Photo -->
      <div class="cover-photo-section">
        <label for="coverPhoto" class="cover-label">Cover Photo</label>
        <div class="cover-preview">
          <img id="coverPreview" src="<?= !empty($user['cover_photo']) ? '../uploads/camera/' . e($user['cover_photo']) : '../Images/default-cover.gif' ?>" alt="Cover Photo Preview">
        </div>
        <input type="file" id="coverPhoto" name="coverPhoto" accept="image/*" onchange="previewImage(event, 'coverPreview')">
      </div>

      <!-- Profile Photo -->
      <div class="profile-photo-section">
        <label for="profilePhoto" class="profile-label">Profile Picture</label>
        <div class="profile-preview">
          <img id="profilePreview" src="<?= !empty($user['profile_photo']) ? '../uploads/camera/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" alt="Profile Picture Preview">
        </div>
        <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*" onchange="previewImage(event, 'profilePreview')">
      </div>

      <!-- Bio -->
      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" placeholder="Write something about yourself..." maxlength="250"><?= e($user['bio'] ?? '') ?></textarea>

      <!-- Name -->
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="Enter your name" value="<?= e($user['full_name'] ?? '') ?>" required>

      <!-- Email -->
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= e($user['email'] ?? '') ?>" required>

      <!-- Phone -->
      <label for="phone">Phone Number</label>
      <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" value="<?= e($user['mobile'] ?? '') ?>" pattern="[0-9]{6,15}" required>

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
      <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">Please select your location by clicking the <b>Select location from map</b> button below.</p>

      <!-- Address -->
      <label for="street">Street Address</label>
      <input type="text" id="street" name="street" value="<?= e($user['street'] ?? '') ?>">
      <label for="city">City</label>
      <input type="text" id="city" name="city" value="<?= e($user['city'] ?? '') ?>">
      <label for="country">Country</label>
      <input type="text" id="country" name="country" value="<?= e($user['country'] ?? '') ?>">

      <input type="hidden" id="latitude" name="latitude" value="<?= e($user['latitude'] ?? '') ?>">
      <input type="hidden" id="longitude" name="longitude" value="<?= e($user['longitude'] ?? '') ?>">

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
        <button type="button" class="cancel-btn" onclick="window.location.href='../Html/Camera_Contribution_profile.php'">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>
    </form>
  </main>

  <script src="../javascrpit/Camera_Contribution_Edit_profile.js"></script>

  <script>
  // small preview helper
  function previewImage(event, id) {
    const out = document.getElementById(id);
    const file = event.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) { out.src = e.target.result; };
    reader.readAsDataURL(file);
  }

  // Map helper functions will be reused from template JS if present
  </script>
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
