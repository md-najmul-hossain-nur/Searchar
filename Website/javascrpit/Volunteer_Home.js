
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
  const anonymousToggle = document.getElementById('anonymousShareToggle');
  if (anonymousToggle) anonymousToggle.checked = false;
  if (mediaPreview) {
    mediaPreview.innerHTML = "";
    mediaPreview.style.display = 'block';
  }
  const mediaOptions = document.querySelector('.post-media-options');
  if (mediaOptions) mediaOptions.style.display = 'flex';
  resetSharedPreviewUi();
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

// Get modal element
const volunteerMissionModal = document.getElementById('volunteerMissionModal');
if (volunteerMissionModal && volunteerMissionModal.parentElement !== document.body) {
  document.body.appendChild(volunteerMissionModal);
}
const missionListEl = volunteerMissionModal?.querySelector('.mission-list');
let missionLoadInFlight = false;
let missionTimerInterval = null;
let missionProofSubmitted = false;
let currentMissionNotificationId = 0;
let currentMissionCaseId = '';
let currentMissionId = 0;
let currentMissionContext = null;
const MISSION_HISTORY_STORAGE_KEY = 'volunteer_completed_mission_history_v1';

function escInline(v) {
  return String(v ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

let certificateSnapshot = {
  unlocked: false,
  rank: 'Bronze Volunteer',
  points: 0,
  completedMissions: 0,
  volunteerName: 'Volunteer'
};

function getCertificateVolunteerName() {
  const certificateBox = document.getElementById('certificate-unlock');
  const dataName = String(certificateBox?.dataset?.volunteerName || '').trim();
  if (dataName) return dataName;

  const profileName = document.querySelector('.profile-card h2')?.childNodes?.[0]?.textContent;
  const cleaned = String(profileName || '').trim();
  return cleaned || 'Volunteer';
}

function getCertificateRankTheme(rankRaw) {
  const rank = String(rankRaw || '').toLowerCase();

  if (rank.includes('platinum')) {
    return {
      key: 'platinum',
      ribbon: 'PLATINUM',
      bg: [246, 251, 255],
      watermark: [222, 235, 246],
      primary: [39, 84, 122],
      secondary: [146, 186, 214],
      nameColor: [26, 78, 125],
      bodyColor: [23, 37, 51],
      divider: [147, 197, 253],
      sealStroke: [30, 64, 175],
      sealText: [30, 64, 175],
      signatureText: 'Platinum Board Approval'
    };
  }

  if (rank.includes('gold')) {
    return {
      key: 'gold',
      ribbon: 'GOLD',
      bg: [255, 252, 241],
      watermark: [252, 242, 205],
      primary: [161, 98, 7],
      secondary: [253, 224, 71],
      nameColor: [146, 64, 14],
      bodyColor: [69, 26, 3],
      divider: [251, 191, 36],
      sealStroke: [180, 83, 9],
      sealText: [146, 64, 14],
      signatureText: 'Gold Tier Approval'
    };
  }

  if (rank.includes('silver')) {
    return {
      key: 'silver',
      ribbon: 'SILVER',
      bg: [248, 250, 252],
      watermark: [226, 232, 240],
      primary: [71, 85, 105],
      secondary: [203, 213, 225],
      nameColor: [51, 65, 85],
      bodyColor: [30, 41, 59],
      divider: [148, 163, 184],
      sealStroke: [100, 116, 139],
      sealText: [51, 65, 85],
      signatureText: 'Silver Tier Approval'
    };
  }

  return {
    key: 'bronze',
    ribbon: 'BRONZE',
    bg: [255, 248, 244],
    watermark: [252, 228, 221],
    primary: [146, 64, 14],
    secondary: [253, 186, 116],
    nameColor: [180, 83, 9],
    bodyColor: [67, 20, 7],
    divider: [234, 179, 122],
    sealStroke: [180, 83, 9],
    sealText: [146, 64, 14],
    signatureText: 'Bronze Tier Approval'
  };
}

function getRankUnlockMessage(rankRaw) {
  const rank = String(rankRaw || '').trim();
  if (rank === 'Silver Responder') {
    return {
      en: `🎉 Congratulations! You’ve reached <strong>${escInline(rank)}</strong>! Certificate unlocked.`,
      bn: `অভিনন্দন! আপনি <strong>${escInline(rank)}</strong> র‍্যাঙ্ক অর্জন করেছেন। আপনার সার্টিফিকেট এখন প্রস্তুত।`
    };
  }
  if (rank === 'Gold Responder') {
    return {
      en: `🏆 Incredible progress! You are now <strong>${escInline(rank)}</strong>. Your upgraded certificate is ready.`,
      bn: `দারুণ অগ্রগতি! আপনি এখন <strong>${escInline(rank)}</strong> র‍্যাঙ্কে আছেন। আপনার আপগ্রেডেড সার্টিফিকেট প্রস্তুত।`
    };
  }
  if (rank === 'Platinum Responder') {
    return {
      en: `👑 Legendary achievement! You reached <strong>${escInline(rank)}</strong>. Your elite certificate is ready.`,
      bn: `অসাধারণ সাফল্য! আপনি <strong>${escInline(rank)}</strong> র‍্যাঙ্কে পৌঁছেছেন। আপনার এলিট সার্টিফিকেট প্রস্তুত।`
    };
  }
  return null;
}

function buildVolunteerCertificatePdf() {
  const jsPdfLib = window.jspdf?.jsPDF;
  if (!jsPdfLib) {
    throw new Error('Certificate PDF library is not loaded.');
  }

  const doc = new jsPdfLib({ orientation: 'landscape', unit: 'pt', format: 'a4' });
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const theme = getCertificateRankTheme(certificateSnapshot.rank);

  doc.setFillColor(...theme.bg);
  doc.rect(0, 0, pageWidth, pageHeight, 'F');

  doc.setTextColor(...theme.watermark);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(96);
  doc.text('SEARCHAR', pageWidth / 2, pageHeight / 2 + 18, { align: 'center', angle: 16 });

  doc.setDrawColor(...theme.primary);
  doc.setLineWidth(3);
  doc.rect(24, 24, pageWidth - 48, pageHeight - 48);

  doc.setDrawColor(...theme.secondary);
  doc.setLineWidth(1.2);
  doc.rect(36, 36, pageWidth - 72, pageHeight - 72);

  doc.setFillColor(...theme.primary);
  doc.roundedRect((pageWidth / 2) - 54, 46, 108, 28, 8, 8, 'F');
  doc.setTextColor(255, 255, 255);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(12);
  doc.text(theme.ribbon, pageWidth / 2, 64, { align: 'center' });

  doc.setTextColor(...theme.primary);
  doc.setFont('times', 'bolditalic');
  doc.setFontSize(22);
  doc.text('SEARCHAR VOLUNTEER HONOR', pageWidth / 2, 104, { align: 'center' });

  doc.setTextColor(55, 65, 81);
  doc.setFontSize(17);
  doc.setFont('times', 'normal');
  doc.text('Certificate of Achievement', pageWidth / 2, 132, { align: 'center' });
  doc.setTextColor(113, 113, 122);
  doc.setFontSize(11);
  doc.text('Proshongsha Potro (Volunteer Service)', pageWidth / 2, 150, { align: 'center' });

  doc.setTextColor(...theme.bodyColor);
  doc.setFont('times', 'normal');
  doc.setFontSize(15);
  doc.text('This is proudly presented to', pageWidth / 2, 184, { align: 'center' });
  doc.setFontSize(11);
  doc.setTextColor(107, 114, 128);
  doc.text('Eyi shonodti sommaner sathe prodan kora holo', pageWidth / 2, 202, { align: 'center' });

  doc.setTextColor(...theme.nameColor);
  doc.setFont('times', 'bold');
  doc.setFontSize(36);
  doc.text(certificateSnapshot.volunteerName, pageWidth / 2, 246, { align: 'center' });

  doc.setTextColor(...theme.bodyColor);
  doc.setFont('times', 'normal');
  doc.setFontSize(16);
  doc.text(`in recognition of outstanding service as a ${certificateSnapshot.rank}`, pageWidth / 2, 286, { align: 'center' });
  doc.text(`and successfully completing ${certificateSnapshot.completedMissions} mission(s) with dedication and courage`, pageWidth / 2, 314, { align: 'center' });

  doc.setDrawColor(...theme.divider);
  doc.setLineWidth(1.1);
  doc.line((pageWidth / 2) - 215, 332, (pageWidth / 2) + 215, 332);

  const sealX = pageWidth - 136;
  const sealY = pageHeight - 170;
  doc.setDrawColor(...theme.sealStroke);
  doc.setLineWidth(1.6);
  doc.circle(sealX, sealY, 44);
  doc.circle(sealX, sealY, 36);
  doc.setTextColor(...theme.sealText);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.text('OFFICIAL', sealX, sealY - 8, { align: 'center' });
  doc.text('VOLUNTEER', sealX, sealY + 5, { align: 'center' });
  doc.text('CERTIFIED', sealX, sealY + 18, { align: 'center' });

  const issuedOn = new Date().toLocaleDateString();
  doc.setTextColor(31, 41, 55);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(12);
  doc.text(`Issued on: ${issuedOn}`, 78, pageHeight - 114);

  doc.setDrawColor(120, 120, 120);
  doc.line(68, pageHeight - 120, 210, pageHeight - 120);
  doc.setFontSize(10);
  doc.setTextColor(107, 114, 128);
  doc.text('Date of Recognition', 78, pageHeight - 104);

  doc.setDrawColor(120, 120, 120);
  doc.line(pageWidth - 288, pageHeight - 120, pageWidth - 82, pageHeight - 120);
  doc.setTextColor(...theme.bodyColor);
  doc.setFont('times', 'italic');
  doc.setFontSize(14);
  doc.text('SEARCHAR Admin', pageWidth - 182, pageHeight - 128, { align: 'center' });
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(10);
  doc.setTextColor(107, 114, 128);
  doc.text(theme.signatureText, pageWidth - 182, pageHeight - 104, { align: 'center' });

  doc.setFontSize(10);
  doc.setTextColor(107, 114, 128);
  doc.text('This certificate is digitally generated and verifiable in SEARCHAR records.', pageWidth / 2, pageHeight - 58, { align: 'center' });

  return doc;
}

function certificateFileName() {
  const safeName = String(certificateSnapshot.volunteerName || 'Volunteer')
    .trim()
    .replace(/[^a-z0-9]+/gi, '_')
    .replace(/^_+|_+$/g, '') || 'Volunteer';
  const theme = getCertificateRankTheme(certificateSnapshot.rank);
  return `SEARCHAR_${theme.key}_Certificate_${safeName}.pdf`;
}

function viewCertificatePdf() {
  const doc = buildVolunteerCertificatePdf();
  doc.output('dataurlnewwindow');
}

function downloadCertificatePdf() {
  const doc = buildVolunteerCertificatePdf();
  doc.save(certificateFileName());
}

function bindCertificateActions() {
  const viewBtn = document.getElementById('view-certificate-btn');
  if (!viewBtn) return;

  const canUse = !!certificateSnapshot.unlocked;
  viewBtn.disabled = !canUse;

  viewBtn.onclick = () => {
    if (!certificateSnapshot.unlocked) {
      alert('Complete more XP to unlock the certificate.');
      return;
    }
    try {
      viewCertificatePdf();
    } catch (e) {
      alert(e?.message || 'Could not open certificate PDF.');
    }
  };
}

async function loadVolunteerRankStatus() {
  try {
    const res = await fetch('../Php/volunteer_rank_status.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    if (!json?.success) return;

    const stats = json.stats || {};
    const rankBadge = document.getElementById('rank-badge-name');
    const rankPoints = document.getElementById('rank-points-value');
    const rankNext = document.getElementById('rank-next-value');
    const rankNeed = document.getElementById('rank-needed-value');
    const rankProgressFill = document.getElementById('rank-progress-fill');
    const rankProgressPercent = document.getElementById('rank-progress-percent');
    const rankStats = document.getElementById('rank-stats');
    const rankRules = document.getElementById('rank-rules');
    const certificateBox = document.getElementById('certificate-unlock');
    const certificateMessage = document.getElementById('certificate-message');

    const rank = String(stats.rank || 'Bronze Volunteer');
    const points = Number(stats.points || 0);
    const nextRank = stats.next_rank ? String(stats.next_rank) : 'Top Rank';
    const need = Number(stats.points_to_next_rank ?? 0);

    const unlockMessage = getRankUnlockMessage(rank);

    certificateSnapshot = {
      unlocked: !!stats.certificate_unlocked && rank !== 'Bronze Volunteer',
      rank,
      points,
      completedMissions: Number(stats.completed_missions || 0),
      volunteerName: getCertificateVolunteerName()
    };

    if (rankBadge) rankBadge.textContent = rank;
    if (rankPoints) rankPoints.textContent = `${points} XP`;
    if (rankNext) rankNext.textContent = stats.next_rank ? `Next: ${nextRank}` : 'Next: Top rank achieved';
    if (rankNeed) rankNeed.textContent = stats.next_rank ? `Need ${need} XP` : 'Max reached';

    const thresholds = [100, 380, 700, 1000];
    const tierIndex = points >= thresholds[3] ? 3 : points >= thresholds[2] ? 2 : points >= thresholds[1] ? 1 : 0;
    const tierStart = thresholds[tierIndex];
    const tierEnd = tierIndex === thresholds.length - 1 ? thresholds[tierIndex] : thresholds[tierIndex + 1];
    const tierSpan = Math.max(1, tierEnd - tierStart);
    const tierProgress = tierIndex === thresholds.length - 1 ? 100 : Math.min(100, Math.max(0, ((points - tierStart) / tierSpan) * 100));

    if (rankProgressFill) rankProgressFill.style.width = `${tierProgress}%`;
    if (rankProgressPercent) rankProgressPercent.textContent = `${Math.round(tierProgress)}%`;

    if (rankStats) {
      rankStats.textContent = `Accepted ${stats.accepted_missions || 0} • Completed ${stats.completed_missions || 0} • Busy ${stats.busy_missions || 0}`;
    }

    const acceptedXP = Number(json?.rules?.accepted_mission_xp || 10);
    const completedXP = Number(json?.rules?.completed_mission_xp || 20);
    const autoClosedXP = Number(json?.rules?.auto_closed_by_police_xp || 2);
    if (rankRules) {
      rankRules.textContent = `+${acceptedXP} XP (Accept) • +${completedXP} XP (Complete) • +${autoClosedXP} XP (Auto-close by Police)`;
    }

    if (certificateBox && certificateMessage) {
      if (certificateSnapshot.unlocked && unlockMessage) {
        certificateBox.classList.remove('hidden');
        certificateBox.removeAttribute('hidden');
        certificateMessage.innerHTML = `${unlockMessage.en}<br><small>${unlockMessage.bn}</small>`;
      } else {
        certificateBox.classList.add('hidden');
        certificateBox.setAttribute('hidden', 'hidden');
      }
    }

    bindCertificateActions();
  } catch (_e) {
    // ignore panel load errors
  }
}

function missionProofStorageKey(notificationId, caseId) {
  const idPart = Number(notificationId) > 0 ? `nid_${Number(notificationId)}` : '';
  const casePart = String(caseId || '').trim() ? `case_${String(caseId || '').trim()}` : '';
  const token = [idPart, casePart].filter(Boolean).join('_');
  return token ? `mission_proof_submitted_${token}` : '';
}

function isMissionProofSaved(notificationId, caseId) {
  const key = missionProofStorageKey(notificationId, caseId);
  if (!key) return false;
  try {
    return localStorage.getItem(key) === '1';
  } catch (_e) {
    return false;
  }
}

function saveMissionProofState(notificationId, caseId) {
  const key = missionProofStorageKey(notificationId, caseId);
  if (!key) return;
  try {
    localStorage.setItem(key, '1');
  } catch (_e) {
    // ignore storage errors
  }
}

function readMissionHistory() {
  try {
    const raw = localStorage.getItem(MISSION_HISTORY_STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    const rows = Array.isArray(parsed) ? parsed : [];
    const sanitized = rows.filter(isValidMissionHistoryEntry);
    if (sanitized.length !== rows.length) {
      writeMissionHistory(sanitized);
    }
    return sanitized;
  } catch (_e) {
    return [];
  }
}

function isValidMissionHistoryEntry(entry) {
  if (!entry || typeof entry !== 'object') return false;

  const caseId = String(entry.case_id || '').trim();
  const area = String(entry.area || '').trim();
  const label = String(entry.mission_label || '').trim();
  const completedAt = String(entry.completed_at || '').trim();
  const verified = entry.verified === true;

  if (isKnownDummyMissionHistory(caseId, area)) {
    return false;
  }

  const hasValidCase = caseId !== '' && caseId !== 'N/A' && !/^demo/i.test(caseId);
  const hasValidTime = Number.isFinite(Date.parse(completedAt));

  // Keep only mission rows that are explicitly verified by completion flow.
  return verified && hasValidCase && hasValidTime && label !== '';
}

function isKnownDummyMissionHistory(caseId, area) {
  const normalizedCaseId = String(caseId || '').trim().toUpperCase();
  const normalizedArea = String(area || '').trim().toLowerCase();

  const dummyCaseIds = new Set([
    'MP0001',
    'MP0002',
    'MP0005',
    'MP0006',
    'CR-2026-001',
    'CR-2026-002'
  ]);

  if (dummyCaseIds.has(normalizedCaseId)) {
    return true;
  }

  const dummyAreas = new Set([
    'dhanmodi 27',
    'dhanmondi 27',
    'mirpur 10 bus stand',
    'banani rail crossing',
    'banani lake bridge',
    'kawran bazar crossing'
  ]);

  return dummyAreas.has(normalizedArea);
}

function writeMissionHistory(rows) {
  try {
    localStorage.setItem(MISSION_HISTORY_STORAGE_KEY, JSON.stringify(Array.isArray(rows) ? rows : []));
  } catch (_e) {
    // ignore storage errors
  }
}

function formatHistoryTime(iso) {
  const d = new Date(String(iso || ''));
  if (Number.isNaN(d.getTime())) return 'Recently';
  return d.toLocaleString();
}

function renderMissionHistory() {
  const list = document.getElementById('mission-history-list');
  const empty = document.getElementById('mission-history-empty');
  if (!list || !empty) return;

  const rows = readMissionHistory();
  if (!rows.length) {
    list.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }

  empty.classList.add('hidden');
  list.innerHTML = rows
    .slice()
    .sort((a, b) => Date.parse(String(b.completed_at || '')) - Date.parse(String(a.completed_at || '')))
    .map(item => `
      <div class="mission-history-item">
        ✅ <strong>${escapeHtmlInline(item.mission_label || 'Mission')}</strong><br>
        📌 Case: <strong>${escapeHtmlInline(item.case_id || 'N/A')}</strong> • 📍 Area: ${escapeHtmlInline(item.area || 'N/A')}<br>
        🕒 Completed: ${escapeHtmlInline(formatHistoryTime(item.completed_at || ''))}
      </div>
    `).join('');
}

function saveCompletedMissionHistory(entry) {
  const caseId = String(entry?.case_id || '').trim();
  if (!caseId) return;

  const rows = readMissionHistory();
  const next = {
    case_id: caseId,
    area: String(entry?.area || '').trim(),
    mission_label: String(entry?.mission_label || '').trim(),
    completed_at: String(entry?.completed_at || new Date().toISOString()),
    verified: true
  };

  if (!isValidMissionHistoryEntry(next)) return;

  const idx = rows.findIndex(r => String(r?.case_id || '').trim() === caseId);
  if (idx >= 0) {
    rows[idx] = { ...rows[idx], ...next };
  } else {
    rows.push(next);
  }

  writeMissionHistory(rows);
}

function clearMissionTimer() {
  if (missionTimerInterval) {
    clearInterval(missionTimerInterval);
    missionTimerInterval = null;
  }
}

function formatTimerDuration(totalSeconds) {
  const safe = Math.max(0, Number(totalSeconds) || 0);
  const hours = String(Math.floor(safe / 3600)).padStart(2, '0');
  const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0');
  const seconds = String(Math.floor(safe % 60)).padStart(2, '0');
  return `${hours}:${minutes}:${seconds}`;
}

function parseAcceptedAtFromMessage(message) {
  const match = String(message || '').match(/\[AcceptedAt:\s*([^\]]+)\]/i);
  return match ? String(match[1] || '').trim() : '';
}

function setMissionTimer(acceptedAtText) {
  const wraps = Array.from(document.querySelectorAll('[data-mission-timer-wrap]'));
  const valueEls = Array.from(document.querySelectorAll('[data-mission-timer-value]'));
  if (!wraps.length || !valueEls.length) return;

  if (missionProofSubmitted) {
    clearMissionTimer();
    wraps.forEach(w => w.classList.add('hidden'));
    valueEls.forEach(el => { el.textContent = '00:00:00'; });
    return;
  }

  const acceptedTs = Date.parse(String(acceptedAtText || ''));
  if (!Number.isFinite(acceptedTs)) {
    clearMissionTimer();
    wraps.forEach(w => w.classList.add('hidden'));
    valueEls.forEach(el => { el.textContent = '00:00:00'; });
    return;
  }

  wraps.forEach(w => w.classList.remove('hidden'));
  const tick = () => {
    const elapsedSec = Math.floor((Date.now() - acceptedTs) / 1000);
    const text = formatTimerDuration(elapsedSec);
    valueEls.forEach(el => { el.textContent = text; });
  };

  clearMissionTimer();
  tick();
  missionTimerInterval = setInterval(tick, 1000);
}

function updateMissionProofLock(responseState) {
  const step = document.getElementById('mission-proof-single');
  const proofInput = document.getElementById('mission-proof-file');
  const submitBtn = document.querySelector('[data-mission-proof-submit="1"]');
  const status = document.getElementById('mission-proof-status');

  if (!step || !proofInput || !submitBtn || !status) return;

  if (step.classList.contains('done')) {
    proofInput.disabled = true;
    submitBtn.disabled = true;
    status.textContent = 'Proof already submitted for this mission.';
    return;
  }

  if (responseState === 'accepted') {
    proofInput.disabled = false;
    submitBtn.disabled = false;
    status.textContent = 'Mission accepted. Now you can submit proof.';
    return;
  }

  proofInput.disabled = true;
  submitBtn.disabled = true;
  if (responseState === 'rejected_busy') {
    status.textContent = 'Mission rejected (busy). Proof submission is disabled.';
  } else {
    status.textContent = 'Please accept the assigned mission first, then submit proof.';
  }
}

// Get the button that opens the modal
const openMissionBtn = document.querySelector('.view-missions-btn');

// Get all close buttons (modal close & any other)
const closeButtons = volunteerMissionModal?.querySelectorAll('.close') || [];

// Function to open the modal
function openMissionModal() {
  if (!volunteerMissionModal) return;
  volunteerMissionModal.classList.remove('hidden');
  document.body.classList.add('mission-modal-open');
  volunteerMissionModal.focus(); // for accessibility, focus modal
  renderMissionHistory();
  loadAssignedMissionDetails();
}

// Function to close the modal
function closeMissionModal() {
  if (!volunteerMissionModal) return;
  volunteerMissionModal.classList.add('hidden');
  document.body.classList.remove('mission-modal-open');
  clearMissionTimer();
}

function buildMissionPreview(file, previewEl) {
  if (!previewEl) return;
  previewEl.innerHTML = '';
  if (!file) return;

  const type = String(file.type || '').toLowerCase();
  if (type.startsWith('image/')) {
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = 'Mission proof image';
    previewEl.appendChild(img);
    return;
  }

  if (type.startsWith('video/')) {
    const video = document.createElement('video');
    video.src = URL.createObjectURL(file);
    video.controls = true;
    previewEl.appendChild(video);
    return;
  }

  const info = document.createElement('div');
  info.textContent = `Selected file: ${file.name}`;
  previewEl.appendChild(info);
}

function initMissionFlow() {
  const step = document.getElementById('mission-proof-single');
  const proofInput = document.getElementById('mission-proof-file');
  const preview = document.getElementById('mission-proof-preview');
  const status = document.getElementById('mission-proof-status');
  const submitBtn = document.querySelector('[data-mission-proof-submit="1"]');

  if (!step || !proofInput || !preview || !submitBtn) return;

  updateMissionProofLock('pending');

  proofInput.addEventListener('change', () => buildMissionPreview(proofInput.files?.[0], preview));

  submitBtn.addEventListener('click', async () => {
    if (submitBtn.disabled) {
      alert('Accept the mission first to submit proof.');
      return;
    }
    if (!proofInput.files || !proofInput.files[0]) {
      alert('Please upload a proof file first.');
      return;
    }

    const proofFile = proofInput.files[0];
    const fd = new FormData();
    fd.append('proof_file', proofFile);
    fd.append('notification_id', String(Number(currentMissionNotificationId) || 0));
    fd.append('mission_id', String(Number(currentMissionId) || 0));

    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    if (status) status.textContent = 'Uploading mission proof...';

    try {
      const res = await fetch('../Php/volunteer_submit_mission_proof.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const json = await res.json();
      if (!json?.success) {
        throw new Error(json?.error || 'Failed to submit proof');
      }

      currentMissionId = Number(json?.mission_id || currentMissionId || 0);

      step.classList.add('done');
      missionProofSubmitted = true;
      saveMissionProofState(currentMissionNotificationId, currentMissionCaseId);
      if (currentMissionContext) {
        saveCompletedMissionHistory({
          case_id: currentMissionContext.case_id,
          area: currentMissionContext.area,
          mission_label: currentMissionContext.mission_label,
          completed_at: new Date().toISOString()
        });
        renderMissionHistory();
      }
      submitBtn.disabled = true;
      proofInput.disabled = true;
      submitBtn.textContent = '✅ Proof Submitted';
      if (status) status.textContent = 'Proof submitted successfully.';
      setMissionTimer('');
      loadVolunteerRankStatus();
    } catch (error) {
      console.error('Failed to submit mission proof', error);
      alert(error?.message || 'Failed to submit proof right now.');
      submitBtn.disabled = false;
      submitBtn.textContent = '✅ Submit Proof';
      if (status) status.textContent = 'Proof submit failed. Try again.';
    }
  });
}

function escapeHtmlInline(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function parseAssignmentMessage(message) {
  const text = String(message || '');
  const caseMatch = text.match(/\(([A-Za-z0-9\-]+)\)/);
  const nearMatch = text.match(/near\s+([^\.]+)\./i);
  return {
    caseId: caseMatch ? caseMatch[1] : 'N/A',
    landmark: nearMatch ? nearMatch[1].trim() : 'N/A'
  };
}

function getAssignmentResponseState(message) {
  const text = String(message || '').toLowerCase();
  if (text.includes('[response: accepted]')) return 'accepted';
  if (text.includes('[response: rejected_busy]')) return 'rejected_busy';
  return 'pending';
}

function renderAssignmentStatus(state) {
  if (state === 'accepted') return '<span class="mission-response accepted">Accepted</span>';
  if (state === 'rejected_busy') return '<span class="mission-response rejected">Rejected (Busy)</span>';
  return '<span class="mission-response pending">Pending response</span>';
}

function renderAssignmentMedia(metaJsonText) {
  let meta = null;
  try {
    meta = metaJsonText ? JSON.parse(metaJsonText) : null;
  } catch (_e) {
    meta = null;
  }

  const media = Array.isArray(meta?.media) ? meta.media : [];
  if (!media.length) return '';

  const resolveMediaUrl = (rawUrl) => {
    const url = String(rawUrl || '').trim();
    if (!url) return '';
    if (/^https?:\/\//i.test(url)) return url;
    if (url.startsWith('../') || url.startsWith('./') || url.startsWith('/')) return url;
    return `../${url}`;
  };

  const inferKind = (type, url) => {
    const t = String(type || '').toLowerCase();
    const u = String(url || '').toLowerCase();
    if (t.includes('video') || /\.(mp4|webm|mov|m4v)(\?|#|$)/.test(u)) return 'video';
    if (t.includes('audio') || /\.(mp3|wav|m4a|ogg)(\?|#|$)/.test(u)) return 'audio';
    if (t.includes('image') || t.includes('photo') || /\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|#|$)/.test(u)) return 'image';
    return 'file';
  };

  const mediaHtml = media.map(item => {
    const type = String(item?.type || '').toLowerCase();
    const url = resolveMediaUrl(item?.url || '');
    if (!url) return '';
    const kind = inferKind(type, url);

    if (kind === 'video') {
      return `<video class="assignment-media" controls preload="metadata" src="${escapeHtmlInline(url)}"></video>`;
    }

    if (kind === 'image') {
      return `
        <div class="assignment-media-item">
          <img class="assignment-media" src="${escapeHtmlInline(url)}" alt="Mission evidence" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
          <a class="assignment-media-fallback" href="${escapeHtmlInline(url)}" target="_blank" rel="noopener" style="display:none;">Open evidence file</a>
        </div>
      `;
    }

    return '';
  }).join('');

  if (!mediaHtml) return '';
  return `<div class="assignment-media-wrap"><div class="assignment-media-title">Evidence</div>${mediaHtml}</div>`;
}

function parseAssignmentMeta(metaJsonText) {
  try {
    return metaJsonText ? JSON.parse(metaJsonText) : null;
  } catch (_e) {
    return null;
  }
}

function getLatestAssignment(items) {
  const assignments = (Array.isArray(items) ? items : []).filter(n => String(n.title || '').toLowerCase().includes('new crime assignment'));
  if (!assignments.length) return null;

  assignments.sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
  return assignments[0] || null;
}

function renderRankAssignedPreview(latestAssignment, options = {}) {
  const preview = document.getElementById('rank-assigned-preview');
  if (!preview) return;

  if (!latestAssignment || options.hidden) {
    preview.classList.add('empty');
    preview.innerHTML = 'No assigned crime mission right now.';
    return;
  }

  const details = parseAssignmentMessage(latestAssignment.message || '');
  const meta = parseAssignmentMeta(latestAssignment.meta_json || '');
  const missionLabel = String(meta?.mission_label || '').trim();
  const responseState = String(options.responseState || getAssignmentResponseState(latestAssignment.message || ''));
  const proofDone = Boolean(options.proofDone);
  const mediaBlock = proofDone ? '' : renderAssignmentMedia(latestAssignment.meta_json || '');

  preview.classList.remove('empty');
  preview.innerHTML = `
    <strong>🚨 Assigned Crime Mission</strong><br>
    ${missionLabel ? `🧭 <strong>Type:</strong> ${escapeHtmlInline(missionLabel)}<br>` : ''}
    📍 <strong>Area:</strong> ${escapeHtmlInline(details.landmark)}<br>
    <div class="mission-timer-wrap ${responseState === 'accepted' ? '' : 'hidden'}" data-mission-timer-wrap>⏱ <strong>Mission Timer:</strong> <span data-mission-timer-value>00:00:00</span></div>
    <div class="mission-response-wrap">${renderAssignmentStatus(responseState)}</div>
    ${mediaBlock}
  `;
}

async function loadRankAssignedPreview() {
  try {
    const res = await fetch('../Php/fetch_user_notifications.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    if (!json?.success || !Array.isArray(json.data)) {
      renderRankAssignedPreview(null);
      setMissionTimer('');
      return;
    }

    const latest = getLatestAssignment(json.data);
    if (!latest) {
      renderRankAssignedPreview(null);
      setMissionTimer('');
      return;
    }

    const responseState = getAssignmentResponseState(latest.message || '');
    const details = parseAssignmentMessage(latest.message || '');
    const proofDone = isMissionProofSaved(Number(latest.id || 0), details.caseId);

    if (responseState === 'rejected_busy' || proofDone) {
      renderRankAssignedPreview(latest, { responseState, proofDone });
      setMissionTimer('');
      return;
    }

    renderRankAssignedPreview(latest, { responseState, proofDone });
    const acceptedAt = parseAcceptedAtFromMessage(latest.message || '') || String(latest.created_at || '').trim();
    setMissionTimer(responseState === 'accepted' && !proofDone ? acceptedAt : '');
  } catch (_error) {
    renderRankAssignedPreview(null);
    setMissionTimer('');
  }
}

async function submitAssignmentResponse(notificationId, action, targetCard) {
  try {
    const res = await fetch('../Php/volunteer_assignment_response.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ notification_id: notificationId, action })
    });
    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.error || 'Failed to update assignment response');
    }

    const nextState = action === 'accept' ? 'accepted' : 'rejected_busy';

    if (action === 'reject' || json?.deleted) {
      missionProofSubmitted = false;
      currentMissionNotificationId = 0;
      currentMissionCaseId = '';
      currentMissionId = 0;
      currentMissionContext = null;
      setMissionTimer('');
      targetCard?.remove();
      await loadAssignedMissionDetails();
      await loadRankAssignedPreview();
      await loadVolunteerRankStatus();
      return;
    }

    const statusEl = targetCard?.querySelector('.mission-response-wrap');
    if (statusEl) statusEl.innerHTML = renderAssignmentStatus(nextState);
    updateMissionProofLock(nextState);

    if (nextState === 'accepted') {
      missionProofSubmitted = false;
      const acceptedAt = String(json?.accepted_at || '').trim() || new Date().toISOString();
      setMissionTimer(acceptedAt);
    } else {
      setMissionTimer('');
    }

    targetCard?.querySelectorAll('[data-assignment-action]').forEach(btn => {
      btn.disabled = true;
    });

    loadRankAssignedPreview();
    loadVolunteerRankStatus();
  } catch (error) {
    console.error('Failed to submit assignment response', error);
    alert('Could not update mission response right now.');
  }
}

async function loadAssignedMissionDetails() {
  if (!missionListEl) return;
  if (missionLoadInFlight) return;
  missionLoadInFlight = true;

  missionListEl.querySelectorAll('.dynamic-assignment-item').forEach(item => item.remove());

  try {
    const res = await fetch('../Php/fetch_user_notifications.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    if (!json?.success || !Array.isArray(json.data)) return;

    const latest = getLatestAssignment(json.data);
    if (!latest) {
      currentMissionId = 0;
      updateMissionProofLock('pending');
      setMissionTimer('');
      renderRankAssignedPreview(null);
      return;
    }

    const details = parseAssignmentMessage(latest.message || '');
    const meta = parseAssignmentMeta(latest.meta_json || '');
    const responseState = getAssignmentResponseState(latest.message || '');
    const missionLabel = String(meta?.mission_label || '').trim();
    const missionNote = String(meta?.mission_note || '').trim();
    const missionId = Number(meta?.mission_id || 0);
    const acceptedAt = parseAcceptedAtFromMessage(latest.message || '') || String(latest.created_at || '').trim();
    const latestNotificationId = Number(latest.id || 0);
    const proofDone = isMissionProofSaved(latestNotificationId, details.caseId);

    currentMissionNotificationId = latestNotificationId;
    currentMissionCaseId = details.caseId;
    currentMissionId = missionId;
    currentMissionContext = {
      case_id: details.caseId,
      area: details.landmark,
      mission_label: missionLabel || 'Assigned Mission'
    };
    missionProofSubmitted = proofDone;

    if (responseState === 'rejected_busy') {
      currentMissionNotificationId = 0;
      currentMissionCaseId = '';
      currentMissionId = 0;
      currentMissionContext = null;
      missionProofSubmitted = false;
      updateMissionProofLock('pending');
      setMissionTimer('');
      renderRankAssignedPreview(latest, { responseState, proofDone: false });
      return;
    }

    if (proofDone) {
      const step = document.getElementById('mission-proof-single');
      const submitBtn = document.querySelector('[data-mission-proof-submit="1"]');
      const proofInput = document.getElementById('mission-proof-file');
      const status = document.getElementById('mission-proof-status');
      step?.classList.add('done');
      if (submitBtn) submitBtn.textContent = '✅ Proof Submitted';
      if (proofInput) proofInput.disabled = true;
      if (submitBtn) submitBtn.disabled = true;
      if (status) status.textContent = 'Proof already submitted for this mission.';
      updateMissionProofLock('accepted');
      setMissionTimer('');
      renderRankAssignedPreview(latest, { responseState, proofDone: true });
      return;
    }

    const mediaBlock = proofDone ? '' : renderAssignmentMedia(latest.meta_json || '');

    const li = document.createElement('li');
    li.className = 'dynamic-assignment-item';
    li.innerHTML = `
      <strong>🚨 Assigned Crime Mission</strong><br>
      ${missionLabel ? `🧭 <strong>Mission Type:</strong> ${escapeHtmlInline(missionLabel)}<br>` : ''}
      📍 <strong>Area:</strong> ${escapeHtmlInline(details.landmark)}<br>
      🕒 <strong>Assigned:</strong> ${escapeHtmlInline(latest.time_ago || 'Recently')}<br>
      💬 <strong>Note:</strong> ${escapeHtmlInline(missionNote || latest.message || '')}
      <div class="mission-timer-wrap ${responseState === 'accepted' ? '' : 'hidden'}" data-mission-timer-wrap>⏱ <strong>Mission Timer:</strong> <span data-mission-timer-value>00:00:00</span></div>
      ${mediaBlock}
      <div class="mission-response-wrap">${renderAssignmentStatus(responseState)}</div>
      <div class="mission-response-actions">
        <button type="button" class="submit-proof-btn" data-assignment-action="accept" ${responseState === 'pending' ? '' : 'disabled'}>✅ Accept Mission</button>
        <button type="button" class="reject-mission-btn" data-assignment-action="reject" ${responseState === 'pending' ? '' : 'disabled'}>⛔ Reject (Busy)</button>
      </div>
    `;

    li.querySelectorAll('[data-assignment-action]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const action = btn.getAttribute('data-assignment-action');
        await submitAssignmentResponse(Number(latest.id || 0), action, li);
      });
    });

    missionListEl.insertBefore(li, missionListEl.firstChild);
    const step = document.getElementById('mission-proof-single');
    const submitBtn = document.querySelector('[data-mission-proof-submit="1"]');
    const proofInput = document.getElementById('mission-proof-file');
    const status = document.getElementById('mission-proof-status');
    if (proofDone) {
      step?.classList.add('done');
      if (submitBtn) submitBtn.textContent = '✅ Proof Submitted';
      if (proofInput) proofInput.disabled = true;
      if (submitBtn) submitBtn.disabled = true;
      if (status) status.textContent = 'Proof already submitted for this mission.';
    } else {
      step?.classList.remove('done');
      if (submitBtn) submitBtn.textContent = '✅ Submit Proof';
    }
    updateMissionProofLock(responseState);
    setMissionTimer(responseState === 'accepted' && !proofDone ? acceptedAt : '');
    renderRankAssignedPreview(latest, { responseState, proofDone });
  } catch (error) {
    console.error('Failed to load mission assignment details', error);
  } finally {
    missionLoadInFlight = false;
  }
}

// Event listener to open modal on button click (avoid double-open with inline onclick)
if (openMissionBtn && !openMissionBtn.hasAttribute('onclick')) {
  openMissionBtn.addEventListener('click', openMissionModal);
}

// Event listeners to close modal on close button click
closeButtons.forEach(btn => {
  btn.addEventListener('click', closeMissionModal);
});

// Optional: close modal on clicking outside modal-content
if (volunteerMissionModal) {
  volunteerMissionModal.addEventListener('click', (e) => {
    if (e.target === volunteerMissionModal) {
      closeMissionModal();
    }
  });
}

// Optional: close modal on pressing Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && volunteerMissionModal && !volunteerMissionModal.classList.contains('hidden')) {
    closeMissionModal();
  }
});

initMissionFlow();
renderMissionHistory();
loadRankAssignedPreview();
loadVolunteerRankStatus();
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

(function initVolunteerAdminChatDrawer() {
  const launcher = document.getElementById('volunteer-admin-chat-launcher');
  const drawer = document.getElementById('volunteer-admin-chat-drawer');
  const closeBtn = document.getElementById('volunteer-admin-chat-close');
  const input = document.getElementById('volunteer-admin-chat-input');

  if (!launcher || !drawer || !closeBtn) return;

  function setOpen(isOpen) {
    drawer.classList.toggle('is-open', isOpen);
    drawer.setAttribute('aria-hidden', String(!isOpen));
    launcher.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
      setTimeout(() => input?.focus(), 220);
    } else {
      launcher.focus();
    }
  }

  launcher.addEventListener('click', () => {
    setOpen(!drawer.classList.contains('is-open'));
  });

  closeBtn.addEventListener('click', () => setOpen(false));

  drawer.addEventListener('click', (event) => {
    if (event.target === drawer) setOpen(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
      setOpen(false);
    }
  });
})();

(function initVolunteerAdminDbChat() {
  const feed = document.getElementById('volunteer-admin-chat-feed');
  const input = document.getElementById('volunteer-admin-chat-input');
  const sendBtn = document.getElementById('volunteer-admin-chat-send');

  if (!feed || !input || !sendBtn) return;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[ch]));
  }

  async function fetchJson(url, options) {
    const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json.success) throw new Error(json.error || 'Chat request failed');
    return json;
  }

  function renderMessages(messages) {
    if (!messages.length) {
      feed.innerHTML = '<div class="volunteer-admin-chat-date">No messages yet</div>';
      return;
    }

    feed.innerHTML = messages.map(message => {
      const mine = Boolean(message.is_mine);
      const avatar = mine ? '' : '<img src="../Images/default-profile.gif" alt="">';
      return `
        <div class="volunteer-admin-chat-row ${mine ? 'outgoing' : 'incoming'}">
          ${avatar}
          <div class="volunteer-admin-chat-stack">
            <p>${escapeHtml(message.message_text)}</p>
          </div>
        </div>
      `;
    }).join('');
    feed.scrollTop = feed.scrollHeight;
  }

  async function loadMessages() {
    try {
      const json = await fetchJson('../Php/admin_chat_messages.php');
      renderMessages(Array.isArray(json.data) ? json.data : []);
    } catch (error) {
      feed.innerHTML = `<div class="volunteer-admin-chat-date">${escapeHtml(error.message)}</div>`;
    }
  }

  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;
    input.value = '';
    sendBtn.disabled = true;
    try {
      await fetchJson('../Php/admin_chat_send.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });
      await loadMessages();
    } catch (error) {
      alert(error.message);
      input.value = text;
    } finally {
      sendBtn.disabled = false;
      input.focus();
    }
  }

  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
      event.preventDefault();
      sendMessage();
    }
  });

  loadMessages();
  setInterval(loadMessages, 4000);
})();
