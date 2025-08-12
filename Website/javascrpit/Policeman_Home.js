document.getElementById('requestBroadcastBtn').addEventListener('click', function() {
  const status = document.getElementById('broadcastStatus');
  status.innerText = "Request sent to admin. Please wait for approval...";
  status.style.color = "orange";

  // Simulate admin approval after 3 seconds
  setTimeout(() => {
    const isApproved = true; // Simulate admin approval (replace with real logic)

    if (isApproved) {
      status.innerText = "Request approved! Broadcast link is now available.";
      status.style.color = "green";
      document.getElementById('broadcastLink').style.display = "block";
    } else {
      status.innerText = "Request denied by admin.";
      status.style.color = "red";
    }
  }, 3000);
});


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

const monthYear = document.getElementById('monthYear');
const calendarGrid = document.getElementById('calendarGrid');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');

const eventModal = document.getElementById('myEventModal');
const closeModalBtn = document.getElementById('closeMyModal');
const selectedDateText = document.getElementById('selectedDateText');
const eventInput = document.getElementById('eventInput');
const saveEventBtn = document.getElementById('saveEventBtn');

const now = new Date();
let activeDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
let selectedDate = null;

// Store events by date string "YYYY-MM-DD"
let events = {};

function daysInMonth(year, month) {
  return new Date(year, month + 1, 0).getDate();
}

function formatDate(year, month, day) {
  return `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
}

function renderWeekdays() {
  let weekdaysContainer = document.querySelector('.calendar-weekdays');
  if (!weekdaysContainer) {
    weekdaysContainer = document.createElement('div');
    weekdaysContainer.className = 'calendar-weekdays';
    monthYear.parentNode.insertBefore(weekdaysContainer, calendarGrid);
  }

  const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  weekdaysContainer.innerHTML = '';

  weekdays.forEach(day => {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = day;
    dayDiv.style.textAlign = 'center';
    dayDiv.style.fontWeight = 'bold';
    weekdaysContainer.appendChild(dayDiv);
  });

  weekdaysContainer.style.display = 'grid';
  weekdaysContainer.style.gridTemplateColumns = 'repeat(7, 1fr)';
  weekdaysContainer.style.marginBottom = '8px';
  weekdaysContainer.style.color = '#666';
}

function renderCalendar() {
  const year = activeDate.getFullYear();
  const month = activeDate.getMonth();

  monthYear.textContent = activeDate.toLocaleString('default', { month: 'long', year: 'numeric' });

  renderWeekdays();

  calendarGrid.classList.add('fade-out');
  setTimeout(() => {
    calendarGrid.innerHTML = '';

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = daysInMonth(year, month);

    // Empty cells before first day
    for(let i = 0; i < firstDay; i++) {
      const emptyCell = document.createElement('div');
      emptyCell.classList.add('empty-cell');
      calendarGrid.appendChild(emptyCell);
    }

    // Days of month
    for(let day = 1; day <= totalDays; day++) {
      const dayDiv = document.createElement('div');
      dayDiv.classList.add('calendar-day');
      dayDiv.textContent = day;

      const dateKey = formatDate(year, month, day);

      // Highlight today
      const today = new Date();
      if (
        day === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear()
      ) {
        dayDiv.classList.add('today');
      }

      // Event icon
      if (events[dateKey]) {
        const img = document.createElement('img');
        img.src = '../Images/calendar.gif';  // Replace with correct path
        img.alt = 'Event';
        img.classList.add('event-icon');
        dayDiv.appendChild(img);
      }

      dayDiv.addEventListener('click', () => {
        selectedDate = dateKey;
        selectedDateText.textContent = `Selected date: ${selectedDate}`;
        eventInput.value = events[selectedDate] ? events[selectedDate].join(', ') : '';
        eventModal.style.display = 'flex';
        eventInput.focus();
      });

      calendarGrid.appendChild(dayDiv);
    }

    calendarGrid.classList.remove('fade-out');
  }, 300);
}

// Handle month navigation with animation
prevMonthBtn.onclick = () => {
  activeDate.setMonth(activeDate.getMonth() - 1);
  renderCalendar();
};

nextMonthBtn.onclick = () => {
  activeDate.setMonth(activeDate.getMonth() + 1);
  renderCalendar();
};

closeModalBtn.onclick = () => {
  eventModal.style.display = 'none';
};

saveEventBtn.onclick = () => {
  if (!selectedDate) return;
  const val = eventInput.value.trim();
  if (val) {
    events[selectedDate] = val.split(',').map(s => s.trim());
  } else {
    delete events[selectedDate];
  }
  eventModal.style.display = 'none';
  renderCalendar();
};

window.onclick = (e) => {
  if (e.target === eventModal) {
    eventModal.style.display = 'none';
  }
};

// Auto update at midnight
setInterval(() => {
  const now = new Date();
  if (now.getHours() === 0 && now.getMinutes() === 0) {
    activeDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    renderCalendar();
  }
}, 60000);

// Initial render
renderCalendar();

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