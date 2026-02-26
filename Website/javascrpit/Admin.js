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

// Post control actions (Approve / Reject)
let applyPostControlFilters = null;

document.addEventListener('click', function (event) {
  const detailsButton = event.target.closest('[data-post-details]');
  if (detailsButton) {
    const row = detailsButton.closest('tr');
    if (!row) return;

    const statusText = (row.querySelector('.post-status')?.textContent || '').trim() || (row.dataset.status || 'pending');
    const postDetails = {
      __title: 'Post Details',
      id: row.dataset.id || '',
      author_role: row.dataset.authorRole || '',
      author_name: row.dataset.authorName || '',
      category: row.dataset.category || '',
      text: row.dataset.text || '',
      media_path: row.dataset.mediaPath || '',
      media_json: row.dataset.mediaJson || '',
      media_type: row.dataset.mediaType || '',
      status: statusText,
      share_facebook: row.dataset.shareFacebook || '',
      share_anonymous: row.dataset.shareAnonymous || '',
      shared_post_id: row.dataset.sharedPostId || '',
      shared_payload: row.dataset.sharedPayload || ''
    };

    if (typeof window.openProfileModal === 'function') {
      window.openProfileModal(postDetails, false);
    }
    return;
  }

  const sendCrimeBtn = event.target.closest('[data-post-send-crime]');
  if (sendCrimeBtn) {
    const row = sendCrimeBtn.closest('tr');
    const postId = row?.dataset?.id || 'post';
    const caseId = row?.dataset?.caseId || postId;
    alert(`Make report for ${caseId} into Crime Reporting queue (hook up backend).`);
    return;
  }

  const actionButton = event.target.closest('[data-post-action]');
  if (!actionButton) return;

  const row = actionButton.closest('tr');
  if (!row) return;

  const statusElement = row.querySelector('.post-status');
  if (!statusElement) return;

  const action = actionButton.getAttribute('data-post-action');
  const approveButton = row.querySelector('[data-post-action="approve"]');
  const rejectButton = row.querySelector('[data-post-action="reject"]');

  const postId = row.dataset.id;
  if (!postId) return;

  const originalLabel = actionButton.textContent;
  actionButton.disabled = true;
  actionButton.textContent = action === 'approve' ? 'Approving…' : 'Rejecting…';
  if (approveButton && approveButton !== actionButton) approveButton.disabled = true;
  if (rejectButton && rejectButton !== actionButton) rejectButton.disabled = true;

  const targetStatus = action === 'approve' ? 'approved' : 'rejected';

  function setStatusUI(statusText) {
    statusElement.textContent = statusText;
    statusElement.classList.remove('status-pending', 'status-approved', 'status-rejected');
    if (statusText.toLowerCase() === 'approved') {
      statusElement.classList.add('status-approved');
    } else if (statusText.toLowerCase() === 'rejected') {
      statusElement.classList.add('status-rejected');
    } else {
      statusElement.classList.add('status-pending');
    }
  }

  fetch('../Php/admin_update_post_status.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ post_id: postId, action })
  })
    .then(res => res.json())
    .then(json => {
      if (!json || !json.success) {
        throw new Error(json?.error || 'Update failed');
      }

      if (json.deleted) {
        row.remove();
        if (typeof applyPostControlFilters === 'function') {
          applyPostControlFilters();
        }
        return;
      }

      const newStatus = json.status || targetStatus;
      setStatusUI(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

      if (approveButton) approveButton.disabled = true;
      if (rejectButton) rejectButton.disabled = true;

      if (typeof applyPostControlFilters === 'function') {
        applyPostControlFilters();
      }
    })
    .catch(err => {
      console.error('Post status update failed', err);
      actionButton.disabled = false;
      actionButton.textContent = originalLabel;
      if (approveButton && approveButton !== actionButton) approveButton.disabled = false;
      if (rejectButton && rejectButton !== actionButton) rejectButton.disabled = false;
      alert('Could not update status. This post may already be decided or a network error occurred.');
    });
});

// Post control filters (keyword + status + date range)
(function () {
  const section = document.getElementById('post-control');
  if (!section) return;

  const keywordInput = document.getElementById('post-filter-keyword');
  const statusSelect = document.getElementById('post-filter-status');
  const fromInput = document.getElementById('post-filter-from');
  const toInput = document.getElementById('post-filter-to');
  const applyButton = document.getElementById('post-filter-apply');
    const refreshButton = document.getElementById('post-filter-refresh');
  const tableBody = document.getElementById('post-control-body') || section.querySelector('#post-control-table tbody');

  if (!keywordInput || !statusSelect || !fromInput || !toInput || !tableBody) return;

  function parseDateOnly(dateText) {
    if (!dateText) return null;
    const parsed = new Date(`${dateText}T00:00:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function statusClass(status) {
    const val = String(status || '').toLowerCase();
    if (val === 'approved') return 'status-approved';
    if (val === 'rejected') return 'status-rejected';
    return 'status-pending';
  }

  function yesNoBadge(value) {
    const yes = String(value ?? '') === '1' || String(value ?? '').toLowerCase() === 'true';
    return yes
      ? '<span class="share-status share-yes">Yes</span>'
      : '<span class="share-status share-no">No</span>';
  }

  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return escapeHtml(String(value));
    return date.toISOString().slice(0, 10);
  }

  function titleCase(value) {
    const raw = String(value || '').trim();
    if (!raw) return '—';
    return raw.charAt(0).toUpperCase() + raw.slice(1);
  }

  function renderPostRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="9">No posts found.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(row => {
      const id = Number(row.id || 0);
      const postIdText = id > 0 ? `PT-${String(id).padStart(3, '0')}` : '—';
      const statusText = titleCase(row.status || 'pending');
      const roleText = titleCase(row.author_role || 'user');

      return `
        <tr data-id="${escapeHtml(row.id || '')}" data-case-id="${escapeHtml(row.case_id || '')}" data-author-role="${escapeHtml(row.author_role || '')}" data-author-id="${escapeHtml(row.author_id || '')}" data-author-name="${escapeHtml(row.author_name || '')}"
            data-category="${escapeHtml(row.category || '')}" data-text="${escapeHtml(row.text || '')}" data-media-path="${escapeHtml(row.media_path || '')}"
            data-media-json='${escapeHtml(row.media_json || '')}' data-media-type="${escapeHtml(row.media_type || '')}" data-status="${escapeHtml((row.status || 'pending').toLowerCase())}" data-share-facebook="${escapeHtml(row.share_facebook || 0)}"
            data-share-anonymous="${escapeHtml(row.share_anonymous || 0)}" data-is-share="${escapeHtml(row.is_share || 0)}" data-shared-post-id="${escapeHtml(row.shared_post_id || '')}" data-shared-payload='${escapeHtml(row.shared_payload || '')}'>
          <td>${escapeHtml(postIdText)}</td>
          <td>${escapeHtml(titleCase(row.category || 'general'))}</td>
          <td>${escapeHtml(row.author_name || 'Unknown')}</td>
          <td>${escapeHtml(roleText)}</td>
          <td>${yesNoBadge(row.share_facebook || 0)}</td>
          <td>${yesNoBadge(row.share_anonymous || 0)}</td>
          <td><span class="post-status ${statusClass(row.status)}">${escapeHtml(statusText)}</span></td>
          <td>${escapeHtml(formatDate(row.created_at || ''))}</td>
          <td>
            <button class="view-profile-btn" data-post-details="1">View Details</button>
            <button class="ghost" data-post-send-crime="1">Make Report</button>
            <button class="approve-btn" data-post-action="approve" ${statusClass(row.status) !== 'status-pending' ? 'disabled' : ''}>Approve</button>
            <button class="reject-btn" data-post-action="reject" ${statusClass(row.status) !== 'status-pending' ? 'disabled' : ''}>Reject</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  async function loadPostRows() {
    const prevLabel = refreshButton ? refreshButton.textContent : '';
    if (refreshButton) {
      refreshButton.disabled = true;
      refreshButton.textContent = 'Refreshing…';
    }
    try {
      const res = await fetch('../Php/admin_fetch_pending_posts.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!json || !json.success) {
        throw new Error(json?.error || 'Fetch failed');
      }
      renderPostRows(Array.isArray(json.rows) ? json.rows : []);
      filterRows();
    } catch (error) {
      console.error('Post control fetch failed', error);
      tableBody.innerHTML = '<tr><td colspan="9">Failed to load posts.</td></tr>';
    }
    if (refreshButton) {
      refreshButton.disabled = false;
      refreshButton.textContent = prevLabel || 'Refresh';
    }
  }

  function filterRows() {
    const keyword = keywordInput.value.trim().toLowerCase();
    const selectedStatus = statusSelect.value.trim().toLowerCase();
    const fromDate = parseDateOnly(fromInput.value);
    const toDate = parseDateOnly(toInput.value);

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    let visibleCount = 0;

    rows.forEach(row => {
      if (row.classList.contains('post-filter-empty-row')) {
        row.remove();
        return;
      }

      const cells = row.querySelectorAll('td');
      if (cells.length < 9) {
        row.style.display = '';
        visibleCount += 1;
        return;
      }

      const postId = (cells[0]?.textContent || '').trim().toLowerCase();
      const category = (cells[1]?.textContent || '').trim().toLowerCase();
      const postedBy = (cells[2]?.textContent || '').trim().toLowerCase();
      const role = (cells[3]?.textContent || '').trim().toLowerCase();
      const statusText = (row.querySelector('.post-status')?.textContent || '').trim().toLowerCase();
      const submittedText = (cells[7]?.textContent || '').trim();
      const submittedDate = parseDateOnly(submittedText);

      const keywordHaystack = `${postId} ${category} ${postedBy} ${role}`;
      const keywordOk = !keyword || keywordHaystack.includes(keyword);
      const statusOk = selectedStatus === 'all' || statusText === selectedStatus;
      const fromOk = !fromDate || (submittedDate && submittedDate >= fromDate);
      const toOk = !toDate || (submittedDate && submittedDate <= toDate);

      const isVisible = keywordOk && statusOk && fromOk && toOk;
      row.style.display = isVisible ? '' : 'none';
      if (isVisible) visibleCount += 1;
    });

    if (visibleCount === 0) {
      const noMatchRow = document.createElement('tr');
      noMatchRow.className = 'post-filter-empty-row';
      noMatchRow.innerHTML = '<td colspan="9">No posts match the selected filters.</td>';
      tableBody.appendChild(noMatchRow);
    }
  }

  if (applyButton) {
    applyButton.addEventListener('click', filterRows);
  }
  statusSelect.addEventListener('change', filterRows);
  fromInput.addEventListener('change', filterRows);
  toInput.addEventListener('change', filterRows);
  keywordInput.addEventListener('input', filterRows);
  keywordInput.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      filterRows();
    }
  });

  if (refreshButton) {
    refreshButton.addEventListener('click', function () {
      loadPostRows();
    });
  }

  applyPostControlFilters = filterRows;
  loadPostRows();
})();

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
            <button type="button" class="danger-btn" data-missing-reject="${escapeHtml(labelId)}">Reject</button>
            <button type="button" data-send-to-crime="${escapeHtml(labelId)}">Make Report</button>
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

  document.addEventListener('click', (event) => {
    const rejectBtn = event.target.closest('[data-missing-reject]');
    if (rejectBtn) {
      const caseId = rejectBtn.getAttribute('data-missing-reject') || 'case';
      alert(`Reject ${caseId} (hook up backend).`);
      return;
    }

    const sendBtn = event.target.closest('[data-send-to-crime]');
    if (sendBtn) {
      const caseId = sendBtn.getAttribute('data-send-to-crime') || 'case';
      alert(`Make report for ${caseId} into Crime Reporting queue (hook up backend).`);
    }
  });
})();

// Crime reporting: map, filters, proximity search, geo-tag capture, evidence preview
(function () {
  const section = document.getElementById('crime');
  if (!section) return;

  const mapEl = document.getElementById('crime-map');
  const tableBody = document.getElementById('crime-table-body');

  const filterText = document.getElementById('crime-filter-text');
  const filterType = document.getElementById('crime-filter-type');
  const filterSeverity = document.getElementById('crime-filter-severity');
  const filterStatus = document.getElementById('crime-filter-status');
  const filterFrom = document.getElementById('crime-filter-from');
  const filterTo = document.getElementById('crime-filter-to');
  const filterReset = document.getElementById('crime-filter-reset');

  const toggleHeatmap = document.getElementById('crime-toggle-heatmap');
  const toggleLast24 = document.getElementById('crime-toggle-last24');
  const toggleClosed = document.getElementById('crime-toggle-closed');

  const proximityAddress = document.getElementById('crime-proximity-address');
  const proximityRadius = document.getElementById('crime-proximity-radius');
  const proximityRadiusLabel = document.getElementById('crime-proximity-radius-label');
  const proximityType = document.getElementById('crime-proximity-type');
  const proximityRun = document.getElementById('crime-proximity-run');
  const proximityResults = document.getElementById('crime-proximity-results');

  const geolocateBtn = document.getElementById('crime-geolocate-btn');
  const geotagCoords = document.getElementById('crime-geotag-coords');
  const geotagLandmark = document.getElementById('crime-geotag-landmark');
  const geotagType = document.getElementById('crime-geotag-type');
  const geotagSeverity = document.getElementById('crime-geotag-severity');
  const geotagAnon = document.getElementById('crime-geotag-anon');
  const anonToken = document.getElementById('crime-anon-token');
  const generateTokenBtn = document.getElementById('crime-generate-token');

  const statNew = document.getElementById('crime-stat-new');
  const statReview = document.getElementById('crime-stat-review');
  const statActioned = document.getElementById('crime-stat-actioned');
  const statClosed = document.getElementById('crime-stat-closed');

  const mapWrapper = document.getElementById('crime-map-wrapper');
  const mapToggle = document.getElementById('crime-map-toggle');

  if (!mapEl || !tableBody) return;

  const demoCrimes = [
    {
      id: 'CR-2026-001',
      type: 'theft',
      severity: 'medium',
      status: 'new',
      lat: 23.8105,
      lng: 90.4127,
      landmark: 'Banani Lake Bridge',
      submitted: '2026-02-25T10:05:00Z',
      updated_at: '2026-02-25T11:05:00Z',
      media: [
        { type: 'photo', url: '../uploads/crime/theft-1.jpg', hash: 'a4f2c7d9' },
        { type: 'video', url: '../uploads/crime/theft-clip.mp4', hash: 'f1c8e3b2' }
      ],
      reporter: 'Anonymous',
      anonymous: true,
      token: 'ANON-9F3B',
      description: 'Bag stolen from parked bike near the bridge.',
      reward_paid: false
    },
    {
      id: 'CR-2026-002',
      type: 'robbery',
      severity: 'high',
      status: 'under_review',
      lat: 23.7808,
      lng: 90.4098,
      landmark: 'Kawran Bazar crossing',
      submitted: '2026-02-26T02:40:00Z',
      updated_at: '2026-02-26T03:10:00Z',
      media: [{ type: 'photo', url: '../uploads/crime/robbery-1.jpg', hash: 'e29f7caa' }],
      reporter: 'Md. Rahim',
      anonymous: false,
      token: '',
      description: 'Phone snatched; suspect fled towards Farmgate.',
      reward_paid: false
    },
    {
      id: 'CR-2026-003',
      type: 'assault',
      severity: 'critical',
      status: 'actioned',
      lat: 23.744,
      lng: 90.3735,
      landmark: 'Jatrabari bus stand',
      submitted: '2026-02-23T18:25:00Z',
      updated_at: '2026-02-24T08:00:00Z',
      media: [
        { type: 'photo', url: '../uploads/crime/assault-1.jpg', hash: '99a1b3de' },
        { type: 'audio', url: '../uploads/crime/assault-audio.m4a', hash: 'b7f2ff10' }
      ],
      reporter: 'Anonymous',
      anonymous: true,
      token: 'ANON-52KD',
      description: 'Night-time assault near ticket counter.',
      reward_paid: true
    },
    {
      id: 'CR-2026-004',
      type: 'vandalism',
      severity: 'low',
      status: 'closed',
      lat: 23.9002,
      lng: 90.3285,
      landmark: 'Uttara Sector 7 park',
      submitted: '2026-02-15T15:10:00Z',
      updated_at: '2026-02-18T09:20:00Z',
      media: [{ type: 'photo', url: '../uploads/crime/vandalism-1.jpg', hash: '558e3321' }],
      reporter: 'Salma H.',
      anonymous: false,
      token: '',
      description: 'Playground equipment damaged; CCTV available from nearby shop.',
      reward_paid: false
    }
  ];

  let filteredCrimes = [...demoCrimes];
  let crimeMap = null;
  let crimeMarkers = [];
  let crimeZones = [];
  let crimeHeat = null;
  let geotagMarker = null;
  let proximityMarker = null;
  let proximityCircle = null;

  function severityColor(sev) {
    const v = String(sev || '').toLowerCase();
    if (v === 'critical') return '#8b5cf6';
    if (v === 'high') return '#ef4444';
    if (v === 'medium') return '#f59e0b';
    return '#9ca3af';
  }

  function severityWeight(sev) {
    const v = String(sev || '').toLowerCase();
    if (v === 'critical') return 0.8;
    if (v === 'high') return 0.65;
    if (v === 'medium') return 0.5;
    return 0.35;
  }

  // Distinct palette for zone fill/border (keeps markers using severityColor)
  function severityZoneColor(sev) {
    const v = String(sev || '').toLowerCase();
    if (v === 'critical') return '#ef4444';   // red
    if (v === 'high') return '#f97316';       // orange
    if (v === 'medium') return '#f59e0b';     // amber
    return '#22c55e';                         // green
  }

  function severityRadius(sev) {
    const v = String(sev || '').toLowerCase();
    if (v === 'critical') return 850;
    if (v === 'high') return 650;
    if (v === 'medium') return 450;
    return 300;
  }

  function statusLabel(val) {
    const v = String(val || '').toLowerCase();
    if (v === 'under_review') return 'Under Review';
    if (v === 'actioned') return 'Actioned';
    if (v === 'closed') return 'Closed';
    return 'New';
  }

  function withinLast24(iso) {
    if (!iso) return false;
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return false;
    const diff = Date.now() - date.getTime();
    return diff <= 24 * 60 * 60 * 1000;
  }

  function formatDateLocal(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return `${d.toLocaleDateString()} ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
  }

  function parseDateInput(input) {
    if (!input) return null;
    const d = new Date(input);
    return Number.isNaN(d.getTime()) ? null : d;
  }

  function distanceKm(aLat, aLng, bLat, bLng) {
    const toRad = (deg) => deg * Math.PI / 180;
    const R = 6371;
    const dLat = toRad(bLat - aLat);
    const dLon = toRad(bLng - aLng);
    const lat1 = toRad(aLat);
    const lat2 = toRad(bLat);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(h));
  }

  function createMarker(crime) {
    const color = severityColor(crime.severity);
    const isRecent = withinLast24(crime.submitted);
    const marker = L.circleMarker([crime.lat, crime.lng], {
      radius: isRecent ? 11 : 9,
      color,
      weight: 2,
      fillColor: isRecent ? '#10b981' : color,
      fillOpacity: 0.6
    });

    const mediaList = (crime.media || []).map(m => `<li>${m.type || 'media'} — hash ${m.hash || '—'}</li>`).join('');
    const popup = `
      <div style="min-width:220px;">
        <div style="font-weight:800; margin-bottom:4px;">${crime.id} • ${statusLabel(crime.status)}</div>
        <div><strong>Type:</strong> ${crime.type}</div>
        <div><strong>Severity:</strong> ${crime.severity}</div>
        <div><strong>Landmark:</strong> ${crime.landmark || '—'}</div>
        <div><strong>Submitted:</strong> ${formatDateLocal(crime.submitted)}</div>
        <div><strong>Reporter:</strong> ${crime.anonymous ? 'Anonymous' : (crime.reporter || '—')}</div>
        ${crime.anonymous && crime.token ? `<div><strong>Token:</strong> ${crime.token}</div>` : ''}
        <div><strong>Evidence:</strong><ul style="padding-left:18px; margin:6px 0 0;">${mediaList || '<li>None</li>'}</ul></div>
      </div>`;

    marker.bindPopup(popup);
    return marker;
  }

  function renderMap(rows) {
    if (!crimeMap) return;
    crimeMarkers.forEach(m => crimeMap.removeLayer(m));
    crimeMarkers = [];

    crimeZones.forEach(z => crimeMap.removeLayer(z));
    crimeZones = [];

    if (crimeHeat) {
      crimeMap.removeLayer(crimeHeat);
      crimeHeat = null;
    }

    rows.forEach(r => {
      const zone = L.circle([r.lat, r.lng], {
        radius: severityRadius(r.severity),
        color: severityZoneColor(r.severity),
        weight: 1.4,
        fillColor: severityZoneColor(r.severity),
        fillOpacity: 0.12,
        opacity: 0.9,
        dashArray: '6 4'
      });
      zone.addTo(crimeMap);
      crimeZones.push(zone);

      const marker = createMarker(r);
      marker.addTo(crimeMap);
      crimeMarkers.push(marker);
    });

    if (toggleHeatmap && toggleHeatmap.checked && typeof L.heatLayer === 'function') {
      const heatData = rows.map(r => [r.lat, r.lng, severityWeight(r.severity)]);
      if (heatData.length) {
        crimeHeat = L.heatLayer(heatData, { radius: 26, blur: 18, maxZoom: 16 });
        crimeHeat.addTo(crimeMap);
      }
    }

    if (rows.length) {
      const group = L.featureGroup(crimeMarkers);
      crimeMap.fitBounds(group.getBounds().pad(0.2));
    } else {
      crimeMap.setView([23.8103, 90.4125], 12);
    }
  }

  function statusBadge(status) {
    const cls = `status-${String(status || '').toLowerCase()}`;
    return `<span class="${cls}">${statusLabel(status)}</span>`;
  }

  function renderTable(rows) {
    if (!tableBody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="9">No crime reports match the current filters.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(r => {
      const mediaCount = Array.isArray(r.media) ? r.media.length : 0;
      return `
        <tr data-crime-id="${r.id}">
          <td>${r.id}</td>
          <td>${r.type}</td>
          <td>${r.severity}</td>
          <td>${r.landmark || '—'}</td>
          <td>${statusBadge(r.status)}</td>
          <td>${formatDateLocal(r.submitted)}</td>
          <td>${mediaCount} file${mediaCount === 1 ? '' : 's'}</td>
          <td>${r.anonymous ? 'Anonymous' : (r.reporter || '—')}</td>
          <td>
            <select class="crime-status-select" data-crime-status="${r.id}">
              <option value="new" ${r.status === 'new' ? 'selected' : ''}>New</option>
              <option value="under_review" ${r.status === 'under_review' ? 'selected' : ''}>Under Review</option>
              <option value="actioned" ${r.status === 'actioned' ? 'selected' : ''}>Actioned</option>
              <option value="closed" ${r.status === 'closed' ? 'selected' : ''}>Closed</option>
            </select>
          </td>
          <td>
            <button type="button" class="view-profile-btn" data-crime-view="${r.id}">View</button>
            <button type="button" data-crime-assign="${r.id}">Assign Volunteer</button>
            <button type="button" data-crime-cctv="${r.id}">Alert CCTV</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function updateStats(rows) {
    const counts = { new: 0, under_review: 0, actioned: 0, closed: 0 };
    rows.forEach(r => {
      const key = String(r.status || 'new').toLowerCase();
      if (counts[key] !== undefined) counts[key] += 1;
    });
    if (statNew) statNew.textContent = `New: ${counts.new}`;
    if (statReview) statReview.textContent = `Under Review: ${counts.under_review}`;
    if (statActioned) statActioned.textContent = `Actioned: ${counts.actioned}`;
    if (statClosed) statClosed.textContent = `Closed: ${counts.closed}`;
  }

  function applyFilters() {
    const q = String(filterText?.value || '').toLowerCase().trim();
    const type = String(filterType?.value || '').toLowerCase();
    const severity = String(filterSeverity?.value || '').toLowerCase();
    const status = String(filterStatus?.value || '').toLowerCase();
    const fromDate = parseDateInput(filterFrom?.value || '');
    const toDate = parseDateInput(filterTo?.value || '');

    filteredCrimes = demoCrimes.filter(r => {
      const statusVal = String(r.status || '').toLowerCase();
      if (!toggleClosed?.checked && statusVal === 'closed') return false;
      if (toggleLast24?.checked && !withinLast24(r.submitted)) return false;
      if (type && String(r.type).toLowerCase() !== type) return false;
      if (severity && String(r.severity).toLowerCase() !== severity) return false;
      if (status && statusVal !== status) return false;

      if (fromDate || toDate) {
        const sub = new Date(r.submitted);
        if (Number.isNaN(sub.getTime())) return false;
        if (fromDate && sub < fromDate) return false;
        if (toDate && sub > toDate) return false;
      }

      if (q) {
        const haystack = `${r.id} ${r.type} ${r.landmark || ''} ${r.token || ''} ${statusVal}`.toLowerCase();
        if (!haystack.includes(q)) return false;
      }

      return true;
    });

    renderTable(filteredCrimes);
    renderMap(filteredCrimes);
    updateStats(filteredCrimes);
  }

  function updateCrimeStatus(id, newStatus) {
    const crime = demoCrimes.find(c => c.id === id);
    if (!crime) return;
    crime.status = newStatus;
    crime.updated_at = new Date().toISOString();
    applyFilters();
  }

  function setGeotag(lat, lng, label) {
    if (!crimeMap) return;
    if (geotagMarker) crimeMap.removeLayer(geotagMarker);
    geotagMarker = L.marker([lat, lng]).addTo(crimeMap).bindPopup(label || 'Selected location');
    geotagMarker.openPopup();
    if (geotagCoords) geotagCoords.textContent = `Lat ${lat.toFixed(5)}, Lng ${lng.toFixed(5)}`;
  }

  function generateAnonToken() {
    const token = `ANON-${Math.random().toString(36).slice(2, 6).toUpperCase()}-${Date.now().toString().slice(-4)}`;
    if (anonToken) anonToken.textContent = token;
    return token;
  }

  async function geocode(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`;
    const res = await fetch(url);
    return res.json();
  }

  async function runProximitySearch() {
    const radius = parseFloat(proximityRadius?.value || '3') || 3;
    const typeFilter = String(proximityType?.value || '').toLowerCase();
    let center = null;

    if (geotagMarker) {
      center = geotagMarker.getLatLng();
    }

    const address = (proximityAddress?.value || '').trim();
    if (!center && address) {
      try {
        const results = await geocode(address);
        if (results && results[0]) {
          center = { lat: parseFloat(results[0].lat), lng: parseFloat(results[0].lon) };
        }
      } catch (err) {
        console.error('Geocode failed', err);
      }
    }

    if (!center) {
      if (proximityResults) proximityResults.textContent = 'Drop a pin on the map or enter an address first.';
      return;
    }

    const hits = filteredCrimes.filter(r => {
      const dist = distanceKm(center.lat, center.lng, r.lat, r.lng);
      if (typeFilter && String(r.type).toLowerCase() !== typeFilter) return false;
      return dist <= radius;
    }).map(r => ({ ...r, distance: distanceKm(center.lat, center.lng, r.lat, r.lng) }));

    hits.sort((a, b) => a.distance - b.distance);

    if (proximityResults) {
      if (!hits.length) {
        proximityResults.textContent = 'No reports within the selected radius.';
      } else {
        proximityResults.innerHTML = hits.map(h => `
          <div class="proximity-hit">
            <strong>${h.id}</strong> • ${h.type} • ${h.distance.toFixed(2)} km<br>
            ${h.landmark || '—'}
          </div>
        `).join('');
      }
    }

    if (proximityMarker) crimeMap.removeLayer(proximityMarker);
    if (proximityCircle) crimeMap.removeLayer(proximityCircle);

    proximityMarker = L.marker([center.lat, center.lng]).addTo(crimeMap).bindPopup('Search center');
    proximityCircle = L.circle([center.lat, center.lng], { radius: radius * 1000, color: '#2563eb', fillColor: '#60a5fa', fillOpacity: 0.08 }).addTo(crimeMap);
    crimeMap.setView([center.lat, center.lng], 14);
  }

  function openCrimeDetail(id) {
    const crime = demoCrimes.find(c => c.id === id);
    if (!crime) return;

    const payload = {
      __title: `Case ${crime.id}`,
      case_id: crime.id,
      type: crime.type,
      severity: crime.severity,
      status: statusLabel(crime.status),
      landmark: crime.landmark,
      submitted: formatDateLocal(crime.submitted),
      updated_at: formatDateLocal(crime.updated_at),
      coordinates: `${crime.lat.toFixed(5)}, ${crime.lng.toFixed(5)}`,
      reporter: crime.anonymous ? 'Anonymous' : (crime.reporter || '—'),
      token: crime.anonymous ? (crime.token || '—') : '',
      reward_paid: crime.reward_paid ? 'Yes' : 'No',
      description: crime.description || '',
      media_json: JSON.stringify(crime.media || [])
    };

    if (typeof window.openProfileModal === 'function') {
      window.openProfileModal(payload, false);
    }
  }

  function initMap() {
    crimeMap = L.map('crime-map').setView([23.8103, 90.4125], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '© OpenStreetMap'
    }).addTo(crimeMap);

    setTimeout(() => crimeMap.invalidateSize(), 300);
  }

  function bindEvents() {
    if (filterReset) {
      filterReset.addEventListener('click', () => {
        if (filterText) filterText.value = '';
        if (filterType) filterType.value = '';
        if (filterSeverity) filterSeverity.value = '';
        if (filterStatus) filterStatus.value = '';
        if (filterFrom) filterFrom.value = '';
        if (filterTo) filterTo.value = '';
        if (toggleLast24) toggleLast24.checked = true;
        if (toggleClosed) toggleClosed.checked = true;
        applyFilters();
      });
    }

    [filterText, filterType, filterSeverity, filterStatus, filterFrom, filterTo]
      .filter(Boolean)
      .forEach(el => {
        const eventName = el.tagName === 'INPUT' ? 'input' : 'change';
        el.addEventListener(eventName, applyFilters);
      });

    [toggleHeatmap, toggleLast24, toggleClosed].forEach(el => {
      if (el) el.addEventListener('change', applyFilters);
    });

    if (proximityRadius) {
      proximityRadius.addEventListener('input', () => {
        if (proximityRadiusLabel) proximityRadiusLabel.textContent = `${proximityRadius.value} km`;
      });
    }
    if (proximityRun) proximityRun.addEventListener('click', runProximitySearch);

    if (geolocateBtn) {
      geolocateBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
          alert('Geolocation not supported');
          return;
        }
        navigator.geolocation.getCurrentPosition((pos) => {
          setGeotag(pos.coords.latitude, pos.coords.longitude, 'Current location');
          crimeMap.setView([pos.coords.latitude, pos.coords.longitude], 15);
        }, () => alert('Unable to fetch location'));
      });
    }

    if (generateTokenBtn) generateTokenBtn.addEventListener('click', generateAnonToken);

    document.addEventListener('click', (event) => {
      const viewBtn = event.target.closest('[data-crime-view]');
      if (viewBtn) {
        openCrimeDetail(viewBtn.getAttribute('data-crime-view'));
        return;
      }

      const assignBtn = event.target.closest('[data-crime-assign]');
      if (assignBtn) {
        const id = assignBtn.getAttribute('data-crime-assign');
        alert(`Assign volunteer for ${id} (hook up backend).`);
        return;
      }

      const cctvBtn = event.target.closest('[data-crime-cctv]');
      if (cctvBtn) {
        const id = cctvBtn.getAttribute('data-crime-cctv');
        alert(`Send CCTV alert for ${id} (hook up backend).`);
        return;
      }
    });

    document.addEventListener('change', (event) => {
      const select = event.target.closest('[data-crime-status]');
      if (select) {
        updateCrimeStatus(select.getAttribute('data-crime-status'), select.value);
      }
    });

    const crimeNav = document.querySelector('.sidebar li[data-section="crime"]');
    if (crimeNav) {
      crimeNav.addEventListener('click', () => {
        setTimeout(() => crimeMap && crimeMap.invalidateSize(), 320);
      });
    }

    if (mapToggle && mapWrapper) {
      mapToggle.addEventListener('click', () => {
        const isHidden = mapWrapper.classList.toggle('hidden');
        mapToggle.textContent = isHidden ? 'Show map' : 'Hide map';
        if (!isHidden) {
          setTimeout(() => crimeMap && crimeMap.invalidateSize(), 220);
        }
      });
    }
  }

  initMap();
  bindEvents();
  generateAnonToken();
  applyFilters();
})();