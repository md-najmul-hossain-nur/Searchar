let container = document.getElementById('container')

toggle = () => {
	container.classList.toggle('sign-in')
	container.classList.toggle('sign-up')
}

setTimeout(() => {
	container.classList.add('sign-in')
}, 200)

// Logo click redirects to home page
document.getElementById('logo').onclick = function() {
  window.location.href = '../Html/index.html';
};

// Show role-based sign-up form with animation
function showForm() {
  const role = document.getElementById('role').value;
  const formContainer = document.getElementById('dynamicForm');
  let formHTML = '';

  if (role === 'user') {
    formHTML = `
      <h3>User Sign Up</h3>
      <!-- Social Login Buttons -->
<div class="social-login-buttons">
  <button type="button" class="social-btn fb-btn">
    <img src="../Images/facebook.png" alt="Facebook" class="social-icon" /> Sign in with Facebook
  </button>
  <button type="button" class="social-btn google-btn">
    <img src="../Images/google.png" alt="Google" class="social-icon" /> Sign in with Google
  </button>
</div>

      <form id="userSignupForm" enctype="multipart/form-data" method="post" action="user_signup.php">      
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
        <input type="tel" class="form-control" id="mobile" name="mobile" pattern="01[3-9]\d{8}" placeholder="e.g. 017xxxxxxxx" required>
      </div>

      <div class="mb-3">
        <label for="nid" class="form-label">NID Number </label>
        <input type="text" class="form-control" id="nid" name="nid" pattern="\d{10}|\d{17}" placeholder="10 or 17 digits" required>
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

         <!-- Helper Instruction -->
<p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
  Please select your location by clicking the <b>Select location from map</b> button below.
</p>

<!-- Address Fields -->
<div class="mb-3">
  <label for="street" class="form-label">Street Address</label>
  <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" disabled>
</div>

<div class="mb-3">
  <label for="city" class="form-label">City</label>
  <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" disabled>
</div>

<div class="mb-3">
  <label for="postal" class="form-label">Postal Code</label>
  <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" disabled>
</div>

<div class="mb-3">
  <label for="country" class="form-label">Country</label>
  <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" disabled>
</div>

<!-- Hidden Latitude & Longitude -->
<input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">

<!-- Map Select Button -->
<button type="button" class="btn btn-primary map-select-btn" 
        onclick="selectLocationFromMap()" 
        style="margin-bottom: 15px;">
    Select location from map
</button>

<!-- Map Modal -->
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
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms">
          I agree to the <a href="../Html/User_Terms_&_Privacy.html">Terms & Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-primary w-100">Sign Up</button>  

    `;
  } else if (role === 'police') {
    formHTML = `
      <h3 class="text-center mb-3">👮 Policeman / Authority Sign Up</h3>
    <!-- Social Login Buttons -->
     <div class="social-login-buttons">
  <button type="button" class="social-btn fb-btn">
    <img src="../Images/facebook.png" alt="Facebook" class="social-icon" /> Sign in with Facebook
  </button>
  <button type="button" class="social-btn google-btn">
    <img src="../Images/google.png" alt="Google" class="social-icon" /> Sign in with Google
  </button>
</div>


      <form id="policeSignupForm" enctype="multipart/form-data" method="post" action="police_signup.php">

      <!-- 🔐 Common Fields -->
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
        <input type="tel" class="form-control" id="mobile" name="mobile" pattern="01[3-9]\d{8}" placeholder="e.g. 017xxxxxxxx" required>
      </div>

      <div class="mb-3">
        <label for="nid" class="form-label">NID Number </label>
        <input type="text" class="form-control" id="nid" name="nid" pattern="\d{10}|\d{17}" placeholder="10 or 17 digits" required>
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

      <!-- Helper Instruction -->
<p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
  Please select your location by clicking the <b>Select location from map</b> button below.
</p>

<!-- Address Fields -->
<div class="mb-3">
  <label for="street" class="form-label">Street Address</label>
  <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" disabled>
</div>

<div class="mb-3">
  <label for="city" class="form-label">City</label>
  <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" disabled>
</div>

<div class="mb-3">
  <label for="postal" class="form-label">Postal Code</label>
  <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" disabled>
</div>

<div class="mb-3">
  <label for="country" class="form-label">Country</label>
  <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" disabled>
</div>

<!-- Hidden Latitude & Longitude -->
<input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">

<!-- Map Select Button -->
<button type="button" class="btn btn-primary map-select-btn" 
        onclick="selectLocationFromMap()" 
        style="margin-bottom: 15px;">
    Select location from map
</button>

<!-- Map Modal -->
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

      <!-- 👮 Policeman / Authority Specific Fields -->
      <h5 class="form-section-title">👮 Authority Details</h5>

      <div class="mb-3">
        <label for="badge_id" class="form-label">Badge ID / Police ID Number </label>
        <input type="text" class="form-control" id="badge_id" name="badge_id" required>
      </div>

      <div class="mb-3">
        <label for="designation" class="form-label">Designation </label>
        <select class="form-select" id="designation" name="designation" required>
          <option value="">-- Select Designation --</option>
          <option value="si">Sub Inspector (SI)</option>
          <option value="asi">Assistant Sub Inspector (ASI)</option>
          <option value="inspector">Inspector</option>
          <option value="officer">Officer</option>
          <option value="fire_service">Fire Service Official</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="station" class="form-label">Station Name </label>
        <input type="text" class="form-control" id="station" name="station" placeholder="e.g. Dhanmondi Police Station" required>
      </div>

      <div class="mb-3">
        <label for="official_id" class="form-label">Official Letter / Appointment ID (PDF) </label>
        <input type="file" class="form-control" id="official_id" name="official_id" accept=".pdf" required>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms">
          I agree to the <a href="../Html/Policeman_Terms_&_Privacy.html">Terms & Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-primary w-100">Register as Authority</button>
    `;
  } else if (role === 'volunteer') {
    formHTML = `
       <div class="signup-container">
    <h3 class="text-center mb-3">🚨 Volunteer Sign Up</h3>
     <!-- Social Login Buttons -->
<div class="social-login-buttons">
 
 <button type="button" class="social-btn fb-btn">
    <img src="../Images/facebook.png" alt="Facebook" class="social-icon" /> Sign in with Facebook
  </button>
  <button type="button" class="social-btn google-btn">
    <img src="../Images/google.png" alt="Google" class="social-icon" /> Sign in with Google
  </button>
  
</div>

    <form id="volunteerSignupForm" enctype="multipart/form-data" method="post" action="volunteer_signup.php">

      <!-- 🔐 Common Fields -->
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
        <input type="tel" class="form-control" id="mobile" name="mobile" pattern="01[3-9]\d{8}" placeholder="e.g. 017xxxxxxxx" required>
      </div>

      <div class="mb-3">
        <label for="nid" class="form-label">NID Number </label>
        <input type="text" class="form-control" id="nid" name="nid" pattern="\d{10}|\d{17}" placeholder="10 or 17 digits" required>
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

    <!-- Helper Instruction -->
<p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
  Please select your location by clicking the <b>Select location from map</b> button below.
</p>

<!-- Address Fields -->
<div class="mb-3">
  <label for="street" class="form-label">Street Address</label>
  <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" disabled>
</div>

<div class="mb-3">
  <label for="city" class="form-label">City</label>
  <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" disabled>
</div>

<div class="mb-3">
  <label for="postal" class="form-label">Postal Code</label>
  <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" disabled>
</div>

<div class="mb-3">
  <label for="country" class="form-label">Country</label>
  <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" disabled>
</div>

<!-- Hidden Latitude & Longitude -->
<input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">

<!-- Map Select Button -->
<button type="button" class="btn btn-primary map-select-btn" 
        onclick="selectLocationFromMap()" 
        style="margin-bottom: 15px;">
    Select location from map
</button>

<!-- Map Modal -->
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

     

      <div class="mb-3">
        <label for="police_clearance" class="form-label">Police Clearance Certificate (Optional)</label>
        <input type="file" class="form-control" id="police_clearance" name="police_clearance" accept=".jpg,.jpeg,.png,.pdf">
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="geo_permission" name="geo_permission" value="yes" required>
        <label class="form-check-label" for="geo_permission">I agree to share my geo-location during missions.</label>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms">
          I agree to the <a href="../Html/Volunteer_Terms_&_Privacy.html">Terms & Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-danger w-100">Join as Volunteer</button>

    `;
  } else if (role === 'contributor') {
    formHTML = `
       <h3 class="text-center mb-3">🎥 Camera Contributor Sign Up</h3>
      <!-- Social Login Buttons -->
<div class="social-login-buttons">
  <button type="button" class="social-btn fb-btn">
    <img src="../Images/facebook.png" alt="Facebook" class="social-icon" /> Sign in with Facebook
  </button>
  <button type="button" class="social-btn google-btn">
    <img src="../Images/google.png" alt="Google" class="social-icon" /> Sign in with Google
  </button>
</div>

       <form id="cameraSignupForm" enctype="multipart/form-data" method="post" action="camera_signup.php">

      <!-- 🔐 Common Fields -->
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
        <input type="tel" class="form-control" id="mobile" name="mobile" pattern="01[3-9]\d{8}" placeholder="e.g. 017xxxxxxxx" required>
      </div>

      <div class="mb-3">
        <label for="nid" class="form-label">NID Number </label>
        <input type="text" class="form-control" id="nid" name="nid" pattern="\d{10}|\d{17}" placeholder="10 or 17 digits" required>
      </div>

      <div class="mb-3">
        <label for="nid_photo" class="form-label">Upload NID Photo </label>
        <input type="file" class="form-control" id="nid_photo" name="nid_photo" accept=".jpg,.jpeg,.png" required>
      </div>

      <div class="mb-3">
        <label for="profile_photo" class="form-label">Profile Photo (Optional)</label>
        <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png">
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

         <!-- Helper Instruction -->
<p class="map-helper-text" style="margin:12px 0 8px 0; color:#425a78; font-size:1em;">
  Please select your location by clicking the <b>Select location from map</b> button below.
</p>

<!-- Address Fields -->
<div class="mb-3">
  <label for="street" class="form-label">Street Address</label>
  <input type="text" id="street" name="street" class="form-control" placeholder="Enter street address" disabled>
</div>

<div class="mb-3">
  <label for="city" class="form-label">City</label>
  <input type="text" id="city" name="city" class="form-control" placeholder="Enter city" disabled>
</div>

<div class="mb-3">
  <label for="postal" class="form-label">Postal Code</label>
  <input type="text" id="postal" name="postal" class="form-control" placeholder="Enter postal code" disabled>
</div>

<div class="mb-3">
  <label for="country" class="form-label">Country</label>
  <input type="text" id="country" name="country" class="form-control" placeholder="Enter country" disabled>
</div>

<!-- Hidden Latitude & Longitude -->
<input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">

<!-- Map Select Button -->
<button type="button" class="btn btn-primary map-select-btn" 
        onclick="selectLocationFromMap()" 
        style="margin-bottom: 15px;">
    Select location from map
</button>
<!-- Map Modal -->
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

      <!-- 🎥 Camera Contributor Specific Fields -->
      <h5 class="form-section-title">🎥 Camera Information</h5>

      <div class="mb-3">
        <label for="camera_location" class="form-label">Camera Location (GPS Address) </label>
        <input type="text" class="form-control" id="camera_location" name="camera_location" placeholder="Enter GPS or full address" required>
      </div>

      <div class="mb-3">
        <label for="camera_type" class="form-label">Camera Type </label>
        <select class="form-select" id="camera_type" name="camera_type" required>
          <option value="">-- Select Camera Type --</option>
          <option value="indoor">Indoor</option>
          <option value="outdoor">Outdoor</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="stream_type" class="form-label">Camera Stream Type </label>
        <select class="form-select" id="stream_type" name="stream_type" required>
          <option value="">-- Select Stream Type --</option>
          <option value="rtsp">RTSP Stream</option>
          <option value="ip_camera">IP Camera</option>
          <option value="recorded">Recorded Video Upload</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="bandwidth" class="form-label">Monthly Bandwidth Limit (GB) </label>
        <input type="number" class="form-control" id="bandwidth" name="bandwidth" required>
      </div>

      <div class="mb-3">
        <label for="payment_number" class="form-label">Bkash/Nagad Number (Payment Receiving) </label>
        <input type="tel" class="form-control" id="payment_number" name="payment_number" pattern="01[3-9]\d{8}" required>
      </div>

      <div class="mb-3">
        <label for="agreement" class="form-label">Contract Agreement (PDF) </label>
        <input type="file" class="form-control" id="agreement" name="agreement" accept=".pdf" required>
      </div>

      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms">
          I agree to the <a href="../Html/Camera_Contribution_Terms_&_Privacy.html">Terms & Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn btn-success w-100">Join as Camera Contributor</button>
    `;
  }

  // Set HTML and animate in
  formContainer.innerHTML = formHTML;
  formContainer.classList.remove('show-role-form');
  setTimeout(() => {
    if (role) {
      formContainer.classList.add('show-role-form');
    }
  }, 10);

  // If no role, clear the form and remove animation
  if (!role) {
    formContainer.innerHTML = '';
    formContainer.classList.remove('show-role-form');
  }
}

// Bubble animation script (larger bubbles)
document.addEventListener('DOMContentLoaded', () => {
  const bubbleContainer = document.querySelector('.bubble-background');
  for (let i = 0; i < 18; i++) {
    const bubble = document.createElement('div');
    bubble.classList.add('bubble');
    // Increase bubble size: min 80px, max 180px
    const size = Math.random() * (180 - 80) + 80; // px
    bubble.style.width = `${size}px`;
    bubble.style.height = `${size}px`;
    bubble.style.left = `${Math.random() * 100}vw`;
    bubble.style.animationDuration = `${Math.random() * (19 - 9) + 9}s`;
    bubble.style.animationDelay = `-${Math.random() * 19}s`;
    bubbleContainer.appendChild(bubble);
  }
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
document.addEventListener('click', function(e) {
  const googleBtn = e.target.closest('.google-btn');
  if (googleBtn) {
    let selectedRole = document.getElementById('role')?.value || '';
    if (!selectedRole) {
      alert('Please select your role first!');
      return;
    }
    google.accounts.id.initialize({
      client_id: '469478841301-arnhu8ocbr8pfji2fhochn3bbqrf5ivf.apps.googleusercontent.com',
      callback: function(response) {
        fetch('../Php/google-signup.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            credential: response.credential,
            role: selectedRole
          })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Role-wise page mapping
            const roleToPage = {
              user: '../Html/User_Home.html',
              volunteer: '../Html/Volunteer_Home.html',
              police: '../Html/Policeman_Home.html',
              contributor: '../Html/Camera_Contribution_Home.html'
            };
            const goTo = roleToPage[data.role || selectedRole] || '../Html/User_Home.html';
            alert('Sign up successful as ' + (data.role || selectedRole) + '!');
            window.location.href = goTo;
          } else {
            alert('Google sign up failed! ' + (data.error || ''));
          }
        });
      }
    });
    google.accounts.id.prompt();
  }
});
// Facebook SDK initialization
window.fbAsyncInit = function() {
  FB.init({
    appId      : '760174033843727',
    cookie     : true,
    xfbml      : true,
    version    : 'v19.0'
  });
};

document.addEventListener('click', function(e) {
  const fbBtn = e.target.closest('.facebook-btn');
  if (!fbBtn) return;

  const selectedRole = document.getElementById('role')?.value || '';
  if (!selectedRole) {
    alert('Please select your role first!');
    return;
  }

  // Facebook Login Flow
  FB.login(function(response) {
    if (!response.authResponse) {
      alert('Facebook login cancelled or failed!');
      return;
    }

    FB.api('/me', {fields: 'id,name,email,picture'}, function(userInfo) {
      if (!userInfo || userInfo.error) {
        alert('Could not fetch Facebook user info!');
        return;
      }

      fetch('../Php/facebook-signup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          fb_id: userInfo.id,
          name: userInfo.name,
          email: userInfo.email,
          picture: userInfo.picture?.data?.url || '',
          role: selectedRole
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          // Role-wise page mapping
          const roleToPage = {
            user: '../Html/User_Home.html',
            volunteer: '../Html/Volunteer_Home.html',
            police: '../Html/Policeman_Home.html',
            contributor: '../Html/Camera_Contribution_Home.html'
          };
          const goTo = roleToPage[data.role || selectedRole] || '../Html/User_Home.html';
          alert('Sign up successful as ' + (data.role || selectedRole) + '!');
          window.location.href = goTo;
        } else {
          alert('Facebook sign up failed! ' + (data.error || ''));
        }
      })
      .catch(() => {
        alert('Network error during sign up!');
      });
    });
  }, {scope: 'public_profile,email'});
});