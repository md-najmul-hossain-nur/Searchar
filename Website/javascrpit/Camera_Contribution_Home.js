
const modal = document.getElementById("postModal");
const feed = document.getElementById("post-feed");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImage = null;
let selectedVideo = null;

function openModal() {
  modal.style.display = "flex";
}

function closeModal() {
  modal.style.display = "none";
  document.getElementById("postText").value = "";
  document.getElementById("imageUpload").value = "";
  document.getElementById("videoUpload").value = "";
  mediaPreview.innerHTML = "";
  selectedImage = null;
  selectedVideo = null;
}

// Handle image upload preview
document.getElementById("imageUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedImage = file;
    mediaPreview.innerHTML = `<img src="${URL.createObjectURL(file)}">`;
    selectedVideo = null;
    document.getElementById("videoUpload").value = "";
  }
});

// Handle video upload preview
document.getElementById("videoUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedVideo = file;
    mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls></video>`;
    selectedImage = null;
    document.getElementById("imageUpload").value = "";
  }
});

// Create post
function createPost() {
  const text = document.getElementById("postText").value.trim();
  if (text === "" && !selectedImage && !selectedVideo) {
    alert("Please add text or media to post!");
    return;
  }

  const post = document.createElement("div");
  post.classList.add("post");
  if (text) post.innerHTML = `<p>${text}</p>`;

  if (selectedImage) {
    const img = document.createElement("img");
    img.src = URL.createObjectURL(selectedImage);
    post.appendChild(img);
  }

  if (selectedVideo) {
    const video = document.createElement("video");
    video.src = URL.createObjectURL(selectedVideo);
    video.controls = true;
    post.appendChild(video);
  }

  feed.prepend(post);
  closeModal();
}
function openMissingForm() {
  document.getElementById("missingFormModal").style.display = "flex";
}

function closeMissingForm() {
  document.getElementById("missingFormModal").style.display = "none";
}

// Close when clicking outside the form
window.onclick = function(event) {
  const modal = document.getElementById("missingFormModal");
  if (event.target === modal) {
    modal.style.display = "none";
  }
};
document.addEventListener("DOMContentLoaded", function () {
    var map = L.map('emergency-map').setView([23.8103, 90.4125], 13);

    // Map tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Icons
    var hospitalIcon = L.icon({ 
  iconUrl: '../Images/hospital.gif',  // your hospital icon in image folder
  iconSize: [30, 30] 
});

var fireIcon = L.icon({ 
  iconUrl: '../Images/fire.gif',  // your fire station icon in image folder
  iconSize: [30, 30] 
});

var policeIcon = L.icon({ 
  iconUrl: '../Images/police.gif',  // your police icon in image folder
  iconSize: [30, 30] 
});

    var userMarker, routingControl;
    var markers = []; // store markers to remove later

    // Remove all markers & routes
    function clearMap() {
        markers.forEach(m => map.removeLayer(m));
        markers = [];
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
    }

    // Fetch places function
    function fetchPlaces(lat, lon, type, icon) {
        clearMap(); // Remove old markers and routes

        // Show user marker
        userMarker = L.marker([lat, lon]).addTo(map)
            .bindPopup("📍 You are here").openPopup();
        markers.push(userMarker);

        var url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${type}&bounded=1&viewbox=${lon-0.02},${lat+0.02},${lon+0.02},${lat-0.02}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    alert("No " + type + " found nearby.");
                    return;
                }
                data.forEach(place => {
                    var marker = L.marker([place.lat, place.lon], { icon: icon })
                        .addTo(map)
                       .bindPopup(`<b>${place.display_name}</b><br>
  <button class="route-btn" onclick="showRoute(${lat}, ${lon}, ${place.lat}, ${place.lon})">
    🚗 Show Route
  </button>`);

                    markers.push(marker);
                });
            });
    }

    // Show route function
    window.showRoute = function(startLat, startLon, endLat, endLon) {
        if (routingControl) {
            map.removeControl(routingControl);
        }
        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(startLat, startLon),
                L.latLng(endLat, endLon)
            ],
            routeWhileDragging: false,
            show: false
        }).addTo(map);
    }

    // Get current location and show places
    function locateAndShow(type, icon) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (pos) {
                var lat = pos.coords.latitude;
                var lon = pos.coords.longitude;
                map.setView([lat, lon], 14);
                fetchPlaces(lat, lon, type, icon);
            });
        } else {
            alert("Geolocation not supported.");
        }
    }

    // Button events
    document.getElementById("find-hospitals").addEventListener("click", function () {
        locateAndShow("hospital", hospitalIcon);
    });
    document.getElementById("find-fire").addEventListener("click", function () {
        locateAndShow("fire station", fireIcon);
    });
    document.getElementById("find-police").addEventListener("click", function () {
        locateAndShow("police station", policeIcon);
    });
});
// Comment Show/Hide Toggle
document.querySelectorAll('.comment-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const commentSection = post.querySelector('.comment-module');
    if (commentSection.style.display === "none" || commentSection.style.display === "") {
      commentSection.style.display = "block"; // Show comments
    } else {
      commentSection.style.display = "none"; // Hide comments
    }
  });
});

// Likes & Dislikes Count
let likesUpParent = document.getElementsByClassName("comment-likes-up");
let likesDownParent = document.getElementsByClassName("comment-likes-down");

let likesEl = [];
for (let i = 0; i < likesUpParent.length; i++) {
  let likesUp = likesUpParent[i].getElementsByTagName("img")[0];
  let likesDown = likesDownParent[i].getElementsByTagName("img")[0];
  likesEl.push(likesUp, likesDown);
}

likesEl.forEach(el => {
  el.addEventListener("click", function () {
    let likesCountEl = this.parentElement.querySelector("span");
    let likesCount = likesCountEl ? parseInt(likesCountEl.innerText) || 0 : 0;
    likesCountEl.innerText = likesCount + 1;
  });
});
document.querySelectorAll('.comment-reply a').forEach(replyBtn => {
  replyBtn.addEventListener('click', function (e) {
    e.preventDefault();

    // পুরানো reply box রিমুভ
    document.querySelectorAll('.reply-input-area').forEach(box => box.remove());

    // নতুন reply box
    let replyBox = document.createElement('div');
    replyBox.classList.add('reply-input-area');
    replyBox.innerHTML = `
      <div class="comment-editor" contenteditable="true" data-placeholder="Write a reply..."></div>
<button class="comment-send-btn">
  <img src="../Images/send.png" alt="Send">
</button>    `;

    // `.comment` এর নিচে বসানো
    let commentLi = this.closest('li');
    commentLi.appendChild(replyBox); // এখন এটা নিচে দেখাবে

    // Auto resize
    const editor = replyBox.querySelector('.comment-editor');
    editor.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });

    // Reply send
    replyBox.querySelector('.comment-send-btn').addEventListener('click', function () {
      let replyText = editor.innerText.trim();
      if (replyText) {
        alert("Reply sent: " + replyText); // এখানে AJAX দিয়ে সার্ভারে পাঠানো যাবে
        replyBox.remove();
      }
    });
  });
});
// Open Modal and Set Preview
document.querySelectorAll('.share-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const text = post.querySelector('p')?.innerText || '';
    const img = post.querySelector('.post-img')?.getAttribute('src') || '';

    // Fill preview
    document.getElementById('sharedPostText').innerText = text;
    document.getElementById('sharedPostImage').src = img;

    // Show modal in center
    document.getElementById('postModal').style.display = 'flex';
  });
});
function closeModal() {
  document.getElementById('postModal').style.display = 'none';
  document.getElementById('postText').value = '';
  document.getElementById('sharedPostText').innerText = '';  // ❌ এই লাইন
  document.getElementById('sharedPostImage').src = '';       // ❌ এই লাইন
  document.getElementById('facebookShareToggle').checked = false;
}
document.getElementById('anonToggle').addEventListener('change', function () {
  if (this.checked) {
    console.log("Anonymous mode enabled");
    // Hide user's name or change UI if needed
  } else {
    console.log("Anonymous mode disabled");
    // Revert changes
  }
});

const openBtn = document.getElementById('openWithdrawBtn');
const withdrawModal = document.getElementById('withdrawModal');
const closeBtn = document.getElementById('closeModalBtn');
const withdrawForm = document.getElementById('withdrawForm');

openBtn.addEventListener('click', () => {
  withdrawModal.style.display = 'flex';
});

closeBtn.addEventListener('click', () => {
  withdrawModal.style.display = 'none';
});

window.addEventListener('click', (e) => {
  if (e.target === withdrawModal) {
    withdrawModal.style.display = 'none';
  }
});

withdrawForm.addEventListener('submit', function(e) {
  e.preventDefault();

  const amount = Number(this.amount.value);
  const availableBalance = 1000;
  const minWithdrawal = 5;

  if (amount < minWithdrawal) {
    alert(`Minimum withdrawal amount is $${minWithdrawal}.`);
    return;
  }

  if (amount > availableBalance) {
    alert('Amount cannot exceed available balance.');
    return;
  }

  alert('Withdrawal request submitted!');
  withdrawModal.style.display = 'none';
  this.reset();
});
function filterPosts(category) {
  // Remove .active from all filter buttons
  document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
  // Add .active to the clicked button
  event.target.classList.add('active');
  // Show/hide posts
  document.querySelectorAll('.post').forEach(post => {
    if (category === 'all' || post.dataset.category === category) {
      post.style.display = '';
    } else {
      post.style.display = 'none';
    }
  });
}
document.addEventListener("DOMContentLoaded", () => {
  // Elements
  const feedForm = document.getElementById("camFeedForm");
  const uploadSection = document.getElementById("camUploadSection");
  const liveInputSection = document.getElementById("camLiveInputSection");
  const fileInput = document.getElementById("camFileInput");
  const liveURLInput = document.getElementById("camLiveURL");

  const feedFormModal = document.getElementById("camFeedFormModal");
  const feedFormClose = feedFormModal.querySelector(".cam-form-close");

  const startFeedBtn = document.getElementById("startFeedBtn");

  // Helpers
  const openModal = () => feedFormModal.classList.add("show");
  const closeModal = () => {
    feedFormModal.classList.remove("show");
    // Reset form and hide sections
    feedForm.reset();
    uploadSection.style.display = "none";
    liveInputSection.style.display = "none";
  };

  // Open feed form modal
  startFeedBtn.addEventListener("click", openModal);

  // Toggle input sections based on radio selection
  feedForm.addEventListener("change", (e) => {
    const type = feedForm.feedType?.value;
    if (type === "live") {
      liveInputSection.style.display = "block";
      uploadSection.style.display = "none";
    } else if (type === "recorded") {
      uploadSection.style.display = "block";
      liveInputSection.style.display = "none";
    }
  });

  // Form submit
  feedForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const type = feedForm.feedType?.value;

    if (type === "live") {
      const url = liveURLInput.value.trim();
      if (url) {
        alert("Video link has been submitted!");
      } else {
        try {
          await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
          alert("Live feed has started!");
        } catch (err) {
          alert("Camera/Mic permission denied or unavailable.\n" + err);
        }
      }
    } else if (type === "recorded") {
      const file = fileInput.files[0];
      if (file) {
        alert("Video file has been uploaded!");
      } else {
        alert("Please select a video file.");
      }
    }

    closeModal();
  });

  // Close button
  feedFormClose.addEventListener("click", closeModal);

  // Close modal on outside click
  window.addEventListener("click", (e) => {
    if (e.target === feedFormModal) closeModal();
  });
});
