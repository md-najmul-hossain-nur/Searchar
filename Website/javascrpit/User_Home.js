const chatWindow = document.getElementById('chatWindow');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');

sendBtn.addEventListener('click', () => {
  const msg = chatInput.value.trim();
  if(msg === '') return;

  const msgDiv = document.createElement('div');
  msgDiv.classList.add('message', 'sent');
  msgDiv.textContent = msg;

  chatWindow.appendChild(msgDiv);
  chatInput.value = '';
  chatWindow.scrollTop = chatWindow.scrollHeight;
});

chatInput.addEventListener('keypress', (e) => {
  if(e.key === 'Enter') {
    sendBtn.click();
  }
});

const modal = document.getElementById("postModal");
const feed = document.getElementById("post-feed");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImages = [];
let selectedVideo = null;
let isShareMode = false;
let shareContext = null;

function getPostVideoSource(postElement) {
  if (!postElement) return '';
  const postVideo = postElement.querySelector('video');
  if (!postVideo) return '';
  return postVideo.currentSrc
    || postVideo.getAttribute('src')
    || postVideo.querySelector('source')?.getAttribute('src')
    || '';
}

function initFeedVideoCenterPlayButtons() {
  document.querySelectorAll('.post-video').forEach(video => {
    if (video.dataset.centerPlayReady === '1') return;
    video.dataset.centerPlayReady = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'post-video-wrap';
    video.parentNode.insertBefore(wrapper, video);
    wrapper.appendChild(video);

    const centerBtn = document.createElement('button');
    centerBtn.type = 'button';
    centerBtn.className = 'post-video-center-btn';
    centerBtn.setAttribute('aria-label', 'Play video');
    centerBtn.innerHTML = '<i class="fa fa-play"></i>';
    wrapper.appendChild(centerBtn);

    const syncState = () => {
      const playing = !video.paused && !video.ended && video.readyState > 2;
      wrapper.classList.toggle('is-playing', playing);
      centerBtn.innerHTML = playing ? '<i class="fa fa-pause"></i>' : '<i class="fa fa-play"></i>';
      centerBtn.setAttribute('aria-label', playing ? 'Pause video' : 'Play video');
    };

    centerBtn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (video.paused || video.ended) {
        video.play().catch(() => {});
      } else {
        video.pause();
      }
      syncState();
    });

    video.addEventListener('play', syncState);
    video.addEventListener('pause', syncState);
    video.addEventListener('ended', syncState);
    syncState();
  });
}

function openModal(isShareMode = false) {
  const isSharing = Boolean(isShareMode);
  const mediaOptions = document.querySelector('.post-media-options');
  const sharedPreview = document.querySelector('.post-modal-preview');
  const sharedPostMeta = document.getElementById('sharedPostMeta');
  const sharedPostAuthorImage = document.getElementById('sharedPostAuthorImage');
  const sharedPostAuthorName = document.getElementById('sharedPostAuthorName');
  const sharedPostTime = document.getElementById('sharedPostTime');
  const sharedPostImage = document.getElementById('sharedPostImage');
  const sharedPostVideo = document.getElementById('sharedPostVideo');
  const sharedPostText = document.getElementById('sharedPostText');

  window.isShareMode = isSharing;

  if (mediaOptions) {
    mediaOptions.style.display = isSharing ? 'none' : 'flex';
  }

  if (mediaPreview) {
    mediaPreview.style.display = isSharing ? 'none' : 'block';
  }

  if (sharedPreview) {
    sharedPreview.style.display = isSharing ? 'block' : 'none';
  }

  if (isSharing) {
    if (sharedPostMeta && sharedPostAuthorImage && sharedPostAuthorName && sharedPostTime) {
      sharedPostMeta.style.display = 'flex';
      sharedPostAuthorImage.src = shareContext?.authorImage || '../Images/default_profile.png';
      sharedPostAuthorName.innerText = shareContext?.authorName || 'Unknown User';
      sharedPostTime.innerText = shareContext?.timeAgo || '';
    }

    if (sharedPostText) {
      sharedPostText.innerText = shareContext?.text || '';
      sharedPostText.style.display = (shareContext?.text || '').trim() ? 'block' : 'none';
    }
    if (sharedPostImage) {
      if (shareContext?.imageSrc) {
        sharedPostImage.src = shareContext.imageSrc;
        sharedPostImage.style.display = 'block';
      } else {
        sharedPostImage.removeAttribute('src');
        sharedPostImage.style.display = 'none';
      }
    }
    if (sharedPostVideo) {
      if (shareContext?.videoSrc) {
        sharedPostVideo.src = shareContext.videoSrc;
        sharedPostVideo.style.display = 'block';
      } else {
        sharedPostVideo.removeAttribute('src');
        sharedPostVideo.style.display = 'none';
      }
    }
  } else {
    if (sharedPostMeta && sharedPostAuthorImage && sharedPostAuthorName && sharedPostTime) {
      sharedPostMeta.style.display = 'none';
      sharedPostAuthorImage.removeAttribute('src');
      sharedPostAuthorName.innerText = '';
      sharedPostTime.innerText = '';
    }

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

  modal.style.display = "flex";
}

function closeModal() {
  isShareMode = false;
  shareContext = null;
  modal.style.display = "none";
  document.getElementById("postText").value = "";
  document.getElementById("imageUpload").value = "";
  document.getElementById("videoUpload").value = "";
  mediaPreview.innerHTML = "";
  mediaPreview.style.display = 'block';
  const mediaOptions = document.querySelector('.post-media-options');
  if (mediaOptions) mediaOptions.style.display = 'flex';

  const sharedPreview = document.querySelector('.post-modal-preview');
  const sharedPostMeta = document.getElementById('sharedPostMeta');
  const sharedPostAuthorImage = document.getElementById('sharedPostAuthorImage');
  const sharedPostAuthorName = document.getElementById('sharedPostAuthorName');
  const sharedPostTime = document.getElementById('sharedPostTime');
  const sharedPostText = document.getElementById('sharedPostText');
  const sharedPostImage = document.getElementById('sharedPostImage');
  const sharedPostVideo = document.getElementById('sharedPostVideo');
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

  selectedImages = [];
  selectedVideo = null;
  document.getElementById('facebookShareToggle').checked = false;
}

// Handle image upload preview
document.getElementById("imageUpload").addEventListener("change", function() {
  const files = Array.from(this.files || []);
  if (!files.length) return;

  if (files.length > 5) {
    alert('You can upload maximum 5 images in one post.');
    this.value = '';
    return;
  }

  const invalid = files.find(file => !file.type || !file.type.startsWith('image/'));
  if (invalid) {
    alert('Only image files are allowed in Photo upload.');
    this.value = '';
    return;
  }

  selectedImages = files;
  selectedVideo = null;
  document.getElementById("videoUpload").value = "";

  const countLabel = selectedImages.length > 1 ? `<p class="post-media-hint">${selectedImages.length} photos selected</p>` : '';
  mediaPreview.innerHTML = `
    ${countLabel}
    <div class="post-media-grid">
      ${selectedImages.map(file => `<img src="${URL.createObjectURL(file)}" alt="Preview image">`).join('')}
    </div>
  `;
});

// Handle video upload preview
document.getElementById("videoUpload").addEventListener("change", function() {
  const file = this.files[0];
  if (file) {
    selectedVideo = file;
    mediaPreview.innerHTML = `<video src="${URL.createObjectURL(file)}" controls controlsList="nodownload nofullscreen noplaybackrate" disablePictureInPicture oncontextmenu="return false;"></video>`;
    selectedImages = [];
    document.getElementById("imageUpload").value = "";
  }
});

// Create post
function createPost() {
  const caption = document.getElementById("postText").value.trim();
  const sharedText = shareContext?.text?.trim() || '';
  const finalText = isShareMode
    ? [caption, sharedText ? `\n\n🔁 Shared Post:\n${sharedText}` : ''].join('').trim()
    : caption;

  if (finalText === "" && selectedImages.length === 0 && !selectedVideo) {
    alert("Please add text or media to post!");
    return;
  }
  // Build FormData and submit to backend. Do NOT render locally — saved for later retrieval.
  const category = isShareMode
    ? (shareContext?.category || 'general')
    : (document.querySelector('input[name="category"]:checked')?.value || 'general');
  const fd = new FormData();
  fd.append('text', finalText);
  fd.append('category', category);
  fd.append('case_id', '1'); // single shared case; change if dynamic
  // include facebook toggle value
  const shareFb = document.getElementById('facebookShareToggle')?.checked ? '1' : '0';
  fd.append('share_facebook', shareFb);
  if (selectedImages.length > 0) {
    selectedImages.forEach(imageFile => {
      fd.append('media_images[]', imageFile, imageFile.name);
    });
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
        // reload page so saved posts (or admin view) can pick up the new contribution
        alert('Saved successfully. Your contribution is stored and will be available to case reviewers.');
        closeModal();
        window.location.reload();
      } else {
        alert('Save failed: ' + (res.error || 'Unknown'));
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

const recentNotificationsList = document.getElementById('recentNotificationsList');
const allNotificationsList = document.getElementById('allNotificationsList');
const notificationsSeeMoreBtn = document.getElementById('notificationsSeeMore');
const notificationsDrawer = document.getElementById('notificationsDrawer');
const notificationsDrawerBackdrop = document.getElementById('notificationsDrawerBackdrop');
const notificationsDrawerClose = document.getElementById('notificationsDrawerClose');
const notificationsDrawerFooter = notificationsDrawer ? notificationsDrawer.querySelector('.notifications-drawer-footer') : null;
let notificationsCache = [];

function formatRelativeTime(createdAt, fallback) {
  if (!createdAt) return fallback || 'Just now';
  const date = new Date(createdAt);
  if (Number.isNaN(date.getTime())) return fallback || 'Just now';

  const seconds = Math.floor((Date.now() - date.getTime()) / 1000);
  if (seconds < 60) return 'Just now';

  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} min ago`;

  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hr ago`;

  const days = Math.floor(hours / 24);
  if (days < 30) return `${days} day${days > 1 ? 's' : ''} ago`;

  return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
}

function parseServerDateTime(value) {
  if (!value) return null;
  const direct = new Date(value);
  if (!Number.isNaN(direct.getTime())) return direct;

  const normalized = String(value).replace(' ', 'T');
  const fallback = new Date(normalized);
  if (!Number.isNaN(fallback.getTime())) return fallback;
  return null;
}

function refreshPostRelativeTimes() {
  document.querySelectorAll('.post-time[data-created-at]').forEach(node => {
    const raw = node.getAttribute('data-created-at') || '';
    const parsed = parseServerDateTime(raw);
    if (!parsed) {
      node.textContent = 'Just now';
      return;
    }

    const seconds = Math.floor((Date.now() - parsed.getTime()) / 1000);
    if (seconds < 60) {
      node.textContent = 'Just now';
      return;
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
      node.textContent = `${minutes} min ago`;
      return;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
      node.textContent = `${hours} hr ago`;
      return;
    }

    const days = Math.floor(hours / 24);
    if (days < 30) {
      node.textContent = `${days} day${days > 1 ? 's' : ''} ago`;
      return;
    }

    node.textContent = parsed.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
  });
}

function notificationIconBySource(source) {
  if (source === 'admin') return '🛡️';
  if (source === 'police') return '👮';
  if (source === 'comment') return '💬';
  if (source === 'like') return '❤️';
  if (source === 'share') return '🔁';
  if (source === 'sms') return '📩';
  return '🔔';
}

function renderNotificationItems(items, { compact = false } = {}) {
  if (!Array.isArray(items) || items.length === 0) {
    return compact
      ? '<li class="notifications-empty">No notifications yet.</li>'
      : '<div class="notifications-empty">No notifications yet.</div>';
  }

  const list = compact ? items.slice(0, 3) : items;

  if (compact) {
    return list.map(item => {
      const levelClass = item.level === 'warning' || item.source === 'admin' || item.source === 'police'
        ? 'notification-item warning'
        : 'notification-item';
      const readClass = item.is_read ? 'is-read' : 'is-unread';
      return `
        <li class="${levelClass} ${readClass}" data-notification-id="${item.id || 0}" data-target-post-id="${item.target_post_id || ''}">
          <div class="notification-icon">${notificationIconBySource(item.source)}</div>
          <div class="notification-body">
            <div class="notification-title">${item.title || 'Notification'}</div>
            <div class="notification-message">${item.message || ''}</div>
          </div>
          <span class="notification-time">${formatRelativeTime(item.created_at, item.time_ago)}</span>
        </li>
      `;
    }).join('');
  }

  return list.map(item => {
    const levelClass = item.level === 'warning' || item.source === 'admin' || item.source === 'police'
      ? 'drawer-notification warning'
      : 'drawer-notification';
    const readClass = item.is_read ? 'is-read' : 'is-unread';
    return `
      <article class="${levelClass} ${readClass}" data-notification-id="${item.id || 0}" data-target-post-id="${item.target_post_id || ''}">
        <div class="drawer-notification-icon">${notificationIconBySource(item.source)}</div>
        <div class="drawer-notification-content">
          <h4>${item.title || 'Notification'}</h4>
          <p>${item.message || ''}</p>
          <small>${formatRelativeTime(item.created_at, item.time_ago)}</small>
        </div>
      </article>
    `;
  }).join('');
}

async function loadUserNotifications() {
  if (!recentNotificationsList || !allNotificationsList) return;
  try {
    const res = await fetch('../Php/fetch_user_notifications.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    const data = json && json.success && Array.isArray(json.data) ? json.data : [];

    notificationsCache = data;
    recentNotificationsList.innerHTML = renderNotificationItems(data, { compact: true });
    allNotificationsList.innerHTML = renderNotificationItems(data, { compact: false });
  } catch (error) {
    console.error('notification load failed', error);
    recentNotificationsList.innerHTML = '<li class="notifications-empty">Could not load notifications.</li>';
    allNotificationsList.innerHTML = '<div class="notifications-empty">Could not load notifications.</div>';
  }
}

async function markNotificationRead(notificationId) {
  if (!notificationId || notificationId <= 0) return;
  try {
    await fetch('../Php/mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ notification_id: notificationId })
    });

    notificationsCache = notificationsCache.map(item =>
      Number(item.id) === Number(notificationId) ? { ...item, is_read: true } : item
    );

    recentNotificationsList.innerHTML = renderNotificationItems(notificationsCache, { compact: true });
    allNotificationsList.innerHTML = renderNotificationItems(notificationsCache, { compact: false });
  } catch (error) {
    console.error('mark read failed', error);
  }
}

async function markAllNotificationsRead() {
  try {
    await fetch('../Php/mark_all_notifications_read.php', {
      method: 'POST',
      credentials: 'same-origin'
    });

    notificationsCache = notificationsCache.map(item => ({ ...item, is_read: true }));
    recentNotificationsList.innerHTML = renderNotificationItems(notificationsCache, { compact: true });
    allNotificationsList.innerHTML = renderNotificationItems(notificationsCache, { compact: false });

    const btn = document.getElementById('notificationsMarkAllRead');
    if (btn) {
      btn.classList.add('is-done');
      btn.textContent = 'All marked read';
      setTimeout(() => {
        btn.classList.remove('is-done');
        btn.textContent = 'Mark all read';
      }, 1800);
    }
  } catch (error) {
    console.error('mark all read failed', error);
  }
}

function ensureMarkAllReadButton() {
  if (!notificationsDrawerFooter) return;
  if (document.getElementById('notificationsMarkAllRead')) return;

  const button = document.createElement('button');
  button.type = 'button';
  button.id = 'notificationsMarkAllRead';
  button.className = 'notifications-mark-all';
  button.textContent = 'Mark all read';
  button.addEventListener('click', function (event) {
    event.preventDefault();
    event.stopPropagation();
    markAllNotificationsRead();
  });

  notificationsDrawerFooter.appendChild(button);
}

function goToTargetPost(targetPostId) {
  const id = Number(targetPostId);
  if (!id || id <= 0) return;

  const targetPost = document.querySelector(`.post[data-post-id="${id}"]`) || document.getElementById(`post-${id}`);
  if (!targetPost) return;

  targetPost.scrollIntoView({ behavior: 'smooth', block: 'center' });
  targetPost.classList.add('post-target-flash');
  setTimeout(() => targetPost.classList.remove('post-target-flash'), 1800);
}

async function handleNotificationClick(row) {
  const notificationId = Number(row.getAttribute('data-notification-id'));
  const targetPostId = Number(row.getAttribute('data-target-post-id'));
  await markNotificationRead(notificationId);

  if (targetPostId > 0) {
    closeNotificationsDrawer();
    goToTargetPost(targetPostId);
  }
}

async function notifyPostInteraction(postId, actionType) {
  const id = Number(postId);
  if (!id || id <= 0) return;
  if (!['like', 'comment', 'share'].includes(actionType)) return;

  try {
    await fetch('../Php/notify_post_interaction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ post_id: id, action_type: actionType })
    });
  } catch (error) {
    console.error('notify interaction failed', error);
  }
}

function openNotificationsDrawer() {
  if (!notificationsDrawer || !notificationsDrawerBackdrop) return;
  notificationsDrawer.classList.add('open');
  notificationsDrawerBackdrop.classList.add('open');
  notificationsDrawer.setAttribute('aria-hidden', 'false');
}

function closeNotificationsDrawer() {
  if (!notificationsDrawer || !notificationsDrawerBackdrop) return;
  notificationsDrawer.classList.remove('open');
  notificationsDrawerBackdrop.classList.remove('open');
  notificationsDrawer.setAttribute('aria-hidden', 'true');
}

if (notificationsSeeMoreBtn) {
  notificationsSeeMoreBtn.addEventListener('click', openNotificationsDrawer);
}

if (notificationsDrawerClose) {
  notificationsDrawerClose.addEventListener('click', closeNotificationsDrawer);
}

if (notificationsDrawerBackdrop) {
  notificationsDrawerBackdrop.addEventListener('click', closeNotificationsDrawer);
}

ensureMarkAllReadButton();

if (recentNotificationsList) {
  recentNotificationsList.addEventListener('click', function (event) {
    const row = event.target.closest('[data-notification-id]');
    if (!row) return;
    handleNotificationClick(row);
  });
}

if (allNotificationsList) {
  allNotificationsList.addEventListener('click', function (event) {
    const row = event.target.closest('[data-notification-id]');
    if (!row) return;
    handleNotificationClick(row);
  });
}

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    closeNotificationsDrawer();
  }
});

loadUserNotifications();
setInterval(loadUserNotifications, 30000);
refreshPostRelativeTimes();
setInterval(refreshPostRelativeTimes, 60000);

const personPhotoInput = document.getElementById('personPhotoInput');
const personPhotoPreviewWrap = document.getElementById('personPhotoPreviewWrap');
const personPhotoPreview = document.getElementById('personPhotoPreview');

if (personPhotoInput && personPhotoPreviewWrap && personPhotoPreview) {
  personPhotoInput.addEventListener('change', function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (!file) {
      personPhotoPreview.src = '';
      personPhotoPreviewWrap.style.display = 'none';
      return;
    }

    if (!file.type || !file.type.startsWith('image/')) {
      alert('Please select a valid image file.');
      this.value = '';
      personPhotoPreview.src = '';
      personPhotoPreviewWrap.style.display = 'none';
      return;
    }

    personPhotoPreview.src = URL.createObjectURL(file);
    personPhotoPreviewWrap.style.display = 'block';
  });
}

// Close missing-person modal when clicking outside the form
window.addEventListener('click', function(event) {
  const modal = document.getElementById("missingFormModal");
  if (event.target === modal) {
    modal.style.display = "none";
  }
});

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
// Comment Show/Hide Toggle
document.querySelectorAll('.comment-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const commentSection = post.querySelector('.comment-module');
    notifyPostInteraction(post?.dataset?.postId, 'comment');
    if (commentSection.style.display === "none" || commentSection.style.display === "") {
      commentSection.style.display = "block"; // Show comments
    } else {
      commentSection.style.display = "none"; // Hide comments
    }
  });
});

document.querySelectorAll('.like-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    notifyPostInteraction(post?.dataset?.postId, 'like');
  });
});

// Likes & Dislikes Count
let likesUpParent = document.getElementsByClassName("comment-likes-up");
let likesDownParent = document.getElementsByClassName("comment-likes-down");

let likesEl = [];
for (let i = 0; i < likesUpParent.length; i++) {
  let likesUp = likesUpParent[i].getElementsByTagName("img")[0];
  let likesDown = likesDownParent[i].getElementsByTagName("img")[0];
  likesEl.push(likesUp, likesDown);
}

likesEl.forEach(el => {
  el.addEventListener("click", function () {
    let likesCountEl = this.parentElement.querySelector("span");
    let likesCount = likesCountEl ? parseInt(likesCountEl.innerText) || 0 : 0;
    likesCountEl.innerText = likesCount + 1;
  });
});
document.querySelectorAll('.comment-reply a').forEach(replyBtn => {
  replyBtn.addEventListener('click', function (e) {
    e.preventDefault();

    // পুরানো reply box রিমুভ
    document.querySelectorAll('.reply-input-area').forEach(box => box.remove());

    // নতুন reply box
    let replyBox = document.createElement('div');
    replyBox.classList.add('reply-input-area');
    replyBox.innerHTML = `
      <div class="comment-editor" contenteditable="true" data-placeholder="Write a reply..."></div>
<button class="comment-send-btn">
  <img src="../Images/send.png" alt="Send">
</button>    `;

    // `.comment` এর নিচে বসানো
    let commentLi = this.closest('li');
    commentLi.appendChild(replyBox); // এখন এটা নিচে দেখাবে

    // Auto resize
    const editor = replyBox.querySelector('.comment-editor');
    editor.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });

    // Reply send
    replyBox.querySelector('.comment-send-btn').addEventListener('click', function () {
      let replyText = editor.innerText.trim();
      if (replyText) {
        alert("Reply sent: " + replyText); // এখানে AJAX দিয়ে সার্ভারে পাঠানো যাবে
        replyBox.remove();
      }
    });
  });
});

// Open Modal and Set Preview
document.querySelectorAll('.share-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    const post = this.closest('.post');
    const postHeader = post ? post.querySelector('.post-header') : null;
    notifyPostInteraction(post?.dataset?.postId, 'share');
    const text = post.querySelector('p')?.innerText || '';
    const imageSrc = post.querySelector('.post-img')?.getAttribute('src') || '';
    const videoSrc = getPostVideoSource(post);
    const category = post?.dataset?.category || 'general';
    const authorImage = postHeader?.querySelector('img')?.getAttribute('src') || '../Images/default_profile.png';
    const authorName = postHeader?.querySelector('h5')?.innerText || 'Unknown User';
    const timeAgo = postHeader?.querySelector('small')?.innerText || '';

    shareContext = { text, imageSrc, videoSrc, category, authorImage, authorName, timeAgo };
    isShareMode = true;

    openModal(true);
  });
});
initFeedVideoCenterPlayButtons();

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