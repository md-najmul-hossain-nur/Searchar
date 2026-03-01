
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
  const category = document.querySelector('input[name="category"]:checked')?.value || 'general';
  const fd = new FormData();
  fd.append('text', text);
  fd.append('category', category);
  fd.append('case_id', '1');
  fd.append('share_facebook', document.getElementById('facebookShareToggle')?.checked ? '1' : '0');
  fd.append('share_anonymous', document.getElementById('anonToggle')?.checked ? '1' : '0');

  if (selectedImage) {
    fd.append('media_images[]', selectedImage, selectedImage.name);
  }
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
document.getElementById('anonToggle').addEventListener('change', function () {
  if (this.checked) {
    console.log("Anonymous mode enabled");
    // Hide user's name or change UI if needed
  } else {
    console.log("Anonymous mode disabled");
    // Revert changes
  }
});

const openBtn = document.getElementById('openWithdrawBtn');
const withdrawModal = document.getElementById('withdrawModal');
const closeBtn = document.getElementById('closeModalBtn');
const withdrawForm = document.getElementById('withdrawForm');

openBtn.addEventListener('click', () => {
  withdrawModal.style.display = 'flex';
});

closeBtn.addEventListener('click', () => {
  withdrawModal.style.display = 'none';
});

window.addEventListener('click', (e) => {
  if (e.target === withdrawModal) {
    withdrawModal.style.display = 'none';
  }
});

withdrawForm.addEventListener('submit', function(e) {
  e.preventDefault();

  const amount = Number(this.amount.value);
  const availableBalance = 1000;
  const minWithdrawal = 5;

  if (amount < minWithdrawal) {
    alert(`Minimum withdrawal amount is $${minWithdrawal}.`);
    return;
  }

  if (amount > availableBalance) {
    alert('Amount cannot exceed available balance.');
    return;
  }

  alert('Withdrawal request submitted!');
  withdrawModal.style.display = 'none';
  this.reset();
});
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
  const liveInputSection = document.getElementById("camLiveInputSection");
  const fileInput = document.getElementById("camFileInput");
  const liveURLInput = document.getElementById("camLiveURL");
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
    const type = feedForm.feedType?.value || "live";
    if (type === "live") {
      liveInputSection.style.display = "block";
      uploadSection.style.display = "none";
      liveURLInput.required = true;
      fileInput.required = false;
    } else {
      uploadSection.style.display = "block";
      liveInputSection.style.display = "none";
      liveURLInput.required = false;
      fileInput.required = true;
    }
  };

  const formHardReset = () => {
    feedForm.reset();
    const liveRadio = feedForm.querySelector('input[name="feedType"][value="live"]');
    if (liveRadio) liveRadio.checked = true;
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
      const typeText = (feed.feed_type || 'live').toLowerCase() === 'recorded' ? 'Recorded Video' : 'Live URL';
      const statusText = Number(feed.is_active) === 1 ? 'Active' : 'Closed';
      const toggleText = Number(feed.is_active) === 1 ? 'Close CCTV' : 'Reopen CCTV';
      const feedLink = feed.feed_type === 'live' && feed.live_url
        ? `<a href="${feed.live_url}" target="_blank" rel="noopener">Open URL</a>`
        : (feed.video_url ? `<a href="${feed.video_url}" target="_blank" rel="noopener">Open Video</a>` : 'No media');

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
      fd.append('action', 'create');
      fd.append('feed_type', feedForm.feedType?.value || 'live');
      fd.append('feed_label', getNextCameraLabel());
      fd.append('stream_scope', feedForm.stream_scope?.value || 'private');
      fd.append('live_url', liveURLInput.value.trim());
      fd.append('streaming_hours', feedForm.streaming_hours?.value || 'continuous');
      fd.append('permission_confirmed', '1');
      if (fileInput.files[0]) fd.append('recorded_video', fileInput.files[0], fileInput.files[0].name);

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
