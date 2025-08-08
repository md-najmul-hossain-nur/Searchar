 // Sales Chart
    const salesChart = new Chart(document.getElementById('salesChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
        datasets: [
          {
            label: '2025',
            data: [65, 75, 70, 60, 65, 75, 85],
            borderColor: '#4339f2',
            backgroundColor: 'transparent',
            borderWidth: 3,
            pointBackgroundColor: '#4339f2',
            tension: 0.4
          },
          {
            label: '2024',
            data: [40, 60, 80, 70, 60, 70, 90],
            borderColor: '#fff',
            backgroundColor: 'transparent',
            borderWidth: 3,
            pointBackgroundColor: '#fff',
            tension: 0.4
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } },
          y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' }, beginAtZero: true, min: 40, max: 90 }
        }
      }
    });

    // Orders Chart
    const ordersChart = new Chart(document.getElementById('ordersChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
        datasets: [
          {
            label: '2025',
            data: [40, 60, 80, 70, 100, 30, 10],
            backgroundColor: '#f64e60'
          },
          {
            label: '2024',
            data: [20, 50, 60, 30, 90, 25, 80],
            backgroundColor: '#4339f2'
          }
        ]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,0.05)' } },
          y: { grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, min: 0, max: 110 }
        }
      }
    });
    // Sidebar click logic
    document.querySelectorAll('.sidebar ul li').forEach(function(item) {
      item.addEventListener('click', function() {
        // Remove active from all sidebar items
        document.querySelectorAll('.sidebar ul li').forEach(li => li.classList.remove('active'));
        item.classList.add('active');
        // Hide all sections
        document.querySelectorAll('.main-section').forEach(sec => sec.classList.remove('active'));
        // Show the one with same id as data-section
        const sectionId = item.getAttribute('data-section');
        if(sectionId) {
          const section = document.getElementById(sectionId);
          if(section) section.classList.add('active');
        }
      });
    });

// Initialize the map
const map = L.map('map').setView([23.8103, 90.4125], 13); // Dhaka default

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 16,
  attribution: '© OpenStreetMap'
}).addTo(map);

let marker;

// Create consistent popup style
const popupOptions = {
  maxWidth: 200,
  className: 'custom-popup'
};

// Search location function
function searchLocation() {
  const query = document.getElementById('searchInput').value.trim();
  if (!query) {
    alert("Please enter a location.");
    return;
  }

  fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
      if (data.length === 0) {
        alert("Location not found.");
        return;
      }

      const { lat, lon, display_name } = data[0];
      const latLng = [parseFloat(lat), parseFloat(lon)];
      map.setView(latLng, 18);

      if (marker) map.removeLayer(marker);
      marker = L.marker(latLng).addTo(map)
        .bindPopup(`<div>${display_name}</div>`, popupOptions)
        .openPopup();
    })
    .catch(() => {
      alert("Error fetching location.");
    });
}

// Go to current location
function goToCurrentLocation() {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported.");
    return;
  }

  navigator.geolocation.getCurrentPosition(pos => {
    const latLng = [pos.coords.latitude, pos.coords.longitude];
    map.setView(latLng, 18);

    if (marker) map.removeLayer(marker);
    marker = L.marker(latLng).addTo(map)
      .bindPopup(`<div>You are here</div>`, popupOptions)
      .openPopup();
  }, () => {
    alert("Unable to retrieve your location.");
  });
}

function confirmDelete(name) {
  if (confirm("Are you sure you want to delete " + name + "?")) {
    alert("Deleted " + name);
    // Optionally remove row from table here
  }
}
function warnUser(name) {
  alert("Warning sent to " + name);
}
// MODAL LOGIC (simple example, expand as needed)
function openVolunteerProfileModal(name) {
  document.getElementById('volunteerProfileModal').style.display = 'flex';
  document.getElementById('volunteerName').innerText = name;
  // Fill other fields dynamically if you have data
}
function closeVolunteerProfileModal() {
  document.getElementById('volunteerProfileModal').style.display = 'none';
}

function openAILogModal(id) {
  document.getElementById('aiLogModal').style.display = 'flex';
  // Fill modal with log info based on id if needed
}
function closeAILogModal() {
  document.getElementById('aiLogModal').style.display = 'none';
}

// Confidence slider display
const aiConfidence = document.getElementById('aiConfidence');
const confidenceValue = document.getElementById('confidenceValue');
if (aiConfidence && confidenceValue) {
  aiConfidence.addEventListener('input', function() {
    confidenceValue.innerText = aiConfidence.value + '%';
  });
}

// Add Volunteer Modal logic placeholder
function openAddVolunteerModal() {
  alert("Add/Invite Volunteer form/modal goes here.");
}