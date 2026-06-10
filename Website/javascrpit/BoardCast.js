let divisionDistrictAreaData = {};

// DOM elements
const divisionSelect = document.getElementById('divisionSelect');
const districtSelect = document.getElementById('districtSelect');
const areaSelect = document.getElementById('areaSelect');
const broadcastSearch = document.getElementById('broadcastSearch');
const feedTypeFilter = document.getElementById('feedTypeFilter');
const broadcastFilterButton = document.getElementById('broadcastFilterButton');
const cameraGrid = document.getElementById('cameraGrid');
const cameraInfo = document.getElementById('cameraInfo');
const cameraCount = document.getElementById('cameraCount');
const areaName = document.getElementById('areaName');
const logo = document.getElementById('logo');
const useFilters = !!(divisionSelect && districtSelect && areaSelect);
let currentFeeds = [];
let currentAreaLabel = '';

async function loadLocationTreeFromDatabase() {
  if (!useFilters) {
    return;
  }
  try {
    const res = await fetch('../Php/fetch_broadcast_locations.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });

    const data = await res.json();
    if (!res.ok || !data?.success || typeof data.tree !== 'object' || data.tree === null) {
      throw new Error(data?.error || 'Unable to load location tree');
    }

    divisionDistrictAreaData = data.tree;
    populateDivisions();

    const divisionCount = Object.keys(divisionDistrictAreaData).length;
    if (divisionCount === 0) {
      renderEmptyState('No public cameraman location found yet. Ask cameraman to add public CCTV feed first.');
    } else {
      renderEmptyState('Select division, district and area from database-driven list to load broadcast feeds.');
    }
  } catch (error) {
    divisionDistrictAreaData = {};
    populateDivisions();
    renderEmptyState('Could not load location filters from database right now.');
  }
}

function generateFooterCalendar(year, month) {
  const calendarHeader = document.getElementById('calendarHeader');
  const calendarBody = document.querySelector('#calendar tbody');
  if (!calendarHeader || !calendarBody) return;

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  calendarHeader.textContent = `${monthNames[month]} ${year}`;

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const startingDay = firstDay === 0 ? 6 : firstDay - 1;

  calendarBody.innerHTML = '';
  let date = 1;

  for (let i = 0; i < 6; i++) {
    const row = document.createElement('tr');

    for (let j = 0; j < 7; j++) {
      const cell = document.createElement('td');

      if (i === 0 && j < startingDay) {
        cell.textContent = '';
      } else if (date <= daysInMonth) {
        cell.textContent = date;

        const today = new Date();
        if (
          date === today.getDate() &&
          year === today.getFullYear() &&
          month === today.getMonth()
        ) {
          cell.style.backgroundColor = '#ffffff';
          cell.style.color = '#111111';
          cell.style.fontWeight = '700';
        }

        date++;
      } else {
        cell.textContent = '';
      }

      row.appendChild(cell);
    }

    calendarBody.appendChild(row);
    if (date > daysInMonth) break;
  }
}

function renderEmptyState(message) {
  cameraGrid.innerHTML = `
    <article class="camera-empty-state">
      <h3>Broadcast Standby</h3>
      <p>${message}</p>
    </article>
  `;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function toYouTubeEmbedUrl(url) {
  try {
    const u = new URL(url);
    const host = u.hostname.toLowerCase();

    if (host.includes('youtu.be')) {
      const id = u.pathname.replace('/', '').trim();
      return id ? `https://www.youtube.com/embed/${id}` : '';
    }

    if (host.includes('youtube.com')) {
      const videoId = u.searchParams.get('v');
      if (videoId) {
        return `https://www.youtube.com/embed/${videoId}`;
      }
    }
  } catch (error) {
    return '';
  }

  return '';
}

function renderStreamMarkup(feed) {
  const feedType = String(feed.feed_type || '').toLowerCase();
  const isRecorded = feedType === 'recorded';
  const isWebcam = feedType === 'webcam';
  const videoUrl = String(feed.video_url || '');
  const liveUrl = String(feed.live_url || '');

  if (isWebcam) {
    return {
      hasMedia: true,
      html: `<div class="camera-stream-iframe" style="background:#000; display:flex; align-items:center; justify-content:center; color:#fff; width:100%; height:100%; text-align:center; min-height: 200px;">
               <div>
                 <i class="fa fa-video-camera" style="font-size:3rem; margin-bottom:10px;"></i>
                 <br>Live CCTV Feed
               </div>
             </div>`
    };
  }

  if (isRecorded && videoUrl) {
    return {
      hasMedia: true,
      html: `<video class="camera-stream-video" controls preload="metadata" src="${escapeHtml(videoUrl)}"></video>`
    };
  }

  if (!isRecorded && !isWebcam && liveUrl) {
    const ytEmbed = toYouTubeEmbedUrl(liveUrl);
    if (ytEmbed) {
      return {
        hasMedia: true,
        html: `<iframe class="camera-stream-iframe" src="${escapeHtml(ytEmbed)}" title="Live CCTV Stream" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe>`
      };
    }

    return {
      hasMedia: false,
      html: `<a class="camera-open-link" href="${escapeHtml(liveUrl)}" target="_blank" rel="noopener noreferrer">Open Live Feed</a>`
    };
  }

  return {
    hasMedia: false,
    html: '<div class="camera-no-stream">No preview available for this feed.</div>'
  };
}

function renderCameraCards(area, feeds) {
  cameraGrid.innerHTML = "";

  feeds.forEach((feed, index) => {
    const stream = renderStreamMarkup(feed);
    const isLive = String(feed.feed_type || '').toLowerCase() !== 'recorded';
    const liveBadge = isLive ? 'LIVE' : 'RECORDED';
    const signalLabel = Number(feed.is_active) === 1 ? 'Signal Active' : 'Signal Offline';
    const aiClass = Number(feed.allow_ai_detection) === 1 ? 'risk-medium' : 'neutral';
    const aiLabel = Number(feed.allow_ai_detection) === 1 ? 'AI Detection ON' : 'AI Detection OFF';
    const ownerName = String(feed.owner_name || 'Camera Contributor');
    const label = String(feed.feed_label || `Camera ${index + 1}`);
    const location = String(feed.camera_location || area || 'Unknown area');

    const videoCard = document.createElement("article");
    videoCard.className = "camera-card";
    videoCard.innerHTML = `
      <div class="camera-card-stream ${stream.hasMedia ? 'has-media' : ''}">
        <span class="camera-live-badge">${escapeHtml(liveBadge)}</span>
        <div class="camera-signal">${escapeHtml(signalLabel)}</div>
        ${stream.html}
      </div>
      <div class="camera-meta-row">
        <h3>${escapeHtml(label)}</h3>
        <span class="camera-zone">${escapeHtml(location)}</span>
      </div>
      <div class="camera-owner">By ${escapeHtml(ownerName)}</div>
      <div class="camera-tags-row">
        <span class="camera-tag ${aiClass}">${escapeHtml(aiLabel)}</span>
        <span class="camera-tag neutral">${isLive ? 'Public Live Stream' : 'Recorded Evidence'}</span>
      </div>
      <p class="camera-status">Unified CCTV desk showing cameraman feeds for the selected region.</p>
    `;
    cameraGrid.appendChild(videoCard);
  });
}

function applyFeedFilters() {
  const query = String(broadcastSearch?.value || '').trim().toLowerCase();
  const typeFilter = String(feedTypeFilter?.value || 'all').toLowerCase();

  let filtered = currentFeeds.slice();
  if (typeFilter !== 'all') {
    filtered = filtered.filter(feed => String(feed.feed_type || '').toLowerCase() === typeFilter);
  }

  if (query) {
    filtered = filtered.filter(feed => {
      const label = String(feed.feed_label || '').toLowerCase();
      const location = String(feed.camera_location || '').toLowerCase();
      const owner = String(feed.owner_name || '').toLowerCase();
      return label.includes(query) || location.includes(query) || owner.includes(query);
    });
  }

  cameraInfo.classList.remove("hidden");
  if (areaName) {
    areaName.textContent = currentAreaLabel || 'Selected Area';
  }
  cameraCount.textContent = String(filtered.length);

  if (filtered.length === 0) {
    renderEmptyState("No cameras match your filters.");
    return;
  }

  renderCameraCards(currentAreaLabel, filtered);
}

let latestFeedRequestId = 0;

async function loadBroadcastFeeds(selection) {
  const area = String(selection?.area || '').trim();
  const district = String(selection?.district || '').trim();
  const division = String(selection?.division || '').trim();

  if (!area && useFilters) {
    cameraInfo.classList.add("hidden");
    renderEmptyState("Please choose an area to start monitoring.");
    return;
  }

  const requestId = ++latestFeedRequestId;
  renderEmptyState("Loading CCTV feeds for selected location...");

  try {
    const qs = new URLSearchParams();
    if (division) qs.set('division', division);
    if (district) qs.set('district', district);
    if (area) qs.set('area', area);
    const res = await fetch(`../Php/fetch_public_cctv_broadcast.php?${qs.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });

    const data = await res.json();
    if (requestId !== latestFeedRequestId) return;

    if (!res.ok || !data?.success) {
      throw new Error(data?.error || 'Failed to load CCTV feeds');
    }

    const feeds = Array.isArray(data.feeds) ? data.feeds : [];
    currentFeeds = feeds;
    currentAreaLabel = area || 'Selected Area';

    if (feeds.length === 0) {
      cameraInfo.classList.add("hidden");
      renderEmptyState("No public CCTV feed found yet.");
      return;
    }

    applyFeedFilters();
  } catch (error) {
    cameraInfo.classList.add("hidden");
    renderEmptyState("Could not load feeds right now. Please try again.");
  }
}

// Populate Division dropdown
function populateDivisions() {
  divisionSelect.innerHTML = '<option value="">-- Choose Division --</option>';
  districtSelect.innerHTML = '<option value="">-- Choose District --</option>';
  areaSelect.innerHTML = '<option value="">-- Choose Area --</option>';

  const divisions = Object.keys(divisionDistrictAreaData || {});
  for (const division of divisions) {
    const option = document.createElement('option');
    option.value = division;
    option.textContent = division;
    divisionSelect.appendChild(option);
  }

  divisionSelect.disabled = divisions.length === 0;
  districtSelect.disabled = true;
  areaSelect.disabled = true;

  if (divisions.length > 0) {
    divisionSelect.value = divisions[0];
    populateDistricts(divisions[0]);
  }
}

// Populate Districts based on Division
function populateDistricts(division) {
  districtSelect.innerHTML = '<option value="">-- Choose District --</option>';
  areaSelect.innerHTML = '<option value="">-- Choose Area --</option>';
  districtSelect.disabled = true;
  areaSelect.disabled = true;

  if (division && divisionDistrictAreaData[division]) {
    for (const district in divisionDistrictAreaData[division]) {
      const option = document.createElement('option');
      option.value = district;
      option.textContent = district;
      districtSelect.appendChild(option);
    }
    districtSelect.disabled = false;

    const districts = Object.keys(divisionDistrictAreaData[division]);
    if (districts.length > 0) {
      districtSelect.value = districts[0];
      populateAreas(division, districts[0]);
    }
  }
}

// Populate Areas based on District
function populateAreas(division, district) {
  areaSelect.innerHTML = '<option value="">-- Choose Area --</option>';
  areaSelect.disabled = true;

  if (
    division &&
    district &&
    divisionDistrictAreaData[division] &&
    divisionDistrictAreaData[division][district]
  ) {
    divisionDistrictAreaData[division][district].forEach(area => {
      const option = document.createElement('option');
      option.value = area;
      option.textContent = area;
      areaSelect.appendChild(option);
    });
    areaSelect.disabled = false;

    const areas = divisionDistrictAreaData[division][district];
    if (Array.isArray(areas) && areas.length > 0) {
      areaSelect.value = areas[0];
      loadBroadcastFeeds({
        division,
        district,
        area: areas[0],
      });
    }
  }
}

// Event Listeners
if (logo) {
  logo.style.cursor = 'pointer';
  logo.addEventListener('click', () => {
    const referrer = document.referrer || '';

    // Prefer explicit referrer so user returns to the exact previous page.
    if (referrer) {
      try {
        const refUrl = new URL(referrer);
        const sameOrigin = refUrl.origin === window.location.origin;
        const notSamePage = refUrl.pathname !== window.location.pathname;
        if (sameOrigin && notSamePage) {
          window.location.href = refUrl.href;
          return;
        }
      } catch (error) {
      }
    }

    if (window.history.length > 1) {
      window.history.back();
      return;
    }

    window.location.href = '../Html/Policeman_Home.php';
  });
}

if (useFilters) {
  divisionSelect.addEventListener('change', function () {
    populateDistricts(this.value);
    cameraInfo.classList.add("hidden");
    renderEmptyState("Select a district and area to load active camera feeds.");
  });
  districtSelect.addEventListener('change', function () {
    populateAreas(divisionSelect.value, this.value);
    cameraInfo.classList.add("hidden");
    renderEmptyState("Almost there. Pick an area to view live broadcast cards.");
  });

  areaSelect.addEventListener('change', function () {
    const area = areaSelect.value;
    if (area) {
      loadBroadcastFeeds({
        division: divisionSelect.value,
        district: districtSelect.value,
        area,
      });
    } else {
      cameraInfo.classList.add("hidden");
      renderEmptyState("Please choose an area to start monitoring.");
    }
  });
}

if (broadcastSearch) {
  broadcastSearch.addEventListener('input', () => {
    if (currentFeeds.length > 0) {
      applyFeedFilters();
    }
  });
}

if (feedTypeFilter) {
  feedTypeFilter.addEventListener('change', () => {
    if (currentFeeds.length > 0) {
      applyFeedFilters();
    }
  });
}

if (broadcastFilterButton) {
  broadcastFilterButton.addEventListener('click', () => {
    if (currentFeeds.length > 0) {
      applyFeedFilters();
    }
  });
}

// Initial call
loadLocationTreeFromDatabase();

const today = new Date();
generateFooterCalendar(today.getFullYear(), today.getMonth());