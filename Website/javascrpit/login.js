let container = document.getElementById('container');

const SIGNUP_DRAFT_KEY = 'searcharSignupDraft';

function shouldOpenSignupInitially() {
  const params = new URLSearchParams(window.location.search);
  return params.get('openSignup') === '1';
}

function openSignupPanel() {
  if (!container) {
    container = document.getElementById('container');
  }
  if (!container) return;
  container.classList.add('sign-up');
  container.classList.remove('sign-in');
}

function saveSignupDraft() {
  const roleSelect = document.getElementById('role');
  const dynamicForm = document.getElementById('dynamicForm');
  if (!roleSelect || !dynamicForm || !roleSelect.value) return;

  const values = {};
  const fields = dynamicForm.querySelectorAll('input, select, textarea');

  fields.forEach((field) => {
    const key = field.name || field.id;
    if (!key) return;
    if (field.type === 'file' || field.type === 'password') return;

    if (field.type === 'checkbox' || field.type === 'radio') {
      values[key] = !!field.checked;
      return;
    }

    values[key] = field.value;
  });

  const payload = {
    role: roleSelect.value,
    values,
    timestamp: Date.now()
  };

  try {
    sessionStorage.setItem(SIGNUP_DRAFT_KEY, JSON.stringify(payload));
  } catch (error) {
    console.warn('Failed to save signup draft', error);
  }
}

function applySignupDraftValues(values) {
  const dynamicForm = document.getElementById('dynamicForm');
  if (!dynamicForm || !values) return;

  const fields = dynamicForm.querySelectorAll('input, select, textarea');
  fields.forEach((field) => {
    const key = field.name || field.id;
    if (!key || !(key in values)) return;
    if (field.type === 'file' || field.type === 'password') return;

    if (field.type === 'checkbox' || field.type === 'radio') {
      field.checked = !!values[key];
      return;
    }

    field.value = values[key];
  });
}

function updateTermsLinksForCurrentRole() {
  const dynamicForm = document.getElementById('dynamicForm');
  if (!dynamicForm) return;

  const termsLinks = dynamicForm.querySelectorAll('a[href*="Terms_&_Privacy.html"]');
  termsLinks.forEach((link) => {
    const targetUrl = new URL(link.getAttribute('href') || '', window.location.href);
    targetUrl.search = '';
    link.setAttribute('href', targetUrl.toString());
    link.setAttribute('data-terms-modal', '1');
  });
}

function restoreSignupStateIfAvailable() {
  const roleSelect = document.getElementById('role');
  if (!roleSelect) return;

  const params = new URLSearchParams(window.location.search);
  const shouldOpen = params.get('openSignup') === '1';
  if (!shouldOpen) return;

  const roleFromUrl = params.get('role') || '';

  let storedDraft = null;
  try {
    const raw = sessionStorage.getItem(SIGNUP_DRAFT_KEY);
    storedDraft = raw ? JSON.parse(raw) : null;
  } catch (error) {
    storedDraft = null;
  }

  openSignupPanel();

  const roleToUse = roleFromUrl || (storedDraft && storedDraft.role) || '';
  if (!roleToUse) return;

  roleSelect.value = roleToUse;
  showForm();

  if (storedDraft && storedDraft.role === roleToUse) {
    applySignupDraftValues(storedDraft.values || {});
  }

  updateTermsLinksForCurrentRole();
}

// Expose toggle for inline onclick and guard if the container is missing
window.toggle = function toggle() {
  if (!container) {
    container = document.getElementById('container');
  }
  if (container) {
    container.classList.toggle('sign-in');
    container.classList.toggle('sign-up');
  }
};

setTimeout(() => {
  if (shouldOpenSignupInitially()) {
    openSignupPanel();
  } else if (container) {
    container.classList.add('sign-in');
  }
}, 200);

// Logo click redirects to home page
document.getElementById('logo').onclick = function () {
  window.location.href = '../Html/Index.html';
};

// Show role-based sign-up form with animation
function showForm() {
  const role = document.getElementById('role').value;
  const formContainer = document.getElementById('dynamicForm');
  let formHTML = '';

  if (role === 'user') {
    formHTML = `
      <h3>User Sign Up</h3>
      <form id="userSignupForm" enctype="multipart/form-data" method="post" action="../Php/user_signup.php">
        <h5 class="form-section-title">🔐 General Information</h5>
        <div class="mb-3">
          <label for="fullname" class="form-label">Full Name </label>
          <input type="text" class="form-control" id="fullname" name="fullname" required>
        </div>
        <div class="mb-3">
          <label for="emailOrPhone" class="form-label">Email or Phone Number </label>
          <input type="text" class="form-control" id="emailOrPhone" name="emailOrPhone" placeholder="example@mail.com or 017xxxxxxxx" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password </label>
          <input type="password" class="form-control" id="password" name="password" minlength="6" required>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password </label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="terms" required>
          <label class="form-check-label" for="terms">
            I agree to the <a href="../Html/User_Terms_&_Privacy.html">Terms & Privacy Policy</a>
          </label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Sign Up</button>
      </form>
    `;
  } else if (role === 'police') {
    formHTML = `
      <h3 class="text-center mb-3">👮 Policeman / Authority Sign Up</h3>
      <form id="policeSignupForm" enctype="multipart/form-data" method="post" action="../Php/police_signup.php">
        <h5 class="form-section-title">🔐 General Information</h5>
        <div class="mb-3">
          <label for="fullname" class="form-label">Full Name </label>
          <input type="text" class="form-control" id="fullname" name="fullname" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Official Email Address </label>
          <input type="email" class="form-control" id="email" name="email" placeholder="example@police.gov.bd" required>
        </div>
        <div class="mb-3">
          <label for="mobile" class="form-label">Mobile Number </label>
          <input type="tel" class="form-control" id="mobile" name="mobile" maxlength="11" minlength="11" placeholder="e.g. 017xxxxxxxx" required>
        </div>
        <div class="mb-3">
          <label for="nid" class="form-label">NID Number </label>
          <input type="text" class="form-control" id="nid" name="nid" placeholder="10 or 17 digits" required>
        </div>
        <div class="mb-3">
          <label for="nid_photo" class="form-label">Upload NID Photo </label>
          <input type="file" class="form-control" id="nid_photo" name="nid_photo" accept=".jpg,.jpeg,.png" required>
        </div>
        <div class="mb-3">
          <label for="profile_photo" class="form-label">Profile Photo </label>
          <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
        </div>
           <div class="mb-3">
  <label for="cover_photo" class="form-label">Cover Photo</label>
  <input type="file" class="form-control" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png">
</div>
        <div class="mb-3">
          <label for="dob" class="form-label">Date of Birth </label>
          <input type="date" class="form-control" id="dob" name="dob" required>
        </div>
        <div class="mb-3">
          <label for="gender" class="form-label">Gender </label>
          <select class="form-select" id="gender" name="gender" required>
            <option value="">-- Select Gender --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
          Please select your location by clicking the <b>Select location from map</b> button below.
        </p>
        <div class="mb-3">
          <label for="street" class="form-label">Street Address</label>
          <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" >
        </div>
        <div class="mb-3">
          <label for="city" class="form-label">City</label>
          <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" >
        </div>
        <div class="mb-3">
          <label for="postal" class="form-label">Postal Code</label>
          <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" >
        </div>
        <div class="mb-3">
          <label for="country" class="form-label">Country</label>
          <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" >
        </div>
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">
        <button type="button" class="btn btn-primary map-select-btn"
                onclick="selectLocationFromMap()"
                style="margin-bottom: 15px;">
          Select location from map
        </button>
        <div id="mapModal" class="map-modal" style="display:none;">
          <div class="map-modal-content" style="background:#fff; padding:15px; border-radius:8px; position:relative;">
            <span class="map-close" onclick="closeMapModal()"
                  style="position:absolute; top:8px; right:12px; cursor:pointer; font-size:20px;">&times;</span>
            <div id="map" style="width:100%;height:320px; margin-bottom:10px;"></div>
            <button id="currentLocationBtn" type="button" class="btn btn-secondary" onclick="getCurrentLocation()">Use Current Location</button>
            <button id="saveLocationBtn" type="button" class="btn btn-success" onclick="saveMapLocation()">Save Location</button>
          </div>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password </label>
          <input type="password" class="form-control" id="password" name="password" minlength="6" required>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password </label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <h5 class="form-section-title">👮 Authority Details</h5>
        <div class="mb-3">
          <label for="badge_id" class="form-label">Badge ID / Police ID Number </label>
          <input type="text" class="form-control" id="badge_id" name="badge_id" required>
        </div>
        <div class="mb-3">
          <label for="designation" class="form-label">Designation </label>
          <select class="form-select" id="designation" name="designation" required>
            <option value="">-- Select Designation --</option>
            <option value="policeman">Policeman</option>
            <option value="fire_service">Fire Service</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="station" class="form-label">Station Name </label>
          <input type="text" class="form-control" id="station" name="station" placeholder="e.g. Dhanmondi Police Station" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="terms" required>
          <label class="form-check-label" for="terms">
            I agree to the <a href="../Html/Policeman_Terms_&_Privacy.html">Terms & Privacy Policy</a>
          </label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Register as Authority</button>
      </form>
    `;
  } else if (role === 'volunteer') {
    formHTML = `
      <div class="signup-container">
        <h3 class="text-center mb-3">🚨 Volunteer Sign Up</h3>
        <form id="volunteerSignupForm" enctype="multipart/form-data" method="post" action="../Php/volunteer_signup.php">
          <h5 class="form-section-title">🔐 General Information</h5>
          <div class="mb-3">
            <label for="fullname" class="form-label">Full Name </label>
            <input type="text" class="form-control" id="fullname" name="fullname" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email Address </label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="mobile" class="form-label">Mobile Number </label>
            <input type="tel" class="form-control" id="mobile" name="mobile" maxlength="11" minlength="11" placeholder="e.g. 017xxxxxxxx" required>
          </div>
          <div class="mb-3">
            <label for="nid" class="form-label">NID Number </label>
            <input type="text" class="form-control" id="nid" name="nid" placeholder="10 or 17 digits" required>
          </div>
          <div class="mb-3">
            <label for="nid_photo" class="form-label">Upload NID Photo </label>
            <input type="file" class="form-control" id="nid_photo" name="nid_photo" accept=".jpg,.jpeg,.png" required>
          </div>
          <div class="mb-3">
            <label for="profile_photo" class="form-label">Profile Photo </label>
            <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
          </div>
             <div class="mb-3">
  <label for="cover_photo" class="form-label">Cover Photo</label>
  <input type="file" class="form-control" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png">
</div>
          <div class="mb-3">
            <label for="dob" class="form-label">Date of Birth </label>
            <input type="date" class="form-control" id="dob" name="dob" required>
          </div>
          <div class="mb-3">
            <label for="gender" class="form-label">Gender </label>
            <select class="form-select" id="gender" name="gender" required>
              <option value="">-- Select Gender --</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
          <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
            Please select your location by clicking the <b>Select location from map</b> button below.
          </p>
          <div class="mb-3">
            <label for="street" class="form-label">Street Address</label>
            <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" >
          </div>
          <div class="mb-3">
            <label for="city" class="form-label">City</label>
            <input type="text" id="city" name="city" class="form-control" placeholder="Enter city">
          </div>
          <div class="mb-3">
            <label for="postal" class="form-label">Postal Code</label>
            <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" >
          </div>
          <div class="mb-3">
            <label for="country" class="form-label">Country</label>
            <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" >
          </div>
          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">
          <button type="button" class="btn btn-primary map-select-btn"
                  onclick="selectLocationFromMap()"
                  style="margin-bottom: 15px;">
            Select location from map
          </button>
          <div id="mapModal" class="map-modal" style="display:none;">
            <div class="map-modal-content" style="background:#fff; padding:15px; border-radius:8px; position:relative;">
              <span class="map-close" onclick="closeMapModal()"
                    style="position:absolute; top:8px; right:12px; cursor:pointer; font-size:20px;">&times;</span>
              <div id="map" style="width:100%;height:320px; margin-bottom:10px;"></div>
              <button id="currentLocationBtn" type="button" class="btn btn-secondary" onclick="getCurrentLocation()">Use Current Location</button>
              <button id="saveLocationBtn" type="button" class="btn btn-success" onclick="saveMapLocation()">Save Location</button>
            </div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password </label>
            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password </label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
          </div>
          <h5 class="form-section-title">🚨 Volunteer Details</h5>
          <div class="mb-3">
            <label for="occupation" class="form-label">Occupation </label>
            <select class="form-select" id="occupation" name="occupation" required>
              <option value="">-- Select Occupation --</option>
              <option value="student">Student</option>
              <option value="job_holder">Job Holder</option>
              <option value="business">Business</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="availability" class="form-label">Availability </label>
            <select class="form-select" id="availability" name="availability" required>
              <option value="">-- Select Availability --</option>
              <option value="full_time">Full-Time</option>
              <option value="part_time">Part-Time</option>
              <option value="on_call">On Call</option>
            </select>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="terms" required>
            <label class="form-check-label" for="terms">
              I agree to the <a href="../Html/Volunteer_Terms_&_Privacy.html">Terms & Privacy Policy</a>
            </label>
          </div>
          <button type="submit" class="btn btn-danger w-100">Join as Volunteer</button>
        </form>
      </div>
    `;
  } else if (role === 'contributor') {
    formHTML = `
      <h3 class="text-center mb-3">🎥 Camera Contributor Sign Up</h3>
      <form id="cameraSignupForm" enctype="multipart/form-data" method="post" action="../Php/camera_signup.php">
        <h5 class="form-section-title">🔐 General Information</h5>
        <div class="mb-3">
          <label for="fullname" class="form-label">Full Name </label>
          <input type="text" class="form-control" id="fullname" name="fullname" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email Address </label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
          <label for="mobile" class="form-label">Mobile Number </label>
          <input type="tel" class="form-control" id="mobile" name="mobile" maxlength="11" minlength="11" placeholder="e.g. 017xxxxxxxx" required>
        </div>
        <div class="mb-3">
          <label for="nid" class="form-label">NID Number </label>
          <input type="text" class="form-control" id="nid" name="nid" placeholder="10 or 17 digits" required>
        </div>
        <div class="mb-3">
          <label for="nid_photo" class="form-label">Upload NID Photo </label>
          <input type="file" class="form-control" id="nid_photo" name="nid_photo" accept=".jpg,.jpeg,.png" required>
        </div>
        <div class="mb-3">
          <label for="profile_photo" class="form-label">Profile Photo</label>
          <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
        </div>
           <div class="mb-3">
  <label for="cover_photo" class="form-label">Cover Photo</label>
  <input type="file" class="form-control" id="cover_photo" name="cover_photo" accept=".jpg,.jpeg,.png">
</div>
        <div class="mb-3">
          <label for="dob" class="form-label">Date of Birth </label>
          <input type="date" class="form-control" id="dob" name="dob" required>
        </div>
        <div class="mb-3">
          <label for="gender" class="form-label">Gender </label>
          <select class="form-select" id="gender" name="gender" required>
            <option value="">-- Select Gender --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
          Please select your location by clicking the <b>Select location from map</b> button below.
        </p>
        <div class="mb-3">
          <label for="street" class="form-label">Street Address</label>
          <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address">
        </div>
        <div class="mb-3">
          <label for="city" class="form-label">City</label>
          <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" >
        </div>
        <div class="mb-3">
          <label for="postal" class="form-label">Postal Code</label>
          <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" >
        </div>
        <div class="mb-3">
          <label for="country" class="form-label">Country</label>
          <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" >
        </div>
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">
        <button type="button" class="btn btn-primary map-select-btn"
                onclick="selectLocationFromMap()"
                style="margin-bottom: 15px;">
          Select location from map
        </button>
        <div id="mapModal" class="map-modal" style="display:none;">
          <div class="map-modal-content" style="background:#fff; padding:15px; border-radius:8px; position:relative;">
            <span class="map-close" onclick="closeMapModal()"
                  style="position:absolute; top:8px; right:12px; cursor:pointer; font-size:20px;">&times;</span>
            <div id="map" style="width:100%;height:320px; margin-bottom:10px;"></div>
            <button id="currentLocationBtn" type="button" class="btn btn-secondary" onclick="getCurrentLocation()">Use Current Location</button>
            <button id="saveLocationBtn" type="button" class="btn btn-success" onclick="saveMapLocation()">Save Location</button>
          </div>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password </label>
          <input type="password" class="form-control" id="password" name="password" minlength="6" required>
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password </label>
          <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <h5 class="form-section-title">🎥 Camera Information</h5>
        <div class="mb-3">
          <label for="camera_type" class="form-label">Camera Type </label>
          <select class="form-select" id="camera_type" name="camera_type" required>
            <option value="">-- Select Camera Type --</option>
            <option value="indoor">Indoor</option>
            <option value="outdoor">Outdoor</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="payment_number" class="form-label">Bkash/Nagad Number (Payment Receiving) </label>
          <input type="tel" class="form-control" id="payment_number" name="payment_number" maxlength="11" minlength="11" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="terms" required>
          <label class="form-check-label" for="terms">
            I agree to the <a href="../Html/Camera_Contribution_Terms_&_Privacy.html">Terms & Privacy Policy</a>
          </label>
        </div>
        <button type="submit" class="btn btn-success w-100">Join as Camera Contributor</button>
      </form>
    `;
  }

  formContainer.innerHTML = formHTML;
  formContainer.classList.remove('show-role-form');
  setTimeout(() => {
    if (role) {
      formContainer.classList.add('show-role-form');
    }
  }, 10);

  if (!role) {
    formContainer.innerHTML = '';
    formContainer.classList.remove('show-role-form');
  }

  updateTermsLinksForCurrentRole();
  saveSignupDraft();
}

document.addEventListener('DOMContentLoaded', () => {
  const roleSelect = document.getElementById('role');
  const dynamicForm = document.getElementById('dynamicForm');

  ensureTermsModal();

  if (roleSelect) {
    roleSelect.addEventListener('change', () => {
      saveSignupDraft();
      updateTermsLinksForCurrentRole();
    });
  }

  if (dynamicForm) {
    dynamicForm.addEventListener('input', saveSignupDraft);
    dynamicForm.addEventListener('change', saveSignupDraft);
  }

  restoreSignupStateIfAvailable();
}); 
let map, marker;

// Open Map Modal
function selectLocationFromMap() {
  document.getElementById('mapModal').style.display = 'flex';

  // Initialize map if not created yet
  if (!map) {
    map = L.map('map').setView([23.8103, 90.4125], 13); // Default: Dhaka

    // Add tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
    }).addTo(map);

    // Click to set marker
    map.on('click', function (e) {
      setMarker(e.latlng);
    });
  }
  setTimeout(() => { map.invalidateSize(); }, 200); // Fix map sizing
}

// Set marker and save coordinates
function setMarker(latlng) {
  if (marker) map.removeLayer(marker);
  marker = L.marker(latlng).addTo(map);

  document.getElementById('latitude').value = latlng.lat;
  document.getElementById('longitude').value = latlng.lng;

  fetchAddress(latlng.lat, latlng.lng);
}

// Get current location
function getCurrentLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      map.setView(latlng, 15);
      setMarker(latlng);
    }, () => alert("Unable to fetch location!"));
  } else {
    alert("Geolocation not supported by your browser.");
  }
}

// Fetch address from coordinates (Reverse Geocoding using Nominatim)
function fetchAddress(lat, lng) {
  fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
    .then(res => res.json())
    .then(data => {
      if (data.address) {
        document.getElementById('street').value = data.address.road || '';
        document.getElementById('city').value = data.address.city || data.address.town || data.address.village || '';
        document.getElementById('postal').value = data.address.postcode || '';
        document.getElementById('country').value = data.address.country || '';
      }
    });
}

// Save location and close modal
function saveMapLocation() {
  if (!document.getElementById('latitude').value) {
    alert("Please select a location on the map.");
    return;
  }
  closeMapModal();
}

// Close Map Modal
function closeMapModal() {
  document.getElementById('mapModal').style.display = 'none';
}

function ensureTermsModal() {
  if (document.getElementById('termsModal')) return;

  const modal = document.createElement('div');
  modal.id = 'termsModal';
  modal.className = 'map-modal terms-modal';
  modal.style.display = 'none';
  modal.innerHTML = `
    <div class="map-modal-content terms-modal-content" role="dialog" aria-modal="true" aria-label="Terms and Privacy Policy">
      <span class="map-close terms-close" id="termsModalClose" aria-label="Close">&times;</span>
      <h4 class="terms-modal-title">Terms & Privacy Policy</h4>
      <iframe id="termsPolicyFrame" class="terms-policy-frame" title="Terms and Privacy Policy" style="width: 100%; height: 100%; min-width: 100%; min-height: 100%; border: none;"></iframe>
    </div>
  `;

  document.body.appendChild(modal);

  const closeBtn = modal.querySelector('#termsModalClose');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeTermsModal);
  }

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeTermsModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.style.display === 'flex') {
      closeTermsModal();
    }
  });
}

function openTermsModal(url) {
  ensureTermsModal();

  const modal = document.getElementById('termsModal');
  const frame = document.getElementById('termsPolicyFrame');
  if (!modal || !frame) return;

  frame.src = new URL(url, window.location.href).toString();
  modal.style.display = 'flex';
}

function closeTermsModal() {
  const modal = document.getElementById('termsModal');
  const frame = document.getElementById('termsPolicyFrame');
  if (!modal || !frame) return;

  modal.style.display = 'none';
  frame.src = 'about:blank';
}


// Open all role-specific terms links as a popup modal instead of navigating away.
document.addEventListener('click', function (event) {
  const termsLink = event.target.closest('a[data-terms-modal="1"], a[href*="Terms_&_Privacy.html"]');
  if (!termsLink) return;

  event.preventDefault();
  openTermsModal(termsLink.getAttribute('href') || '');
});

// --- FORGOT PASSWORD LOGIC ---
let resetEmail = '';

function openForgotPasswordModal() {
  document.getElementById('forgot-password-modal').style.display = 'block';
  document.getElementById('fp-step-1').style.display = 'block';
  document.getElementById('fp-step-2').style.display = 'none';
  document.getElementById('fp-step-3').style.display = 'none';
  document.getElementById('fp-email').value = '';
  document.getElementById('fp-code').value = '';
  document.getElementById('fp-new-password').value = '';
}

function closeForgotPasswordModal() {
  document.getElementById('forgot-password-modal').style.display = 'none';
}

async function requestPasswordReset() {
  const email = document.getElementById('fp-email').value.trim();
  if (!email) {
    alert("Please enter your email");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', email);
  
  try {
    const res = await fetch('../Php/forgot_password.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      resetEmail = email;
      document.getElementById('fp-step-1').style.display = 'none';
      document.getElementById('fp-step-2').style.display = 'block';
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}

async function verifyPasswordCode() {
  const code = document.getElementById('fp-code').value.trim();
  if (!code) {
    alert("Please enter the code");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', resetEmail);
  fd.append('code', code);
  
  try {
    const res = await fetch('../Php/verify_code.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('fp-step-2').style.display = 'none';
      document.getElementById('fp-step-3').style.display = 'block';
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}

async function resetPassword() {
  const newPassword = document.getElementById('fp-new-password').value;
  if (!newPassword) {
    alert("Please enter a new password");
    return;
  }
  
  const fd = new FormData();
  fd.append('email', resetEmail);
  fd.append('new_password', newPassword);
  
  try {
    const res = await fetch('../Php/reset_password.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      alert("Password updated successfully! You can now log in.");
      closeForgotPasswordModal();
    } else {
      alert("Error: " + data.error);
    }
  } catch (err) {
    alert("Network error");
  }
}
