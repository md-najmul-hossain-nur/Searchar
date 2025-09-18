<?php
require_once __DIR__ . '/../Php/db.php'; // Make sure $pdo is set
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../Html/login.html');
    exit;
}

$user_id = $_SESSION['user_id'];

// Helper to escape output
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $mobile = $_POST['phone'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
$street  = $_POST['street'] ?? '';
$city    = $_POST['city'] ?? '';
$postal  = $_POST['postal_code'] ?? '';
$country = $_POST['country'] ?? '';

    // Profile photo upload
    if (!empty($_FILES['profilePhoto']['name']) && $_FILES['profilePhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
        $profile_photo_name = 'profile_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['profilePhoto']['tmp_name'], "../uploads/user/$profile_photo_name");
    } else {
        $profile_photo_name = $_POST['current_profile'] ?? $user['profile_photo'];
    }

    // Cover photo upload
    if (!empty($_FILES['coverPhoto']['name']) && $_FILES['coverPhoto']['error'] === 0) {
        $ext = pathinfo($_FILES['coverPhoto']['name'], PATHINFO_EXTENSION);
        $cover_photo_name = 'cover_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['coverPhoto']['tmp_name'], "../uploads/user/$cover_photo_name");
    } else {
        $cover_photo_name = $_POST['current_cover'] ?? $user['cover_photo'];
    }



// Update user in DB including address
$stmt = $pdo->prepare("UPDATE users 
    SET full_name = ?, email = ?, mobile = ?, bio = ?, profile_photo = ?, cover_photo = ?, latitude = ?, longitude = ?, street = ?, city = ?, postal_code = ?, country = ?
    WHERE user_id = ?");
$stmt->execute([
    $full_name,
    $email,
    $mobile,
    $bio,
    $profile_photo_name,
    $cover_photo_name,
    $latitude,
    $longitude,
    $street,
    $city,
    $postal,
    $country,
    $user_id
]);


    // ✅ Redirect to profile page after successful save
    header("Location: ../Html/User_profile.php");
    exit;
}

// Fetch user data to prefill form
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile - Searchar</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <link rel="stylesheet" href="../css/User_Edit_profile.css">
</head>
<body>
  <!-- Navbar -->
  <header class="navbar">
    <div class="navbar-logo">
      <img src="../Images/logo.png" alt="SEARCHAR Logo" class="navbar-logo-img" id="logo">
    </div>
  </header>

  <div class="bubble-background"></div>

  <!-- Main Content -->
  <main class="edit-profile-container">
    <div class="edit-profile-header-vertical">
      <img src="../Images/edit-profile.gif" alt="Edit Icon" class="edit-profile-icon-vertical">
      <h2>EDIT YOUR PROFILE</h2>
    </div>

    <!-- Back Button -->
    <div class="back-button-container">
      <a href="../Html/User_profile.php" class="back-btn">← Back</a>
    </div>

    <form class="edit-profile-form" method="POST" enctype="multipart/form-data">

      <!-- Hidden for current images -->
      <input type="hidden" name="current_profile" value="<?= e($user['profile_photo']) ?>">
      <input type="hidden" name="current_cover" value="<?= e($user['cover_photo']) ?>">

      <!-- Cover Photo -->
      <div class="cover-photo-section">
        <label for="coverPhoto" class="cover-label">Cover Photo</label>
        <div class="cover-preview">
          <img id="coverPreview" src="<?= !empty($user['cover_photo']) ? '../uploads/user/' . e($user['cover_photo']) : '../Images/default-cover.gif' ?>" alt="Cover Photo Preview">
        </div>
        <input type="file" id="coverPhoto" name="coverPhoto" accept="image/*" onchange="previewImage(event, 'coverPreview')">
      </div>

      <!-- Profile Photo -->
      <div class="profile-photo-section">
        <label for="profilePhoto" class="profile-label">Profile Picture</label>
        <div class="profile-preview">
          <img id="profilePreview" src="<?= !empty($user['profile_photo']) ? '../uploads/user/' . e($user['profile_photo']) : '../Images/default-profile.gif' ?>" alt="Profile Picture Preview">
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
  <script src="../javascrpit/User_Edit_profile.js"></script>
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


</body>
</html>
