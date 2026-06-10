(function () {
  if (!window.L || !document.getElementById('redZoneMap')) {
    return;
  }

  const defaultCenter = [23.8103, 90.4125];

  // ── State ────────────────────────────────────────────────────────────
  const state = {
    selectedCategories: new Set(['all']),
    hoursWindow: 'all',
    radius: 30,
    markers: [],
    allIncidents: [],      // raw data from API
    currentPoints: []      // after filter
  };

  // ── Map setup ────────────────────────────────────────────────────────
  const map = L.map('redZoneMap', {
    zoomControl: true,
    minZoom: 11,
    maxZoom: 18
  }).setView(defaultCenter, 13);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  let heatLayer = L.heatLayer([], {
    radius: state.radius,
    blur: 26,
    maxZoom: 17,
    gradient: {
      0.2: '#22c55e',
      0.45: '#facc15',
      0.75: '#f97316',
      1.0: '#ef4444'
    }
  }).addTo(map);

  // ── DOM refs ─────────────────────────────────────────────────────────
  const hotspotCount  = document.getElementById('rzHotspotCount');
  const hotspotList   = document.getElementById('rzHotspotList');
  const categoryChips = document.getElementById('rzCategoryGroup');
  const timeFilter    = document.getElementById('rzTimeGroup');
  const locateMeBtn   = document.getElementById('rzLocateBtn');

  const categoryColor = {
    crime:          '#ef4444',
    fire:           '#f97316',
    missing_person: '#8b5cf6'
  };

  // ── Helpers ──────────────────────────────────────────────────────────
  function formatTimeAgo(timestampMs) {
    const diffHours = Math.max(1, Math.round((Date.now() - timestampMs) / 3_600_000));
    if (diffHours < 24) return diffHours + 'h ago';
    return Math.round(diffHours / 24) + 'd ago';
  }

  function isInWindow(item) {
    if (state.hoursWindow === 'all') return true;
    return (Date.now() - item.timestamp_ms) / 3_600_000 <= Number(state.hoursWindow);
  }

  function matchesCategory(item) {
    if (state.selectedCategories.has('all')) return true;
    return state.selectedCategories.has(item.category);
  }

  function filteredIncidents() {
    return state.allIncidents.filter(i => isInWindow(i) && matchesCategory(i));
  }

  function computeRisk(points) {
    if (!points.length) return { level: 'Low', detail: 'No matching incidents.' };
    const avg = points.reduce((s, r) => s + r.intensity, 0) / points.length;
    const w   = avg * 100 + Math.min(points.length, 12) * 4.5;
    if (w >= 78) return { level: 'High',     detail: 'Dense and recent incident activity.' };
    if (w >= 52) return { level: 'Moderate', detail: 'Watchful zone with repeated patterns.' };
    return            { level: 'Low',      detail: 'Comparatively calm under current filter.' };
  }

  // ── Render ───────────────────────────────────────────────────────────
  function clearMarkers() {
    state.markers.forEach(m => map.removeLayer(m));
    state.markers = [];
  }

  function renderHotspotFeed(points) {
    hotspotCount.textContent = points.length + ' zones';
    if (!points.length) {
      hotspotList.innerHTML = '<li><p class="rz-zone-meta">No hotspots in this view. Try broader filters.</p></li>';
      return;
    }
    const sorted = [...points].sort((a, b) => b.intensity - a.intensity).slice(0, 8);
    hotspotList.innerHTML = sorted.map(row => {
      const score = Math.round(row.intensity * 100);
      return (
        '<li>' +
          '<div class="rz-zone-top">' +
            '<span class="rz-zone-name">' + escHtml(row.place) + '</span>' +
            '<span class="rz-zone-score">' + score + '%</span>' +
          '</div>' +
          '<p class="rz-zone-meta">' +
            escHtml(row.category.replace('_', ' ')) +
            ' | ' + formatTimeAgo(row.timestamp_ms) +
          '</p>' +
        '</li>'
      );
    }).join('');
  }

  function renderMap() {
    const points = filteredIncidents();
    state.currentPoints = points;

    // Heat layer
    map.removeLayer(heatLayer);
    heatLayer = L.heatLayer(
      points.map(r => [r.lat, r.lng, r.intensity]),
      {
        radius: state.radius,
        blur: 26,
        maxZoom: 17,
        gradient: { 0.2: '#22c55e', 0.45: '#facc15', 0.75: '#f97316', 1.0: '#ef4444' }
      }
    ).addTo(map);

    // Circle markers
    clearMarkers();
    const groupLayers = [];
    points.forEach(row => {
      const marker = L.circleMarker([row.lat, row.lng], {
        radius:      5 + row.intensity * 5,
        color:       categoryColor[row.category] || '#f97316',
        fillColor:   categoryColor[row.category] || '#f97316',
        fillOpacity: 0.45,
        weight:      1.1
      }).addTo(map);

      marker.bindPopup(
        '<strong>' + escHtml(row.place) + '</strong><br>' +
        'Type: ' + escHtml(row.category.replace('_', ' ')) + '<br>' +
        'Severity: ' + Math.round(row.intensity * 100) + '%<br>' +
        'Reported: ' + formatTimeAgo(row.timestamp_ms) + '<br>' +
        '<small>' + escHtml(row.note) + '</small>'
      );

      state.markers.push(marker);
      groupLayers.push(marker);
    });

    if (groupLayers.length > 0) {
      const group = L.featureGroup(groupLayers);
      map.fitBounds(group.getBounds().pad(0.2));
    } else {
      map.setView(defaultCenter, 13);
    }

    // Risk badge on map card
    const risk = computeRisk(points);
    const mapCard = document.querySelector('.rz-map-card');
    if (mapCard) mapCard.setAttribute('data-risk', risk.level.toLowerCase());

    renderHotspotFeed(points);
  }

  // ── XSS helper ───────────────────────────────────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── API fetch ─────────────────────────────────────────────────────────
  // Shows a loading skeleton, then fetches from PHP, then renders.
  function fetchAndRender() {
    // Show loading state
    hotspotCount.textContent = 'Loading…';
    hotspotList.innerHTML    = '<li><p class="rz-zone-meta">Fetching live data…</p></li>';

    const hours = state.hoursWindow === 'all' ? 'all' : state.hoursWindow;
    const cats  = state.selectedCategories.has('all')
                    ? 'all'
                    : [...state.selectedCategories].join(',');

    // We always pass 'all' categories to the PHP and filter client-side,
    // so a category toggle never triggers a round-trip.
    const url = `../Php/fetch_redzone_incidents.php?hours=${encodeURIComponent(hours)}&cat=all`;

    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
      .then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(json => {
        if (!json.success || !Array.isArray(json.incidents)) {
          throw new Error('Bad response from server');
        }
        state.allIncidents = json.incidents;
        renderMap();
      })
      .catch(err => {
        hotspotCount.textContent = '0 zones';
        hotspotList.innerHTML    =
          '<li><p class="rz-zone-meta" style="color:#ef4444;">Could not load data. ' +
          escHtml(err.message) + '</p></li>';
        console.error('[RedZone] fetchAndRender error:', err);
      });
  }

  // ── Auto-refresh every 60 seconds ────────────────────────────────────
  let refreshTimer = setInterval(fetchAndRender, 60_000);

  // ── Filter event handlers ─────────────────────────────────────────────
  function syncChipActiveState() {
    categoryChips.querySelectorAll('.rz-chip').forEach(chip => {
      const input = chip.querySelector('input');
      if (!input) return;
      const active = state.selectedCategories.has(input.value);
      chip.classList.toggle('active', active);
      input.checked = active;
    });
  }

  function setupCategoryFilters() {
    categoryChips.addEventListener('change', event => {
      if (event.target.tagName !== 'INPUT') return;
      const input = event.target;
      const value = input.value;
      const isChecked = input.checked;

      if (value === 'all') {
        state.selectedCategories = new Set(['all']);
      } else {
        state.selectedCategories.delete('all');
        if (isChecked) {
          state.selectedCategories.add(value);
        } else {
          state.selectedCategories.delete(value);
        }
        if (!state.selectedCategories.size) {
          state.selectedCategories.add('all');
        }
      }

      syncChipActiveState();
      renderMap();   // client-side filter — no extra API call needed
    });
  }

  function setupTimeFilter() {
    timeFilter.addEventListener('click', event => {
      const btn = event.target.closest('button');
      if (!btn) return;

      timeFilter.querySelectorAll('button').forEach(el => el.classList.remove('active'));
      btn.classList.add('active');

      const raw = btn.getAttribute('data-hours') || '24';
      state.hoursWindow = raw === 'all' ? 'all' : Number(raw);

      // Time change → re-fetch from server (new INTERVAL in SQL)
      clearInterval(refreshTimer);
      fetchAndRender();
      refreshTimer = setInterval(fetchAndRender, 60_000);
    });
  }



  function setupLocateMe() {
    locateMeBtn.addEventListener('click', () => {
      if (!navigator.geolocation) return;

      locateMeBtn.disabled = true;
      locateMeBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Locating…';

      navigator.geolocation.getCurrentPosition(
        pos => {
          const { latitude: lat, longitude: lng } = pos.coords;
          map.setView([lat, lng], 15);
          L.circle([lat, lng], {
            radius: 180, color: '#38bdf8', weight: 1,
            fillColor: '#38bdf8', fillOpacity: 0.12
          }).addTo(map);
          L.marker([lat, lng]).addTo(map).bindPopup('You are here').openPopup();
        },
        () => {},
        { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 }
      );

      setTimeout(() => {
        locateMeBtn.disabled = false;
        locateMeBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> My Location';
      }, 1200);
    });
  }

  function setupNavbarBack() {
    const logoWrap = document.querySelector('.navbar-logo');
    if (!logoWrap) return;
    logoWrap.style.cursor = 'pointer';
    logoWrap.addEventListener('click', () => {
      const sameOrigin = !!document.referrer && document.referrer.startsWith(window.location.origin);
      if (sameOrigin && window.history.length > 1) {
        window.history.back();
        return;
      }
      window.location.href = '../Html/Index.html';
    });
  }

  function roleLabel(role) {
    const k = String(role || '').toLowerCase();
    if (k === 'user')        return 'User';
    if (k === 'volunteer')   return 'Volunteer';
    if (k === 'police')      return 'Policeman';
    if (k === 'contributor') return 'Camera Contributor';
    return 'User';
  }

  function hydrateProfileCard() {
    fetch('../Php/fetch_current_profile.php', { credentials: 'same-origin', cache: 'no-store' })
      .then(res => {
        if (!res.ok) throw new Error('Unauthorized');
        return res.json();
      })
      .then(json => {
        if (!json?.success || !json.data) throw new Error('Invalid profile');
        const d = json.data;
        const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        const setSrc  = (id, src) => { const el = document.getElementById(id); if (el && src) el.src = src; };

        setText('rzUserName',  d.full_name || 'User');
        setText('rzUserRole',  roleLabel(d.role));
        setText('rzUserEmail', d.email || '');
        setText('rzUserBio',
          d.bio?.trim()
            ? d.bio.trim()
            : 'Tell people a little about yourself by adding a bio in your profile.'
        );
        setSrc('rzProfilePhoto', d.profile_photo);
        setSrc('rzCoverPhoto',   d.cover_photo);

        const profileBtn = document.getElementById('rzProfileBtn');
        if (profileBtn && d.profile_page) {
          profileBtn.setAttribute('onclick', `location.href='${d.profile_page}'`);
        }
      })
      .catch(() => {
        window.location.href = '../Html/login.html';
      });
  }

  // ── Boot ─────────────────────────────────────────────────────────────
  setupCategoryFilters();
  setupTimeFilter();
  setupLocateMe();
  setupNavbarBack();
  hydrateProfileCard();
  syncChipActiveState();
  fetchAndRender();   // initial data load

})();