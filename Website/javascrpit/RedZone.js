(function () {
  if (!window.L || !document.getElementById('redZoneMap')) {
    return;
  }

  const defaultCenter = [23.8103, 90.4125];
  const now = Date.now();

  const incidents = [
    { lat: 23.7469, lng: 90.3820, category: 'crime', intensity: 0.94, place: 'Chawkbazar', ageHours: 4, note: 'Snatching hotspot near market area.' },
    { lat: 23.7512, lng: 90.3740, category: 'crime', intensity: 0.82, place: 'Sadarghat', ageHours: 12, note: 'Violent crime report during late hour.' },
    { lat: 23.7732, lng: 90.3985, category: 'crime', intensity: 0.69, place: 'Farmgate', ageHours: 20, note: 'Harassment complaint near bus stand.' },
    { lat: 23.7807, lng: 90.4070, category: 'crime', intensity: 0.76, place: 'Gulshan 1', ageHours: 8, note: 'Bike-assisted robbery report.' },
    { lat: 23.7925, lng: 90.4078, category: 'crime', intensity: 0.64, place: 'Banani', ageHours: 16, note: 'Street assault and theft cluster.' },
    { lat: 23.8682, lng: 90.3983, category: 'crime', intensity: 0.59, place: 'Uttara Sector 7', ageHours: 6, note: 'Late night mugging pattern observed.' },
    { lat: 23.8355, lng: 90.3680, category: 'fire', intensity: 0.73, place: 'Mirpur DOHS', ageHours: 36, note: 'Warehouse fire incident.' },
    { lat: 23.7631, lng: 90.3586, category: 'crime', intensity: 0.68, place: 'Mohammadpur', ageHours: 48, note: 'Gang conflict in lane area.' },
    { lat: 23.7299, lng: 90.3914, category: 'crime', intensity: 0.56, place: 'Lalbagh', ageHours: 28, note: 'Public safety complaint cluster.' },
    { lat: 23.7428, lng: 90.4201, category: 'crime', intensity: 0.71, place: 'Wari', ageHours: 22, note: 'Motorbike snatching incidents.' },
    { lat: 23.8004, lng: 90.4245, category: 'crime', intensity: 0.78, place: 'Badda', ageHours: 7, note: 'Backpack snatching reports.' },
    { lat: 23.7680, lng: 90.4255, category: 'crime', intensity: 0.66, place: 'Rampura', ageHours: 32, note: 'Street confrontation reports.' },
    { lat: 23.7524, lng: 90.4241, category: 'fire', intensity: 0.61, place: 'Khilgaon', ageHours: 9, note: 'Roadside vehicle fire alert.' },
    { lat: 23.7384, lng: 90.4319, category: 'fire', intensity: 0.58, place: 'Jatrabari', ageHours: 72, note: 'Electrical short-circuit fire.' },
    { lat: 23.8079, lng: 90.3624, category: 'crime', intensity: 0.55, place: 'Shyamoli', ageHours: 15, note: 'Harassment report spike.' },
    { lat: 23.8143, lng: 90.3768, category: 'crime', intensity: 0.63, place: 'Agargaon', ageHours: 10, note: 'Assault complaint near crossing.' },
    { lat: 23.7445, lng: 90.4013, category: 'missing_person', intensity: 0.57, place: 'Paltan', ageHours: 5, note: 'Missing person sighting alert.' },
    { lat: 23.7327, lng: 90.4004, category: 'missing_person', intensity: 0.72, place: 'Azimpur', ageHours: 14, note: 'Recent missing person report.' },
    { lat: 23.8234, lng: 90.4168, category: 'missing_person', intensity: 0.65, place: 'Kuril', ageHours: 18, note: 'Potential match report under review.' }
  ].map((item, idx) => ({
    id: idx + 1,
    timestamp: now - (item.ageHours * 60 * 60 * 1000),
    ...item
  }));

  const state = {
    selectedCategories: new Set(['all']),
    hoursWindow: 24,
    radius: 30,
    markers: [],
    currentPoints: []
  };

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

  const hotspotCount = document.getElementById('rzHotspotCount');
  const hotspotList = document.getElementById('rzHotspotList');
  const categoryChips = document.getElementById('rzCategoryGroup');
  const timeFilter = document.getElementById('rzTimeGroup');
  const radiusSlider = document.getElementById('rzRadius');
  const radiusValue = document.getElementById('rzRadiusValue');
  const locateMeBtn = document.getElementById('rzLocateBtn');

  const categoryColor = {
    crime: '#ef4444',
    fire: '#f97316',
    missing_person: '#8b5cf6'
  };

  function formatTimeAgo(timestamp) {
    const diffHours = Math.max(1, Math.round((Date.now() - timestamp) / (1000 * 60 * 60)));
    if (diffHours < 24) return diffHours + 'h ago';
    const days = Math.round(diffHours / 24);
    return days + 'd ago';
  }

  function isInWindow(item) {
    if (state.hoursWindow === 'all') return true;
    const age = (Date.now() - item.timestamp) / (1000 * 60 * 60);
    return age <= Number(state.hoursWindow);
  }

  function matchesCategory(item) {
    if (state.selectedCategories.has('all')) return true;
    return state.selectedCategories.has(item.category);
  }

  function filteredIncidents() {
    return incidents.filter((item) => isInWindow(item) && matchesCategory(item));
  }

  function computeRisk(points) {
    if (!points.length) {
      return { level: 'Low', detail: 'No matching incidents in this filter.' };
    }

    const avgIntensity = points.reduce((sum, row) => sum + row.intensity, 0) / points.length;
    const weighted = (avgIntensity * 100) + (Math.min(points.length, 12) * 4.5);

    if (weighted >= 78) {
      return { level: 'High', detail: 'Dense and recent incident activity detected.' };
    }
    if (weighted >= 52) {
      return { level: 'Moderate', detail: 'Watchful zone with repeated event patterns.' };
    }
    return { level: 'Low', detail: 'Comparatively calm area under current filter.' };
  }

  function clearMarkers() {
    state.markers.forEach((marker) => map.removeLayer(marker));
    state.markers = [];
  }

  function renderHotspotFeed(points) {
    hotspotCount.textContent = points.length + ' zones';

    if (!points.length) {
      hotspotList.innerHTML = '<li><p class="rz-zone-meta">No hotspot in this view. Try broader filters.</p></li>';
      return;
    }

    const sorted = [...points].sort((a, b) => b.intensity - a.intensity).slice(0, 8);
    hotspotList.innerHTML = sorted.map((row) => {
      const score = Math.round(row.intensity * 100);
      return '' +
        '<li>' +
        '<div class="rz-zone-top">' +
        '<span class="rz-zone-name">' + row.place + '</span>' +
        '<span class="rz-zone-score">' + score + '%</span>' +
        '</div>' +
        '<p class="rz-zone-meta">' + row.category.replace('_', ' ') + ' | ' + formatTimeAgo(row.timestamp) + '</p>' +
        '</li>';
    }).join('');
  }

  function renderMap() {
    const points = filteredIncidents();
    state.currentPoints = points;

    const heatPoints = points.map((row) => [row.lat, row.lng, row.intensity]);
    map.removeLayer(heatLayer);
    heatLayer = L.heatLayer(heatPoints, {
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

    clearMarkers();
    points.forEach((row) => {
      const marker = L.circleMarker([row.lat, row.lng], {
        radius: 5 + (row.intensity * 5),
        color: categoryColor[row.category] || '#f97316',
        fillColor: categoryColor[row.category] || '#f97316',
        fillOpacity: 0.45,
        weight: 1.1
      }).addTo(map);

      marker.bindPopup(
        '<strong>' + row.place + '</strong><br>' +
        'Type: ' + row.category + '<br>' +
        'Severity: ' + Math.round(row.intensity * 100) + '%<br>' +
        'Reported: ' + formatTimeAgo(row.timestamp) + '<br><small>' + row.note + '</small>'
      );

      state.markers.push(marker);
    });

    const risk = computeRisk(points);
    const mapCard = document.querySelector('.rz-map-card');
    if (mapCard) {
      mapCard.setAttribute('data-risk', risk.level.toLowerCase());
    }

    renderHotspotFeed(points);
  }

  function syncChipActiveState() {
    const chips = categoryChips.querySelectorAll('.rz-chip');
    chips.forEach((chip) => {
      const input = chip.querySelector('input');
      if (!input) return;
      const isActive = state.selectedCategories.has(input.value);
      chip.classList.toggle('active', isActive);
      input.checked = isActive;
    });
  }

  function setupCategoryFilters() {
    categoryChips.addEventListener('click', (event) => {
      const chip = event.target.closest('.rz-chip');
      if (!chip) return;

      const input = chip.querySelector('input');
      if (!input) return;
      const value = input.value;

      if (value === 'all') {
        state.selectedCategories = new Set(['all']);
      } else {
        if (state.selectedCategories.has('all')) {
          state.selectedCategories.delete('all');
        }

        if (state.selectedCategories.has(value)) {
          state.selectedCategories.delete(value);
        } else {
          state.selectedCategories.add(value);
        }

        if (!state.selectedCategories.size) {
          state.selectedCategories.add('all');
        }
      }

      syncChipActiveState();
      renderMap();
    });
  }

  function setupTimeFilter() {
    timeFilter.addEventListener('click', (event) => {
      const btn = event.target.closest('.seg-btn');
      if (!btn) return;

      timeFilter.querySelectorAll('button').forEach((el) => el.classList.remove('active'));
      btn.classList.add('active');

      const raw = btn.getAttribute('data-hours') || '24';
      state.hoursWindow = raw === 'all' ? 'all' : Number(raw);
      renderMap();
    });
  }

  function setupRadiusControl() {
    radiusSlider.addEventListener('input', () => {
      state.radius = Number(radiusSlider.value || 30);
      radiusValue.textContent = state.radius + ' px';
      renderMap();
    });
  }

  function setupQuickJumps() {
    document.querySelectorAll('.quick-jump button').forEach((btn) => {
      btn.addEventListener('click', () => {
        const lat = Number(btn.getAttribute('data-lat'));
        const lng = Number(btn.getAttribute('data-lng'));
        const zoom = Number(btn.getAttribute('data-zoom'));
        map.setView([lat, lng], zoom);
      });
    });
  }

  function setupLocateMe() {
    locateMeBtn.addEventListener('click', () => {
      if (!navigator.geolocation) {
        return;
      }

      locateMeBtn.disabled = true;
      locateMeBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Locating...';

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          map.setView([lat, lng], 15);

          L.circle([lat, lng], {
            radius: 180,
            color: '#38bdf8',
            weight: 1,
            fillColor: '#38bdf8',
            fillOpacity: 0.12
          }).addTo(map);

          L.marker([lat, lng]).addTo(map).bindPopup('You are here').openPopup();
        },
        () => {},
        { enableHighAccuracy: true, timeout: 7000, maximumAge: 0 }
      );

      setTimeout(() => {
        locateMeBtn.disabled = false;
        locateMeBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Locate Me';
      }, 1200);
    });
  }

  function roleLabel(role) {
    const key = String(role || '').toLowerCase();
    if (key === 'user') return 'User';
    if (key === 'volunteer') return 'Volunteer';
    if (key === 'police') return 'Policeman';
    if (key === 'contributor') return 'Camera Contributor';
    return 'User';
  }

  function hydrateProfileCard() {
    fetch('../Php/fetch_current_profile.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    })
      .then((res) => {
        if (!res.ok) {
          throw new Error('Unauthorized');
        }
        return res.json();
      })
      .then((json) => {
        if (!json || json.success !== true || !json.data) {
          throw new Error('Invalid profile response');
        }

        const data = json.data;
        const nameEl = document.getElementById('rzUserName');
        const roleEl = document.getElementById('rzUserRole');
        const emailEl = document.getElementById('rzUserEmail');
        const bioEl = document.getElementById('rzUserBio');
        const profileEl = document.getElementById('rzProfilePhoto');
        const coverEl = document.getElementById('rzCoverPhoto');
        const profileBtn = document.getElementById('rzProfileBtn');

        if (nameEl) nameEl.textContent = data.full_name || 'User';
        if (roleEl) roleEl.textContent = roleLabel(data.role);
        if (emailEl) emailEl.textContent = data.email || '';
        if (bioEl) {
          const rawBio = String(data.bio || '').trim();
          bioEl.textContent = rawBio !== ''
            ? rawBio
            : 'Tell people a little about yourself by adding a bio in your profile.';
        }
        if (profileEl && data.profile_photo) profileEl.src = data.profile_photo;
        if (coverEl && data.cover_photo) coverEl.src = data.cover_photo;
        if (profileBtn && data.profile_page) {
          profileBtn.setAttribute('onclick', "location.href='" + data.profile_page + "'");
        }
      })
      .catch(() => {
        window.location.href = '../Html/login.html';
      });
  }

  setupCategoryFilters();
  setupTimeFilter();
  setupRadiusControl();
  setupQuickJumps();
  setupLocateMe();
  hydrateProfileCard();
  syncChipActiveState();
  renderMap();
})();
