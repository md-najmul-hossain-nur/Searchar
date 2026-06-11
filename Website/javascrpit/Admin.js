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

// Orders Chart
const ordersChart = new Chart(document.getElementById('ordersChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
      {
        label: '2025',
        data: [40, 60, 80, 70, 100, 30, 10, 45, 65, 80, 50, 90],
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
        data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
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

// Fetch real data for 2026 AI Matches
fetch('../Php/admin_fetch_ai_matches_chart.php')
  .then(res => res.json())
  .then(data => {
    if (data.success && data.data_2026) {
      ordersChart.data.datasets[1].data = data.data_2026;
      ordersChart.update();
    }
  })
  .catch(err => console.error('Failed to fetch AI matches chart data', err));

function activateSection(sectionId) {
  if (!sectionId) return false;
  const section = document.getElementById(sectionId);
  if (!section) return false;

  document.querySelectorAll('.sidebar ul li').forEach(li => li.classList.remove('active'));
  const sidebarItem = document.querySelector(`.sidebar ul li[data-section="${sectionId}"]`);
  if (sidebarItem) sidebarItem.classList.add('active');

  document.querySelectorAll('.main-section').forEach(sec => sec.classList.remove('active'));
  section.classList.add('active');
  document.dispatchEvent(new CustomEvent('admin:section-activated', {
    detail: { sectionId }
  }));
  return true;
}

// Sidebar click logic
document.querySelectorAll('.sidebar ul li').forEach(function (item) {
  item.addEventListener('click', function () {
    activateSection(item.getAttribute('data-section'));
  });
});

// Global search: table name / person name / phone => jump to matching section
const globalSearchInput = document.getElementById('global-smart-search') || document.querySelector('.navbar-search input');

function findMatchingSection(query) {
  const q = String(query || '').trim().toLowerCase();
  if (!q) return null;

  const sectionNameMap = [
    { terms: ['dashboard'], id: 'dashboard' },
    { terms: ['tables'], id: 'tables' },
    { terms: ['missing', 'missing persons'], id: 'missing' },
    { terms: ['ai', 'ai detection logs'], id: 'ai' },
    { terms: ['crime', 'crime reporting'], id: 'crime' },
    { terms: ['admin post', 'admin update'], id: 'admin-post' },
    { terms: ['post', 'post control'], id: 'post-control' },
    { terms: ['donation', 'donations control'], id: 'donations' },
    { terms: ['broadcast', 'notifications'], id: 'broadcast' },
    { terms: ['volunteer approver', 'approver', 'approval'], id: 'volunteer-approver' },
    { terms: ['volunteer', 'volunteer missions'], id: 'volunteer' },
    { terms: ['withdraw', 'withdraw control'], id: 'withdraw' },
    { terms: ['review'], id: 'review' },
    { terms: ['report', 'reports'], id: 'reports' },
    { terms: ['chat', 'chat management', 'messenger'], id: 'chat-management' }
  ];

  const direct = sectionNameMap.find(s => s.terms.some(t => t.includes(q) || q.includes(t)));
  if (direct) return direct.id;

  const sections = Array.from(document.querySelectorAll('.main-section'));
  for (const sec of sections) {
    const title = (sec.querySelector('h2')?.innerText || '').toLowerCase();
    if (title.includes(q)) return sec.id;

    const rowMatch = Array.from(sec.querySelectorAll('table tbody tr')).some(row => {
      const text = (row.innerText || '').toLowerCase();
      return text.includes(q);
    });
    if (rowMatch) return sec.id;
  }

  return null;
}

if (globalSearchInput) {
  let searchTimer = null;

  const runSearch = () => {
    const q = globalSearchInput.value;
    if (!q || q.trim().length < 2) return;
    const matchedSection = findMatchingSection(q);
    if (matchedSection) activateSection(matchedSection);
  };

  globalSearchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 250);
  });

  globalSearchInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      runSearch();
    }
  });
}

// Tables section visibility controls
(function () {
  const tablesSection = document.getElementById('tables');
  if (!tablesSection) return;

  const tableCheckboxes = Array.from(tablesSection.querySelectorAll('.table-visibility-checkbox'));
  const allCheckbox = tablesSection.querySelector('#tables-visibility-all');
  const emptyNote = document.getElementById('table-selection-empty-note');

  if (!tableCheckboxes.length) return;

  function setSectionVisible(targetId, visible) {
    if (!targetId) return;
    const block = document.getElementById(targetId);
    if (!block) return;
    block.style.display = visible ? '' : 'none';
  }

  function syncAllCheckbox() {
    if (!allCheckbox) return;
    const checkedCount = tableCheckboxes.filter(cb => cb.checked).length;
    if (checkedCount === tableCheckboxes.length) {
      allCheckbox.checked = true;
      allCheckbox.indeterminate = false;
      return;
    }
    if (checkedCount === 0) {
      allCheckbox.checked = false;
      allCheckbox.indeterminate = false;
      return;
    }
    allCheckbox.checked = false;
    allCheckbox.indeterminate = true;
  }

  function applyTableVisibility() {
    let checkedCount = 0;
    tableCheckboxes.forEach(cb => {
      const visible = cb.checked;
      if (visible) checkedCount += 1;
      setSectionVisible(cb.getAttribute('data-table-target'), visible);
    });

    if (checkedCount === 0) {
      const first = tableCheckboxes[0];
      first.checked = true;
      setSectionVisible(first.getAttribute('data-table-target'), true);
      checkedCount = 1;
      if (emptyNote) emptyNote.style.display = '';
    } else if (emptyNote) {
      emptyNote.style.display = 'none';
    }

    syncAllCheckbox();
  }

  tableCheckboxes.forEach(cb => {
    cb.addEventListener('change', applyTableVisibility);
  });

  if (allCheckbox) {
    allCheckbox.addEventListener('change', () => {
      const checked = allCheckbox.checked;
      allCheckbox.indeterminate = false;
      tableCheckboxes.forEach(cb => {
        cb.checked = checked;
      });
      applyTableVisibility();
    });
  }

  applyTableVisibility();
})();

// Post control actions (Approve / Reject)
let applyPostControlFilters = null;
let postControlActionInFlight = 0;

document.addEventListener('click', function (event) {
  const groupToggleButton = event.target.closest('[data-post-group-toggle]');
  if (groupToggleButton) {
    const groupKey = groupToggleButton.getAttribute('data-post-group-toggle');
    if (!groupKey) return;

    const detailRow = document.querySelector(`[data-post-group-detail="${groupKey}"]`);
    if (!detailRow) return;

    const expanded = groupToggleButton.getAttribute('data-expanded') === '1';
    if (expanded) {
      detailRow.style.display = 'none';
      groupToggleButton.setAttribute('data-expanded', '0');
      groupToggleButton.textContent = 'View Details';
    } else {
      detailRow.style.display = '';
      groupToggleButton.setAttribute('data-expanded', '1');
      groupToggleButton.textContent = 'Hide Details';
    }
    return;
  }

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
    const alreadyReported = String(row?.dataset?.reportStatus || '').toLowerCase() === 'reported';
    if (alreadyReported) {
      sendCrimeBtn.disabled = true;
      sendCrimeBtn.textContent = 'Reported';
      return;
    }

    const postId = row?.dataset?.id || 'post';
    const caseId = row?.dataset?.caseId || postId;
    const category = row?.dataset?.category || 'other';
    const reporter = row?.dataset?.authorName || 'Unknown';
    const text = row?.dataset?.text || '';
    const landmark = row?.dataset?.category || '';
    const mediaPathRaw = String(row?.dataset?.mediaPath || '').trim();
    const mediaJsonRaw = String(row?.dataset?.mediaJson || '').trim();

    const toAbsoluteLike = (v) => {
      const s = String(v || '').trim();
      if (!s) return '';
      if (/^https?:\/\//i.test(s) || s.startsWith('../')) return s;
      return `../${s.replace(/^\/+/, '')}`;
    };

    const mediaUrls = [];
    if (mediaJsonRaw) {
      try {
        const parsed = JSON.parse(mediaJsonRaw);
        if (Array.isArray(parsed)) {
          parsed.forEach((item) => {
            let raw = '';
            if (typeof item === 'string') {
              raw = item;
            } else if (item && typeof item === 'object') {
              raw = String(item.url || item.path || item.media_path || item.file || item.src || '');
            }
            const normalized = toAbsoluteLike(raw);
            if (normalized) mediaUrls.push(normalized);
          });
        }
      } catch (_err) { }
    }

    const normalizedMediaPath = toAbsoluteLike(mediaPathRaw);
    if (!mediaUrls.length && normalizedMediaPath) {
      mediaUrls.push(normalizedMediaPath);
    }

    const mediaPayload = mediaUrls.map((url) => ({ type: 'media', url, hash: '' }));

    sendCrimeBtn.disabled = true;
    const prevLabel = sendCrimeBtn.textContent;
    sendCrimeBtn.textContent = 'Reporting…';
    const body = new FormData();
    body.append('post_id', postId);
    body.append('action', 'make_report');

    fetch('../Php/admin_update_post_status.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: body
    })
      .then(res => res.json())
      .then(json => {
        if (!json?.success) {
          throw new Error(json?.error || 'Could not mark as reported');
        }

        if (typeof window.pushCrimeFromExternal === 'function') {
          window.pushCrimeFromExternal({
            id: caseId || postId,
            type: category.toLowerCase() || 'other',
            severity: 'medium',
            status: 'new',
            landmark,
            reporter,
            description: text,
            media: mediaPayload
          });
        }

        if (row) row.dataset.reportStatus = 'reported';
        sendCrimeBtn.disabled = true;
        sendCrimeBtn.textContent = 'Reported';
      })
      .catch(error => {
        console.error('Post make_report failed', error);
        sendCrimeBtn.disabled = false;
        sendCrimeBtn.textContent = prevLabel || 'Make Report';
        alert(error?.message || 'Could not make report right now.');
      });
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
  const approveShareButton = row.querySelector('[data-post-action="approve_share"]');

  let customCaption = null;
  if (action === 'approve_share') {
    let defaultText = row.dataset.text || '';

    customCaption = prompt("Add a custom caption for the Facebook Post (or leave as is):", defaultText);
    if (customCaption === null) {
      return;
    }
  }

  const postId = row.dataset.id;
  if (!postId) return;

  const originalLabel = actionButton.textContent;
  actionButton.disabled = true;
  actionButton.textContent = action === 'reject' ? 'Rejecting…' : 'Approving/Sharing…';
  if (approveButton && approveButton !== actionButton) approveButton.disabled = true;
  if (rejectButton && rejectButton !== actionButton) rejectButton.disabled = true;
  if (approveShareButton && approveShareButton !== actionButton) approveShareButton.disabled = true;

  const targetStatus = action === 'reject' ? 'rejected' : 'approved';
  postControlActionInFlight += 1;

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

  const reqBody = new URLSearchParams({ post_id: postId, action });
  if (customCaption !== null) {
    reqBody.append('custom_caption', customCaption);
  }

  fetch('../Php/admin_update_post_status.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: reqBody
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
      if (approveShareButton) approveShareButton.disabled = true;

      if (typeof applyPostControlFilters === 'function') {
        applyPostControlFilters();
      }

      // Surface Facebook share result if present
      try {
        const fb = json.facebook_share;
        if (fb) {
          if (fb.shared) {
            alert('Facebook: shared successfully' + (fb.post_id ? ' (id: ' + fb.post_id + ')' : ''));
            if (row) {
              row.dataset.sharedPostId = fb.post_id || '';
              row.dataset.sharedPayload = JSON.stringify(fb);
            }
          } else if (fb.attempted && !fb.shared) {
            alert('Facebook share attempted but failed: ' + (fb.error || fb.message || 'Unknown error'));
          } else if (fb.skipped) {
            // intentionally skipped (missing config or anonymous); no alert
          }
        }
      } catch (e) {
        console.error('Failed to parse facebook_share result', e);
      }
    })
    .catch(err => {
      console.error('Post status update failed', err);
      actionButton.disabled = false;
      actionButton.textContent = originalLabel;
      if (approveButton && approveButton !== actionButton) approveButton.disabled = false;
      if (rejectButton && rejectButton !== actionButton) rejectButton.disabled = false;
      if (approveShareButton && approveShareButton !== actionButton) approveShareButton.disabled = false;
      alert('Could not update status. This post may already be decided or a network error occurred.');
    })
    .finally(() => {
      postControlActionInFlight = Math.max(0, postControlActionInFlight - 1);
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
  const roleCheckboxes = Array.from(section.querySelectorAll('input[name="post-filter-role"]'));
  const allRoleCheckbox = section.querySelector('input[name="post-filter-role-all"]');
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
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return escapeHtml(String(value));
    return date.toISOString().slice(0, 10);
  }

  function titleCase(value) {
    const raw = String(value || '').trim();
    if (!raw) return 'N/A';
    return raw.charAt(0).toUpperCase() + raw.slice(1);
  }

  const _catBadgeMap = { alert:'🔴 Alert', missing_person:'🟡 Missing Person', criminal_found:'🟢 Criminal Found', disaster:'🟠 Disaster', mission:'🔵 Mission', general:'⚪ General' };
  function categoryBadge(value) {
    const key = String(value || 'general').trim().toLowerCase();
    const label = _catBadgeMap[key] || '⚪ General';
    return `<span class="post-category-badge cat-${escapeHtml(key)}">${label}</span>`;
  }

  function normalizeRoleKey(value) {
    const raw = String(value || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
    if (!raw) return 'user';
    if (raw.includes('camera') || raw === 'contributor' || raw.includes('cameraman') || raw.includes('camera_contributor')) return 'camera_contribution';
    if (raw.includes('police')) return 'policeman';
    if (raw.includes('volunteer')) return 'volunteer';
    if (raw.includes('user')) return 'user';
    return raw;
  }

  function renderPostRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="9">No posts found.</td></tr>';
      return;
    }

    const groups = new Map();
    rows.forEach((row) => {
      const groupKey = `${String(row.author_role || '').toLowerCase()}|${String(row.author_id || '')}|${String(row.author_name || '')}`;
      if (!groups.has(groupKey)) groups.set(groupKey, []);
      groups.get(groupKey).push(row);
    });

    const groupEntries = Array.from(groups.entries());

    tableBody.innerHTML = groupEntries.map(([groupKey, groupRows], index) => {
      const first = groupRows[0] || {};
      const roleText = titleCase(first.author_role || 'user');
      const authorName = first.author_name || 'Unknown';
      const latestDate = formatDate(first.created_at || '');

      const counts = {
        total: groupRows.length,
        pending: groupRows.filter(r => String(r.status || 'pending').toLowerCase() === 'pending').length,
        approved: groupRows.filter(r => String(r.status || '').toLowerCase() === 'approved').length,
        rejected: groupRows.filter(r => String(r.status || '').toLowerCase() === 'rejected').length
      };

      const detailRows = groupRows.map((row) => {
        const id = Number(row.id || 0);
        const postIdText = id > 0 ? `PT-${String(id).padStart(3, '0')}` : 'N/A';
        const statusText = titleCase(row.status || 'pending');
        const reportStatus = String(row.report_status || 'not_reported').toLowerCase();
        const isReported = reportStatus === 'reported';

        return `
          <tr class="post-detail-item" data-id="${escapeHtml(row.id || '')}" data-case-id="${escapeHtml(row.case_id || '')}" data-author-role="${escapeHtml(row.author_role || '')}" data-author-id="${escapeHtml(row.author_id || '')}" data-author-name="${escapeHtml(row.author_name || '')}"
              data-category="${escapeHtml(row.category || '')}" data-text="${escapeHtml(row.text || '')}" data-media-path="${escapeHtml(row.media_path || '')}"
              data-media-json='${escapeHtml(row.media_json || '')}' data-media-type="${escapeHtml(row.media_type || '')}" data-status="${escapeHtml((row.status || 'pending').toLowerCase())}" data-share-facebook="${escapeHtml(row.share_facebook || 0)}"
              data-share-anonymous="${escapeHtml(row.share_anonymous || 0)}" data-is-share="${escapeHtml(row.is_share || 0)}" data-shared-post-id="${escapeHtml(row.shared_post_id || '')}" data-shared-payload='${escapeHtml(row.shared_payload || '')}' data-report-status="${escapeHtml(reportStatus)}">
            <td>${escapeHtml(postIdText)}</td>
            <td>${categoryBadge(row.category)}</td>
            <td>${escapeHtml(row.author_name || 'Unknown')}</td>
            <td>${escapeHtml(titleCase(row.author_role || 'user'))}</td>
            <td>${yesNoBadge(row.share_facebook || 0)}</td>
            <td>${yesNoBadge(row.share_anonymous || 0)}</td>
            <td><span class="post-status ${statusClass(row.status)}">${escapeHtml(statusText)}</span></td>
            <td>${escapeHtml(formatDate(row.created_at || ''))}</td>
            <td>
              <button class="view-profile-btn" data-post-details="1">View Details</button>
              <button class="ghost" data-post-send-crime="1" ${isReported ? 'disabled' : ''}>${isReported ? 'Reported' : 'Make Report'}</button>
              <button class="approve-btn" data-post-action="approve" ${statusClass(row.status) !== 'status-pending' ? 'disabled' : ''}>Approve</button>
              ${Number(row.share_facebook || 0) === 1 ? `<button class="approve-btn" style="background:#1877f2; color:#ffffff;" data-post-action="approve_share" ${statusClass(row.status) === 'status-rejected' ? 'disabled' : ''}><i class="fa-brands fa-facebook"></i> Share FB</button>` : ''}
              <button class="reject-btn" data-post-action="reject" ${statusClass(row.status) !== 'status-pending' ? 'disabled' : ''}>Reject</button>
            </td>
          </tr>
        `;
      }).join('');

      const safeGroupKey = `post-group-${index}`;

      return `
        <tr class="post-group-row" data-post-group="${safeGroupKey}">
          <td><strong>${escapeHtml(`GRP-${String(index + 1).padStart(3, '0')}`)}</strong></td>
          <td>${escapeHtml(`${counts.total} Posts`)}</td>
          <td>${escapeHtml(authorName)}</td>
          <td>${escapeHtml(roleText)}</td>
          <td><span class="share-status share-yes">Total ${counts.total}</span></td>
          <td><span class="status-pending">Pending ${counts.pending}</span></td>
          <td><span class="status-approved">A ${counts.approved}</span> <span class="status-rejected">R ${counts.rejected}</span></td>
          <td>${escapeHtml(latestDate)}</td>
          <td><button class="ghost" type="button" data-post-group-toggle="${safeGroupKey}" data-expanded="0">View Details</button></td>
        </tr>
        <tr class="post-group-detail-row" data-post-group-detail="${safeGroupKey}" style="display:none;">
          <td colspan="9" class="post-group-detail-cell">
            <div class="post-group-detail-title">Post Details</div>
            <table class="styled-table post-group-detail-table">
              <thead>
                <tr>
                  <th>Post ID</th>
                  <th>Category</th>
                  <th>Posted By</th>
                  <th>Role</th>
                  <th>Share Facebook</th>
                  <th>Share Anonymous</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${detailRows}
              </tbody>
            </table>
          </td>
        </tr>
      `;
    }).join('');
  }

  async function loadPostRows() {
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
      reorderPostDetailsByStatus();
      filterRows();
    } catch (error) {
      console.error('Post control fetch failed', error);
      tableBody.innerHTML = '<tr><td colspan="9">Failed to load posts.</td></tr>';
    }
  }

  function filterRows() {
    const keyword = keywordInput.value.trim().toLowerCase();
    const selectedStatus = statusSelect.value.trim().toLowerCase();
    const fromDate = parseDateOnly(fromInput.value);
    const toDate = parseDateOnly(toInput.value);
    const selectedRoles = new Set(
      roleCheckboxes
        .filter(input => input.checked)
        .map(input => normalizeRoleKey(input.value))
    );
    const allRolesSelected = !!allRoleCheckbox && allRoleCheckbox.checked;

    Array.from(tableBody.querySelectorAll('.post-filter-empty-row')).forEach(row => row.remove());

    const groupRows = Array.from(tableBody.querySelectorAll('.post-group-row'));
    let visibleCount = 0;

    groupRows.forEach(groupRow => {
      const groupKey = groupRow.getAttribute('data-post-group');
      const detailRow = groupKey
        ? tableBody.querySelector(`.post-group-detail-row[data-post-group-detail="${groupKey}"]`)
        : null;
      const detailItems = Array.from(detailRow?.querySelectorAll('tr[data-id]') || []);

      let groupVisiblePosts = 0;

      detailItems.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 9) {
          row.style.display = '';
          groupVisiblePosts += 1;
          visibleCount += 1;
          return;
        }

        const postId = (cells[0]?.textContent || '').trim().toLowerCase();
        const category = (cells[1]?.textContent || '').trim().toLowerCase();
        const postedBy = (cells[2]?.textContent || '').trim().toLowerCase();
        const role = (cells[3]?.textContent || '').trim().toLowerCase();
        const roleKey = normalizeRoleKey(row.dataset.authorRole || role);
        const statusText = (row.querySelector('.post-status')?.textContent || '').trim().toLowerCase();
        const submittedText = (cells[7]?.textContent || '').trim();
        const submittedDate = parseDateOnly(submittedText);

        const keywordHaystack = `${postId} ${category} ${postedBy} ${role}`;
        const keywordOk = !keyword || keywordHaystack.includes(keyword);
        const statusOk = selectedStatus === 'all' || statusText === selectedStatus;
        const roleOk = roleCheckboxes.length === 0 || allRolesSelected
          ? true
          : selectedRoles.size > 0 && selectedRoles.has(roleKey);
        const fromOk = !fromDate || (submittedDate && submittedDate >= fromDate);
        const toOk = !toDate || (submittedDate && submittedDate <= toDate);

        const isVisible = keywordOk && statusOk && roleOk && fromOk && toOk;
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) {
          groupVisiblePosts += 1;
          visibleCount += 1;
        }
      });

      const toggleBtn = groupRow.querySelector('[data-post-group-toggle]');
      const expanded = toggleBtn?.getAttribute('data-expanded') === '1';
      const groupVisible = groupVisiblePosts > 0;

      groupRow.style.display = groupVisible ? '' : 'none';
      if (detailRow) {
        detailRow.style.display = groupVisible && expanded ? '' : 'none';
      }
    });

    if (visibleCount === 0) {
      const noMatchRow = document.createElement('tr');
      noMatchRow.className = 'post-filter-empty-row';
      noMatchRow.innerHTML = '<td colspan="9">No posts match the selected filters.</td>';
      tableBody.appendChild(noMatchRow);
    }
  }

  function syncAllRoleCheckboxState() {
    if (!allRoleCheckbox) return;
    const checkedCount = roleCheckboxes.filter(input => input.checked).length;
    if (checkedCount === roleCheckboxes.length) {
      allRoleCheckbox.checked = true;
      allRoleCheckbox.indeterminate = false;
      return;
    }
    if (checkedCount === 0) {
      allRoleCheckbox.checked = false;
      allRoleCheckbox.indeterminate = false;
      return;
    }
    allRoleCheckbox.checked = false;
    allRoleCheckbox.indeterminate = true;
  }

  function getStatusSortWeight(statusText) {
    const status = String(statusText || '').trim().toLowerCase();
    if (status === 'approved') return 2;
    if (status === 'rejected') return 1;
    return 0;
  }

  function parseSortableDate(text) {
    const d = new Date(String(text || '').trim());
    return Number.isNaN(d.getTime()) ? 0 : d.getTime();
  }

  function reorderPostDetailsByStatus() {
    const detailBodies = Array.from(section.querySelectorAll('.post-group-detail-table tbody'));
    detailBodies.forEach((tbody) => {
      const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
      rows.sort((a, b) => {
        const aStatus = (a.querySelector('.post-status')?.textContent || '').trim();
        const bStatus = (b.querySelector('.post-status')?.textContent || '').trim();
        const statusOrder = getStatusSortWeight(aStatus) - getStatusSortWeight(bStatus);
        if (statusOrder !== 0) return statusOrder;

        const aDateText = a.querySelectorAll('td')[7]?.textContent || '';
        const bDateText = b.querySelectorAll('td')[7]?.textContent || '';
        return parseSortableDate(bDateText) - parseSortableDate(aDateText);
      });

      rows.forEach(row => tbody.appendChild(row));
    });
  }

  if (applyButton) {
    applyButton.addEventListener('click', filterRows);
  }
  statusSelect.addEventListener('change', filterRows);
  fromInput.addEventListener('change', filterRows);
  toInput.addEventListener('change', filterRows);
  roleCheckboxes.forEach(input => {
    input.addEventListener('change', () => {
      syncAllRoleCheckboxState();
      filterRows();
    });
  });
  if (allRoleCheckbox) {
    allRoleCheckbox.addEventListener('change', () => {
      const checked = allRoleCheckbox.checked;
      allRoleCheckbox.indeterminate = false;
      roleCheckboxes.forEach(input => {
        input.checked = checked;
      });
      filterRows();
    });
  }
  keywordInput.addEventListener('input', filterRows);
  keywordInput.addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
      event.preventDefault();
      filterRows();
    }
  });

  applyPostControlFilters = filterRows;
  const applyPostControlFiltersWithOrder = () => {
    reorderPostDetailsByStatus();
    syncAllRoleCheckboxState();
    filterRows();
  };
  applyPostControlFilters = applyPostControlFiltersWithOrder;
  document.addEventListener('admin:section-activated', function (event) {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'post-control' && postControlActionInFlight === 0) {
      loadPostRows();
    }
    if (sectionId === 'chat-management' && window.SearcharChat && typeof window.SearcharChat.refresh === 'function') {
      window.SearcharChat.refresh().catch(() => { });
    }
  });

  document.addEventListener('admin:refresh-section', function (event) {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'post-control' && postControlActionInFlight === 0) {
      loadPostRows();
    }
  });
  loadPostRows();
})();

// Admin direct post publish
(function () {
  const textInput = document.getElementById('admin-post-text');
  const categoryInput = document.getElementById('admin-post-category');
  const mediaInput = document.getElementById('admin-post-media');
  const mediaName = document.getElementById('admin-post-media-name');
  const shareFacebookInput = document.getElementById('admin-post-share-facebook');
  const mediaPreview = document.getElementById('admin-post-media-preview');
  const submitBtn = document.getElementById('admin-post-submit');
  if (!textInput || !categoryInput || !submitBtn) return;

  if (mediaInput && mediaPreview) {
    mediaInput.addEventListener('change', () => {
      const file = mediaInput.files && mediaInput.files[0] ? mediaInput.files[0] : null;
      if (!file) {
        if (mediaName) mediaName.textContent = 'No file chosen';
        mediaPreview.innerHTML = 'Media preview will appear here';
        return;
      }

      if (mediaName) {
        mediaName.textContent = file.name;
      }

      const url = URL.createObjectURL(file);
      if (file.type.startsWith('video/')) {
        mediaPreview.innerHTML = `<video src="${url}" controls></video>`;
      } else {
        mediaPreview.innerHTML = `<img src="${url}" alt="Admin post media preview">`;
      }
    });
  }

  submitBtn.addEventListener('click', async () => {
    const text = String(textInput.value || '').trim();
    const category = String(categoryInput.value || 'general').trim().toLowerCase();
    const mediaFile = mediaInput && mediaInput.files && mediaInput.files[0] ? mediaInput.files[0] : null;
    const shareFacebook = shareFacebookInput && shareFacebookInput.checked ? '1' : '0';

    if (!text && !mediaFile) {
      alert('Please write post text or add image/video.');
      return;
    }

    if (mediaFile && mediaFile.size > 20 * 1024 * 1024) {
      alert('Media file is too large. Max 20MB allowed.');
      return;
    }

    const prevLabel = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Publishing…';

    try {
      const body = new FormData();
      body.append('text', text);
      body.append('category', category);
      body.append('share_facebook', shareFacebook);
      if (mediaFile) {
        body.append('media_file', mediaFile, mediaFile.name);
      }

      const res = await fetch('../Php/admin_publish_feed_post.php', {
        method: 'POST',
        credentials: 'same-origin',
        body
      });
      const json = await res.json();
      if (!json?.success) {
        throw new Error(json?.error || 'Failed to publish post');
      }

      textInput.value = '';
      if (mediaInput) mediaInput.value = '';
      if (mediaName) mediaName.textContent = 'No file chosen';
      if (shareFacebookInput) shareFacebookInput.checked = true;
      if (mediaPreview) {
        mediaPreview.innerHTML = 'Media preview will appear here';
      }

      let alertMsg = 'Admin post published successfully.';
      try {
        const fb = json.facebook_share;
        if (fb) {
          if (fb.shared) {
            alertMsg += '\nFacebook: shared successfully' + (fb.post_id ? ' (id: ' + fb.post_id + ')' : '');
          } else if (fb.attempted && !fb.shared) {
            alertMsg += '\nFacebook share attempted but failed: ' + (fb.error || fb.message || 'Unknown error');
          }
        }
      } catch (e) {
        console.error('Failed to parse facebook_share result', e);
      }

      alert(alertMsg);

      document.dispatchEvent(new CustomEvent('admin:section-activated', {
        detail: { sectionId: 'post-control' }
      }));
      if (typeof window.loadAdminPostActivity === 'function') {
        window.loadAdminPostActivity();
      }
    } catch (error) {
      console.error('Admin post publish failed', error);
      alert(error?.message || 'Could not publish admin post right now.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = prevLabel;
    }
  });
})();

// Admin post activity (likes/comments/share + latest comments)
(function () {
  const tableBody = document.getElementById('admin-post-activity-body');
  if (!tableBody) return;

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatDateTime(value) {
    const date = new Date(value || '');
    if (Number.isNaN(date.getTime())) return 'N/A';
    return date.toLocaleString();
  }

  function renderRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="8">No published admin posts found.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map((row) => {
      const comments = Array.isArray(row.comments) ? row.comments : [];
      const commentsHtml = comments.length
        ? `<ul class="admin-post-activity-comments">${comments.map((commentRow) => {
          const actor = escapeHtml(commentRow.actor_name || 'Someone');
          const text = escapeHtml(commentRow.comment_text || '');
          return `<li><strong>${actor}:</strong> ${text}</li>`;
        }).join('')}</ul>`
        : '<span class="admin-post-activity-empty">No comments yet</span>';

      const shareHtml = Number(row.share_facebook || 0) === 1
        ? '<span class="status active">Facebook On</span>'
        : '<span class="status inactive">Facebook Off</span>';

      return `
        <tr>
          <td>#${Number(row.id || 0)}</td>
          <td class="admin-post-message-cell">${escapeHtml(row.text || '')}</td>
          <td>${categoryBadge(row.category)}</td>
          <td>${Number(row.likes_count || 0)}</td>
          <td>${Number(row.comments_count || 0)}</td>
          <td>${shareHtml}</td>
          <td>${commentsHtml}</td>
          <td>${escapeHtml(formatDateTime(row.created_at))}</td>
        </tr>
      `;
    }).join('');
  }

  async function loadAdminPostActivity() {
    tableBody.innerHTML = '<tr><td colspan="8">Loading admin post activity...</td></tr>';
    try {
      const res = await fetch('../Php/admin_fetch_admin_posts_activity.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!json?.success) {
        throw new Error(json?.error || 'Failed to load admin post activity');
      }
      renderRows(Array.isArray(json.rows) ? json.rows : []);
    } catch (error) {
      console.error('admin post activity load failed', error);
      tableBody.innerHTML = '<tr><td colspan="8">Failed to load admin post activity.</td></tr>';
    }
  }

  window.loadAdminPostActivity = loadAdminPostActivity;

  document.addEventListener('admin:section-activated', function (event) {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'admin-post') {
      loadAdminPostActivity();
    }
  });

  document.addEventListener('admin:refresh-section', function (event) {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'admin-post') {
      loadAdminPostActivity();
    }
  });

  loadAdminPostActivity();
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
  aiConfidence.addEventListener('input', function () {
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
    if (!iso) return 'N/A';
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

  function isActionableStatus(status) {
    const normalized = String(status || '').toLowerCase();
    return ['open', 'active', 'pending', 'searching'].includes(normalized);
  }

  function renderRows(rows) {
    if (!tableBody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="9">No missing person reports found.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(row => {
      const reportId = Number(row.report_id || 0);
      const labelId = reportId > 0 ? `MP${String(reportId).padStart(4, '0')}` : 'N/A';
      const lastSeen = [row.last_seen_location || '', row.last_seen_time || ''].filter(Boolean).join(', ');
      const actionable = isActionableStatus(row.status);

      return `
        <tr>
          <td>${escapeHtml(labelId)}</td>
          <td>${escapeHtml(row.full_name || 'N/A')}</td>
          <td>${escapeHtml(row.age || 'N/A')}</td>
          <td>${escapeHtml(row.gender || 'N/A')}</td>
          <td>${escapeHtml(lastSeen || 'N/A')}</td>
          <td><span class="${statusClass(row.status)}">${escapeHtml(prettyStatus(row.status))}</span></td>
          <td>${escapeHtml(row.reporter_name || 'N/A')}</td>
          <td>${escapeHtml(formatDate(row.created_at))}</td>
          <td>
            <button type="button" class="view-profile-btn" data-missing-view="${reportId}">View</button>
            <button type="button" class="danger-btn" data-missing-reject="${reportId}" ${actionable ? '' : 'disabled'}>${actionable ? 'Reject' : 'Locked'}</button>
            <button type="button" class="ghost" data-send-to-crime="${reportId}" ${actionable ? '' : 'disabled'}>${actionable ? 'Make Report' : 'Reported'}</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  async function updateMissingReportStatus(reportId, action) {
    const res = await fetch('../Php/admin_update_missing_report_status.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ report_id: reportId, action })
    });
    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.error || 'Failed to update missing report');
    }
    return json;
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

  window.loadMissingReports = loadMissingReports;

  [statusFilter, locationFilter, minAgeFilter, maxAgeFilter, genderFilter, dateFromFilter, dateToFilter]
    .filter(Boolean)
    .forEach(el => {
      const eventName = el.tagName === 'INPUT' ? 'input' : 'change';
      el.addEventListener(eventName, applyFilters);
    });

  loadMissingReports();

  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'missing') {
      loadMissingReports();
    }
  });

  document.addEventListener('click', (event) => {
    const viewBtn = event.target.closest('[data-missing-view]');
    if (viewBtn) {
      const reportId = Number(viewBtn.getAttribute('data-missing-view') || 0);
      if (!reportId) return;

      const rowData = missingRows.find(r => Number(r.report_id || 0) === reportId);
      if (!rowData) return;

      const payload = {
        __title: `Missing Person Report MP${String(reportId).padStart(4, '0')}`,
        report_id: `MP${String(reportId).padStart(4, '0')}`,
        full_name: rowData.full_name || '',
        nickname: rowData.nickname || '',
        age: rowData.age || '',
        gender: rowData.gender || '',
        status: prettyStatus(rowData.status || ''),
        last_seen_date: rowData.last_seen_date || '',
        last_seen_location: rowData.last_seen_location || '',
        last_seen_time: rowData.last_seen_time || '',
        physical_description: rowData.physical_description || '',
        mental_condition: rowData.mental_condition || '',
        medical_notes: rowData.medical_notes || '',
        reporter_name: rowData.reporter_name || '',
        reporter_mobile: rowData.reporter_mobile || '',
        relationship: rowData.relationship || '',
        consent: Number(rowData.consent || 0) === 1 ? 'Yes' : 'No',
        submitted_at: rowData.created_at || '',
        media_path: rowData.photo_filename ? `../uploads/missing_person/${rowData.photo_filename}` : ''
      };

      if (typeof window.openProfileModal === 'function') {
        window.openProfileModal(payload, false);
      }
      return;
    }

    const rejectBtn = event.target.closest('[data-missing-reject]');
    if (rejectBtn) {
      const reportId = Number(rejectBtn.getAttribute('data-missing-reject') || 0);
      if (!reportId) return;

      const row = rejectBtn.closest('tr');
      updateMissingReportStatus(reportId, 'reject')
        .then((json) => {
          const nextStatus = String(json?.status || 'closed').toLowerCase();
          const idx = missingRows.findIndex(r => Number(r.report_id || 0) === reportId);
          if (idx >= 0) {
            missingRows[idx] = { ...missingRows[idx], status: nextStatus };
          }
          loadMissingReports();
        })
        .catch((error) => {
          console.error('missing reject failed', error);
          alert(error?.message || 'Could not reject report. It may already be processed.');
          loadMissingReports();
        });
      return;
    }

    const sendBtn = event.target.closest('[data-send-to-crime]');
    if (sendBtn) {
      const reportId = Number(sendBtn.getAttribute('data-send-to-crime') || 0);
      if (!reportId) return;

      updateMissingReportStatus(reportId, 'make_report')
        .then((json) => {
          const nextStatus = String(json?.status || 'under_review').toLowerCase();
          const idx = missingRows.findIndex(r => Number(r.report_id || 0) === reportId);
          if (idx >= 0) {
            missingRows[idx] = { ...missingRows[idx], status: nextStatus };
          }
          loadMissingReports();

          const crimeNav = document.querySelector('.sidebar li[data-section="crime"]');
          if (crimeNav) {
            crimeNav.click();
          }
        })
        .catch((error) => {
          console.error('missing make_report failed', error);
          alert(error?.message || 'Could not mark report as processed. It may already be processed.');
          loadMissingReports();
        });
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

  const assignModal = document.getElementById('crime-assign-modal');
  const assignList = document.getElementById('assign-volunteer-list');
  const assignCaseIdEl = document.getElementById('assign-case-id');
  const assignCaseLandmarkEl = document.getElementById('assign-case-landmark');
  const assignMissionTypeEl = document.getElementById('assign-mission-type');
  const assignConfirmBtn = document.getElementById('assign-confirm-btn');

  if (!mapEl || !tableBody) return;

  const defaultDemoCrimes = [
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
        { type: 'photo', url: '../uploads/posts/f936feb7c60087b442de.jpg', hash: 'a4f2c7d9' }
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
      status: 'new',
      lat: 23.7808,
      lng: 90.4098,
      landmark: 'Kawran Bazar crossing',
      submitted: '2026-02-26T02:40:00Z',
      updated_at: '2026-02-26T03:10:00Z',
      media: [{ type: 'photo', url: '../uploads/posts/f936feb7c60087b442de.jpg', hash: 'e29f7caa' }],
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
        { type: 'photo', url: '../uploads/posts/f936feb7c60087b442de.jpg', hash: '99a1b3de' }
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
      media: [{ type: 'photo', url: '../uploads/posts/f936feb7c60087b442de.jpg', hash: '558e3321' }],
      reporter: 'Salma H.',
      anonymous: false,
      token: '',
      description: 'Playground equipment damaged; CCTV available from nearby shop.',
      reward_paid: false
    }
  ];

  const CRIME_STORAGE_KEY = 'searchar_admin_crime_reports_v1';

  function normalizeCrimeRow(row) {
    const rawStatus = String(row?.status || 'new').toLowerCase();
    const normalizedStatus = rawStatus === 'under_review' ? 'actioned' : rawStatus;
    return {
      id: row?.id || '',
      type: row?.type || 'other',
      severity: row?.severity || 'medium',
      status: normalizedStatus || 'new',
      lat: Number(row?.lat ?? 23.8103),
      lng: Number(row?.lng ?? 90.4125),
      landmark: row?.landmark || 'N/A',
      submitted: row?.submitted || new Date().toISOString(),
      updated_at: row?.updated_at || new Date().toISOString(),
      media: Array.isArray(row?.media) ? row.media : [],
      reporter: row?.reporter || 'Unknown',
      anonymous: !!row?.anonymous,
      token: row?.token || '',
      description: row?.description || '',
      reward_paid: !!row?.reward_paid
    };
  }

  function loadCrimeReports() {
    return [];
  }

  function saveCrimeReports(rows) {
    // Crime rows are DB-backed. Keep this as a no-op for compatibility.
  }

  async function syncCrimesFromMissingReports() {
    try {
      const res = await fetch('../Php/fetch_crime_reports_admin.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!json?.success || !Array.isArray(json.rows)) {
        demoCrimes = [];
        applyFilters();
        return;
      }

      demoCrimes = json.rows.map(normalizeCrimeRow);
      applyFilters();
    } catch (error) {
      console.error('missing->crime sync failed', error);
      demoCrimes = [];
      applyFilters();
    }
  }

  window.syncCrimesFromMissingReports = syncCrimesFromMissingReports;

  let demoCrimes = loadCrimeReports();
  let filteredCrimes = [...demoCrimes];
  let crimeMap = null;
  let crimeMarkers = [];
  let crimeZones = [];
  let crimeHeat = null;
  let geotagMarker = null;
  let proximityMarker = null;
  let proximityCircle = null;
  let assignedCrimes = new Set();
  const CASE_ASSIGN_HISTORY_KEY = 'searchar_case_assign_history_v1';

  function loadCaseAssignHistory() {
    try {
      const raw = localStorage.getItem(CASE_ASSIGN_HISTORY_KEY);
      if (!raw) return {};
      const parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (_) {
      return {};
    }
  }

  function saveCaseAssignHistory(history) {
    try {
      localStorage.setItem(CASE_ASSIGN_HISTORY_KEY, JSON.stringify(history || {}));
    } catch (_) {
    }
  }

  let caseAssignHistory = loadCaseAssignHistory();
  const crimeActionState = new Map();
  let currentAssignCaseId = null;
  let currentAssignLandmark = '';
  let currentAssignMedia = [];
  let currentAssignCoords = null;
  let assignCandidates = [];

  function getCrimeActionState(caseId) {
    const key = String(caseId || '');
    if (!crimeActionState.has(key)) {
      crimeActionState.set(key, {
        assigned: false,
        cctv: false,
        marked: false,
        rejected: false
      });
    }
    return crimeActionState.get(key);
  }

  const demoVolunteers = [
    // Volunteers (password 12345678)
    { id: 'VOL-100', name: 'Rahim Uddin', location: 'Banani', status: 'available', role: 'volunteer', password: '12345678' },
    { id: 'VOL-101', name: 'Tariq Rahman', location: 'Banani', status: 'available', role: 'volunteer', password: '12345678' },
    { id: 'VOL-102', name: 'Nabila Sultana', location: 'Dhanmondi', status: 'available', role: 'volunteer', password: '12345678' },
    { id: 'VOL-103', name: 'Arman Hossain', location: 'Farmgate', status: 'busy', role: 'volunteer', password: '12345678' },
    { id: 'VOL-104', name: 'Lamia Chowdhury', location: 'Jatrabari', status: 'available', role: 'volunteer', password: '12345678' },
    { id: 'VOL-105', name: 'Sumon Ahmed', location: 'Uttara', status: 'available', role: 'volunteer', password: '12345678' },
    // Cameramen (password 12345678)
    { id: 'CAM-201', name: 'Rafiul Islam', location: 'Dhanmondi', status: 'available', role: 'cameraman', password: '12345678' },
    { id: 'CAM-202', name: 'Mehedi Hasan', location: 'Gulshan', status: 'available', role: 'cameraman', password: '12345678' },
    { id: 'CAM-203', name: 'Priya Khatun', location: 'Mirpur', status: 'busy', role: 'cameraman', password: '12345678' },
    { id: 'CAM-204', name: 'Shafin Chowdhury', location: 'Banani', status: 'available', role: 'cameraman', password: '12345678' },
    { id: 'CAM-205', name: 'Tanisha Rahman', location: 'Jatrabari', status: 'available', role: 'cameraman', password: '12345678' }
  ];

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

  function randomNearDhaka() {
    const baseLat = 23.8103;
    const baseLng = 90.4125;
    const jitter = () => (Math.random() - 0.5) * 0.12; // ~<7km
    return { lat: baseLat + jitter(), lng: baseLng + jitter() };
  }

  function nextCrimeId() {
    const base = 2000 + demoCrimes.length + 1;
    return `CR-NEW-${base}`;
  }

  function pushCrimeFromExternal(payload) {
    const now = new Date().toISOString();
    const coords = randomNearDhaka();
    const row = {
      id: payload.id || nextCrimeId(),
      type: payload.type || 'other',
      severity: payload.severity || 'medium',
      status: payload.status || 'new',
      lat: payload.lat ?? coords.lat,
      lng: payload.lng ?? coords.lng,
      landmark: payload.landmark || 'N/A',
      submitted: now,
      updated_at: now,
      media: payload.media || [],
      reporter: payload.reporter || 'Unknown',
      anonymous: false,
      token: '',
      description: payload.description || ''
    };
    demoCrimes.push(row);
    saveCrimeReports(demoCrimes);
    applyFilters();
  }
  window.pushCrimeFromExternal = pushCrimeFromExternal;

  function normalizeAssignLocation(raw) {
    const text = String(raw || '')
      .replace(/\bdivision\b/gi, '')
      .replace(/\bdiv\.?\b/gi, '')
      .replace(/বিভাগ/gi, '')
      .replace(/\s+,/g, ',')
      .replace(/,\s*,/g, ',')
      .replace(/\s{2,}/g, ' ')
      .trim()
      .replace(/^,+|,+$/g, '')
      .trim();

    return text;
  }

  async function loadAssignCandidates() {
    try {
      const [volRes, comboRes, miscRes] = await Promise.all([
        fetch('../Php/admin_fetch_volunteers.php', { credentials: 'same-origin', cache: 'no-store' }),
        fetch('../Php/admin_fetch_combo_users.php', { credentials: 'same-origin', cache: 'no-store' }),
        fetch('../Php/admin_fetch_misc_sections.php', { credentials: 'same-origin', cache: 'no-store' })
      ]);

      const volJson = await volRes.json();
      const comboJson = await comboRes.json();
      const miscJson = await miscRes.json();

      const activeMissionIds = new Set(
        Array.isArray(miscJson?.missions)
          ? miscJson.missions
            .filter((row) => {
              const status = String(row?.status || '').toLowerCase();
              const response = String(row?.response_status || '').toLowerCase();
              return !['completed', 'rejected_busy', 'closed_by_police'].includes(status)
                && !['completed', 'rejected_busy', 'closed_by_police'].includes(response);
            })
            .map((row) => Number(row?.volunteer_id || 0))
            .filter((id) => id > 0)
          : []
      );

      const volunteers = Array.isArray(volJson?.data)
        ? volJson.data.map(v => ({
          id: `VOL-${v.volunteer_id}`,
          name: v.full_name || v.name || 'Volunteer',
          location: normalizeAssignLocation(
            [v.street, v.city, v.location]
              .map(item => String(item || '').trim())
              .filter(Boolean)
              .join(', ')
          ) || 'Dhaka',
          status: String(v.status || '').toLowerCase() === 'busy' ? 'busy' : 'available',
          role: 'volunteer',
          recipient_entity: 'volunteer',
          recipient_id: Number(v.volunteer_id || 0),
          lat: Number(v.lat ?? v.latitude),
          lng: Number(v.lng ?? v.lon ?? v.longitude),
          profile_tag: 'Volunteer'
        }))
        : [];

      const volunteerById = new Map();
      volunteers.forEach((item) => {
        const idNum = Number(item.recipient_id || 0);
        if (idNum > 0) volunteerById.set(idNum, item);
      });

      const comboRows = Array.isArray(comboJson?.data) ? comboJson.data : [];
      const comboCandidates = comboRows.map((row) => {
        const volunteerId = Number(row?.volunteer_id || 0);
        const linkedVolunteer = volunteerById.get(volunteerId);
        const name = String(row?.full_name || linkedVolunteer?.name || 'Volunteer').trim();
        const location = normalizeAssignLocation(
          [
            row?.city,
            row?.country,
            linkedVolunteer?.location
          ]
            .map(item => String(item || '').trim())
            .filter(Boolean)
            .join(', ')
        ) || (linkedVolunteer?.location || 'Dhaka');

        const recipientEntity = volunteerId > 0 ? 'volunteer' : 'user';
        const recipientId = volunteerId > 0 ? volunteerId : Number(row?.user_id || 0);

        return {
          id: `VPLUS-${row?.application_id || volunteerId || recipientId || name}`,
          name,
          location,
          status: 'available',
          role: 'volunteer_plus',
          recipient_entity: recipientEntity,
          recipient_id: recipientId,
          lat: Number(linkedVolunteer?.lat),
          lng: Number(linkedVolunteer?.lng),
          profile_tag: 'Volunteer + User'
        };
      }).filter(item => Number(item.recipient_id || 0) > 0);

      const merged = new Map();
      volunteers.forEach((item) => {
        const key = `${item.recipient_entity}:${item.recipient_id}`;
        merged.set(key, item);
      });
      comboCandidates.forEach((item) => {
        const key = `${item.recipient_entity}:${item.recipient_id}`;
        if (merged.has(key)) {
          const prev = merged.get(key);
          merged.set(key, {
            ...prev,
            profile_tag: 'Volunteer + User'
          });
        } else {
          merged.set(key, item);
        }
      });

      assignCandidates = Array.from(merged.values())
        .filter(a => a.recipient_id > 0)
        .map(candidate => ({
          ...candidate,
          status: activeMissionIds.has(Number(candidate.recipient_id || 0)) ? 'busy' : candidate.status
        }));
    } catch (error) {
      console.error('assign candidates load failed', error);
      assignCandidates = [];
    }
  }

  function updateAssignRadiusLabel(_value) { }

  function closeCrimeAssignModal() {
    if (assignModal) assignModal.classList.remove('open');
    currentAssignCaseId = null;
    currentAssignCoords = null;
  }
  window.closeCrimeAssignModal = closeCrimeAssignModal;

  function renderAssignList() {
    if (!assignList) return;
    const sourceList = assignCandidates.length
      ? assignCandidates
      : demoVolunteers.filter(v => String(v.role || '').toLowerCase() === 'volunteer');

    const assignedKey = String(currentAssignCaseId || '');
    const alreadyAssigned = new Set((caseAssignHistory[assignedKey] || []).map(v => Number(v)));
    const eligibleSource = sourceList.filter(v => {
      const rid = Number(v.recipient_id || 0);
      const isAvailable = String(v.status || '').toLowerCase() === 'available';
      if (!isAvailable) return false;
      if (rid > 0 && alreadyAssigned.has(rid)) return false;
      return true;
    });

    const header = `<div class="assign-vol-meta" style="padding:4px 8px 10px; font-weight:700;">Showing all available volunteers from database</div>`;

    if (!eligibleSource.length) {
      assignList.innerHTML = `${header}<div class="assign-vol-meta" style="padding:8px;">No available volunteers found in database.</div>`;
      return;
    }

    const rows = eligibleSource.map(v => {
      return `
        <label class="assign-list-item">
          <span>
            <input type="checkbox" value="${v.id}" data-recipient-entity="${v.recipient_entity || ''}" data-recipient-id="${v.recipient_id || ''}" data-recipient-name="${v.name || ''}">
            <strong>${v.name}</strong>
            <div class="assign-vol-meta">${v.location || 'Location unavailable'} • ${v.profile_tag || v.status}</div>
          </span>
        </label>
      `;
    }).join('');
    assignList.innerHTML = `${header}${rows}`;
  }

  function appendVolunteerRowsToTable(volunteerIds, landmark) {
    const volunteerSection = document.getElementById('volunteer');
    const tbody = volunteerSection?.querySelector('tbody');
    if (!tbody) return;
    const sourceList = assignCandidates.length
      ? assignCandidates
      : demoVolunteers.filter(v => String(v.role || '').toLowerCase() === 'volunteer');
    volunteerIds.forEach(id => {
      const vol = sourceList.find(v => v.id === id);
      if (!vol) return;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${vol.name}</td>
        <td>Crime response at ${landmark || 'N/A'}</td>
        <td>${new Date().toISOString().slice(0, 10)}</td>
        <td>${vol.location}</td>
        <td><span class="status-approved">Assigned</span></td>
        <td>Auto-added from Crime Reporting</td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function sendAssignNotifications(assignments, context) {
    if (!assignments.length) return;
    try {
      await fetch('../Php/admin_notify_assignments.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ assignments, context })
      });
    } catch (error) {
      console.error('assignment notification failed', error);
    }
  }

  function openAssignModal(caseId, landmark, media = [], coords = null) {
    currentAssignCaseId = caseId;
    currentAssignLandmark = String(landmark || '');
    currentAssignMedia = Array.isArray(media) ? media : [];
    currentAssignCoords = coords && Number.isFinite(Number(coords.lat)) && Number.isFinite(Number(coords.lng))
      ? { lat: Number(coords.lat), lng: Number(coords.lng) }
      : null;
    if (assignCaseIdEl) assignCaseIdEl.textContent = caseId;
    if (assignCaseLandmarkEl) assignCaseLandmarkEl.textContent = landmark || 'N/A';
    if (assignList) {
      assignList.innerHTML = '<div class="assign-vol-meta" style="padding:8px;">Loading volunteers from database…</div>';
    }
    if (assignModal) assignModal.classList.add('open');
    loadAssignCandidates()
      .catch((error) => console.error('assign candidates refresh failed', error))
      .finally(() => renderAssignList());
  }
  window.openAssignModal = openAssignModal;

  if (assignConfirmBtn) {
    assignConfirmBtn.addEventListener('click', async () => {
      if (!currentAssignCaseId) return;
      const checks = Array.from(assignList?.querySelectorAll('input[type="checkbox"]') || []).filter(ch => ch.checked && !ch.disabled);
      if (!checks.length) {
        alert('Select at least one available volunteer.');
        return;
      }
      const ids = checks.map(ch => ch.value);
      const assignments = checks.map(ch => ({
        recipient_entity: ch.getAttribute('data-recipient-entity') || '',
        recipient_id: Number(ch.getAttribute('data-recipient-id') || 0),
        recipient_name: ch.getAttribute('data-recipient-name') || ''
      })).filter(a => a.recipient_id > 0 && a.recipient_entity);

      const missionType = String(assignMissionTypeEl?.value || 'locate_verify');
      const missionTextMap = {
        locate_verify: 'Locate and verify the alert spot, then send a quick ground update.',
        collect_evidence: 'Collect clear image/video evidence and submit it immediately.',
        suspect_watch: 'Monitor suspect movement safely and report any live updates.',
        assist_police: 'Coordinate with police team and assist response at scene.'
      };

      const missionLabelMap = {
        locate_verify: 'Locate & Verify Alert',
        collect_evidence: 'Collect Evidence (Photo/Video)',
        suspect_watch: 'Suspect Watch & Report',
        assist_police: 'Assist Police Response'
      };

      appendVolunteerRowsToTable(ids, assignCaseLandmarkEl?.textContent || '');
      await sendAssignNotifications(assignments, {
        case_id: currentAssignCaseId,
        landmark: assignCaseLandmarkEl?.textContent || '',
        media: currentAssignMedia,
        mission_type: missionType,
        mission_label: missionLabelMap[missionType] || 'Assigned Mission',
        mission_note: missionTextMap[missionType] || missionTextMap.locate_verify
      });

      const caseKey = String(currentAssignCaseId || '');
      const previous = new Set((caseAssignHistory[caseKey] || []).map(v => Number(v)));
      assignments.forEach(a => {
        const rid = Number(a.recipient_id || 0);
        if (rid > 0) previous.add(rid);
      });
      caseAssignHistory[caseKey] = Array.from(previous);
      saveCaseAssignHistory(caseAssignHistory);

      assignedCrimes.add(currentAssignCaseId);
      const state = getCrimeActionState(currentAssignCaseId);
      state.assigned = true;

      const crimeRow = demoCrimes.find(c => String(c.id) === String(currentAssignCaseId));
      if (crimeRow) {
        const currentStatus = String(crimeRow.status || 'new').toLowerCase();
        if (currentStatus === 'new') {
          updateCrimeStatus(currentAssignCaseId, 'actioned');
        }
      }

      const btn = document.querySelector(`[data-crime-assign="${currentAssignCaseId}"]`);
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Assigned';
      }
      closeCrimeAssignModal();
    });
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
    if (!iso) return 'N/A';
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
    const isClosed = String(crime.status || '').toLowerCase() === 'closed';
    const color = isClosed ? '#22c55e' : severityColor(crime.severity);
    const isRecent = withinLast24(crime.submitted);
    const marker = L.circleMarker([crime.lat, crime.lng], {
      radius: isRecent && !isClosed ? 11 : 9,
      color,
      weight: 2,
      fillColor: isClosed ? '#22c55e' : (isRecent ? '#10b981' : color),
      fillOpacity: 0.6
    });

    const mediaList = (crime.media || []).map((m, idx) => {
      const mediaUrl = String(m?.url || m?.path || '').trim();
      const mediaType = String(m?.type || 'media').trim() || 'media';
      if (!mediaUrl) {
        return `<li>${mediaType}</li>`;
      }
      return `<li><a href="${mediaUrl}" target="_blank" rel="noopener">${mediaType} ${idx + 1}</a></li>`;
    }).join('');
    const popup = `
      <div style="min-width:220px;">
        <div style="font-weight:800; margin-bottom:4px;">${crime.id} • ${statusLabel(crime.status)}</div>
        <div><strong>Type:</strong> ${crime.type}</div>
        <div><strong>Severity:</strong> ${crime.severity}</div>
        <div><strong>Landmark:</strong> ${crime.landmark || 'N/A'}</div>
        <div><strong>Submitted:</strong> ${formatDateLocal(crime.submitted)}</div>
        <div><strong>Reporter:</strong> ${crime.anonymous ? 'Anonymous' : (crime.reporter || 'N/A')}</div>
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

    const showHeatmap = toggleHeatmap && toggleHeatmap.checked && typeof L.heatLayer === 'function';

    rows.forEach(r => {
      const isClosed = String(r.status || '').toLowerCase() === 'closed';
      const zColor = isClosed ? '#22c55e' : severityZoneColor(r.severity);
      const zone = L.circle([r.lat, r.lng], {
        radius: severityRadius(r.severity),
        color: zColor,
        weight: 1.4,
        fillColor: zColor,
        fillOpacity: 0.12,
        opacity: 0.9,
        dashArray: '6 4'
      });
      crimeZones.push(zone);

      const marker = createMarker(r);
      crimeMarkers.push(marker);

      if (!showHeatmap) {
        zone.addTo(crimeMap);
        marker.addTo(crimeMap);
      }
    });

    if (showHeatmap) {
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
      const actState = getCrimeActionState(r.id);
      const isClosed = String(r.status || '').toLowerCase() === 'closed';
      const isClosedByAI = String(r.description || '').includes('[Closed by Admin AI');
      const assignDisabled = isClosed || actState.assigned || assignedCrimes.has(r.id) || actState.rejected;
      const cctvDisabled = isClosed || actState.cctv || actState.rejected;

      let actionButtonsHtml = '';
      if (isClosed) {
        if (isClosedByAI) {
          actionButtonsHtml = `<span style="color:#666; font-size:12px; margin-left:5px;">Closed by AI</span>`;
        } else {
          if (String(r.id).startsWith('MP-')) {
            actionButtonsHtml = `<button type="button" class="ghost" onclick="notifyReporterManualHandover('${r.id}', this)">Notify Reporter</button>`;
          } else {
            actionButtonsHtml = `<span style="color:#666; font-size:12px; margin-left:5px;">Closed</span>`;
          }
        }
      } else {
        actionButtonsHtml = `
          <button type="button" class="ghost" data-crime-assign="${r.id}" ${assignDisabled ? 'disabled' : ''}>${actState.assigned || assignedCrimes.has(r.id) ? 'Assigned' : 'Assign Volunteer'}</button>
          <button type="button" class="ghost" data-crime-cctv="${r.id}" ${cctvDisabled ? 'disabled' : ''}>AI Investigation</button>
        `;
      }

      return `
        <tr data-crime-id="${r.id}">
          <td>${r.id}</td>
          <td>${r.type}</td>
          <td>${r.severity}</td>
          <td>${r.landmark || 'N/A'}</td>
          <td>${statusBadge(r.status)}</td>
          <td>${formatDateLocal(r.submitted)}</td>
          <td>${mediaCount} file${mediaCount === 1 ? '' : 's'}</td>
          <td>${r.anonymous ? 'Anonymous' : (r.reporter || 'N/A')}</td>
          <td>
            <button type="button" class="view-profile-btn" data-crime-view="${r.id}">View</button>
            ${actionButtonsHtml}
          </td>
        </tr>
      `;
    }).join('');
  }

  function updateStats(rows) {
    const counts = { new: 0, actioned: 0, closed: 0 };
    rows.forEach(r => {
      const key = String(r.status || 'new').toLowerCase();
      if (counts[key] !== undefined) counts[key] += 1;
    });
    if (statNew) statNew.textContent = `New: ${counts.new}`;
    if (statReview) statReview.textContent = `Actioned (Assigned): ${counts.actioned}`;
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
        const haystack = `${r.id} ${r.type} ${r.landmark || ''} ${statusVal}`.toLowerCase();
        if (!haystack.includes(q)) return false;
      }

      return true;
    });

    filteredCrimes.sort((a, b) => {
      const aClosed = String(a?.status || '').toLowerCase() === 'closed';
      const bClosed = String(b?.status || '').toLowerCase() === 'closed';
      if (aClosed !== bClosed) return aClosed ? 1 : -1;

      const aTime = Date.parse(String(a?.updated_at || a?.submitted || ''));
      const bTime = Date.parse(String(b?.updated_at || b?.submitted || ''));
      const aSafe = Number.isNaN(aTime) ? 0 : aTime;
      const bSafe = Number.isNaN(bTime) ? 0 : bTime;
      return bSafe - aSafe;
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
    saveCrimeReports(demoCrimes);
    applyFilters();
  }
  window.updateCrimeCaseStatusFromMission = function (id, status = 'closed') {
    const caseId = String(id || '').trim();
    const nextStatus = String(status || 'closed').toLowerCase();
    if (!caseId) return;
    updateCrimeStatus(caseId, nextStatus);
  };

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
            ${h.landmark || 'N/A'}
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

    const mediaFiles = (Array.isArray(crime.media) ? crime.media : [])
      .map((item) => String(item?.url || item?.path || '').trim())
      .filter(Boolean);

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
      reporter: crime.anonymous ? 'Anonymous' : (crime.reporter || 'N/A'),
      description: crime.description || '',
      media_path: mediaFiles[0] || '',
      media_json: JSON.stringify(mediaFiles)
    };

    if (typeof window.openProfileModal === 'function') {
      window.openProfileModal(payload, false);
    }
  }

  function initMap() {
    crimeMap = L.map('crime-map').setView([23.685, 90.3563], 5); // Bangladesh full map view
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '© OpenStreetMap'
    }).addTo(crimeMap);

    setTimeout(() => crimeMap.invalidateSize(), 300);
  }

  function bindEvents() {
    if (filterReset) {
      filterReset.addEventListener('click', async () => {
        if (filterText) filterText.value = '';
        if (filterType) filterType.value = '';
        if (filterSeverity) filterSeverity.value = '';
        if (filterStatus) filterStatus.value = '';
        if (filterFrom) filterFrom.value = '';
        if (filterTo) filterTo.value = '';
        if (toggleLast24) toggleLast24.checked = false;
        if (toggleClosed) toggleClosed.checked = true;
        await syncCrimesFromMissingReports();
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
        if (assignBtn.disabled) return;
        const id = assignBtn.getAttribute('data-crime-assign');
        const state = getCrimeActionState(id);
        if (state.assigned || state.rejected) return;
        const crime = demoCrimes.find(c => c.id === id);
        if (String(crime?.status || '').toLowerCase() === 'closed') return;
        openAssignModal(id, crime?.landmark || '', crime?.media || [], { lat: crime?.lat, lng: crime?.lng });
        return;
      }

      const cctvBtn = event.target.closest('[data-crime-cctv]');
      if (cctvBtn) {
        if (cctvBtn.disabled) return;
        const id = cctvBtn.getAttribute('data-crime-cctv');
        const crime = demoCrimes.find(c => c.id === id);
        if (String(crime?.status || '').toLowerCase() === 'closed') return;

        if (crime && Array.isArray(crime.media) && crime.media.length > 0) {
          const mediaUrl = crime.media[0].url || crime.media[0].path;
          if (mediaUrl) {
            window.activeAiSearchCaseId = id;
            window.activeAiSearchImage = mediaUrl;

            const emptyRow = document.getElementById('empty-investigations');
            if (emptyRow) emptyRow.style.display = 'none';

            const tbody = document.getElementById('active-investigations-body');
            let existingRow = document.getElementById(`investigation-row-${id}`);

            let resolvedMediaUrl = mediaUrl;
            if (!resolvedMediaUrl.startsWith('http') && !resolvedMediaUrl.startsWith('data:') && !resolvedMediaUrl.startsWith('../')) {
              resolvedMediaUrl = '../' + resolvedMediaUrl;
            }

            if (!existingRow) {
              const tr = document.createElement('tr');
              tr.id = `investigation-row-${id}`;
              tr.innerHTML = `
                <td><strong>${id}</strong><br><small>${escapeHtml(crime.landmark || '')}</small></td>
                <td>
                  <img src="${escapeHtml(resolvedMediaUrl)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;" onerror="this.src='../Images/demo_pic/profile.jpg'">
                </td>
                <td id="search-img-col-${id}">
                  <span style="font-size:12px; color:#666;">No search image yet</span>
                </td>
                <td id="inv-status-${id}"><span class="status-pending">Ready</span></td>
                <td>
                  <button type="button" class="ghost" style="margin-right: 5px;" onclick="startPythonAISearch('${id}', '${resolvedMediaUrl}', 'posts')">Search in Posts</button>
                  <button type="button" class="ghost" onclick="promptCCTVImageAndSearch('${id}', '${resolvedMediaUrl}')">Search in CCTV</button>
                </td>
              `;
              tbody.prepend(tr);
              saveActiveInvestigations();
            } else {
              // Update the row just in case the image was broken previously
              existingRow.querySelector('img').src = escapeHtml(resolvedMediaUrl);
              const btns = existingRow.querySelectorAll('button.ghost');
              const actionBtns = Array.from(btns).filter(b => b.innerText.includes('Search in'));
              if (actionBtns.length >= 2) {
                actionBtns[0].setAttribute('onclick', `startPythonAISearch('${id}', '${resolvedMediaUrl}', 'posts')`);
                actionBtns[1].setAttribute('onclick', `promptCCTVImageAndSearch('${id}', '${resolvedMediaUrl}')`);
              }
              // Highlight the existing row
              existingRow.style.backgroundColor = '#f1f5f9';
              setTimeout(() => { existingRow.style.backgroundColor = ''; }, 1500);
              saveActiveInvestigations();
            }
          }
        } else {
          window.activeAiSearchCaseId = id;
          window.activeAiSearchImage = null;
        }

        const aiTab = document.querySelector('.sidebar-menu li[data-section="ai"], li[data-section="ai"]');
        if (aiTab) aiTab.click();

        return;
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

    document.addEventListener('admin:section-activated', (event) => {
      const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
      if (sectionId === 'crime') {
        syncCrimesFromMissingReports();
      }
    });
  }

  initMap();
  loadAssignCandidates();
  bindEvents();
  generateAnonToken();
  applyFilters();
  syncCrimesFromMissingReports();

  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'crime') {
      syncCrimesFromMissingReports();
    }
  });
})();

// Load Donations, Broadcast, Volunteer Missions, Withdraw sections
(function () {
  const donationsBody = document.getElementById('donations-table-body');
  const broadcastBody = document.getElementById('broadcast-table-body');
  const missionsBody = document.getElementById('volunteer-mission-body');
  const cameraVideoBody = document.getElementById('camera-video-table-body');
  const broadcastRequestBody = document.getElementById('broadcast-request-body');
  // Withdraw Control is managed in Admin.html now; avoid overwriting its data
  const withdrawBody = (typeof window.renderWithdrawalsTable === 'function') ? null : document.getElementById('withdraw-table-body');
  const volunteerTotalMissions = document.getElementById('volunteer-total-missions');
  const volunteerThisMonth = document.getElementById('volunteer-this-month');
  const donationsTotalAmount = document.getElementById('donations-total-amount');
  const donationsTopDonor = document.getElementById('donations-top-donor');
  const withdrawTotalAmount = document.getElementById('withdraw-total-amount');
  const withdrawPendingCount = document.getElementById('withdraw-pending-count');
  const miscSectionIds = ['donations', 'broadcast', 'volunteer'];
  let isLoadingMiscSections = false;

  if (!donationsBody && !broadcastBody && !missionsBody && !withdrawBody && !cameraVideoBody && !broadcastRequestBody) return;

  function esc(v) {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fmtDate(v) {
    if (!v) return 'N/A';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return esc(v);
    return d.toLocaleString();
  }

  function setNoData(tbody, colspan, text) {
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="${colspan}">${esc(text)}</td></tr>`;
  }

  function normalizeMissionState(rawStatus, rawResponse) {
    const status = String(rawStatus || '').toLowerCase();
    const response = String(rawResponse || '').toLowerCase();
    const responseState = response || (status === 'accepted' ? 'accepted' : status === 'rejected_busy' ? 'rejected_busy' : status === 'completed' ? 'completed' : 'pending');
    const lifeState = status || (responseState === 'completed' ? 'completed' : responseState === 'accepted' ? 'accepted' : responseState === 'rejected_busy' ? 'rejected_busy' : 'assigned');
    return { responseState, lifeState };
  }

  function renderStatusChip(state) {
    const s = String(state || '').toLowerCase();
    if (s === 'completed') return '<span class="status-approved">Completed</span>';
    if (s === 'accepted') return '<span class="status-pending">Accepted</span>';
    if (s === 'rejected_busy') return '<span class="status-rejected">Rejected (Busy)</span>';
    if (s === 'assigned' || s === 'pending') return '<span class="status-pending">Pending</span>';
    return `<span class="status-pending">${esc(state || 'pending')}</span>`;
  }

  function renderWithdrawStatusChip(state) {
    const s = String(state || 'pending').toLowerCase();
    if (s === 'approved') return '<span class="status-approved">Approved</span>';
    if (s === 'rejected') return '<span class="status-rejected">Rejected</span>';
    return '<span class="status-pending">Pending</span>';
  }

  function missionProofUrl(rawPath) {
    const path = String(rawPath || '').trim();
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    if (path.startsWith('../') || path.startsWith('./') || path.startsWith('/')) return path;
    return `../${path}`;
  }

  function volunteerProfileUrl(rawPath) {
    const path = String(rawPath || '').trim();
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    if (path.startsWith('../') || path.startsWith('./') || path.startsWith('/')) return path;
    return `../${path}`;
  }

  function cameraVideoUrl(rawPath) {
    const path = String(rawPath || '').trim();
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    if (path.startsWith('../') || path.startsWith('./') || path.startsWith('/')) return path;
    return `../${path}`;
  }

  function broadcastRequestStatusChip(status) {
    const s = String(status || 'pending').toLowerCase();
    if (s === 'approved') return '<span class="status-approved">Approved</span>';
    if (s === 'rejected') return '<span class="status-rejected">Rejected</span>';
    return '<span class="status-pending">Pending</span>';
  }

  function renderBroadcastRequests(rows) {
    if (!broadcastRequestBody) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      setNoData(broadcastRequestBody, 6, 'No broadcast requests yet.');
      return;
    }

    broadcastRequestBody.innerHTML = rows.map((row) => {
      const requestId = Number(row.request_id || 0);
      const policeName = String(row.police_name || 'Police Officer');
      const station = String(row.station || 'N/A');
      const status = String(row.status || 'pending').toLowerCase();
      const statusHtml = broadcastRequestStatusChip(status);
      const createdAt = fmtDate(row.created_at || '');
      const requestReason = String(row.request_reason || '').trim() || 'N/A';
      const canAct = status === 'pending';

      return `
        <tr data-broadcast-request-id="${esc(requestId)}">
          <td>${esc(policeName)}</td>
          <td>${esc(station)}</td>
          <td>${esc(requestReason)}</td>
          <td>${esc(createdAt)}</td>
          <td>${statusHtml}</td>
          <td>
            ${status === 'approved'
          ? `<button type="button" data-broadcast-request-action="close_link" data-broadcast-request-id="${esc(requestId)}" style="background: #fee2e2; color: #dc2626; border: 1px solid #f87171; padding: 6px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: all 0.2s;">Close Link</button>`
          : `
                <button type="button" class="approve-btn" data-broadcast-request-action="approve" data-broadcast-request-id="${esc(requestId)}" ${canAct ? '' : 'disabled'}>Approve</button>
                <button type="button" class="reject-btn" data-broadcast-request-action="reject" data-broadcast-request-id="${esc(requestId)}" ${canAct ? '' : 'disabled'}>Reject</button>
              `
        }
          </td>
        </tr>
      `;
    }).join('');
  }

  async function loadMiscSections() {
    if (isLoadingMiscSections) {
      return;
    }

    isLoadingMiscSections = true;
    try {
      const miscRequest = fetch('../Php/admin_fetch_misc_sections.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const cameraRequest = cameraVideoBody
        ? fetch('../Php/admin_fetch_camera_videos.php', { credentials: 'same-origin', cache: 'no-store' })
        : Promise.resolve(null);

      const broadcastRequest = broadcastRequestBody
        ? fetch('../Php/admin_fetch_broadcast_requests.php', { credentials: 'same-origin', cache: 'no-store' })
        : Promise.resolve(null);

      const [miscRes, cameraRes, broadcastRes] = await Promise.all([miscRequest, cameraRequest, broadcastRequest]);
      const json = await miscRes.json();
      const cameraJson = cameraRes ? await cameraRes.json() : null;
      const broadcastReqJson = broadcastRes ? await broadcastRes.json() : null;
      if (!json?.success) throw new Error(json?.error || 'Load failed');

      const donations = Array.isArray(json.donations) ? json.donations : [];
      const broadcasts = Array.isArray(json.broadcasts) ? json.broadcasts : [];
      const missions = Array.isArray(json.missions) ? json.missions : [];
      const withdraws = Array.isArray(json.withdraws) ? json.withdraws : [];
      const cameraVideos = Array.isArray(cameraJson?.rows) ? cameraJson.rows : [];
      const broadcastRequests = Array.isArray(broadcastReqJson?.rows) ? broadcastReqJson.rows : [];
      const totalDonationAmount = donations.reduce((sum, d) => sum + Number(d?.amount || 0), 0);
      const topDonorRow = donations.reduce((top, row) => {
        const topAmount = Number(top?.amount || 0);
        const rowAmount = Number(row?.amount || 0);
        return rowAmount > topAmount ? row : top;
      }, null);
      const totalWithdrawAmount = withdraws.reduce((sum, w) => sum + Number(w?.amount || 0), 0);
      const pendingWithdrawCount = withdraws.filter(w => String(w?.status || '').toLowerCase() === 'pending').length;

      if (donationsTotalAmount) donationsTotalAmount.textContent = `৳${totalDonationAmount.toFixed(2)}`;
      if (donationsTopDonor) {
        const topDonorName = String(topDonorRow?.donor_name || '').trim() || 'Anonymous';
        donationsTopDonor.textContent = topDonorName;
      }
      if (withdrawTotalAmount) withdrawTotalAmount.textContent = `৳${totalWithdrawAmount.toFixed(2)}`;
      if (withdrawPendingCount) withdrawPendingCount.textContent = String(pendingWithdrawCount);
      const now = new Date();
      const thisMonthCount = missions.filter(m => {
        const dt = new Date(m?.assigned_at || '');
        return !Number.isNaN(dt.getTime())
          && dt.getFullYear() === now.getFullYear()
          && dt.getMonth() === now.getMonth();
      }).length;

      if (volunteerTotalMissions) volunteerTotalMissions.textContent = String(missions.length);
      if (volunteerThisMonth) volunteerThisMonth.textContent = String(thisMonthCount);

      if (donationsBody) {
        if (!donations.length) {
          setNoData(donationsBody, 9, 'No donations found.');
        } else {
          donationsBody.innerHTML = donations.map(d => `
            <tr>
              <td>${esc(d.donor_name || 'Anonymous')}</td>
              <td>${esc(d.donor_email || 'N/A')}</td>
              <td>${esc(d.sender_mobile || '01XXXXXXXXX')}</td>
              <td>${esc(d.tx_id || 'N/A')}</td>
              <td>৳${esc(Number(d.amount || 0).toFixed(2))}</td>
              <td>${esc(fmtDate(d.date))}</td>
              <td>${Number(d.anonymous || 0) === 1 ? 'Yes' : 'No'}</td>
              <td>${esc(d.message || 'N/A')}</td>
              <td><button type="button" class="danger-btn" data-donation-report="1" data-donor-name="${esc(d.donor_name || 'Anonymous')}" data-donation-email="${esc(d.donor_email || '')}" data-donation-mobile="${esc(d.sender_mobile || '')}" data-donation-txid="${esc(d.tx_id || '')}" data-donation-amount="${esc(Number(d.amount || 0).toFixed(2))}" data-donation-date="${esc(d.date || '')}" data-donation-anon="${esc(Number(d.anonymous || 0))}" data-donation-message="${esc(d.message || '')}">Report</button></td>
            </tr>
          `).join('');

          donationsBody.querySelectorAll('[data-donation-report]').forEach(btn => {
            btn.addEventListener('click', () => {
              const donorName = String(btn.getAttribute('data-donor-name') || 'Anonymous');
              const donorEmail = String(btn.getAttribute('data-donation-email') || '').trim() || 'N/A';
              const donorMobile = String(btn.getAttribute('data-donation-mobile') || '').trim() || '01XXXXXXXXX';
              const donationTxId = String(btn.getAttribute('data-donation-txid') || '').trim() || 'N/A';
              const amount = String(btn.getAttribute('data-donation-amount') || '0.00');
              const dateText = String(btn.getAttribute('data-donation-date') || '');
              const anonymous = String(btn.getAttribute('data-donation-anon') || '0') === '1' ? 'Yes' : 'No';
              const message = String(btn.getAttribute('data-donation-message') || '').trim() || 'N/A';

              const popup = window.open('', '_blank', 'width=760,height=640');
              if (!popup) {
                alert('Please allow popups to view donation report.');
                return;
              }

              const safe = (v) => String(v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

              popup.document.write(`<!doctype html><html><head><title>Donation Report</title><style>body{font-family:Arial,sans-serif;padding:20px;color:#1f2937}h2{margin-top:0}table{border-collapse:collapse;width:100%;margin-top:12px}th,td{border:1px solid #d1d5db;padding:10px;text-align:left}th{background:#f3f4f6}</style></head><body><h2>Donation Report</h2><table><tr><th>Donor</th><td>${safe(donorName)}</td></tr><tr><th>Email</th><td>${safe(donorEmail)}</td></tr><tr><th>Mobile</th><td>${safe(donorMobile)}</td></tr><tr><th>TxID</th><td>${safe(donationTxId)}</td></tr><tr><th>Amount</th><td>৳${safe(amount)}</td></tr><tr><th>Date</th><td>${safe(fmtDate(dateText))}</td></tr><tr><th>Anonymous</th><td>${safe(anonymous)}</td></tr><tr><th>Message</th><td>${safe(message)}</td></tr></table><p style="margin-top:16px;color:#6b7280">Generated from SEARCHAR Admin Panel</p></body></html>`);
              popup.document.close();
            });
          });
        }
      }

      if (broadcastBody) {
        if (!broadcasts.length) {
          broadcastBody.innerHTML = '';
        } else {
          broadcastBody.innerHTML = broadcasts.map(b => `
            <tr>
              <td>${esc(b.title || 'Notice')}</td>
              <td>${esc(b.message || 'N/A')}</td>
              <td>All Areas</td>
              <td>${esc(b.recipient_entity || 'all')}</td>
              <td>${esc(fmtDate(b.created_at))}</td>
              <td><button type="button">Repeat</button></td>
            </tr>
          `).join('');
        }
      }

      if (missionsBody) {
        if (!missions.length) {
          setNoData(missionsBody, 9, 'No volunteer missions found.');
        } else {
          const sortedMissions = [...missions].sort((a, b) => {
            const aState = normalizeMissionState(a?.status, a?.response_status).lifeState;
            const bState = normalizeMissionState(b?.status, b?.response_status).lifeState;
            const aRejected = aState === 'rejected_busy';
            const bRejected = bState === 'rejected_busy';
            if (aRejected !== bRejected) return aRejected ? 1 : -1;

            const aTime = Date.parse(String(a?.assigned_at || ''));
            const bTime = Date.parse(String(b?.assigned_at || ''));
            const aSafe = Number.isNaN(aTime) ? 0 : aTime;
            const bSafe = Number.isNaN(bTime) ? 0 : bTime;
            return bSafe - aSafe;
          });

          const groupsMap = new Map();
          sortedMissions.forEach(m => {
            const key = String(m?.volunteer_id || '') || String(m?.volunteer_name || 'volunteer').toLowerCase();
            if (!groupsMap.has(key)) {
              groupsMap.set(key, {
                volunteer_name: m?.volunteer_name || 'Volunteer',
                profile_photo: m?.profile_photo || '',
                profile_photo_entity: m?.profile_photo_entity || '',
                volunteer_rank: m?.volunteer_rank || 'Junior',
                volunteer_points: Number(m?.volunteer_points || 0),
                missions: []
              });
            }
            const g = groupsMap.get(key);
            g.volunteer_points = Math.max(Number(g.volunteer_points || 0), Number(m?.volunteer_points || 0));
            g.volunteer_rank = m?.volunteer_rank || g.volunteer_rank;
            g.profile_photo = g.profile_photo || (m?.profile_photo || '');
            g.profile_photo_entity = g.profile_photo_entity || (m?.profile_photo_entity || '');
            g.missions.push(m);
          });

          const groupedRows = Array.from(groupsMap.values()).sort((a, b) => {
            const pointsDiff = Number(b?.volunteer_points || 0) - Number(a?.volunteer_points || 0);
            if (pointsDiff !== 0) return pointsDiff;
            const aLatest = Date.parse(String(a?.missions?.[0]?.assigned_at || ''));
            const bLatest = Date.parse(String(b?.missions?.[0]?.assigned_at || ''));
            const aSafe = Number.isNaN(aLatest) ? 0 : aLatest;
            const bSafe = Number.isNaN(bLatest) ? 0 : bLatest;
            return bSafe - aSafe;
          });

          missionsBody.innerHTML = groupedRows.map((g, groupIndex) => {
            const missionCount = Array.isArray(g.missions) ? g.missions.length : 0;
            const done = g.missions.filter(m => normalizeMissionState(m.status, m.response_status).lifeState === 'completed').length;
            const busy = g.missions.filter(m => normalizeMissionState(m.status, m.response_status).lifeState === 'rejected_busy').length;
            const pending = Math.max(0, missionCount - done - busy);
            const proofDone = g.missions.filter(m => String(m.proof_file || '').trim() !== '').length;
            const latest = g.missions[0] || {};
            const volunteerProfilePayload = encodeURIComponent(JSON.stringify({
              __title: 'Volunteer Mission Profile',
              __readOnly: true,
              __entity: 'volunteers',
              __id: latest?.volunteer_id || '',
              volunteer_id: latest?.volunteer_id || '',
              full_name: g?.volunteer_name || 'Volunteer',
              volunteer_name: g?.volunteer_name || 'Volunteer',
              profile_photo: g?.profile_photo || '',
              profile_photo_entity: g?.profile_photo_entity || '',
              volunteer_rank: g?.volunteer_rank || 'Junior',
              volunteer_points: Number(g?.volunteer_points || 0),
              total_missions: missionCount,
              pending_missions: pending,
              completed_missions: done,
              busy_missions: busy,
              proofs_submitted: `${proofDone}/${missionCount}`,
              latest_mission_title: latest?.mission_title || 'N/A',
              latest_mission_location: latest?.mission_location || 'N/A',
              last_assigned_at: latest?.assigned_at || ''
            }));

            const detailsHtml = g.missions.map((m, idx) => {
              const state = normalizeMissionState(m.status, m.response_status).lifeState;
              const hasProof = String(m.proof_file || '').trim() !== '';
              let actionHtml = '';
              let stateHtml = '';
              if (state === 'completed') actionHtml = '<span class="status-approved mission-badge">Done</span>';
              else if (state === 'rejected_busy') actionHtml = '<span class="status-rejected mission-badge">Busy</span>';
              else if (!hasProof) actionHtml = '<span class="status-pending mission-badge">Proof Required</span>';
              else actionHtml = `<button type="button" class="mission-compact-btn" data-mission-action="complete" data-mission-id="${esc(m.mission_id || '')}" data-case-ref="${esc(m.case_ref || '')}">Verify Proof & Give +20XP</button>`;

              if (state === 'completed') stateHtml = '<span class="status-approved mission-badge">Completed</span>';
              else if (state === 'rejected_busy') stateHtml = '<span class="status-rejected mission-badge">Rejected (Busy)</span>';
              else if (state === 'accepted') stateHtml = '<span class="status-pending mission-badge">Accepted</span>';
              else stateHtml = '<span class="status-pending mission-badge">Pending</span>';

              const caseText = String(m.case_ref || '').trim() ? `Case ${esc(m.case_ref)}` : `Mission #${esc(m.mission_id || '')}`;
              const rowClass = idx % 2 === 0 ? 'mission-detail-item mission-detail-odd' : 'mission-detail-item mission-detail-even';
              return `
                <div class="${rowClass}">
                  <div><strong>${esc(m.mission_title || 'Mission')}</strong><br><small class="mission-case">${caseText}</small></div>
                  <div>${esc(fmtDate(m.assigned_at))}</div>
                  <div>${esc(m.mission_location || 'N/A')}</div>
                  <div>${stateHtml}</div>
                  <div>${hasProof ? `<a href="${esc(missionProofUrl(m.proof_file))}" target="_blank" rel="noopener">View Proof</a>` : 'N/A'}</div>
                  <div>${actionHtml}</div>
                </div>
              `;
            }).join('');

            return `
              <tr>
                <td>
                  ${esc(g.volunteer_name || 'Volunteer')} <small>(${missionCount} missions)</small>
                  • <button type="button" class="view-profile-btn" data-volunteer-profile="${esc(volunteerProfilePayload)}">View Profile</button>
                </td>
                <td><strong>${esc(latest.mission_title || 'Mission')}</strong></td>
                <td>${esc(fmtDate(latest.assigned_at))}</td>
                <td>${esc(latest.mission_location || 'N/A')}</td>
                <td>${esc(g.volunteer_rank || 'Junior')}</td>
                <td>${esc(Number(g.volunteer_points || 0))}</td>
                <td>
                  <span class="status-pending mission-badge">Pending ${pending}</span>
                  <span class="status-approved mission-badge">Completed ${done}</span>
                  <span class="status-rejected mission-badge">Busy ${busy}</span>
                </td>
                <td>${proofDone}/${missionCount} proofs</td>
                <td><button type="button" class="ghost mission-toggle-btn" data-volunteer-toggle="${groupIndex}">View Missions</button></td>
              </tr>
              <tr data-volunteer-detail="${groupIndex}" class="mission-detail-row" style="display:none;">
                <td colspan="9" class="mission-detail-cell">
                  <div class="mission-detail-title">Mission Details</div>
                  <div class="mission-detail-head">
                    <div>Mission / Case</div><div>Assigned</div><div>Location</div><div>Status</div><div>Proof</div><div>Action</div>
                  </div>
                  ${detailsHtml || '<div>—</div>'}
                </td>
              </tr>
            `;
          }).join('');

          missionsBody.querySelectorAll('[data-volunteer-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
              const idx = String(btn.getAttribute('data-volunteer-toggle') || '');
              const detailRow = missionsBody.querySelector(`tr[data-volunteer-detail="${idx}"]`);
              if (!detailRow) return;
              const expanded = btn.getAttribute('data-expanded') === '1';
              if (expanded) {
                detailRow.style.display = 'none';
                btn.setAttribute('data-expanded', '0');
                btn.textContent = 'View Missions';
              } else {
                detailRow.style.display = 'table-row';
                btn.setAttribute('data-expanded', '1');
                btn.textContent = 'Hide Missions';
              }
            });
          });

          missionsBody.querySelectorAll('[data-volunteer-profile]').forEach((btn) => {
            btn.addEventListener('click', () => {
              try {
                const payload = JSON.parse(decodeURIComponent(String(btn.getAttribute('data-volunteer-profile') || '')));
                if (typeof window.openProfileModal === 'function') {
                  window.openProfileModal(payload, false);
                }
              } catch (error) {
                console.error('volunteer profile parse failed', error);
              }
            });
          });

          missionsBody.querySelectorAll('[data-mission-action="complete"]').forEach(btn => {
            btn.addEventListener('click', async () => {
              const missionId = Number(btn.getAttribute('data-mission-id') || 0);
              if (!missionId) return;
              btn.disabled = true;
              try {
                const res = await fetch('../Php/admin_update_mission_status.php', {
                  method: 'POST',
                  credentials: 'same-origin',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ mission_id: missionId, action: 'complete' })
                });
                const json = await res.json();
                if (!json?.success) throw new Error(json?.error || 'Failed');
                const caseRef = String(btn.getAttribute('data-case-ref') || '').trim();
                if (caseRef && typeof window.updateCrimeCaseStatusFromMission === 'function') {
                  window.updateCrimeCaseStatusFromMission(caseRef, 'closed');
                }
                await loadMiscSections();
              } catch (e) {
                btn.disabled = false;
                alert(e?.message || 'Could not mark mission complete.');
              }
            });
          });
        }
      }

      if (withdrawBody) {
        // Withdraw rendering handled by Admin.html (avoid double render)
      }

      if (cameraVideoBody) {
        if (!cameraVideos.length) {
          setNoData(cameraVideoBody, 5, 'No camera feeds found yet.');
        } else {
          cameraVideoBody.innerHTML = cameraVideos.map(row => {
            const videoUrl = cameraVideoUrl(row.video_path || row.video_url || row.live_url || '');
            const label = String(row.feed_label || (row.feed_type ? `${row.feed_type} feed` : 'Camera Feed'));
            const location = String(row.camera_location || '').trim()
              || [row.street, row.city, row.country].map(v => String(v || '').trim()).filter(Boolean).join(', ')
              || 'N/A';
            const cameraman = String(row.cameraman_name || 'Camera Contributor');
            const sentOn = fmtDate(row.created_at);
            const videoCell = videoUrl
              ? `<a href="${esc(videoUrl)}" target="_blank" rel="noopener">${esc(label)}</a>`
              : '<span>—</span>';
            const actionCell = videoUrl
              ? `<a href="${esc(videoUrl)}" target="_blank" rel="noopener">Open</a>`
              : '<span>—</span>';

            return `
              <tr>
                <td>${esc(cameraman)}</td>
                <td>${videoCell}</td>
                <td>${esc(location)}</td>
                <td>${esc(sentOn)}</td>
                <td>${actionCell}</td>
              </tr>
            `;
          }).join('');
        }
      }

      if (broadcastRequestBody) {
        renderBroadcastRequests(broadcastRequests);
      }
    } catch (error) {
      if (donationsBody) setNoData(donationsBody, 8, 'Failed to load donations.');
      if (broadcastBody) setNoData(broadcastBody, 6, 'Failed to load broadcast notifications.');
      if (missionsBody) setNoData(missionsBody, 9, 'Failed to load volunteer missions.');
      if (withdrawBody) setNoData(withdrawBody, 5, 'Failed to load withdrawals.');
      if (cameraVideoBody) setNoData(cameraVideoBody, 5, 'Failed to load camera videos.');
      if (broadcastRequestBody) setNoData(broadcastRequestBody, 5, 'Failed to load broadcast requests.');
      if (donationsTotalAmount) donationsTotalAmount.textContent = '৳0.00';
      if (donationsTopDonor) donationsTopDonor.textContent = 'N/A';
      if (withdrawTotalAmount) withdrawTotalAmount.textContent = '৳0.00';
      if (withdrawPendingCount) withdrawPendingCount.textContent = '0';
      if (volunteerTotalMissions) volunteerTotalMissions.textContent = '0';
      if (volunteerThisMonth) volunteerThisMonth.textContent = '0';
      console.error('misc section load failed', error);
    } finally {
      isLoadingMiscSections = false;
    }
  }

  loadMiscSections();

  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (!sectionId) return;
    if (miscSectionIds.includes(sectionId)) {
      loadMiscSections();
    }
  });

  document.addEventListener('admin:section-activated', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (miscSectionIds.includes(sectionId)) {
      loadMiscSections();
    }
  });

  document.addEventListener('click', (event) => {
    const actionBtn = event.target.closest('[data-broadcast-request-action]');
    if (!actionBtn) return;
    const action = String(actionBtn.getAttribute('data-broadcast-request-action') || '').toLowerCase();
    const requestId = Number(actionBtn.getAttribute('data-broadcast-request-id') || 0);
    if (!requestId || (action !== 'approve' && action !== 'reject')) return;

    let reasonText = '';
    if (action === 'reject') {
      const reasonPrompt = window.prompt('Write a reason for rejecting this request:');
      if (reasonPrompt === null) return;
      reasonText = String(reasonPrompt || '').trim();
      if (!reasonText) {
        alert('Please write a rejection reason.');
        return;
      }
    }

    actionBtn.disabled = true;
    const prevLabel = actionBtn.textContent;
    actionBtn.textContent = action === 'approve' ? 'Approving...' : 'Rejecting...';

    fetch('../Php/admin_update_broadcast_request.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ request_id: String(requestId), action, reason: reasonText })
    })
      .then(res => res.json())
      .then(json => {
        if (!json?.success) throw new Error(json?.error || 'Update failed');
        loadMiscSections();
      })
      .catch(error => {
        console.error('broadcast request update failed', error);
        actionBtn.disabled = false;
        actionBtn.textContent = prevLabel || 'Update';
        alert(error?.message || 'Could not update request.');
      });
  });
})();

// Dashboard pending action queue
(function () {
  const queueBody = document.getElementById('admin-action-queue-body');
  const summaryEl = document.getElementById('action-queue-summary');
  if (!queueBody) return;

  function esc(v) {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fmtDate(v) {
    if (!v) return 'N/A';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return esc(v);
    return d.toLocaleString();
  }

  function setSummary(summary) {
    if (!summaryEl) return;
    const postPending = Number(summary?.post_pending || 0);
    const missingPending = Number(summary?.missing_pending || 0);
    const withdrawPending = Number(summary?.withdraw_pending || 0);
    const missionPending = Number(summary?.mission_proof_pending || 0);
    const volunteerPending = Number(summary?.volunteer_pending || 0);
    const reportPending = Number(summary?.report_pending || 0);
    const broadcastPending = Number(summary?.broadcast_pending || 0);
    const chatLogTotal = Number(summary?.chat_log_total || 0);
    summaryEl.textContent = `Post ${postPending} • Volunteer ${volunteerPending} • Report ${reportPending} • Missing ${missingPending} • Withdraw ${withdrawPending} • Proof ${missionPending} • Broadcast ${broadcastPending} • Chat Log ${chatLogTotal}`;
  }

  function statusChip(statusText) {
    const status = String(statusText || '').toLowerCase();
    if (status.includes('verify')) return '<span class="status-pending">Needs verification</span>';
    if (status.includes('pending')) return '<span class="status-pending">Pending</span>';
    if (status.includes('open') || status.includes('active') || status.includes('search')) return `<span class="status active">${esc(statusText)}</span>`;
    return `<span class="status-pending">${esc(statusText || 'Pending')}</span>`;
  }

  function renderRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      queueBody.innerHTML = '<tr><td colspan="6">No pending approvals right now.</td></tr>';
      return;
    }

    queueBody.innerHTML = rows.slice(0, 40).map((row) => {
      const type = String(row?.type || 'Task').trim() || 'Task';
      const actor = String(row?.submitted_by || 'Unknown').trim() || 'Unknown';
      const role = String(row?.actor_role || '').trim();
      const itemRef = String(row?.item_ref || 'N/A').trim() || 'N/A';
      const itemLabel = String(row?.item_label || 'N/A').trim() || 'N/A';
      const section = String(row?.section || '').trim().toLowerCase();
      const searchKey = String(row?.search_key || '').trim();
      const actionHtml = `<button type="button" class="ghost" data-queue-open="1" data-queue-type="${esc(type)}" data-queue-section="${esc(section)}" data-queue-search="${esc(searchKey)}" data-queue-ref="${esc(itemRef)}">Open</button>`;

      return `
        <tr>
          <td>${esc(type)}</td>
          <td>${esc(actor)}${role ? ` <small>(${esc(role)})</small>` : ''}</td>
          <td><strong>${esc(itemRef)}</strong><br><small>${esc(itemLabel)}</small></td>
          <td>${statusChip(String(row?.status || 'Pending'))}</td>
          <td>${esc(fmtDate(row?.submitted_at || ''))}</td>
          <td>${actionHtml}</td>
        </tr>
      `;
    }).join('');
  }

  function jumpHighlight(element) {
    if (!element) return;
    element.classList.add('queue-jump-highlight');
    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => {
      element.classList.remove('queue-jump-highlight');
    }, 2200);
  }

  function openQueueTarget(section, searchKey, itemRef, queueType) {
    let targetSection = String(section || '').trim().toLowerCase();
    const normalizedType = String(queueType || '').trim().toLowerCase();

    if (normalizedType === 'volunteer application' && targetSection === 'volunteer') {
      targetSection = 'volunteer-approver';
    }

    if (!targetSection) return;

    if (typeof activateSection === 'function') {
      activateSection(targetSection);
    } else {
      const nav = document.querySelector(`.sidebar li[data-section="${targetSection}"]`);
      if (nav) nav.click();
    }

    setTimeout(() => {
      if (targetSection === 'post-control') {
        const keywordInput = document.getElementById('post-filter-keyword');
        if (keywordInput && searchKey) {
          keywordInput.value = searchKey;
          keywordInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (typeof applyPostControlFilters === 'function') {
          applyPostControlFilters();
        }
        const targetCell = Array.from(document.querySelectorAll('#post-control-body tr[data-id] td:first-child'))
          .find(td => (td.textContent || '').trim() === String(itemRef || '').trim());
        const targetRow = targetCell?.closest('tr');
        if (targetRow) {
          const detailRow = targetRow.closest('[data-post-group-detail]');
          if (detailRow && detailRow.style.display === 'none') {
            const detailKey = detailRow.getAttribute('data-post-group-detail');
            const toggleBtn = detailKey
              ? document.querySelector(`[data-post-group-toggle="${detailKey}"]`)
              : null;
            if (toggleBtn) toggleBtn.click();
          }
          setTimeout(() => jumpHighlight(targetRow), 100);
        }
        return;
      }

      if (targetSection === 'missing') {
        const row = Array.from(document.querySelectorAll('#missing-table-body tr'))
          .find(tr => {
            const first = tr.querySelector('td:first-child');
            return first && (first.textContent || '').trim() === String(itemRef || '').trim();
          });
        if (row) jumpHighlight(row);
        return;
      }

      if (targetSection === 'withdraw') {
        const input = document.getElementById('withdraw-filter-text');
        if (input && searchKey) {
          input.value = searchKey;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        const row = Array.from(document.querySelectorAll('#withdraw-table-body tr'))
          .find(tr => (tr.textContent || '').toLowerCase().includes(String(searchKey || '').toLowerCase()));
        if (row) jumpHighlight(row);
        return;
      }

      if (targetSection === 'volunteer') {
        const input = document.getElementById('volunteer-filter-text');
        if (input && searchKey) {
          input.value = searchKey;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        const row = Array.from(document.querySelectorAll('#volunteer-mission-body tr'))
          .find(tr => (tr.textContent || '').toLowerCase().includes(String(searchKey || '').toLowerCase()));
        if (row) jumpHighlight(row);
        return;
      }

      if (targetSection === 'reports') {
        const input = document.getElementById('reports-filter-text');
        if (input && searchKey) {
          input.value = searchKey;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        const row = Array.from(document.querySelectorAll('#reports-table-body tr'))
          .find(tr => {
            const first = tr.querySelector('td:first-child');
            if (first && (first.textContent || '').trim() === String(itemRef || '').trim()) return true;
            return (tr.textContent || '').toLowerCase().includes(String(searchKey || '').toLowerCase());
          });
        if (row) jumpHighlight(row);
        return;
      }

      if (targetSection === 'broadcast') {
        const input = document.getElementById('broadcast-filter-text');
        if (input && searchKey) {
          input.value = searchKey;
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        const row = Array.from(document.querySelectorAll('#broadcast-request-body tr'))
          .find(tr => (tr.textContent || '').toLowerCase().includes(String(searchKey || '').toLowerCase()));
        if (row) jumpHighlight(row);
      }
    }, 350);
  }

  let actionQueueBroken = false;

  async function loadActionQueue() {
    if (actionQueueBroken) return;
    try {
      const res = await fetch('../Php/admin_fetch_action_queue.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const raw = await res.text();
      const trimmed = String(raw || '').trim();
      if (trimmed.startsWith('<?php')) {
        actionQueueBroken = true;
        throw new Error('PHP source returned instead of JSON.');
      }

      const json = JSON.parse(trimmed || '{}');
      if (!json?.success) throw new Error(json?.error || 'Failed to load action queue');
      setSummary(json.summary || {});
      renderRows(Array.isArray(json.rows) ? json.rows : []);
    } catch (error) {
      console.error('action queue load failed', error);
      if (summaryEl) summaryEl.textContent = 'Load failed';
      if (actionQueueBroken) {
        queueBody.innerHTML = '<tr><td colspan="6">PHP endpoint is not executing. Run project through XAMPP/Apache localhost.</td></tr>';
      } else {
        queueBody.innerHTML = '<tr><td colspan="6">Failed to load pending actions.</td></tr>';
      }
    }
  }

  queueBody.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-queue-open]');
    if (!btn) return;
    openQueueTarget(
      btn.getAttribute('data-queue-section') || '',
      btn.getAttribute('data-queue-search') || '',
      btn.getAttribute('data-queue-ref') || '',
      btn.getAttribute('data-queue-type') || ''
    );
  });

  document.addEventListener('admin:section-activated', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'dashboard') {
      loadActionQueue();
    }
  });

  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (['post-control', 'missing', 'volunteer', 'volunteer-approver', 'withdraw', 'reports', 'dashboard'].includes(sectionId)) {
      loadActionQueue();
    }
  });

  loadActionQueue();
})();

// Generic table filters for various sections
(function () {
  function setupTextFilter(sectionId, filterId, resetId, rowSelector = 'tbody tr') {
    const section = document.getElementById(sectionId);
    if (!section) return;
    const table = section.querySelector('table');
    const input = document.getElementById(filterId);
    const reset = document.getElementById(resetId);
    if (!table || !input) return;

    const apply = () => {
      const q = input.value.trim().toLowerCase();
      const rows = Array.from(table.querySelectorAll(rowSelector));
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = q && !text.includes(q) ? 'none' : '';
      });
    };

    input.addEventListener('input', apply);
    if (reset) {
      reset.addEventListener('click', () => {
        input.value = '';
        apply();
        document.dispatchEvent(new CustomEvent('admin:refresh-section', {
          detail: { sectionId }
        }));
      });
    }
  }

  function setupTextStatusFilter(sectionId, textId, statusId, resetId, rowSelector = 'tbody tr', statusCellIndex = null) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    const table = section.querySelector('table');
    const textInput = document.getElementById(textId);
    const statusSelect = document.getElementById(statusId);
    const reset = document.getElementById(resetId);
    if (!table || !textInput || !statusSelect) return;

    const apply = () => {
      const q = textInput.value.trim().toLowerCase();
      const status = statusSelect.value.trim().toLowerCase();
      const rows = Array.from(table.querySelectorAll(rowSelector));

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        let statusText = '';
        if (statusCellIndex !== null) {
          const cell = row.querySelectorAll('td, th')[statusCellIndex];
          statusText = (cell?.innerText || '').trim().toLowerCase();
        } else {
          statusText = text;
        }
        const textOk = !q || text.includes(q);
        const statusOk = !status || statusText.includes(status);
        row.style.display = textOk && statusOk ? '' : 'none';
      });
    };

    textInput.addEventListener('input', apply);
    statusSelect.addEventListener('change', apply);
    if (reset) {
      reset.addEventListener('click', () => {
        textInput.value = '';
        statusSelect.value = '';
        apply();
        document.dispatchEvent(new CustomEvent('admin:refresh-section', {
          detail: { sectionId }
        }));
      });
    }
  }

  // Donations: text filter
  setupTextFilter('donations', 'donations-filter-text', 'donations-filter-reset');

  // Broadcast: text filter (apply to both broadcast + camera video tables)
  (function () {
    const section = document.getElementById('broadcast');
    if (!section) return;
    const input = document.getElementById('broadcast-filter-text');
    const reset = document.getElementById('broadcast-filter-reset');
    const broadcastBody = document.getElementById('broadcast-table-body');
    const cameraBody = document.getElementById('camera-video-table-body');
    const requestBody = document.getElementById('broadcast-request-body');
    if (!input) return;

    const apply = () => {
      const q = input.value.trim().toLowerCase();
      const rows = [];
      if (broadcastBody) rows.push(...Array.from(broadcastBody.querySelectorAll('tr')));
      if (cameraBody) rows.push(...Array.from(cameraBody.querySelectorAll('tr')));
      if (requestBody) rows.push(...Array.from(requestBody.querySelectorAll('tr')));

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = q && !text.includes(q) ? 'none' : '';
      });
    };

    input.addEventListener('input', apply);
    if (reset) {
      reset.addEventListener('click', () => {
        input.value = '';
        apply();
        document.dispatchEvent(new CustomEvent('admin:refresh-section', {
          detail: { sectionId: 'broadcast' }
        }));
      });
    }
  })();

  // Volunteer: text + status (status col index 6)
  setupTextStatusFilter('volunteer', 'volunteer-filter-text', 'volunteer-filter-status', 'volunteer-filter-reset', 'tbody tr', 6);

  // Withdraw: text + status (status col index 2)
  setupTextStatusFilter('withdraw', 'withdraw-filter-text', 'withdraw-filter-status', 'withdraw-filter-reset', 'tbody tr', 2);

  // Review: text + status (status col index 4)
  setupTextStatusFilter('review', 'review-filter-text', 'review-filter-status', 'review-filter-reset', 'tbody tr', 4);

  // Reports: text filter
  setupTextFilter('reports', 'reports-filter-text', 'reports-filter-reset');
})();

// Remove redundant crime assign modal block (logic moved inside crime module)

(function () {
  const section = document.getElementById('reports');
  if (!section) return;

  const tableBody = document.getElementById('reports-table-body');
  if (!tableBody) return;
  let reportsLoadInFlight = false;

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function titleCase(value) {
    const txt = String(value || '').trim();
    if (!txt) return 'N/A';
    return txt.charAt(0).toUpperCase() + txt.slice(1);
  }

  function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return esc(String(value));
    return date.toISOString().slice(0, 10);
  }

  function mediaBadge(row) {
    const source = String(row?.report_source || 'post').toLowerCase();
    if (source === 'comment') return 'Comment/Reply';

    const type = String(row?.media_type || row?.post_media_type || '').toLowerCase();
    const mediaJson = String(row?.media_json || '').trim();
    const mediaPath = String(row?.media_path || '').trim();

    if (mediaJson) {
      try {
        const arr = JSON.parse(mediaJson);
        if (Array.isArray(arr) && arr.length > 0) {
          return `${arr.length} image${arr.length > 1 ? 's' : ''}`;
        }
      } catch (_e) { }
    }

    if (type === 'video' && mediaPath) return 'Video';
    if (type === 'image' && mediaPath) return 'Image';
    if (mediaPath) return 'Media';
    return 'No media';
  }

  function statusBadge(statusRaw) {
    const status = String(statusRaw || 'pending').toLowerCase();
    if (status === 'resolved') return '<span class="status-approved">Resolved</span>';
    if (status === 'under_review') return '<span class="status-pending">Under Review</span>';
    if (status === 'dismissed') return '<span class="status-rejected">Dismissed</span>';
    return '<span class="status-pending">Pending</span>';
  }

  function toProfilePayload(row) {
    const mediaPathRaw = String(row?.media_path || '').trim();
    const mediaPath = mediaPathRaw && !/^https?:\/\//i.test(mediaPathRaw) && !mediaPathRaw.startsWith('../')
      ? `../${mediaPathRaw.replace(/^\/+/, '')}`
      : mediaPathRaw;

    return {
      __title: 'Post Report Details',
      report_source: row?.report_source || 'post',
      report_category: row?.report_category || '',
      report_details: row?.report_details || '',
      report_submitted_at: row?.report_created_at || '',
      reporter_name: row?.reporter_name || '',
      reporter_role: row?.reporter_role || '',
      reported_user_name: row?.reported_name || row?.post_author_name || '',
      reported_user_role: row?.reported_role || row?.post_author_role || '',
      target_preview_text: row?.target_preview_text || '',
      post_category: row?.post_category || '',
      post_text: row?.post_text || '',
      media_path: mediaPath,
      media_json: row?.media_json || '',
      media_type: row?.media_type || '',
      admin_action_note: row?.admin_action_note || ''
    };
  }

  function renderRows(rows) {
    if (!Array.isArray(rows) || rows.length === 0) {
      tableBody.innerHTML = '<tr><td colspan="7">No post reports found.</td></tr>';
      return;
    }

    tableBody.innerHTML = rows.map(row => {
      const status = String(row?.status || 'pending').toLowerCase();
      const source = String(row?.report_source || 'post').toLowerCase();
      const reportId = Number(row?.report_id || 0);
      const postId = Number(row?.post_id || 0);
      const reportCode = reportId > 0 ? `PR-${String(reportId).padStart(4, '0')}` : 'N/A';
      const postCode = postId > 0 ? `PT-${String(postId).padStart(4, '0')}` : 'N/A';
      const profilePayload = encodeURIComponent(JSON.stringify(toProfilePayload(row)));
      const canReview = status === 'pending';
      const canResolve = status === 'pending' || status === 'under_review';

      return `
        <tr data-report-id="${esc(reportId)}" data-report-source="${esc(source)}" data-report-status="${esc(status)}" data-report-payload="${profilePayload}">
          <td>${esc(reportCode)}</td>
          <td>${esc(source === 'comment' ? (postCode + ' • Comment') : (postCode + ' • ' + mediaBadge(row)))}</td>
          <td>${esc(row?.reporter_name || 'Unknown')}</td>
          <td>${esc(row?.reported_name || row?.post_author_name || 'Unknown')}</td>
          <td>${esc(row?.report_category || 'Other')}</td>
          <td>${statusBadge(status)}</td>
          <td>${esc(formatDate(row?.report_created_at || ''))}</td>
          <td>
            <button type="button" class="view-profile-btn" data-report-view="1">View Details</button>
            <button type="button" class="ghost" data-report-action="mark_reviewed" ${canReview ? '' : 'disabled'}>Review</button>
            <button type="button" class="approve-btn" data-report-action="resolve" ${canResolve ? '' : 'disabled'}>Resolve</button>
          </td>
        </tr>
      `;
    }).join('');
  }

  function applyReportsFilterIfNeeded() {
    const input = document.getElementById('reports-filter-text');
    if (!input) return;
    const activeQuery = String(input.value || '').trim();
    if (!activeQuery) return;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  async function loadReports(options = {}) {
    const silent = Boolean(options?.silent);
    if (reportsLoadInFlight) return;
    reportsLoadInFlight = true;

    if (!silent) {
      tableBody.innerHTML = '<tr><td colspan="7">Loading post reports...</td></tr>';
    }

    try {
      const res = await fetch('../Php/admin_fetch_post_reports.php', {
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.error || 'Failed to load reports');
      renderRows(Array.isArray(json.rows) ? json.rows : []);
      applyReportsFilterIfNeeded();
    } catch (error) {
      console.error('admin post reports load failed', error);
      if (!silent) {
        tableBody.innerHTML = '<tr><td colspan="7">Failed to load post reports.</td></tr>';
      }
    } finally {
      reportsLoadInFlight = false;
    }
  }

  async function updateReportStatus(reportId, reportSource, action, button) {
    const prevText = button?.textContent || '';
    if (button) {
      button.disabled = true;
      button.textContent = action === 'resolve' ? 'Resolving…' : 'Updating…';
    }

    try {
      const res = await fetch('../Php/admin_update_post_report_status.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, report_source: reportSource, action })
      });
      const json = await res.json();
      if (!res.ok || !json?.success) throw new Error(json?.error || 'Update failed');
      await loadReports();
    } catch (error) {
      console.error('update post report status failed', error);
      alert(error?.message || 'Could not update report status.');
      if (button) {
        button.disabled = false;
        button.textContent = prevText;
      }
    }
  }

  section.addEventListener('click', function (event) {
    const viewBtn = event.target.closest('[data-report-view]');
    if (viewBtn) {
      const row = viewBtn.closest('tr[data-report-payload]');
      if (!row) return;

      try {
        const payload = JSON.parse(decodeURIComponent(String(row.dataset.reportPayload || '')));
        if (typeof window.openProfileModal === 'function') {
          window.openProfileModal(payload, false);
        }
      } catch (error) {
        console.error('invalid report payload', error);
      }
      return;
    }

    const actionBtn = event.target.closest('[data-report-action]');
    if (!actionBtn) return;

    const row = actionBtn.closest('tr[data-report-id]');
    if (!row) return;

    const reportId = Number(row.dataset.reportId || 0);
    const reportSource = String(row.dataset.reportSource || 'post').toLowerCase();
    const action = String(actionBtn.getAttribute('data-report-action') || '');
    if (!reportId || !action) return;

    updateReportStatus(reportId, reportSource, action, actionBtn);
  });

  document.addEventListener('admin:refresh-section', function (event) {
    const sectionId = String(event?.detail?.sectionId || '');
    if (sectionId === 'reports') {
      loadReports();
    }
  });

  document.addEventListener('admin:refresh-section', function (event) {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'chat-management' && window.SearcharChat && typeof window.SearcharChat.refresh === 'function') {
      window.SearcharChat.refresh().catch(() => { });
    }
  });

  loadReports();

  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'reports') {
      loadReports();
    }
  });
})();

// Rescue Stories / Reviews Module
(function () {
  const section = document.getElementById('review');
  if (!section) return;
  const tableBody = document.getElementById('review-table-body');
  if (!tableBody) return;

  function statusBadge(status) {
    status = String(status || 'pending').toLowerCase();
    if (status === 'approved') return '<span class="status-approved">Approved</span>';
    if (status === 'rejected') return '<span class="status-rejected">Rejected</span>';
    return '<span class="status-pending">Pending</span>';
  }

  function esc(val) {
    return String(val || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  async function loadRescueStories() {
    tableBody.innerHTML = '<tr><td colspan="7">Loading reviews...</td></tr>';
    try {
      const res = await fetch('../Php/admin_fetch_rescue_stories.php', { cache: 'no-store' });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || 'Failed to fetch reviews');

      const rows = data.data || [];
      if (rows.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7">No rescue stories found.</td></tr>';
        return;
      }

      tableBody.innerHTML = rows.map(r => `
        <tr data-story-id="${esc(r.story_id)}">
          <td>RS-${esc(r.story_id)}</td>
          <td>${esc(r.author_name)}</td>
          <td>${esc(r.author_role)}</td>
          <td style="max-width: 300px; white-space: normal;">${esc(r.story_text)}</td>
          <td class="status-cell">${statusBadge(r.status)}</td>
          <td>${esc(r.created_at)}</td>
          <td>
            ${r.status === 'pending' ? `
            <button class="approve-btn" onclick="updateStoryStatus(${r.story_id}, 'approve', this)" style="margin-right:5px;">Approve</button>
            <button class="reject-btn" onclick="updateStoryStatus(${r.story_id}, 'reject', this)">Reject</button>
            ` : `<button class="ghost" disabled>Actioned</button>`}
          </td>
        </tr>
      `).join('');
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="7" style="color:red;">Error: ${esc(err.message)}</td></tr>`;
    }
  }

  window.updateStoryStatus = async function (storyId, action, btn) {
    if (!confirm(`Are you sure you want to ${action} this story?`)) return;
    const originalText = btn.innerText;
    btn.innerText = 'Wait...';
    btn.disabled = true;
    try {
      const res = await fetch('../Php/admin_update_story_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ story_id: storyId, action })
      });
      const data = await res.json();
      if (data.success) {
        alert('Story status updated!');
        loadRescueStories();
      } else {
        throw new Error(data.error || 'Failed to update status');
      }
    } catch (err) {
      alert('Error: ' + err.message);
      btn.innerText = originalText;
      btn.disabled = false;
    }
  };

  document.addEventListener('admin:refresh-section', (event) => {
    if (String(event?.detail?.sectionId || '').toLowerCase() === 'review') {
      loadRescueStories();
    }
  });

  loadRescueStories();
})();

// AI detection logs are static demo content, so refresh restores the section markup.
(function () {
  const section = document.getElementById('ai');
  const tableBody = document.getElementById('ai-table-body');
  if (!section || !tableBody) return;

  const initialRows = tableBody.innerHTML;
  document.addEventListener('admin:refresh-section', (event) => {
    const sectionId = String(event?.detail?.sectionId || '').toLowerCase();
    if (sectionId === 'ai') {
      tableBody.innerHTML = initialRows;
    }
  });
})();

// Donations export: download current table as CSV
(function () {
  const section = document.getElementById('donations');
  if (!section) return;
  const table = section.querySelector('table');
  const exportBtn = section.querySelector('.btn-export-donations');
  if (!table || !exportBtn) return;

  function toCsvValue(text) {
    const safe = String(text ?? '').replace(/"/g, '""');
    return `"${safe}"`;
  }

  function exportTable() {
    const rows = Array.from(table.querySelectorAll('tr'));

    // Find index of "Action" or "Actions" column
    const headerCells = Array.from(table.querySelectorAll('thead th'));
    const actionColIndex = headerCells.findIndex(th => th.innerText.trim().match(/^(Action|Actions)$/i));

    const csv = rows.map(row => {
      const cells = Array.from(row.querySelectorAll('th, td'))
        .filter((_, idx) => idx !== actionColIndex)
        .map(cell => toCsvValue(cell.innerText.trim()));
      return cells.join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `donations_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }

  function inferFileNameFromHeader(contentDisposition) {
    const raw = String(contentDisposition || '');
    const match = raw.match(/filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i);
    const encoded = match?.[1] || match?.[2] || '';
    if (!encoded) return '';
    try {
      return decodeURIComponent(encoded);
    } catch (_) {
      return encoded;
    }
  }

  async function exportDonationsReport() {
    const originalText = exportBtn.innerHTML;
    exportBtn.disabled = true;
    exportBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Exporting...';

    try {
      const res = await fetch('../Php/admin_donations_report.php', {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store'
      });

      if (!res.ok) {
        throw new Error('Export endpoint failed');
      }

      const blob = await res.blob();
      const fileName = inferFileNameFromHeader(res.headers.get('content-disposition'))
        || `donations_report_${new Date().toISOString().slice(0, 10)}.csv`;

      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    } catch (error) {
      console.warn('Backend donation export failed, falling back to table export.', error);
      exportTable();
    } finally {
      exportBtn.disabled = false;
      exportBtn.innerHTML = originalText;
    }
  }

  exportBtn.addEventListener('click', exportDonationsReport);
})();

// Withdraw export: download current table as CSV
(function () {
  const section = document.getElementById('withdraw');
  if (!section) return;

  const exportBtn = section.querySelector('.btn-export-report');
  const table = section.querySelector('table');
  if (!exportBtn || !table) return;

  function toCsvValue(text) {
    const safe = String(text ?? '').replace(/"/g, '""');
    return `"${safe}"`;
  }

  function exportTable() {
    const rows = Array.from(table.querySelectorAll('tr'));

    // Find index of "Action" or "Actions" column
    const headerCells = Array.from(table.querySelectorAll('thead th'));
    const actionColIndex = headerCells.findIndex(th => th.innerText.trim().match(/^(Action|Actions)$/i));

    const csv = rows.map(row => {
      const cells = Array.from(row.querySelectorAll('th, td'))
        .filter((_, idx) => idx !== actionColIndex)
        .map(cell => toCsvValue(cell.innerText.trim()));
      return cells.join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `withdrawals_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }

  exportBtn.addEventListener('click', exportTable);
})();

// Chatbot logs monitor for admin (backend-first with local fallback)
(function () {
  const CHATBOT_LOG_KEY = 'searchar_chatbot_logs_v1';
  const DEFAULT_QUICK_ADMIN_COMMENTS = [
    'Thanks for your message. Our team is checking now.',
    'Your report has been received and forwarded to the support team.',
    'Please share location and time details for faster action.',
    'We could not verify this yet. Please provide a clear photo or reference.',
    'This issue has been noted and marked for follow-up.',
    'In an emergency, please call 999 immediately.'
  ];
  const section = document.getElementById('chatbot-logs');
  const body = document.getElementById('chatbot-logs-body');
  const filterInput = document.getElementById('chatbot-log-filter');
  const refreshBtn = document.getElementById('chatbot-log-refresh');
  const clearBtn = document.getElementById('chatbot-log-clear');
  const templateInput = document.getElementById('chatbot-template-input');
  const templateAddBtn = document.getElementById('chatbot-template-add');
  const templateList = document.getElementById('chatbot-template-list');
  if (!section || !body) return;

  let cachedRows = [];
  let pauseAutoRefreshUntil = 0;
  let isAddingTemplate = false;
  let quickCommentTemplates = DEFAULT_QUICK_ADMIN_COMMENTS.map((text, index) => ({
    id: `default-${index + 1}`,
    comment_text: text
  }));

  function esc(v) {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function readLocalLogs() {
    try {
      const raw = localStorage.getItem(CHATBOT_LOG_KEY) || '[]';
      const arr = JSON.parse(raw);
      return Array.isArray(arr) ? arr : [];
    } catch (_e) {
      return [];
    }
  }

  function formatTime(iso) {
    if (!iso) return 'N/A';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return esc(iso);
    return d.toLocaleString();
  }

  function normalizeTemplateRows(rows) {
    if (!Array.isArray(rows)) return [];
    return rows
      .map((item) => {
        const id = String(item?.id ?? '').trim();
        const comment = String(item?.comment_text ?? '').trim();
        if (!comment) return null;
        return { id, comment_text: comment };
      })
      .filter(Boolean);
  }

  function renderTemplateList() {
    if (!templateList) return;
    if (!quickCommentTemplates.length) {
      templateList.innerHTML = '<span class="chatbot-template-pill">No comments available</span>';
      return;
    }

    templateList.innerHTML = quickCommentTemplates.map((item) => {
      const isDbRow = /^\d+$/.test(String(item.id));
      const deleteBtn = isDbRow
        ? `<button type="button" class="chatbot-template-delete" data-template-delete="${esc(item.id)}" title="Delete">x</button>`
        : '';
      return `<span class="chatbot-template-pill">${esc(item.comment_text)} ${deleteBtn}</span>`;
    }).join('');
  }

  function renderQuickCommentOptions() {
    const placeholder = '<option value="">Select a quick comment</option>';
    const options = quickCommentTemplates
      .map((item) => `<option value="${esc(item.comment_text)}">${esc(item.comment_text)}</option>`)
      .join('');
    return `${placeholder}${options}`;
  }

  async function fetchTemplateRowsFromServer() {
    const res = await fetch('../Php/chatbot_comment_templates.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json || json.success !== true || !Array.isArray(json.data)) {
      throw new Error(json?.error || 'template read failed');
    }
    return json.data;
  }

  async function refreshCommentTemplates() {
    try {
      const rows = await fetchTemplateRowsFromServer();
      const normalized = normalizeTemplateRows(rows);
      if (normalized.length) {
        quickCommentTemplates = normalized;
      } else {
        quickCommentTemplates = DEFAULT_QUICK_ADMIN_COMMENTS.map((text, index) => ({
          id: `default-${index + 1}`,
          comment_text: text
        }));
      }
    } catch (_e) {
      quickCommentTemplates = DEFAULT_QUICK_ADMIN_COMMENTS.map((text, index) => ({
        id: `default-${index + 1}`,
        comment_text: text
      }));
    }

    renderTemplateList();
    renderRows(cachedRows);
  }

  async function addTemplateToServer(commentText) {
    const res = await fetch('../Php/chatbot_comment_templates.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'add', comment_text: commentText })
    });
    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success !== true) {
      throw new Error(json?.error || `template add failed (HTTP ${res.status})`);
    }
    return json;
  }

  async function deleteTemplateFromServer(templateId) {
    const res = await fetch('../Php/chatbot_comment_templates.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'delete', id: templateId })
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json || json.success !== true) {
      throw new Error(json?.error || 'template delete failed');
    }
  }

  function renderRows(rows) {
    const q = String(filterInput?.value || '').trim().toLowerCase();
    const filtered = rows.filter((row) => {
      if (!q) return true;
      const hay = `${row?.question || ''} ${row?.reply || ''}`.toLowerCase();
      return hay.includes(q);
    });

    if (!filtered.length) {
      body.innerHTML = '<tr><td colspan="4">No chatbot logs found.</td></tr>';
      return;
    }

    body.innerHTML = filtered.map((row) => `
      ${(() => {
        const token = String(row.session_token || '').trim();
        const disabled = token ? '' : 'disabled';
        const btnLabel = token ? 'Send' : 'Unavailable';
        return `
      <tr>
        <td>${esc(formatTime(row.time))}</td>
        <td>${esc(row.question || '')}</td>
        <td>${esc(row.reply || '')}</td>
        <td>
          <div class="chatbot-admin-reply-wrap">
            <select class="chatbot-admin-reply-select" data-chatbot-reply-select="${esc(token)}" ${disabled}>
              ${renderQuickCommentOptions()}
            </select>
            <button type="button" class="chatbot-admin-reply-send" data-chatbot-reply-send="${esc(token)}" ${disabled}>${btnLabel}</button>
          </div>
        </td>
      </tr>
      `;
      })()}
    `).join('');
  }

  async function fetchServerLogs() {
    const res = await fetch('../Php/chatbot_log_read.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    if (!json || json.success !== true || !Array.isArray(json.data)) {
      throw new Error('Invalid chatbot log response');
    }
    return json.data;
  }

  async function refreshLogs() {
    try {
      const rows = await fetchServerLogs();
      cachedRows = rows.slice().reverse();
      renderRows(cachedRows);
    } catch (_e) {
      // Fallback for environments where backend endpoint is unavailable.
      cachedRows = readLocalLogs().slice().reverse();
      renderRows(cachedRows);
    }
  }

  function isSectionActive() {
    return section.classList.contains('active');
  }

  function isAutoRefreshPaused() {
    return Date.now() < pauseAutoRefreshUntil;
  }

  function pauseAutoRefresh(ms = 10000) {
    pauseAutoRefreshUntil = Date.now() + ms;
  }

  if (filterInput) {
    filterInput.addEventListener('input', function () {
      renderRows(cachedRows);
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      refreshLogs();
      refreshCommentTemplates();
    });
  }

  if (templateAddBtn) {
    templateAddBtn.addEventListener('click', async function () {
      if (isAddingTemplate) return;
      pauseAutoRefresh(10000);
      const text = String(templateInput?.value || '').trim();
      if (!text) {
        alert('Please type a comment first.');
        return;
      }

      isAddingTemplate = true;
      templateAddBtn.disabled = true;
      const prevLabel = templateAddBtn.textContent;
      templateAddBtn.textContent = 'Adding...';

      try {
        const result = await addTemplateToServer(text);
        if (templateInput) templateInput.value = '';
        await refreshCommentTemplates();
        if (result && result.message === 'duplicate') {
          alert('This comment already exists in dropdown.');
        }
      } catch (_e) {
        alert(`Could not add comment: ${_e?.message || 'unknown error'}`);
      } finally {
        isAddingTemplate = false;
        templateAddBtn.disabled = false;
        templateAddBtn.textContent = prevLabel;
      }
    });
  }

  if (templateInput) {
    templateInput.addEventListener('focus', function () {
      pauseAutoRefresh(15000);
    });
    templateInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        templateAddBtn?.click();
      }
    });
  }

  if (templateList) {
    templateList.addEventListener('click', async function (event) {
      const deleteBtn = event.target.closest('[data-template-delete]');
      if (!deleteBtn) return;

      const templateId = String(deleteBtn.getAttribute('data-template-delete') || '').trim();
      if (!templateId) return;
      const ok = window.confirm('Delete this dropdown comment?');
      if (!ok) return;

      try {
        await deleteTemplateFromServer(templateId);
        await refreshCommentTemplates();
      } catch (_e) {
        alert(`Could not delete comment: ${_e?.message || 'unknown error'}`);
      }
    });
  }

  body.addEventListener('click', async function (event) {
    const sendBtn = event.target.closest('[data-chatbot-reply-send]');
    if (!sendBtn) return;

    pauseAutoRefresh(15000);

    const token = String(sendBtn.getAttribute('data-chatbot-reply-send') || '').trim();
    if (!token) {
      alert('Missing user session token for this row.');
      return;
    }

    const rowEl = sendBtn.closest('tr');
    const selectEl = rowEl ? rowEl.querySelector('.chatbot-admin-reply-select') : null;
    const text = String(selectEl?.value || '').trim();
    if (!text) {
      alert('Please select a comment from dropdown first.');
      return;
    }

    sendBtn.disabled = true;
    const prevLabel = sendBtn.textContent;
    sendBtn.textContent = 'Sending...';

    try {
      const res = await fetch('../Php/chatbot_admin_reply_write.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
          session_token: token,
          reply_text: text
        })
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.error || 'Reply send failed');
      }

      if (selectEl) selectEl.value = '';
      sendBtn.textContent = 'Sent';
      setTimeout(() => {
        sendBtn.textContent = prevLabel;
        sendBtn.disabled = false;
      }, 800);
    } catch (_e) {
      sendBtn.textContent = prevLabel;
      sendBtn.disabled = false;
      alert(`Could not send admin reply: ${_e?.message || 'unknown error'}`);
    }
  });

  if (clearBtn) {
    clearBtn.addEventListener('click', async function () {
      const ok = window.confirm('Clear all chatbot logs?');
      if (!ok) return;

      try {
        await fetch('../Php/chatbot_log_write.php?clear=1', {
          method: 'POST',
          credentials: 'same-origin'
        });
      } catch (_e) {
        // Ignore and continue with local clear.
      }

      localStorage.removeItem(CHATBOT_LOG_KEY);
      cachedRows = [];
      renderRows(cachedRows);
    });
  }

  document.addEventListener('admin:refresh-section', function (event) {
    if (String(event?.detail?.sectionId || '') === 'chatbot-logs') {
      refreshLogs();
      refreshCommentTemplates();
    }
  });

  // If logs change in another tab (Index page), refresh instantly.
  window.addEventListener('storage', function (event) {
    if (event.key === CHATBOT_LOG_KEY && isSectionActive()) {
      refreshLogs();
    }
  });

  body.addEventListener('focusin', function (event) {
    if (event.target.closest('.chatbot-admin-reply-wrap')) {
      pauseAutoRefresh(15000);
    }
  });

  body.addEventListener('change', function (event) {
    if (event.target.matches('.chatbot-admin-reply-select')) {
      pauseAutoRefresh(15000);
    }
  });

  refreshCommentTemplates();
  refreshLogs();
})();

(function () {
  const launcher = document.getElementById('chat-management-launcher');
  const drawer = document.getElementById('chat-management-drawer');
  const closeBtn = document.getElementById('chat-management-close');
  const input = document.getElementById('chat-management-input');

  if (!launcher || !drawer || !closeBtn) return;

  function setDrawerOpen(isOpen) {
    drawer.classList.toggle('is-open', isOpen);
    drawer.setAttribute('aria-hidden', String(!isOpen));
    launcher.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
      setTimeout(() => input?.focus(), 220);
    } else {
      launcher.focus();
    }
  }

  launcher.addEventListener('click', function () {
    setDrawerOpen(!drawer.classList.contains('is-open'));
  });

  closeBtn.addEventListener('click', function () {
    setDrawerOpen(false);
  });

  drawer.addEventListener('click', function (event) {
    if (event.target === drawer) {
      setDrawerOpen(false);
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
      setDrawerOpen(false);
    }
  });
})();

(function initAdminDbChat() {
  const list = document.getElementById('chat-management-list');
  const feed = document.getElementById('chat-management-feed');
  const title = document.getElementById('chat-management-title');
  const subtitle = document.getElementById('chat-management-subtitle');
  const headerAvatar = document.querySelector('.admin-messenger-thread-user img');
  const input = document.getElementById('chat-management-input');
  const sendBtn = document.getElementById('chat-management-send');
  const searchInput = document.getElementById('chat-management-search');
  const roleFilter = document.getElementById('chat-management-role-filter');
  const tabs = Array.from(document.querySelectorAll('.admin-messenger-tabs button'));

  if (!list || !feed || !title || !subtitle || !input || !sendBtn) return;

  let selected = null;
  let conversations = [];
  let refreshTimer = null;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[ch]));
  }

  function shortTime(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    const diff = Date.now() - date.getTime();
    if (diff < 60000) return 'now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h`;
    return `${Math.floor(diff / 86400000)}d`;
  }

  function selectedKey(item) {
    return `${item.participant_role}:${item.participant_id}`;
  }

  function renderConversations() {
    if (!conversations.length) {
      list.innerHTML = '<div class="admin-messenger-empty">No chat messages yet.</div>';
      return;
    }

    list.innerHTML = conversations.map(item => {
      const active = selected && selectedKey(selected) === selectedKey(item) ? ' active' : '';
      const unread = Number(item.unread_count || 0);
      const badge = unread > 0 ? `<b class="admin-messenger-unread">${unread}</b>` : '';
      return `
        <button type="button" class="admin-messenger-contact${active}" data-chat-role="${escapeHtml(item.participant_role)}" data-chat-id="${Number(item.participant_id)}" data-chat-avatar="${escapeHtml(item.profile_photo || '../Images/default-profile.gif')}">
          <img src="${escapeHtml(item.profile_photo || '../Images/default-profile.gif')}" alt="${escapeHtml(item.participant_name)}">
          <span>
            <strong>${escapeHtml(item.participant_name || 'Unknown')}</strong>
            <small>${escapeHtml(item.participant_label)} • ${escapeHtml(item.last_message || '')} · ${shortTime(item.last_at)}</small>
          </span>
          ${badge}
        </button>
      `;
    }).join('');
  }

  function renderMessages(messages) {
    if (!messages.length) {
      feed.innerHTML = '<div class="admin-messenger-date">No messages yet</div>';
      return;
    }

    feed.innerHTML = messages.map(message => {
      const senderRole = String(message.sender_role || '').toLowerCase();
      const mine = senderRole === 'admin' || Boolean(message.is_mine);
      const participantAvatar = selected?.profile_photo || '../Images/default-profile.gif';
      const avatar = mine ? '' : `<img src="${escapeHtml(participantAvatar)}" alt="">`;
      const senderLabel = mine ? 'You' : (selected?.participant_label || 'Participant');
      const sentAt = shortTime(message.created_at);
      return `
        <div class="admin-message-row ${mine ? 'outgoing' : 'incoming'}">
          ${avatar}
          <div class="admin-message-stack">
            <span class="admin-message-sender">${escapeHtml(senderLabel)}</span>
            <p>${escapeHtml(message.message_text)}</p>
            <small class="admin-message-time">${escapeHtml(sentAt)}</small>
          </div>
        </div>
      `;
    }).join('');
    feed.scrollTop = feed.scrollHeight;
  }

  async function fetchJson(url, options) {
    const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json.success) throw new Error(json.error || 'Chat request failed');
    return json;
  }

  async function loadConversations(keepSelection = true, refreshMessages = true) {
    const params = new URLSearchParams();
    const role = roleFilter ? roleFilter.value : 'all';
    const search = searchInput ? searchInput.value.trim() : '';
    if (role && role !== 'all') params.set('role', role);
    if (search) params.set('search', search);

    try {
      params.set('as_admin', '1');
      const json = await fetchJson(`../Php/admin_chat_conversations.php?${params.toString()}`);
      conversations = Array.isArray(json.data) ? json.data : [];
      if (keepSelection && selected) {
        selected = conversations.find(item => selectedKey(item) === selectedKey(selected)) || selected;
      } else {
        selected = conversations[0] || null;
      }
      renderConversations();
      if (selected && refreshMessages) {
        await loadMessages();
      } else {
        title.textContent = 'Select conversation';
        subtitle.textContent = 'Volunteer, Police, or Contributor';
        feed.innerHTML = '<div class="admin-messenger-date">No chat messages yet</div>';
      }
    } catch (error) {
      list.innerHTML = `<div class="admin-messenger-empty">${escapeHtml(error.message)}</div>`;
    }
  }

  async function loadMessages() {
    if (!selected) return;
    title.textContent = selected.participant_name || 'Unknown';
    subtitle.textContent = `${selected.participant_label} • ID ${selected.participant_id}`;
    if (headerAvatar) {
      headerAvatar.src = selected.profile_photo || '../Images/default-profile.gif';
      headerAvatar.alt = selected.participant_name || 'Selected conversation';
    }
    const params = new URLSearchParams({
      participant_role: selected.participant_role,
      participant_id: String(selected.participant_id)
    });
    params.set('as_admin', '1');
    const json = await fetchJson(`../Php/admin_chat_messages.php?${params.toString()}`);
    renderMessages(Array.isArray(json.data) ? json.data : []);
  }

  async function sendMessage() {
    if (!selected) {
      alert('Select a conversation first.');
      return;
    }
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    sendBtn.disabled = true;
    try {
      await fetchJson('../Php/admin_chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          participant_role: selected.participant_role,
          participant_id: selected.participant_id,
          message: text,
          as_admin: 1
        })
      });
      await loadConversations(true);
    } catch (error) {
      alert(error.message);
      input.value = text;
    } finally {
      sendBtn.disabled = false;
      input.focus();
    }
  }

  list.addEventListener('click', async event => {
    const btn = event.target.closest('[data-chat-role][data-chat-id]');
    if (!btn) return;
    selected = {
      participant_role: btn.getAttribute('data-chat-role'),
      participant_id: Number(btn.getAttribute('data-chat-id')),
      participant_name: btn.querySelector('strong')?.textContent || 'Unknown',
      participant_label: btn.querySelector('small')?.textContent?.split('•')[0]?.trim() || 'Participant',
      profile_photo: btn.getAttribute('data-chat-avatar') || '../Images/default-profile.gif'
    };
    renderConversations();
    await loadMessages();
    await loadConversations(true, false);
  });

  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      sendMessage();
    }
  });

  if (searchInput) searchInput.addEventListener('input', () => loadConversations(false, true));
  if (roleFilter) roleFilter.addEventListener('change', () => loadConversations(false, true));
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(btn => btn.classList.remove('active'));
      tab.classList.add('active');
      const label = tab.textContent.trim().toLowerCase();
      if (roleFilter) roleFilter.value = ['police', 'volunteer', 'contributor'].includes(label) ? label : 'all';
      loadConversations(false, true);
    });
  });

  loadConversations(false);
  refreshTimer = setInterval(() => {
    loadConversations(true);
  }, 4000);
  window.addEventListener('beforeunload', () => clearInterval(refreshTimer));
})();

// Global auto refresh heartbeat for all major admin sections.
(function () {
  const refreshSections = [
    'dashboard',
    'tables',
    'post-control',
    'admin-post',
    'crime',
    'missing',
    'donations',
    'broadcast',
    'volunteer',
    'volunteer-approver',
    'withdraw',
    'review',
    'reports',
    'chat-management',
    'chatbot-logs',
    'fire-detection'
  ];
})();

// AI Investigation Search Logic
async function checkAIEngineStatus() {
  const statusEl = document.getElementById('ai-engine-status');
  const fireStatusEl = document.getElementById('fire-engine-status');
  
  try {
    const res = await fetch('../Php/check_python_ai.php', { credentials: 'same-origin', cache: 'no-store' });
    const data = await res.json();
    if (data.success && data.status === 'online') {
      if (statusEl) {
        statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Status: Online';
        statusEl.style.background = '#e8f5e9';
        statusEl.style.color = '#2e7d32';
      }
      if (fireStatusEl) {
        fireStatusEl.innerHTML = '<i class="fa-solid fa-fire-flame-curved"></i> Detector: Online';
        fireStatusEl.style.background = '#e8f5e9';
        fireStatusEl.style.color = '#2e7d32';
      }
    } else {
      if (statusEl) {
        statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Status: Offline';
        statusEl.style.background = '#ffebee';
        statusEl.style.color = '#d32f2f';
      }
      if (fireStatusEl) {
        fireStatusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Detector: Offline';
        fireStatusEl.style.background = '#ffebee';
        fireStatusEl.style.color = '#d32f2f';
      }
    }
  } catch (err) {
    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Status: Error';
    if (fireStatusEl) fireStatusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Detector: Error';
  }
}
setInterval(checkAIEngineStatus, 3000);
setTimeout(checkAIEngineStatus, 500);

async function startAIEngineViaPHP() {
  const btn = document.getElementById('start-ai-engine-btn');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Starting...';
  }
  try {
    const res = await fetch('../Php/start_python_ai.php', { method: 'POST', credentials: 'same-origin' });
    const data = await res.json();
    if (data.success) {
      alert('Python AI Engine is starting in the background! Please wait a few seconds for the status to turn Online.');
      if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Started';
    } else {
      alert('Error starting AI engine: ' + data.error);
      if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Python AI Engine';
    }
  } catch (err) {
    alert('Failed to contact server to start AI engine.');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Python AI Engine';
  }
  if (btn) {
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-play"></i> Start Python AI Engine';
    }, 5000);
  }
}

function switchAiTab(tabId) {
  document.querySelectorAll('.ai-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.ai-tab-content').forEach(content => content.classList.add('hidden'));
  document.querySelectorAll('.ai-tab-content').forEach(content => content.classList.remove('active'));

  document.querySelector(`.ai-tab-btn[data-tab="${tabId}"]`).classList.add('active');
  const targetContent = document.getElementById(`ai-tab-${tabId}`);
  if (targetContent) {
    targetContent.classList.remove('hidden');
    targetContent.classList.add('active');
  }
}

function previewAiImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById('ai-preview-img').src = e.target.result;
      document.getElementById('ai-reference-preview').classList.remove('hidden');
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function saveActiveInvestigations() {
  const tbody = document.getElementById('active-investigations-body');
  if (tbody) {
    localStorage.setItem('activeAiInvestigationsV4', tbody.innerHTML);
  }
}

function loadActiveInvestigations() {
  const tbody = document.getElementById('active-investigations-body');
  const saved = localStorage.getItem('activeAiInvestigationsV4');
  if (tbody && saved && saved.trim() !== '') {
    tbody.innerHTML = saved;
    // Clean up any old "Resolved" rows that might still be in localStorage
    const resolvedRows = tbody.querySelectorAll('tr');
    let changed = false;
    resolvedRows.forEach(row => {
      if (row.innerHTML.includes('Resolved') || row.innerHTML.includes('status-approved')) {
        row.remove();
        changed = true;
      }
    });
    if (changed) {
      saveActiveInvestigations();
    }
  }
}

// Call load on page start
document.addEventListener('DOMContentLoaded', () => {
  loadActiveInvestigations();
  loadConfirmedMatches();
});

async function updateTargetImage(id, input) {
  if (!input.files || !input.files[0]) return;
  const formData = new FormData();
  formData.append('image', input.files[0]);

  // Show a loading state on the button
  const btn = input.nextElementSibling;
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
  btn.disabled = true;

  try {
    const res = await fetch('../Php/update_target_image.php', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      // Update the image src in the row
      const tr = document.getElementById(`investigation-row-${id}`);
      if (tr) {
        const img = tr.querySelector('img');
        img.src = data.new_image_url;

        // Update the onclick attributes for the search buttons to use the new URL
        const searchBtns = tr.querySelectorAll('button.ghost');
        // The first button in the actions cell is Posts, second is CCTV
        const actionBtns = Array.from(searchBtns).filter(b => b.innerText.includes('Search in'));
        if (actionBtns.length >= 2) {
          actionBtns[0].setAttribute('onclick', `startPythonAISearch('${id}', '${data.new_image_url}', 'posts')`);
          actionBtns[1].setAttribute('onclick', `promptCCTVImageAndSearch('${id}', '${data.new_image_url}')`);
        }
        saveActiveInvestigations();
      }
    } else {
      alert('Failed to update image: ' + data.error);
    }
  } catch (err) {
    console.error(err);
    alert('Error uploading image');
  } finally {
    btn.innerHTML = originalText;
    btn.disabled = false;
    input.value = ''; // Reset file input
  }
}

function promptCCTVImageAndSearch(caseId, fallbackImageUrl) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.onchange = async (e) => {
    if (!e.target.files || !e.target.files[0]) return;

    // Show status
    const statusEl = document.getElementById(`inv-status-${caseId}`);
    if (statusEl) {
      statusEl.innerHTML = `<span class="status-pending"><i class="fa-solid fa-spinner fa-spin"></i> Uploading Image...</span>`;
    }

    const formData = new FormData();
    formData.append('image', e.target.files[0]);

    try {
      const res = await fetch('../Php/update_target_image.php', { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        const searchCol = document.getElementById(`search-img-col-${caseId}`);
        if (searchCol) {
          searchCol.innerHTML = `<img src="${data.new_image_url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;"><div style="font-size:10px; margin-top:2px;">CCTV Ref</div>`;
          saveActiveInvestigations();
        }
        // Call search CCTV with new image
        startPythonAISearch(caseId, data.new_image_url, 'cctv');
      } else {
        alert('Upload failed: ' + data.error);
        if (statusEl) statusEl.innerHTML = `<span class="status-rejected">Upload Error</span>`;
      }
    } catch (err) {
      console.error(err);
      alert('Error uploading image');
      if (statusEl) statusEl.innerHTML = `<span class="status-rejected">Upload Error</span>`;
    }
  };
  // If they cancel, we can optionally fall back to the existing image
  // But for now, we just prompt it. If no file, it won't trigger onchange.
  input.click();
}

async function confirmAiMatch(btn, sourceType) {
  const card = btn.closest('.ai-result-card');
  const matchImgSrc = card.querySelector('.ai-result-img').src;

  const caseId = window.activeAiSearchCaseId;
  const targetImgSrc = document.querySelector(`#investigation-row-${caseId} img`).src;
  const detailsHtml = card.querySelector('.ai-result-details').innerHTML;

  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Confirming...';
  btn.disabled = true;

  try {
    const res = await fetch('../Php/admin_confirm_ai_match.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: caseId, source_type: sourceType })
    });
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.error || 'Failed to confirm AI match');
    }

    let confirmedTable = document.getElementById('confirmed-ai-matches-body');
    if (!confirmedTable) {
      const panel = document.querySelector('.ai-investigation-panel');
      const tableHtml = `
          <div class="section-table-block" style="margin-top: 30px;">
            <h3 style="margin:0 0 10px; color:#1f2937;">Confirmed AI Matches</h3>
            <table class="styled-table" id="confirmed-ai-matches-table">
              <thead>
                <tr>
                  <th>Case ID</th>
                  <th>Target Image</th>
                  <th>Matched Source</th>
                  <th>Match Details</th>
                  <th>Original Reporter</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="confirmed-ai-matches-body">
              </tbody>
            </table>
          </div>`;
      panel.insertAdjacentHTML('beforeend', tableHtml);
      confirmedTable = document.getElementById('confirmed-ai-matches-body');
    }

    // Use real data from backend
    let reporter = data.reporter_name || 'Unknown';

    const tr = document.createElement('tr');
    tr.innerHTML = `
          <td><strong>${caseId}</strong></td>
          <td><img src="${targetImgSrc}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;"></td>
          <td><img src="${matchImgSrc}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc;"></td>
          <td><div style="font-size: 0.9em; max-width: 250px;">${detailsHtml.replace(new RegExp('<button[^>]*>.*?</button>', 'ig'), '')}</div></td>
          <td>${escapeHtml(reporter)}</td>
          <td><span class="status-approved">Confirmed: ${escapeHtml(sourceType)}</span></td>
          <td>
            <button class="ai-action-btn" style="background:#4b5563; padding: 5px 10px; font-size:12px; margin-bottom: 5px; width: 100%;" onclick="openMatchDetailsModal('${caseId}', '${sourceType}', '${targetImgSrc}', '${matchImgSrc}', '${encodeURIComponent(detailsHtml)}', '${escapeHtml(reporter)}')">View Details</button>
            <button class="ai-action-btn ghost" style="width: 100%; margin-bottom: 5px;" onclick="notifyReporterHandover('${caseId}', this)">Notify Reporter</button>
            ${sourceType === 'Website Post' ? `<button class="ai-action-btn btn-say-thanks" style="background:#28a745; padding: 5px 10px; font-size:12px; width: 100%;" onclick="sayThanksToFinder('${caseId}', '${data.matched_post_id}', this)">Say Thanks</button>` : ''}
          </td>
      `;
    confirmedTable.appendChild(tr);

    // After confirming, immediately open the match details modal as requested
    openMatchDetailsModal(caseId, sourceType, targetImgSrc, matchImgSrc, encodeURIComponent(detailsHtml), reporter);

    window.openMatchDetailsModal = function (caseId, sourceType, targetImg, matchImg, encodedDetails, reporter) {
      const modal = document.getElementById('matchDetailsModal');
      const content = document.getElementById('matchDetailsContent');
      if (!modal || !content) return;

      const detailsHtml = decodeURIComponent(encodedDetails).replace(new RegExp('<button[^>]*>.*?</button>', 'ig'), '');

      let extraInfo = '';
      if (sourceType === 'Website Post') {
        extraInfo = `
            <div style="margin-top:15px; padding:10px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px;">
                <h4 style="margin:0 0 5px; color:#166534;">Related Post Information</h4>
                <p style="margin:0; font-size:14px; color:#15803d;">This match was found in a user-submitted post on the website.</p>
                <div style="margin-top:10px;">
                    <a href="#" style="color:#166534; font-weight:600; text-decoration:underline;" onclick="closeMatchDetailsModal(); showSection('post-control'); return false;"><i class="fa-solid fa-arrow-right-to-bracket"></i> View in Post Control</a>
                </div>
            </div>`;
      } else if (sourceType.toLowerCase().includes('cctv') || sourceType.toLowerCase().includes('camera')) {
        extraInfo = `
            <div style="margin-top:15px; padding:10px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px;">
                <h4 style="margin:0 0 5px; color:#1e40af;">Related Camera Feed Information</h4>
                <p style="margin:0; font-size:14px; color:#1d4ed8;">This match was identified via an active CCTV/Camera feed.</p>
                <div style="margin-top:10px;">
                    <a href="#" style="color:#1e40af; font-weight:600; text-decoration:underline;" onclick="closeMatchDetailsModal(); showSection('cctv'); return false;"><i class="fa-solid fa-arrow-right-to-bracket"></i> View in Camera Video Submissions</a>
                </div>
            </div>`;
      }

      content.innerHTML = `
        <div style="display:flex; gap:20px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1; min-width:250px;">
                <h4 style="margin-top:0;">Target Image (Case ${caseId})</h4>
                <img src="${targetImg}" style="width:100%; max-width:300px; border-radius:8px; border:2px solid #e5e7eb;">
                <p style="margin-top:10px;"><strong>Original Reporter:</strong> ${reporter}</p>
            </div>
            <div style="flex:1; min-width:250px;">
                <h4 style="margin-top:0;">Matched Image (${sourceType})</h4>
                <img src="${matchImg}" style="width:100%; max-width:300px; border-radius:8px; border:2px solid #22c55e;">
                <div style="margin-top:10px; background:#f9fafb; padding:10px; border-radius:6px;">
                    <strong>Match Details:</strong><br>
                    ${detailsHtml}
                </div>
            </div>
        </div>
        ${extraInfo}
    `;

      modal.style.display = 'flex';
    };

    window.closeMatchDetailsModal = function () {
      const modal = document.getElementById('matchDetailsModal');
      if (modal) modal.style.display = 'none';
    };
    confirmedTable.prepend(tr);

    localStorage.setItem('confirmedAiMatchesV3', confirmedTable.innerHTML);

    btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmed';
    btn.disabled = true;
    btn.style.backgroundColor = '#2e7d32';
    btn.style.color = '#fff';

    // Remove Active Investigation row since it's now confirmed
    const invRow = document.getElementById(`investigation-row-${caseId}`);
    if (invRow) {
      invRow.remove();
      saveActiveInvestigations();
    }

    // Trigger global UI refresh if needed
    if (typeof loadActionQueue === 'function') {
      loadActionQueue();
    }
  } catch (err) {
    alert('Error: ' + err.message);
    btn.innerHTML = 'Confirm Source';
    btn.disabled = false;
  }
}

function loadConfirmedMatches() {
  let saved = localStorage.getItem('confirmedAiMatchesV3');
  if (saved && saved.trim() !== '') {

    // Automatically fix old cached rows that are missing the Action column
    try {
      const parser = new DOMParser();
      const doc = parser.parseFromString(`<table><tbody id="temp-body">${saved}</tbody></table>`, 'text/html');
      const rows = doc.querySelectorAll('#temp-body tr');
      let modified = false;
      rows.forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (tds.length === 8) { // Missing Action column, but still has Policeman/Volunteer
          const caseId = tds[0].innerText.trim();
          tds[5].remove(); // Remove Policeman
          tds[6].remove(); // Remove Volunteer
          const actionTd = document.createElement('td');
          actionTd.innerHTML = `<button class="ai-action-btn ghost" onclick="notifyReporterHandover('${caseId}', this)">Notify Reporter</button>`;
          tr.appendChild(actionTd);
          modified = true;
        } else if (tds.length === 9) { // Has Action column, but still has Policeman/Volunteer
          tds[5].remove(); // Remove Policeman
          tds[6].remove(); // Remove Volunteer
          modified = true;
        }
      });
      if (modified) {
        saved = doc.querySelector('#temp-body').innerHTML;
        localStorage.setItem('confirmedAiMatchesV3', saved);
      }
    } catch (e) {
      console.error('Error repairing cached confirmed matches:', e);
    }

    const panel = document.querySelector('.ai-investigation-panel');
    if (!panel) return;
    const tableHtml = `
        <div class="section-table-block" style="margin-top: 30px;">
          <h3 style="margin:0 0 10px; color:#1f2937;">Confirmed AI Matches</h3>
          <table class="styled-table" id="confirmed-ai-matches-table">
            <thead>
              <tr>
                <th>Case ID</th>
                <th>Target Image</th>
                <th>Matched Source</th>
                <th>Match Details</th>
                <th>Original Reporter</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="confirmed-ai-matches-body">
                ${saved}
            </tbody>
          </table>
        </div>`;
    panel.insertAdjacentHTML('beforeend', tableHtml);
  }
}

async function notifyReporterHandover(caseId, btn) {
  if (!confirm(`Are you sure you want to notify the reporter for handover for case ${caseId}?`)) return;

  const tr = btn.closest('tr');
  let matchImgSrc = '';
  let matchDetails = '';
  if (tr) {
    const tds = tr.querySelectorAll('td');
    if (tds.length >= 4) {
      const imgEl = tds[2].querySelector('img');
      if (imgEl) matchImgSrc = imgEl.src;
      matchDetails = tds[3].innerText.trim();
    }
  }

  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Notifying...';
  btn.disabled = true;

  try {
    const res = await fetch('../Php/admin_notify_reporter_handover.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: caseId, match_img: matchImgSrc, match_details: matchDetails })
    });
    const data = await res.json();
    if (data.success) {
      alert('Handover notification sent successfully! Handover ID: ' + data.handover_id);
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Notified';
      btn.style.backgroundColor = '#2e7d32';
      btn.style.borderColor = '#2e7d32';
      btn.style.color = '#fff';

      // Update localstorage so the button stays updated
      const confirmedTable = document.getElementById('confirmed-ai-matches-body');
      if (confirmedTable) {
        localStorage.setItem('confirmedAiMatchesV3', confirmedTable.innerHTML);
      }
    } else {
      throw new Error(data.error || 'Failed to notify reporter');
    }
  } catch (err) {
    alert('Error: ' + err.message);
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  }
}

async function sayThanksToFinder(caseId, postId, btn) {
  if (!postId || postId === 'undefined') {
    alert('Cannot send thanks: Matched Post ID is unknown.');
    return;
  }
  if (!confirm(`Send a Thank You email to the person whose post (Post ID: ${postId}) matched Case ${caseId}?`)) return;

  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
  btn.disabled = true;

  try {
    const res = await fetch('../Php/admin_notify_finder_thanks.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: caseId, post_id: postId })
    });
    const data = await res.json();
    if (data.success) {
      alert('Thank You notification sent to the finder!');
      btn.innerHTML = '<i class="fa-solid fa-heart"></i> Thanked';
      btn.style.backgroundColor = '#1e7e34';
      btn.style.borderColor = '#1e7e34';
      btn.style.color = '#fff';

      const confirmedTable = document.getElementById('confirmed-ai-matches-body');
      if (confirmedTable) {
        localStorage.setItem('confirmedAiMatchesV3', confirmedTable.innerHTML);
      }
    } else {
      throw new Error(data.error || 'Failed to send thanks');
    }
  } catch (err) {
    alert('Error: ' + err.message);
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  }
}

async function notifyReporterManualHandover(caseId, btn) {
  if (!confirm(`Are you sure you want to notify the original reporter that Case ${caseId} has been successfully resolved?`)) return;

  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Notifying...';
  btn.disabled = true;

  try {
    const res = await fetch('../Php/admin_notify_reporter_handover.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ case_id: caseId, match_img: '', match_details: '' })
    });
    const data = await res.json();
    if (data.success) {
      alert('Handover notification sent successfully! Handover ID: ' + data.handover_id);
      btn.innerHTML = '<i class="fa-solid fa-check"></i> Notified';
      btn.style.backgroundColor = '#2e7d32';
      btn.style.color = '#fff';
      btn.style.cursor = 'default';
    } else {
      throw new Error(data.error || 'Failed to notify reporter');
    }
  } catch (err) {
    alert('Error: ' + err.message);
    btn.innerHTML = originalHtml;
    btn.disabled = false;
  }
}

let activeAiResultButton = null;

async function startPythonAISearch(caseId, targetImage, searchType) {
  window.activeAiSearchCaseId = caseId;
  const gridId = searchType === 'posts' ? 'ai-results-grid-posts' : 'ai-results-grid-cctv';
  const resultsGrid = document.getElementById(gridId);
  const statusEl = document.getElementById(`inv-status-${caseId}`);

  if (statusEl) {
    statusEl.innerHTML = `<span class="status-pending"><i class="fa-solid fa-spinner fa-spin"></i> Searching ${searchType}...</span>`;
    saveActiveInvestigations();
  }

  if (resultsGrid) {
    resultsGrid.innerHTML = `<div class="ai-empty-state"><i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><p style="margin-top: 10px;">Connecting to Python AI Engine to search in ${searchType === 'posts' ? 'Website Posts' : 'CCTV Feeds'}...</p></div>`;
  }

  try {
    const fd = new FormData();
    fd.append('action', searchType === 'posts' ? 'search_posts' : 'search_cctv');
    fd.append('target_image', targetImage);

    const res = await fetch('../Php/ai_search_handler.php', {
      method: 'POST',
      body: fd
    });

    const data = await res.json();

    if (!data.success) {
      throw new Error(data.error || 'Failed to get results from AI engine');
    }

    if (statusEl) {
      statusEl.innerHTML = `<span class="status-approved">Finished</span>`;
      saveActiveInvestigations();
    }

    if (!data.matches || data.matches.length === 0) {
      if (resultsGrid) {
        resultsGrid.innerHTML = `
          <div class="ai-empty-state" style="padding: 40px 20px;">
            <i class="fa-solid fa-fingerprint" style="font-size: 32px; color: #f05454; margin-bottom: 15px;"></i>
            <h4 style="margin: 0 0 10px; color: #1a232a;">No Confirmed Matches</h4>
            <p style="color: #64748b; margin: 0; font-size: 14px;">The AI scanned the database but couldn't find a strong match.</p>
          </div>
        `;
      }
    } else {
      let html = '';
      data.matches.forEach(match => {
        let displayImg = match.url || match.match_image || match.post_image || targetImage;

        // Make sure displayImg is not an absolute C:\ path
        if (displayImg.includes(':\\')) {
          let parts = displayImg.split('htdocs\\\\Searchar\\\\');
          if (parts.length > 1) {
            displayImg = '../' + parts[1].replace(/\\\\/g, '/');
          } else {
            parts = displayImg.split('htdocs/Searchar/');
            if (parts.length > 1) {
              displayImg = '../' + parts[1];
            }
          }
        }

        // If it's a relative path from the root (like uploads/posts/...) prepend ../
        if (!displayImg.startsWith('http') && !displayImg.startsWith('data:') && !displayImg.startsWith('../') && !displayImg.startsWith('/')) {
          displayImg = '../' + displayImg;
        }

        let details = '';

        if (searchType === 'posts') {
          details = `
            <div class="ai-result-location"><i class="fa-solid fa-file-lines" style="color:#1877F2;"></i> Post by ${escapeHtml(match.author || 'Unknown')}</div>
            <div style="font-size:12px; color:#555; margin-bottom: 4px;">${escapeHtml(match.time || '')}</div>
            <div style="font-size:13px; color:#333; margin-bottom: 8px; font-style: italic; max-height: 38px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                "${escapeHtml(match.description || 'No caption provided')}"
            </div>
          `;
        } else {
          details = `
            <div class="ai-result-location"><i class="fa-solid fa-video" style="color:#333;"></i> ${escapeHtml(match.label || 'CCTV')} (${escapeHtml(match.location || 'Unknown')})</div>
            <div style="font-size:12px; color:#555; margin-bottom: 8px;">
               ${match.capture_time ? 'Captured at: ' + escapeHtml(match.capture_time) : 'Time in video: ' + escapeHtml(match.timestamp + 's')}
            </div>
          `;
        }

        html += `
          <div class="ai-result-card">
            <img src="${escapeHtml(displayImg)}" class="ai-result-img" alt="Match" onerror="this.src='../Images/demo_pic/profile.jpg'">
            <div class="ai-result-details">
              <span class="ai-result-confidence">${match.confidence}% Match</span>
              ${details}
              <button class="ai-action-btn" onclick="confirmAiMatch(this, '${searchType === 'posts' ? 'Website Post' : 'CCTV Camera'}')">Confirm Source</button>
            </div>
          </div>
        `;
      });
      if (resultsGrid) {
        resultsGrid.innerHTML = html;
      }
    }

    if (searchType === 'posts') {
      setTimeout(() => {
        const cctvBtn = document.querySelector(`#investigation-row-${caseId} button:nth-child(2)`);
        if (cctvBtn && confirm("Posts search finished. Start CCTV search now?")) {
          cctvBtn.click();
        }
      }, 1000);
    }

  } catch (err) {
    if (statusEl) {
      statusEl.innerHTML = `<span class="status-rejected">Error</span>`;
      saveActiveInvestigations();
    }
    if (resultsGrid) {
      resultsGrid.innerHTML = `<div class="ai-empty-state"><i class="fa-solid fa-circle-exclamation" style="color:red;font-size:24px;"></i><p style="margin-top:10px;">Error: ${err.message}</p></div>`;
    }
  }
}

function openAiConfirmModal(button) {
  activeAiResultButton = button;
  document.getElementById('ai-confirm-modal').classList.remove('hidden');
}

function closeAiConfirmModal() {
  document.getElementById('ai-confirm-modal').classList.add('hidden');
  activeAiResultButton = null;
}

function confirmAiSource(sourceType) {
  if (activeAiResultButton) {
    const parent = activeAiResultButton.parentElement;
    activeAiResultButton.remove();
    const confirmedTag = document.createElement('div');
    confirmedTag.style.marginTop = '10px';
    confirmedTag.style.fontWeight = 'bold';
    confirmedTag.style.color = '#27ae60';
    confirmedTag.innerHTML = `<i class="fa-solid fa-check-circle"></i> Confirmed: ${sourceType}`;
    parent.appendChild(confirmedTag);
  }
  closeAiConfirmModal();
}

// --------------------------------------------------------
// Fire Detection Automation
// --------------------------------------------------------
(function () {
  const tableBody = document.getElementById('fire-table-body');
  if (!tableBody) return;

  function esc(val) {
    if (val === null || val === undefined) return '';
    return String(val)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatTime(isoStr) {
    if (!isoStr) return '';
    const d = new Date(isoStr);
    return d.toLocaleString('en-US', {
      month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
    });
  }

  function statusBadge(statusRaw) {
    switch (statusRaw) {
      case 'new': return '<span class="status-pending" style="color: #b91c1c; background: #fee2e2;">New Alert</span>';
      case 'police_dispatched': return '<span class="status-actioned" style="color: #15803d; background: #ecfdf3;">Police Dispatched</span>';
      case 'fire_station_called': return '<span class="status-actioned" style="color: #15803d; background: #ecfdf3;">Fire Station Called</span>';
      case 'camera_man_notified': return '<span class="status-under_review" style="color: #c2410c; background: #fff7ed;">Cameraman Notified</span>';
      case 'dismissed': return '<span class="status-closed" style="color: #4b5563; background: #f3f4f6;">Dismissed</span>';
      default: return `<span class="status-pending">${esc(statusRaw)}</span>`;
    }
  }

  async function loadFireAlerts() {
    try {
      tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center;">Loading fire alerts...</td></tr>';
      const res = await fetch('../Php/admin_fetch_fire_alerts.php', { cache: 'no-store' });
      const json = await res.json();
      if (!json?.success) throw new Error(json?.error || 'Failed to load fire alerts');
      
      const rows = json.rows || [];
      if (rows.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8" style="text-align: center; color: #6b7280;">No recent fire alerts.</td></tr>';
        return;
      }
      
      tableBody.innerHTML = rows.map(r => `
        <tr>
            <td><strong>#FD-${esc(r.alert_id)}</strong></td>
            <td>${esc(r.feed_label || `Camera ${r.feed_id}`)}</td>
            <td>${esc(r.camera_location || 'Unknown Location')}</td>
            <td>${formatTime(r.created_at)}</td>
            <td><span style="background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 12px;">${esc(r.confidence)}</span></td>
            <td>
              ${r.snapshot_url 
                ? `<button class="ghost" style="padding: 4px 8px; font-size: 12px;" onclick="window.open('${esc(r.snapshot_url)}', '_blank')"><i class="fa-solid fa-image"></i> View Frame</button>`
                : `<span style="color:#9ca3af; font-size: 12px;">No Image</span>`
              }
            </td>
            <td>${statusBadge(r.status)}</td>
            <td style="display: flex; gap: 8px; flex-wrap: wrap; border: none; padding-bottom: 12px;">
                ${r.status === 'new' ? `
                <button class="danger-btn btn-sm" onclick="window.updateFireStatus(${r.alert_id}, 'call_fire_station', this)"><i class="fa-solid fa-phone-volume"></i> Call Fire Station</button>
                <button class="add-btn btn-sm" onclick="window.updateFireStatus(${r.alert_id}, 'dispatch_police', this)"><i class="fa-solid fa-shield-halved"></i> Notify Police</button>
                <button class="ghost btn-sm" onclick="window.updateFireStatus(${r.alert_id}, 'notify_camera_man', this)"><i class="fa-solid fa-camera"></i> Notify Camera Man</button>
                <button class="ghost btn-sm" onclick="window.updateFireStatus(${r.alert_id}, 'dismiss', this)"><i class="fa-solid fa-xmark"></i> Dismiss</button>
                ` : `<span style="color:#6b7280; font-size: 13px; font-style: italic;">Action Taken</span>`}
            </td>
        </tr>
      `).join('');
    } catch (e) {
      console.error(e);
      tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Error: ${esc(e.message)}</td></tr>`;
    }
  }

  window.updateFireStatus = async function (alertId, action, btn) {
    if (!confirm('Are you sure you want to perform this action?')) return;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    try {
      const fd = new FormData();
      fd.append('alert_id', alertId);
      fd.append('action', action);

      const res = await fetch('../Php/admin_action_fire_alert.php', { method: 'POST', body: fd });
      const json = await res.json();
      
      if (!json?.success) throw new Error(json?.error || 'Update failed');
      
      loadFireAlerts();
      if (action === 'call_fire_station') {
        alert('Action recorded: Please call the local fire station immediately.');
      }
    } catch (e) {
      console.error(e);
      alert('Error: ' + e.message);
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  };

  document.addEventListener('admin:refresh-section', function (event) {
    if (event.detail?.sectionId === 'fire-detection') {
      loadFireAlerts();
    }
  });

  loadFireAlerts();
})();

// End of Admin.js

// Sensitive Submissions logic
(function() {
  const tableBody = document.getElementById('sensitive-table-body');
  
  async function loadSensitiveSubmissions() {
    if (!tableBody) return;
    tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading...</td></tr>';
    try {
      const res = await fetch('../Php/admin_fetch_sensitive_submissions.php', { cache: 'no-store' });
      const json = await res.json();
      if (!json || !json.success) throw new Error(json?.error || 'Failed to fetch sensitive submissions');
      
      const rows = Array.isArray(json.data) ? json.data : [];
      if (rows.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No sensitive submissions found.</td></tr>';
        return;
      }
      
      tableBody.innerHTML = rows.map(row => {
        const date = new Date(row.created_at).toLocaleString();
        const esc = str => String(str || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const content = esc(row.content).substring(0, 50) + (row.content?.length > 50 ? '...' : '');
        const badgeClass = row.status === 'reviewed' ? 'status-approved' : 'status-pending';
        return `
          <tr>
            <td>#${row.id}</td>
            <td>${date}</td>
            <td title="${esc(row.content)}">${content}</td>
            <td><span class="${badgeClass}">${esc(row.status)}</span></td>
            <td>
              <button class="ghost" onclick="alert('Viewing submission #${row.id}\\n\\n${esc(row.content)}')">View</button>
            </td>
          </tr>
        `;
      }).join('');
    } catch (e) {
      console.error(e);
      tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error: ${e.message}</td></tr>`;
    }
  }

  document.addEventListener('admin:section-activated', function (event) {
    if (event.detail?.sectionId === 'sensitive-submissions') {
      loadSensitiveSubmissions();
    }
  });
})();
