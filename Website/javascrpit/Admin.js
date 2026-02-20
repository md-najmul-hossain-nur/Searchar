// Camera Connections (salesChart canvas) - now fetches live DB data
const cameraLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const cameraCtx = document.getElementById('salesChart').getContext('2d');
const cameraMeta = document.getElementById('camera-meta');
const legendChips = Array.from(document.querySelectorAll('#camera-legend .legend-chip'));
let cameraSeries = {};
let currentYear = '2025';

// Placeholder zeros used only when the API fails
const emptySeries = { '2025': Array(12).fill(0), '2026': Array(12).fill(0) };

const cameraChart = new Chart(cameraCtx, {
  type: 'line',
  data: {
    labels: cameraLabels,
    datasets: [
      {
        label: '2025',
        data: Array(12).fill(0),
        borderColor: '#4339f2',
        backgroundColor: 'transparent',
        borderWidth: 3,
        pointBackgroundColor: '#4339f2',
        tension: 0.35
      },
      {
        label: '2026',
        data: Array(12).fill(0),
        borderColor: '#ffffff',
        backgroundColor: 'transparent',
        borderWidth: 3,
        pointBackgroundColor: '#ffffff',
        tension: 0.35
      }
    ]
  },
  options: {
    animation: { duration: 800, easing: 'easeOutQuart' },
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' } },
      y: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#fff' }, beginAtZero: true }
    },
    onClick(evt, elements) {
      if (!elements.length || !cameraSeries[currentYear]) return;
      const pt = elements[0];
      const idx = pt.index;
      const month = cameraLabels[idx];
      const value = cameraSeries[currentYear][idx];
      if (cameraMeta) cameraMeta.textContent = `Showing: ${month} ${currentYear} - ${value} camera connections`;
    }
  }
});

function updateCameraDatasets() {
  const ds2025 = cameraSeries['2025'] || Array(12).fill(0);
  const ds2026 = cameraSeries['2026'] || Array(12).fill(0);
  cameraChart.data.datasets[0].data = ds2025;
  cameraChart.data.datasets[1].data = ds2026;
  cameraChart.update();
}

function showAllYears() {
  cameraChart.data.datasets.forEach(ds => {
    ds.hidden = false;
  });
  cameraChart.update();
  if (cameraMeta) cameraMeta.textContent = 'Showing: 2025 & 2026 (all months)';
  legendChips.forEach(btn => btn.classList.remove('legend-active'));
}

function setCameraYear(year) {
  if (!cameraSeries[year]) return;
  currentYear = year;
  // Show only the selected year; keep both lines available
  cameraChart.data.datasets.forEach(ds => {
    ds.hidden = ds.label !== year;
  });
  cameraChart.update();
  if (cameraMeta) cameraMeta.textContent = `Showing: ${year} (all months)`;
  legendChips.forEach(btn => btn.classList.toggle('legend-active', btn.dataset.year === year));
}

async function loadCameraSeries() {
  try {
    const res = await fetch('../Php/fetch_camera_connections.php', { credentials: 'same-origin', cache: 'no-store' });
    const json = await res.json();
    console.info('camera data', json);
    if (!json || !json.success || !json.data) throw new Error('No data');
    cameraSeries = json.data;

    // Ensure both years exist with 12 slots
    ['2025', '2026'].forEach(y => {
      if (!cameraSeries[y]) cameraSeries[y] = Array(12).fill(0);
      if (cameraSeries[y].length < 12) {
        cameraSeries[y] = [...cameraSeries[y], ...Array(12 - cameraSeries[y].length).fill(0)];
      }
    });

    updateCameraDatasets();
    showAllYears();
  } catch (e) {
    console.error('camera data load failed', e);
    // Fallback to empty data so only real DB data is shown when available
    cameraSeries = { ...emptySeries };
    updateCameraDatasets();
    showAllYears();
    if (cameraMeta) cameraMeta.textContent = 'No data: check DB/API response';
  }
}

legendChips.forEach(btn => {
  btn.addEventListener('click', () => setCameraYear(btn.dataset.year));
});

loadCameraSeries();
// Refresh every 30s for a near-real-time view
setInterval(loadCameraSeries, 30000);

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