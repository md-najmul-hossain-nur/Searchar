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
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        datasets: [
          {
            label: '2025',
            data: [40, 60, 80, 70, 100, 30, 10],
            backgroundColor: '#f59e0b',
            borderColor: '#d97706',
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 34,
            categoryPercentage: 0.74,
            barPercentage: 0.9
          },
          {
            label: '2026',
            data: [20, 50, 60, 30, 90, 25, 80],
            backgroundColor: '#2563eb',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 34,
            categoryPercentage: 0.74,
            barPercentage: 0.9
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 1100,
          easing: 'easeOutQuart',
          delay(ctx) {
            if (ctx.type !== 'data' || ctx.mode !== 'default') return 0;
            return (ctx.dataIndex * 70) + (ctx.datasetIndex * 120);
          }
        },
        transitions: {
          active: {
            animation: {
              duration: 500,
              easing: 'easeOutCubic'
            }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.92)',
            titleColor: '#ffffff',
            bodyColor: '#f9fafb',
            padding: 10,
            displayColors: true
          }
        },
        scales: {
          x: {
            grid: { color: 'rgba(0,0,0,0.04)' },
            ticks: { color: '#374151', font: { size: 12, weight: '700' } }
          },
          y: {
            grid: { color: 'rgba(0,0,0,0.08)' },
            ticks: { color: '#4b5563', font: { size: 12, weight: '600' }, stepSize: 20 },
            beginAtZero: true,
            min: 0,
            max: 120
          }
        }
      }
    });

    const ordersLegendButtons = Array.from(document.querySelectorAll('#orders-legend .orders-legend-btn'));

    function showAllOrdersYears() {
      ordersChart.data.datasets.forEach(ds => {
        ds.hidden = false;
      });
      ordersChart.update();
      ordersLegendButtons.forEach(btn => btn.classList.add('active'));
    }

    function setOrdersYear(year) {
      ordersChart.data.datasets.forEach(ds => {
        ds.hidden = ds.label !== year;
      });
      ordersChart.update();
      ordersLegendButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.year === year));
    }

    ordersLegendButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const year = btn.dataset.year;
        if (!year) return;
        setOrdersYear(year);
      });
    });

    function resetOrdersDefaultView() {
      showAllOrdersYears();
    }

    resetOrdersDefaultView();

    window.addEventListener('pageshow', () => {
      resetOrdersDefaultView();
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

// Missing persons: live table + filters + summary metrics
(function () {
  const section = document.getElementById('missing');
  if (!section) return;

  const totalActiveEl = document.getElementById('missing-total-active');
  const resolvedMonthEl = document.getElementById('missing-resolved-month');
  const avgResolutionEl = document.getElementById('missing-avg-resolution');
  const tableBody = document.getElementById('missing-table-body');

  const statusFilter = document.getElementById('missing-filter-status');
  const locationFilter = document.getElementById('missing-filter-location');
  const minAgeFilter = document.getElementById('missing-filter-min-age');
  const maxAgeFilter = document.getElementById('missing-filter-max-age');
  const genderFilter = document.getElementById('missing-filter-gender');
  const dateFromFilter = document.getElementById('missing-filter-date-from');
  const dateToFilter = document.getElementById('missing-filter-date-to');

  let missingRows = [];

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatDate(iso) {
    if (!iso) return '—';
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return escapeHtml(iso);
    return date.toISOString().slice(0, 10);
  }

  function prettyStatus(status) {
    const normalized = String(status || 'open').toLowerCase();
    if (normalized === 'open') return 'Open';
    if (normalized === 'active') return 'Active';
    if (normalized === 'resolved') return 'Resolved';
    if (normalized === 'closed') return 'Closed';
    if (normalized === 'found') return 'Found';
    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
  }

  function statusClass(status) {
    const normalized = String(status || '').toLowerCase();
    if (['resolved', 'closed', 'found'].includes(normalized)) return 'status inactive';
    return 'status active';
  }

  function renderRows(rows) {
    if (!tableBody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="9">No missing person reports found.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(row => {
      const reportId = Number(row.report_id || 0);
      const labelId = reportId > 0 ? `MP${String(reportId).padStart(4, '0')}` : '—';
      const lastSeen = [row.last_seen_location || '', row.last_seen_time || ''].filter(Boolean).join(', ');

      return `
        <tr>
          <td>${escapeHtml(labelId)}</td>
          <td>${escapeHtml(row.full_name || '—')}</td>
          <td>${escapeHtml(row.age || '—')}</td>
          <td>${escapeHtml(row.gender || '—')}</td>
          <td>${escapeHtml(lastSeen || '—')}</td>
          <td><span class="${statusClass(row.status)}">${escapeHtml(prettyStatus(row.status))}</span></td>
          <td>${escapeHtml(row.reporter_name || '—')}</td>
          <td>${escapeHtml(formatDate(row.created_at))}</td>
          <td>
            <button type="button">Assign Volunteer</button>
            <button type="button">Alert CCTV</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function parseDateOnly(input) {
    if (!input) return null;
    const d = new Date(`${input}T00:00:00`);
    return Number.isNaN(d.getTime()) ? null : d;
  }

  function applyFilters() {
    const status = String(statusFilter?.value || '').trim().toLowerCase();
    const location = String(locationFilter?.value || '').trim().toLowerCase();
    const minAgeRaw = String(minAgeFilter?.value || '').trim();
    const maxAgeRaw = String(maxAgeFilter?.value || '').trim();
    const minAge = minAgeRaw === '' ? null : parseInt(minAgeRaw, 10);
    const maxAge = maxAgeRaw === '' ? null : parseInt(maxAgeRaw, 10);
    const gender = String(genderFilter?.value || '').trim().toLowerCase();
    const fromDate = parseDateOnly(dateFromFilter?.value || '');
    const toDate = parseDateOnly(dateToFilter?.value || '');

    let safeMinAge = Number.isInteger(minAge) ? minAge : null;
    let safeMaxAge = Number.isInteger(maxAge) ? maxAge : null;
    if (safeMinAge !== null && safeMaxAge !== null && safeMinAge > safeMaxAge) {
      const tmp = safeMinAge;
      safeMinAge = safeMaxAge;
      safeMaxAge = tmp;
    }

    const filtered = missingRows.filter(row => {
      const rowStatus = String(row.status || '').toLowerCase();
      const rowLocation = String(row.last_seen_location || '').toLowerCase();
      const rowGender = String(row.gender || '').toLowerCase();
      const rowAge = parseInt(String(row.age ?? ''), 10);
      const rowDate = parseDateOnly(formatDate(row.created_at));

      if (status && rowStatus !== status) return false;
      if (location && !rowLocation.includes(location)) return false;
      if (gender && rowGender !== gender) return false;
      if (safeMinAge !== null || safeMaxAge !== null) {
        if (!Number.isInteger(rowAge)) return false;
      }
      if (safeMinAge !== null && rowAge < safeMinAge) return false;
      if (safeMaxAge !== null && rowAge > safeMaxAge) return false;
      if (fromDate && (!rowDate || rowDate < fromDate)) return false;
      if (toDate && (!rowDate || rowDate > toDate)) return false;

      return true;
    });

    renderRows(filtered);
  }

  async function loadMissingReports() {
    if (!tableBody) return;
    tableBody.innerHTML = '<tr><td colspan="9">Loading missing person reports...</td></tr>';

    try {
      const res = await fetch('../Php/fetch_missing_reports_admin.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!json || !json.success) {
        throw new Error(json?.error || 'Unable to load missing reports');
      }

      missingRows = Array.isArray(json.rows) ? json.rows : [];
      const summary = json.summary || {};

      if (totalActiveEl) totalActiveEl.textContent = String(summary.total_active_cases ?? 0).padStart(2, '0');
      if (resolvedMonthEl) resolvedMonthEl.textContent = String(summary.resolved_cases_month ?? 0).padStart(2, '0');
      if (avgResolutionEl) avgResolutionEl.textContent = String(summary.avg_resolution_time || '00d 00h');

      applyFilters();
    } catch (error) {
      console.error('missing reports load failed', error);
      tableBody.innerHTML = '<tr><td colspan="9">Failed to load missing person reports.</td></tr>';
      if (totalActiveEl) totalActiveEl.textContent = '00';
      if (resolvedMonthEl) resolvedMonthEl.textContent = '00';
      if (avgResolutionEl) avgResolutionEl.textContent = '00d 00h';
    }
  }

  [statusFilter, locationFilter, minAgeFilter, maxAgeFilter, genderFilter, dateFromFilter, dateToFilter]
    .filter(Boolean)
    .forEach(el => {
      const eventName = el.tagName === 'INPUT' ? 'input' : 'change';
      el.addEventListener(eventName, applyFilters);
    });

  loadMissingReports();
})();