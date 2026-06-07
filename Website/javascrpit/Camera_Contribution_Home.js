
const modal = document.getElementById("postModal");
const mediaPreview = document.getElementById("mediaPreview");
const postTextInput = document.getElementById("postText");
const imageUploadInput = document.getElementById("imageUpload");
const videoUploadInput = document.getElementById("videoUpload");
const sharedPreview = document.querySelector('.post-modal-preview');
const sharedPostMeta = document.getElementById('sharedPostMeta');
const sharedPostAuthorImage = document.getElementById('sharedPostAuthorImage');
const sharedPostAuthorName = document.getElementById('sharedPostAuthorName');
const sharedPostTime = document.getElementById('sharedPostTime');
const sharedPostText = document.getElementById('sharedPostText');
const sharedPostImage = document.getElementById('sharedPostImage');
const sharedPostVideo = document.getElementById('sharedPostVideo');
const MAX_IMAGE_COUNT = 5;
let selectedImages = [];
let selectedVideo = null;

function renderSelectedImagesPreview() {
  if (!mediaPreview) return;

  if (!selectedImages.length) {
    mediaPreview.innerHTML = '';
    return;
  }

  const gridHtml = selectedImages.map((file, index) => {
    const objectUrl = URL.createObjectURL(file);
    return `
      <div class="post-media-item">
        <img src="${objectUrl}" alt="Selected image ${index + 1}">
        <button type="button" class="post-media-remove-btn" data-remove-index="${index}" aria-label="Remove image">&times;</button>
      </div>
    `;
  }).join('');

  mediaPreview.innerHTML = `<div class="post-media-grid">${gridHtml}</div>`;

  mediaPreview.querySelectorAll('.post-media-remove-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const removeIndex = Number(btn.getAttribute('data-remove-index'));
      if (Number.isNaN(removeIndex)) return;

      selectedImages = selectedImages.filter((_, idx) => idx !== removeIndex);
      if (imageUploadInput && !selectedImages.length) {
        imageUploadInput.value = '';
      }
      renderSelectedImagesPreview();
    });
  });
}

function resetSharedPreviewUi() {
  if (sharedPreview) sharedPreview.style.display = 'none';
  if (sharedPostMeta) sharedPostMeta.style.display = 'none';
  if (sharedPostAuthorImage) sharedPostAuthorImage.removeAttribute('src');
  if (sharedPostAuthorName) sharedPostAuthorName.innerText = '';
  if (sharedPostTime) sharedPostTime.innerText = '';
  if (sharedPostText) {
    sharedPostText.innerText = '';
    sharedPostText.style.display = 'none';
  }
  if (sharedPostImage) {
    sharedPostImage.removeAttribute('src');
    sharedPostImage.style.display = 'none';
  }
  if (sharedPostVideo) {
    sharedPostVideo.removeAttribute('src');
    sharedPostVideo.style.display = 'none';
  }
}

function openModal() {
  if (!modal) return;
  resetSharedPreviewUi();
  modal.style.display = "flex";
}

function closeModal() {
  if (!modal) return;
  modal.style.display = "none";
  if (postTextInput) postTextInput.value = "";
  if (imageUploadInput) imageUploadInput.value = "";
  if (videoUploadInput) videoUploadInput.value = "";
  if (mediaPreview) {
    mediaPreview.innerHTML = "";
    mediaPreview.style.display = 'block';
  }
  const mediaOptions = document.querySelector('.post-media-options');
  if (mediaOptions) mediaOptions.style.display = 'flex';
  resetSharedPreviewUi();
  const anonymousToggle = document.getElementById('anonymousShareToggle');
  if (anonymousToggle) anonymousToggle.checked = false;
  selectedImages = [];
  selectedVideo = null;
}

if (imageUploadInput) {
  imageUploadInput.addEventListener("change", function() {
    const files = Array.from(this.files || []);
    if (!files.length) return;

    if (files.length > MAX_IMAGE_COUNT) {
      alert(`You can select up to ${MAX_IMAGE_COUNT} photos in one post.`);
      this.value = '';
      return;
    }

    const nonImage = files.find((file) => !String(file.type || '').startsWith('image/'));
    if (nonImage) {
      alert('Only image files are allowed in photo selection.');
      this.value = '';
      return;
    }

    selectedImages = files;
    selectedVideo = null;
    renderSelectedImagesPreview();
    if (videoUploadInput) videoUploadInput.value = "";
  });
}

if (videoUploadInput) {
  videoUploadInput.addEventListener("change", function() {
    const file = this.files[0];
    if (!file) return;

    if (!String(file.type || '').startsWith('video/')) {
      alert('Please select a valid video file.');
      this.value = '';
      return;
    }

    selectedVideo = file;
    selectedImages = [];
    if (mediaPreview) mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls></video>`;
    if (imageUploadInput) imageUploadInput.value = "";
  });
}

function createPost() {
  if (!postTextInput) return;

  const text = postTextInput.value.trim();
  if (text === "" && !selectedImages.length && !selectedVideo) {
    alert("Please add text or media to post!");
    return;
  }

  const category = document.querySelector('input[name="category"]:checked')?.value || 'general';
  const fd = new FormData();
  fd.append('text', text);
  fd.append('category', category);
  fd.append('case_id', '1');
  fd.append('share_facebook', document.getElementById('facebookShareToggle')?.checked ? '1' : '0');
  fd.append('share_anonymous', document.getElementById('anonymousShareToggle')?.checked ? '1' : '0');

  selectedImages.forEach((imageFile) => {
    fd.append('media_images[]', imageFile, imageFile.name);
  });
  if (selectedVideo) {
    fd.append('media_video', selectedVideo, selectedVideo.name);
  }

  fetch('../Php/save_post.php', {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  }).then(r => r.json())
    .then(res => {
      if (res && res.success) {
        alert('Post submitted successfully. It will appear after admin approval.');
        closeModal();
        window.location.reload();
      } else {
        alert('Save failed: ' + (res?.error || 'Unknown error'));
      }
    }).catch(err => {
      console.error(err);
      alert('Network error while saving.');
    });
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

    // Basic escaper for popup content
    const HOTLINE_NUMBER = '999'; // national emergency hotline fallback
    function escapeHtml(str) {
      return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function formatPlaceInfo(place, fallbackType) {
      const name = place.display_name?.split(',')[0] || place.name || fallbackType || 'Location';
      const address = place.address
        ? [place.address.road, place.address.city || place.address.town || place.address.village, place.address.state, place.address.country]
          .filter(Boolean)
          .join(', ')
        : (place.display_name || 'Address not available');
      const phoneRaw = place.extratags?.phone || place.extratags?.['contact:phone'] || place.extratags?.['contact:mobile'] || place.extratags?.mobile || '';
      const phoneOsm = phoneRaw.trim();
      const phone = phoneOsm || HOTLINE_NUMBER;
      const phoneDial = phone ? phone.replace(/[^0-9+]/g, '') : '';
      const phoneLabel = phoneOsm ? 'Phone' : 'Hotline';
      return { name, address, phone, phoneDial, phoneLabel };
    }

    window.callPlace = function(phoneDial, name) {
      const target = phoneDial || HOTLINE_NUMBER;
      if (!target) {
        alert('Phone number not available for ' + (name || 'this place'));
        return;
      }
      window.location.href = 'tel:' + target;
    };

    // Fetch places function
    function fetchPlaces(lat, lon, type, icon) {
        clearMap(); // Remove old markers and routes

        // Show user marker
        userMarker = L.marker([lat, lon]).addTo(map)
            .bindPopup("📍 You are here").openPopup();
        markers.push(userMarker);

        var url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&extratags=1&q=${type}&bounded=1&viewbox=${lon-0.02},${lat+0.02},${lon+0.02},${lat-0.02}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    alert("No " + type + " found nearby.");
                    return;
                }
                data.forEach(place => {
                    const info = formatPlaceInfo(place, type);
                    const popupHtml = `<b>${escapeHtml(info.name)}</b><br>
<small>${escapeHtml(info.address)}</small><br>
<div class="popup-actions">
  <button class="route-btn" onclick="showRoute(${lat}, ${lon}, ${place.lat}, ${place.lon})">🚗 Show Route</button>
  <button class="call-btn" onclick="callPlace('${info.phoneDial}', '${escapeHtml(info.name)}')">📞 Call</button>
</div>
<div class="phone-text">${info.phone ? '📞 ' + escapeHtml(info.phoneLabel) + ': ' + escapeHtml(info.phone) : 'Phone not available'}</div>`;

                    var marker = L.marker([place.lat, place.lon], { icon: icon })
                        .addTo(map)
                        .bindPopup(popupHtml);

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


const openBtn = document.getElementById('openWithdrawBtn');
const withdrawModal = document.getElementById('withdrawModal');
const closeBtn = document.getElementById('closeModalBtn');
const withdrawForm = document.getElementById('withdrawForm');
const withdrawHistoryBody = document.getElementById('withdrawHistoryBody');
const pendingCountEl = document.getElementById('ccPendingCount');
const lastWithdrawalEl = document.getElementById('ccLastWithdrawalDate');
const totalStreamsEl = document.getElementById('ccTotalStreams');
const totalEarnedEl = document.getElementById('ccTotalEarned');
const availableBalanceEl = document.getElementById('ccAvailableBalance');

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function formatHistoryDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return escapeHtml(value);
  return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderWithdrawalHistory(rows) {
  if (!withdrawHistoryBody) return;
  if (!Array.isArray(rows) || rows.length === 0) {
    withdrawHistoryBody.innerHTML = '<tr><td colspan="4">No withdrawal history yet.</td></tr>';
  } else {
    withdrawHistoryBody.innerHTML = rows.map(row => {
      const statusRaw = String(row.status || 'pending').toLowerCase();
      const statusLabel = statusRaw.charAt(0).toUpperCase() + statusRaw.slice(1);
      return `
        <tr>
          <td>${escapeHtml(formatHistoryDate(row.created_at))}</td>
          <td>${escapeHtml(row.method || '—')}</td>
          <td>BDT ${escapeHtml(Number(row.amount || 0).toFixed(2))}</td>
          <td>${escapeHtml(statusLabel)}</td>
        </tr>
      `;
    }).join('');
  }

  const pendingCount = Array.isArray(rows)
    ? rows.filter(r => String(r.status || '').toLowerCase() === 'pending').length
    : 0;
  if (pendingCountEl) pendingCountEl.textContent = String(pendingCount);

  if (lastWithdrawalEl) {
    const latest = Array.isArray(rows) && rows.length ? rows[0].created_at : '';
    lastWithdrawalEl.textContent = latest ? formatHistoryDate(latest) : '—';
  }
}

function formatDate(value) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value);
  return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

async function loadCameraEarnings() {
  try {
    const res = await fetch('../Php/fetch_camera_earnings.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    const data = json && json.success ? json.data : null;
    if (!data) return;

    if (totalStreamsEl) totalStreamsEl.textContent = String(data.total_streams ?? 0);
    if (totalEarnedEl) totalEarnedEl.textContent = `BDT ${Number(data.total_earned || 0).toFixed(2)}`;
    if (availableBalanceEl) availableBalanceEl.textContent = `BDT ${Number(data.available_balance || 0).toFixed(2)}`;
    if (pendingCountEl) pendingCountEl.textContent = String(data.pending_withdrawals ?? 0);
    if (lastWithdrawalEl) lastWithdrawalEl.textContent = formatDate(data.last_withdrawal_date);
  } catch (error) {
    // Keep existing values on error.
  }
}

async function loadWithdrawalHistory() {
  if (!withdrawHistoryBody) return;
  try {
    const res = await fetch('../Php/fetch_withdrawal_history.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    const rows = json && json.success && Array.isArray(json.data) ? json.data : [];
    renderWithdrawalHistory(rows);
  } catch (error) {
    withdrawHistoryBody.innerHTML = '<tr><td colspan="4">Failed to load history.</td></tr>';
  }
}

if (openBtn && withdrawModal) {
  openBtn.addEventListener('click', () => {
    withdrawModal.style.display = 'flex';
  });
}

if (closeBtn && withdrawModal) {
  closeBtn.addEventListener('click', () => {
    withdrawModal.style.display = 'none';
  });
}

if (withdrawModal) {
  window.addEventListener('click', (e) => {
    if (e.target === withdrawModal) {
      withdrawModal.style.display = 'none';
    }
  });
}

if (withdrawForm) {
  withdrawForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const method = String(this.method?.value || '').trim();
    const accountNumber = String(this.accountNumber?.value || '').trim();
    const amount = Number(this.amount?.value || 0);

    if (!method || !accountNumber || !amount || Number.isNaN(amount)) {
      alert('Please fill out all fields correctly.');
      return;
    }

    const minWithdrawal = 5;
    if (amount < minWithdrawal) {
      alert(`Minimum withdrawal amount is $${minWithdrawal}.`);
      return;
    }

    try {
      const res = await fetch('../Php/save_withdrawal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ method, accountNumber, amount })
      });
      const json = await res.json();
      if (!json || !json.success) throw new Error(json?.error || 'Request failed');

      alert('Withdrawal request submitted!');
      withdrawModal.style.display = 'none';
      this.reset();
      await loadWithdrawalHistory();
    } catch (error) {
      alert('Could not submit withdrawal.');
    }
  });
}

loadWithdrawalHistory();
loadCameraEarnings();
setInterval(loadCameraEarnings, 30000);
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
  const feedForm = document.getElementById("camFeedForm");
  const uploadSection = document.getElementById("camUploadSection");
  const webcamSection = document.getElementById("camWebcamSection");
  const fileInput = document.getElementById("camFileInput");
  const startWebcamBtn = document.getElementById("camStartWebcamBtn");
  const webcamPreview = document.getElementById("camWebcamPreview");
  const webcamPlaceholder = document.getElementById("camWebcamPlaceholder");
  const webcamStatus = document.getElementById("camWebcamStatus");
  const sourceList = document.getElementById("camSourceList");
  const refreshBtn = document.getElementById("camRefreshFeedsBtn");
  const autoLabelEl = document.getElementById("camAutoLabel");
  const permissionConfirm = document.getElementById("camPermissionConfirm");
  const recordedPreviewWrap = document.getElementById("camRecordedPreviewWrap");
  const recordedPreview = document.getElementById("camRecordedPreview");

  const feedFormModal = document.getElementById("camFeedFormModal");
  const feedFormClose = feedFormModal.querySelector(".cam-form-close");
  const startFeedBtn = document.getElementById("startFeedBtn");

  if (!feedForm || !feedFormModal || !startFeedBtn) {
    return;
  }

  let currentFeeds = [];
  let webcamStream = null;

  const stopWebcamPreview = () => {
    if (webcamStream) {
      webcamStream.getTracks().forEach((track) => track.stop());
      webcamStream = null;
    }
    if (webcamPreview) {
      webcamPreview.pause();
      webcamPreview.srcObject = null;
    }
    if (webcamPlaceholder) webcamPlaceholder.style.display = "grid";
    if (webcamStatus) webcamStatus.textContent = "Browser permission is required to preview your webcam.";
    if (startWebcamBtn) startWebcamBtn.textContent = "Start Webcam";
  };

  const getNextCameraLabel = () => {
    if (!Array.isArray(currentFeeds) || currentFeeds.length === 0) {
      return 'Camera 1';
    }
    let maxIndex = 0;
    currentFeeds.forEach((feed) => {
      const label = String(feed?.feed_label || '').trim();
      const match = label.match(/(\d+)$/);
      if (match) {
        const n = Number(match[1]);
        if (Number.isFinite(n) && n > maxIndex) {
          maxIndex = n;
        }
      }
    });
    return `Camera ${maxIndex + 1}`;
  };

  const updateAutoLabel = () => {
    if (autoLabelEl) {
      autoLabelEl.textContent = getNextCameraLabel();
    }
  };

  const applyTypeUI = () => {
    const type = feedForm.feedType?.value || "webcam";
    if (type === "recorded") {
      uploadSection.style.display = "block";
      webcamSection.style.display = "none";
      fileInput.required = true;
      stopWebcamPreview();
    } else {
      webcamSection.style.display = "block";
      uploadSection.style.display = "none";
      fileInput.required = false;
    }
  };

  const formHardReset = () => {
    feedForm.reset();
    const webcamRadio = feedForm.querySelector('input[name="feedType"][value="webcam"]');
    if (webcamRadio) webcamRadio.checked = true;
    stopWebcamPreview();
    applyTypeUI();
    if (recordedPreview) recordedPreview.removeAttribute('src');
    if (recordedPreviewWrap) recordedPreviewWrap.style.display = 'none';
    updateAutoLabel();
  };

  const renderFeeds = (feeds) => {
    currentFeeds = Array.isArray(feeds) ? feeds : [];
    updateAutoLabel();

    if (!sourceList) return;
    if (!Array.isArray(feeds) || feeds.length === 0) {
      sourceList.innerHTML = '<div class="cam-empty">No CCTV source added yet.</div>';
      return;
    }

    sourceList.innerHTML = feeds.map((feed) => {
      const feedType = (feed.feed_type || 'webcam').toLowerCase();
      const typeText = feedType === 'recorded' ? 'Recorded Video' : (feedType === 'webcam' ? 'Webcam' : 'Live URL');
      const statusText = Number(feed.is_active) === 1 ? 'Active' : 'Closed';
      const toggleText = Number(feed.is_active) === 1 ? 'Close CCTV' : 'Reopen CCTV';
      const feedLink = feed.feed_type === 'live' && feed.live_url
        ? `<a href="${feed.live_url}" target="_blank" rel="noopener">Open URL</a>`
        : (feed.video_url ? `<a href="${feed.video_url}" target="_blank" rel="noopener">Open Video</a>` : (feedType === 'webcam' ? 'Webcam preview' : 'No media'));

      return `
        <article class="cam-source-item" data-feed-id="${feed.feed_id}">
          <div class="cam-source-main">
            <strong>${feed.feed_label || 'Camera Feed'}</strong>
            <span>${typeText}</span>
            <span>${feed.camera_location || 'Location not set'}</span>
            <span class="cam-source-status ${Number(feed.is_active) === 1 ? 'is-active' : 'is-closed'}">${statusText}</span>
            <span class="cam-source-link">${feedLink}</span>
          </div>
          <div class="cam-source-actions">
            <button type="button" class="cam-source-toggle" data-action="toggle" data-next="${Number(feed.is_active) === 1 ? 0 : 1}">${toggleText}</button>
            <button type="button" class="cam-source-delete" data-action="delete">Remove</button>
          </div>
        </article>
      `;
    }).join('');
  };

  const fetchFeeds = async () => {
    try {
      const response = await fetch('../Php/camera_cctv_feeds.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data?.success) {
        currentFeeds = [];
        updateAutoLabel();
        if (sourceList) {
          sourceList.innerHTML = '<div class="cam-empty">Failed to load CCTV sources.</div>';
        }
        return;
      }
      const feeds = Array.isArray(data.feeds) ? data.feeds : [];
      if (sourceList) {
        renderFeeds(feeds);
      } else {
        currentFeeds = feeds;
        updateAutoLabel();
      }
    } catch (error) {
      if (sourceList) {
        sourceList.innerHTML = '<div class="cam-empty">Network error while loading CCTV sources.</div>';
      }
    }
  };

  const openFeedModal = async () => {
    feedFormModal.classList.add('show');
    applyTypeUI();
    await fetchFeeds();
  };

  const closeFeedModal = () => {
    feedFormModal.classList.remove('show');
    formHardReset();
  };

  startFeedBtn.addEventListener('click', openFeedModal);

  feedForm.addEventListener('change', () => {
    applyTypeUI();
  });

  if (startWebcamBtn) {
    startWebcamBtn.addEventListener('click', async () => {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        if (webcamStatus) webcamStatus.textContent = 'Your browser does not support webcam preview.';
        return;
      }

      try {
        startWebcamBtn.disabled = true;
        if (webcamStatus) webcamStatus.textContent = 'Requesting webcam permission...';
        stopWebcamPreview();

        webcamStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        if (webcamPreview) {
          webcamPreview.srcObject = webcamStream;
          await webcamPreview.play();
        }
        if (webcamPlaceholder) webcamPlaceholder.style.display = 'none';
        if (webcamStatus) webcamStatus.textContent = 'Webcam preview is active.';
        startWebcamBtn.textContent = 'Restart Webcam';
      } catch (error) {
        if (webcamStatus) webcamStatus.textContent = 'Webcam permission was denied or unavailable.';
      } finally {
        startWebcamBtn.disabled = false;
      }
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', () => {
      const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (!file || !recordedPreview || !recordedPreviewWrap) {
        if (recordedPreview) recordedPreview.removeAttribute('src');
        if (recordedPreviewWrap) recordedPreviewWrap.style.display = 'none';
        return;
      }

      const previewUrl = URL.createObjectURL(file);
      recordedPreview.src = previewUrl;
      recordedPreviewWrap.style.display = 'block';
      recordedPreview.onloadeddata = () => {
        URL.revokeObjectURL(previewUrl);
      };
    });
  }

  feedForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!permissionConfirm || !permissionConfirm.checked) {
      alert('Owner permission confirmation is required.');
      return;
    }

    const submitBtn = feedForm.querySelector('.cam-submit-btn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
    }

    try {
      const fd = new FormData();
      const selectedType = feedForm.feedType?.value || 'webcam';
      if (selectedType === 'webcam' && !webcamStream) {
        alert('Please start the webcam preview before adding this feed.');
        return;
      }

      fd.append('action', 'create');
      fd.append('feed_type', selectedType);
      fd.append('feed_label', getNextCameraLabel());
      fd.append('stream_scope', 'private');
      fd.append('streaming_hours', 'continuous');
      fd.append('permission_confirmed', '1');
      if (selectedType === 'recorded' && fileInput.files[0]) {
        fd.append('recorded_video', fileInput.files[0], fileInput.files[0].name);
      }

      const response = await fetch('../Php/camera_cctv_feeds.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
        headers: { 'Accept': 'application/json' }
      });
      const data = await response.json();
      if (!response.ok || !data?.success) {
        alert(data?.error || 'Failed to save CCTV feed.');
        return;
      }

      alert('CCTV feed added successfully.');
      formHardReset();
      await fetchFeeds();
    } catch (error) {
      alert('Network error while saving CCTV feed.');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add CCTV Feed';
      }
    }
  });

  if (sourceList) {
    sourceList.addEventListener('click', async (e) => {
      const button = e.target.closest('button[data-action]');
      if (!button) return;
      const item = e.target.closest('.cam-source-item');
      const feedId = Number(item?.getAttribute('data-feed-id') || '0');
      if (!feedId) return;

      const action = button.getAttribute('data-action');
      const fd = new FormData();
      fd.append('action', action || '');
      fd.append('feed_id', String(feedId));

      if (action === 'toggle') {
        fd.append('is_active', button.getAttribute('data-next') || '0');
      }

      if (action === 'delete') {
        const ok = window.confirm('Do you want to remove this CCTV source?');
        if (!ok) return;
      }

      button.disabled = true;
      try {
        const response = await fetch('../Php/camera_cctv_feeds.php', {
          method: 'POST',
          credentials: 'same-origin',
          body: fd,
          headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (!response.ok || !data?.success) {
          alert(data?.error || 'Action failed.');
          return;
        }
        await fetchFeeds();
      } catch (error) {
        alert('Network error while updating source.');
      } finally {
        button.disabled = false;
      }
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', fetchFeeds);
  }

  feedFormClose.addEventListener('click', closeFeedModal);

  window.addEventListener('click', (e) => {
    if (e.target === feedFormModal) {
      closeFeedModal();
    }
  });

  formHardReset();
});
