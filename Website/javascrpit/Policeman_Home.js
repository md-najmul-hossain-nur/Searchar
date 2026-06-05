const requestBroadcastBtn = document.getElementById('requestBroadcastBtn');
const broadcastStatus = document.getElementById('broadcastStatus');
const broadcastLink = document.getElementById('broadcastLink');
let broadcastPollTimer = null;

function setBroadcastUi(state, message) {
  if (broadcastStatus) {
    broadcastStatus.textContent = message || '';
    if (state === 'approved') {
      broadcastStatus.style.color = 'green';
    } else if (state === 'rejected') {
      broadcastStatus.style.color = 'red';
    } else if (state === 'pending') {
      broadcastStatus.style.color = 'orange';
    } else {
      broadcastStatus.style.color = '#444';
    }
  }

  if (broadcastLink) {
    broadcastLink.style.display = state === 'approved' ? 'block' : 'none';
  }

  if (requestBroadcastBtn) {
    if (state === 'approved') {
      requestBroadcastBtn.style.display = 'none';
    } else {
      requestBroadcastBtn.style.display = '';
      requestBroadcastBtn.disabled = state === 'pending';
    }
  }
}

async function fetchBroadcastStatus() {
  try {
    const res = await fetch('../Php/police_fetch_broadcast_status.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (!json?.success) return null;
    return json;
  } catch (error) {
    return null;
  }
}

function startBroadcastPolling() {
  if (broadcastPollTimer) return;
  broadcastPollTimer = setInterval(async () => {
    const data = await fetchBroadcastStatus();
    if (!data) return;
    if (data.status === 'approved') {
      setBroadcastUi('approved', 'Request approved. Join broadcast now.');
      clearInterval(broadcastPollTimer);
      broadcastPollTimer = null;
    } else if (data.status === 'rejected') {
      const reasonText = String(data.reason || '').trim();
      const msg = reasonText ? `Request rejected by admin. Reason: ${reasonText}` : 'Request rejected by admin.';
      setBroadcastUi('rejected', msg);
      clearInterval(broadcastPollTimer);
      broadcastPollTimer = null;
    } else if (data.status === 'pending') {
      setBroadcastUi('pending', 'Request sent. Waiting for admin approval...');
    }
  }, 8000);
}

async function submitBroadcastRequest() {
  if (!requestBroadcastBtn) return;
  const reasonText = window.prompt('Write a short reason for the broadcast request:');
  if (reasonText === null) return;
  const reason = String(reasonText || '').trim();
  if (!reason) {
    alert('Please write a reason before sending the request.');
    return;
  }
  requestBroadcastBtn.disabled = true;
  setBroadcastUi('pending', 'Sending broadcast request...');

  try {
    const res = await fetch('../Php/police_request_broadcast.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'request', reason })
    });
    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.error || 'Request failed');
    }
    setBroadcastUi('pending', json?.message || 'Request sent. Waiting for admin approval...');
    startBroadcastPolling();
  } catch (error) {
    setBroadcastUi('idle', error?.message || 'Could not send request right now.');
    if (requestBroadcastBtn) requestBroadcastBtn.disabled = false;
  }
}

if (requestBroadcastBtn) {
  requestBroadcastBtn.addEventListener('click', submitBroadcastRequest);
}

fetchBroadcastStatus().then((data) => {
  if (!data) return;
  if (data.status === 'approved') {
    setBroadcastUi('approved', 'Request approved. Join broadcast now.');
  } else if (data.status === 'rejected') {
    const reasonText = String(data.reason || '').trim();
    const msg = reasonText ? `Request rejected by admin. Reason: ${reasonText}` : 'Request rejected by admin.';
    setBroadcastUi('rejected', msg);
  } else if (data.status === 'pending') {
    setBroadcastUi('pending', 'Request sent. Waiting for admin approval...');
    startBroadcastPolling();
  }
});


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

const personPhotoInput = document.getElementById('personPhotoInput');
const personPhotoPreviewWrap = document.getElementById('personPhotoPreviewWrap');
const personPhotoPreview = document.getElementById('personPhotoPreview');

if (personPhotoInput && personPhotoPreviewWrap && personPhotoPreview) {
  personPhotoInput.addEventListener('change', function () {
    const file = this.files && this.files[0];

    if (!file) {
      personPhotoPreview.src = '';
      personPhotoPreviewWrap.style.display = 'none';
      return;
    }

    if (!file.type || !file.type.startsWith('image/')) {
      personPhotoPreview.src = '';
      personPhotoPreviewWrap.style.display = 'none';
      return;
    }

    personPhotoPreview.src = URL.createObjectURL(file);
    personPhotoPreviewWrap.style.display = 'block';
  });
}

// Close when clicking outside modals
window.addEventListener('click', function(event) {
  const missingModal = document.getElementById("missingFormModal");
  if (event.target === missingModal) {
    missingModal.style.display = "none";
  }
});

const openAllCasesBtn = document.getElementById('openAllCasesBtn');
const closeAllCasesBtn = document.getElementById('closeAllCasesBtn');
const allCasesModal = document.getElementById('allCasesModal');
const livePublishedBoard = document.getElementById('livePublishedBoard');
const caseFilterPost = document.getElementById('caseFilterPost');
const caseFilterMissing = document.getElementById('caseFilterMissing');
const allCasesTableBody = document.getElementById('allCasesTableBody');
const allCasesFilterEmpty = document.getElementById('allCasesFilterEmpty');

const casePreviewModal = document.getElementById('casePreviewModal');
const casePreviewClose = document.getElementById('casePreviewClose');
const casePreviewCancel = document.getElementById('casePreviewCancel');
const casePreviewPublish = document.getElementById('casePreviewPublish');
const casePreviewTitle = document.getElementById('casePreviewTitle');
const casePreviewDetail = document.getElementById('casePreviewDetail');
const casePreviewContact = document.getElementById('casePreviewContact');
const casePreviewSource = document.getElementById('casePreviewSource');
const casePreviewImage = document.getElementById('casePreviewImage');
const casePreviewAutoThumb = document.getElementById('casePreviewAutoThumb');
const casePreviewExtra = document.getElementById('casePreviewExtra');
const openSolvedCasesBtn = document.getElementById('openSolvedCasesBtn');
const solvedCasesModal = document.getElementById('solvedCasesModal');
const closeSolvedCasesBtn = document.getElementById('closeSolvedCasesBtn');
const solvedCasesTableBody = document.getElementById('solvedCasesTableBody');

[allCasesModal, solvedCasesModal, casePreviewModal].forEach((modalEl) => {
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }
});

function syncCaseModalScrollLock() {
  const hasOpenCaseModal = [allCasesModal, solvedCasesModal, casePreviewModal].some(
    (modalEl) => modalEl && modalEl.style.display === 'flex'
  );
  document.body.style.overflow = hasOpenCaseModal ? 'hidden' : '';
}

function showCaseModal(modalEl) {
  if (!modalEl) return;
  modalEl.style.display = 'flex';
  syncCaseModalScrollLock();
}

function hideCaseModal(modalEl) {
  if (!modalEl) return;
  modalEl.style.display = 'none';
  syncCaseModalScrollLock();
}

const LIVE_BOARD_KEY = 'searchar_police_live_cases_v1';
const SOLVED_CASES_KEY = 'searchar_police_solved_cases_v1';
let previewCasePayload = null;

function normalizeCaseNo(value) {
  return String(value || '').trim().toUpperCase();
}

function looksLikeDummyText(value) {
  const text = String(value || '').trim();
  if (!text) return true;

  const lower = text.toLowerCase();
  const dummyPatterns = [
    'lorem ipsum',
    'dummy',
    'sample',
    'test post',
    'case details pending',
    'asdf',
    'qwerty',
    'dfdsf',
  ];

  if (dummyPatterns.some((p) => lower.includes(p))) {
    return true;
  }

  // Catch keyboard-smash style content such as "rfdgfdgdsf".
  if (/^[a-z]{8,}$/i.test(text)) {
    const vowels = (text.match(/[aeiou]/gi) || []).length;
    if (vowels <= 1) return true;
  }

  return false;
}

function sanitizeCaseRows(rows) {
  const list = Array.isArray(rows) ? rows : [];
  const out = [];
  const seen = new Set();

  list.forEach((item) => {
    if (!item || typeof item !== 'object') return;

    const caseNo = normalizeCaseNo(item.case_no);
    if (!/^(PT|MP)-\d{1,6}$/.test(caseNo)) return;
    if (seen.has(caseNo)) return;

    const details = String(item.details || '').trim();
    if (looksLikeDummyText(details)) return;

    const next = {
      ...item,
      case_no: caseNo,
      type: String(item.type || '').trim() || 'Case',
      details,
      source: String(item.source || '').trim() || 'Case Section',
    };

    out.push(next);
    seen.add(caseNo);
  });

  return out;
}

function purgeStoredDummyCases() {
  try {
    const rawLive = JSON.parse(localStorage.getItem(LIVE_BOARD_KEY) || '[]');
    const rawSolved = JSON.parse(localStorage.getItem(SOLVED_CASES_KEY) || '[]');

    const cleanLive = sanitizeCaseRows(rawLive);
    const cleanSolved = sanitizeCaseRows(rawSolved);

    localStorage.setItem(LIVE_BOARD_KEY, JSON.stringify(cleanLive));
    localStorage.setItem(SOLVED_CASES_KEY, JSON.stringify(cleanSolved));
  } catch (_err) {
    // If malformed storage data exists, reset to safe defaults.
    localStorage.setItem(LIVE_BOARD_KEY, JSON.stringify([]));
    localStorage.setItem(SOLVED_CASES_KEY, JSON.stringify([]));
  }
}

function readLiveCases() {
  try {
    const raw = localStorage.getItem(LIVE_BOARD_KEY);
    if (!raw) return [];
    const data = JSON.parse(raw);
    const sanitized = sanitizeCaseRows(data);
    if (JSON.stringify(sanitized) !== JSON.stringify(Array.isArray(data) ? data : [])) {
      writeLiveCases(sanitized);
    }
    return sanitized;
  } catch (_err) {
    return [];
  }
}

function writeLiveCases(list) {
  try {
    localStorage.setItem(LIVE_BOARD_KEY, JSON.stringify(Array.isArray(list) ? list : []));
  } catch (_err) {}
}

function readSolvedCases() {
  try {
    const raw = localStorage.getItem(SOLVED_CASES_KEY);
    if (!raw) return [];
    const data = JSON.parse(raw);
    const sanitized = sanitizeCaseRows(data);
    if (JSON.stringify(sanitized) !== JSON.stringify(Array.isArray(data) ? data : [])) {
      writeSolvedCases(sanitized);
    }
    return sanitized;
  } catch (_err) {
    return [];
  }
}

function writeSolvedCases(list) {
  try {
    localStorage.setItem(SOLVED_CASES_KEY, JSON.stringify(Array.isArray(list) ? list : []));
  } catch (_err) {}
}

async function callCaseResolutionApi(action, extraPayload = {}) {
  const response = await fetch('../Php/police_case_resolution.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action, ...extraPayload })
  });

  const json = await response.json();
  if (!json?.success) {
    throw new Error(json?.error || 'Case resolution request failed');
  }
  return json;
}

function moveLiveCasesToSolved(caseNos, solvedAtLabel) {
  const targets = new Set((Array.isArray(caseNos) ? caseNos : []).map(v => String(v || '').trim()).filter(Boolean));
  if (!targets.size) return 0;

  const liveRows = readLiveCases();
  const solvedRows = readSolvedCases();
  let moved = 0;

  const keepLive = [];
  liveRows.forEach((row) => {
    const caseNo = String(row?.case_no || '').trim();
    if (!targets.has(caseNo)) {
      keepLive.push(row);
      return;
    }

    const solvedItem = {
      ...row,
      solved_at: solvedAtLabel || new Date().toLocaleString(),
    };

    const solvedIndex = solvedRows.findIndex(item => String(item.case_no || '') === caseNo);
    if (solvedIndex >= 0) {
      solvedRows[solvedIndex] = solvedItem;
    } else {
      solvedRows.unshift(solvedItem);
    }
    moved += 1;
  });

  if (moved > 0) {
    writeLiveCases(keepLive);
    writeSolvedCases(solvedRows);
    renderLivePublishedBoard();
    renderSolvedCasesTable();
    syncPublishedCaseButtons();
  }

  return moved;
}

async function syncClosedCasesFromServer() {
  const liveRows = readLiveCases();
  if (!liveRows.length) return;

  const payloadCases = liveRows.map(row => ({ case_no: row.case_no || '' })).filter(row => row.case_no);
  if (!payloadCases.length) return;

  try {
    const json = await callCaseResolutionApi('sync_published', { cases: payloadCases });
    const closedCaseNos = Array.isArray(json?.closed_case_nos) ? json.closed_case_nos : [];
    moveLiveCasesToSolved(closedCaseNos, new Date().toLocaleString());
  } catch (error) {
    console.error('sync closed cases failed', error);
  }
}

async function pruneSolvedCasesAgainstServer() {
  const solvedRows = readSolvedCases();
  if (!solvedRows.length) return;

  const payloadCases = solvedRows
    .map((row) => ({ case_no: String(row?.case_no || '').trim() }))
    .filter((row) => row.case_no);

  if (!payloadCases.length) return;

  try {
    const json = await callCaseResolutionApi('sync_published', { cases: payloadCases });
    const closedSet = new Set((Array.isArray(json?.closed_case_nos) ? json.closed_case_nos : []).map((v) => String(v || '').trim().toUpperCase()));

    const filteredSolved = solvedRows.filter((row) => closedSet.has(String(row?.case_no || '').trim().toUpperCase()));
    if (filteredSolved.length !== solvedRows.length) {
      writeSolvedCases(filteredSolved);
      renderSolvedCasesTable();
      syncPublishedCaseButtons();
    }
  } catch (error) {
    console.error('prune solved cases failed', error);
  }
}

function renderSolvedCasesTable() {
  if (!solvedCasesTableBody) return;
  const rows = readSolvedCases();
  if (!rows.length) {
    solvedCasesTableBody.innerHTML = '<tr><td colspan="6">No solved cases yet.</td></tr>';
    return;
  }

  solvedCasesTableBody.innerHTML = rows.slice(0, 200).map((item) => {
    const caseNo = item.case_no || 'Case';
    const type = item.type || 'Case';
    const details = item.details || 'No details';
    const source = item.source || 'Case Section';
    const publishedAt = item.published_at || '—';
    const solvedAt = item.solved_at || '—';
    const safeDetails = String(details)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
    return `
      <tr>
        <td><span class="all-cases-case-id">${caseNo}</span></td>
        <td><span class="all-cases-type-chip">${type}</span></td>
        <td class="all-cases-details-cell">${safeDetails}</td>
        <td><span class="all-cases-type-chip">${source}</span></td>
        <td class="all-cases-created">${publishedAt}</td>
        <td class="all-cases-created">${solvedAt}</td>
      </tr>
    `;
  }).join('');
}

async function markCaseAsSolved(caseNo) {
  const targetCaseNo = String(caseNo || '').trim();
  if (!targetCaseNo) return;

  const liveRows = readLiveCases();
  const idx = liveRows.findIndex(item => String(item.case_no || '') === targetCaseNo);
  if (idx < 0) {
    alert('Case not found in live board.');
    return;
  }

  try {
    const json = await callCaseResolutionApi('close_case', { case_no: targetCaseNo });
    moveLiveCasesToSolved([targetCaseNo], new Date().toLocaleString());

    const smsCount = Number(json?.sms_notifications_sent || 0);
    if (smsCount > 0) {
      alert(`Case closed successfully. Mission completed SMS sent: ${smsCount}`);
    } else {
      alert('Case closed successfully and moved to Solved History.');
    }
  } catch (error) {
    console.error('close case failed', error);
    alert(error?.message || 'Could not close case right now. Please try again.');
  }
}

function renderLivePublishedBoard() {
  if (!livePublishedBoard) return;
  const rows = readLiveCases();
  if (!rows.length) {
    livePublishedBoard.innerHTML = '<div style="padding:10px; border:1px dashed #d1d5db; border-radius:10px; color:#6b7280;">No published cases yet.</div>';
    return;
  }

  livePublishedBoard.innerHTML = rows.slice(0, 20).map((item) => {
    const title = `${item.case_no || 'Case'} • ${item.type || 'Alert'}`;
    const details = item.details || 'No details';
    const source = item.source || 'Case Section';
    const publishedAt = item.published_at || '';
    const imageHtml = item.image_url
      ? `<img src="${item.image_url}" alt="Published case image" style="width:100%; max-height:190px; object-fit:contain; background:#fff; border-radius:8px; border:1px solid #fecaca; margin:6px 0 8px;">`
      : `<div style="width:100%; height:140px; border-radius:8px; border:1px solid #fecaca; margin:6px 0 8px; background:linear-gradient(135deg,#fee2e2,#fecaca); display:flex; align-items:center; justify-content:center; color:#991b1b; font-weight:800; text-align:center; padding:10px;">${item.case_no || 'Case'}<br>${item.type || 'Alert'}</div>`;
    return `
      <article style="border-left:5px solid #ef4444; border-radius:10px; background:linear-gradient(135deg,#fff7f7,#fff); padding:10px 12px; box-shadow:0 2px 8px rgba(0,0,0,.08);">
        <div style="display:flex; justify-content:space-between; gap:10px;">
          <strong style="color:#991b1b;">${title}</strong>
          <span style="font-size:12px; color:#166534; font-weight:700;">Published</span>
        </div>
        ${imageHtml}
        <p style="margin:6px 0 8px; color:#111827;">${details}</p>
        <small style="color:#4b5563;">Source: ${source} • ${publishedAt || 'Just now'}</small>
        <div style="margin-top:8px; display:flex; justify-content:flex-end;">
          <button type="button" class="all-cases-action-btn publish js-mark-solved-btn" data-case-no="${item.case_no || ''}" style="background:#166534;">Mark Solved</button>
        </div>
      </article>
    `;
  }).join('');
}

function syncPublishedCaseButtons() {
  if (!allCasesModal) return;
  const publishedSet = new Set(readLiveCases().map(item => String(item.case_no || '')));
  const solvedSet = new Set(readSolvedCases().map(item => String(item.case_no || '')));
  allCasesModal.querySelectorAll('tr[data-case-source-key]').forEach((row) => {
    const publishBtn = row.querySelector('.js-case-publish-btn');
    const previewBtn = row.querySelector('.js-case-preview-btn');
    const caseNo = String(publishBtn?.getAttribute('data-case-no') || previewBtn?.getAttribute('data-case-no') || '');
    if (solvedSet.has(caseNo)) {
      if (publishBtn) {
        publishBtn.disabled = true;
        publishBtn.textContent = 'Solved';
        publishBtn.style.background = '#166534';
        publishBtn.style.cursor = 'not-allowed';
      }
      if (previewBtn) {
        previewBtn.disabled = true;
        previewBtn.textContent = 'Preview Off';
        previewBtn.style.background = '#e5e7eb';
        previewBtn.style.color = '#6b7280';
        previewBtn.style.cursor = 'not-allowed';
      }
      return;
    }
    if (publishedSet.has(caseNo)) {
      if (publishBtn) {
        publishBtn.disabled = true;
        publishBtn.textContent = 'Published';
        publishBtn.style.background = '#16a34a';
        publishBtn.style.cursor = 'not-allowed';
      }
      if (previewBtn) {
        previewBtn.disabled = true;
        previewBtn.textContent = 'Preview Off';
        previewBtn.style.background = '#e5e7eb';
        previewBtn.style.color = '#6b7280';
        previewBtn.style.cursor = 'not-allowed';
      }
    }
  });
}

function applyCaseSourceFilters() {
  if (!allCasesTableBody) return;
  const showPost = !!(caseFilterPost && caseFilterPost.checked);
  const showMissing = !!(caseFilterMissing && caseFilterMissing.checked);

  let visible = 0;
  allCasesTableBody.querySelectorAll('tr[data-case-source-key]').forEach((row) => {
    const sourceKey = String(row.getAttribute('data-case-source-key') || 'post');
    const isPost = sourceKey === 'post';
    const isMissing = sourceKey === 'missing';

    const shouldShow = (isPost && showPost) || (isMissing && showMissing) || (!isPost && !isMissing);
    row.style.display = shouldShow ? '' : 'none';
    if (shouldShow) visible += 1;
  });

  if (allCasesFilterEmpty) {
    allCasesFilterEmpty.style.display = visible === 0 ? '' : 'none';
  }
}

function getCasePayloadFromButton(btn) {
  if (!btn) return null;
  return {
    case_no: btn.getAttribute('data-case-no') || 'Case',
    type: btn.getAttribute('data-case-type') || 'Case',
    details: btn.getAttribute('data-case-details') || 'No details',
    status: btn.getAttribute('data-case-status') || 'open',
    source: btn.getAttribute('data-case-source') || 'Case Section',
    created_at: btn.getAttribute('data-case-created') || '',
    image_url: btn.getAttribute('data-case-image') || '',
    contact_mobile: btn.getAttribute('data-case-contact') || '',
    missing_name: btn.getAttribute('data-case-missing-name') || '',
    extra_details: btn.getAttribute('data-case-extra') || '',
  };
}

function openCasePreview(payload) {
  if (!casePreviewModal || !payload) return;
  previewCasePayload = payload;
  if (casePreviewTitle) casePreviewTitle.textContent = `${payload.case_no} • ${payload.type}`;
  if (casePreviewDetail) casePreviewDetail.textContent = payload.details;
  if (casePreviewContact) casePreviewContact.textContent = payload.contact_mobile ? `Contact: ${payload.contact_mobile}` : 'Contact: N/A';
  if (casePreviewSource) casePreviewSource.textContent = `Source: ${payload.source}`;
  if (casePreviewExtra) casePreviewExtra.textContent = payload.extra_details || '';

  if (casePreviewAutoThumb) {
    casePreviewAutoThumb.innerHTML = `${payload.case_no || 'Case'}<br>${payload.type || 'Alert'}`;
  }

  if (casePreviewImage) {
    if (payload.image_url) {
      casePreviewImage.src = payload.image_url;
      casePreviewImage.style.display = 'block';
      if (casePreviewAutoThumb) casePreviewAutoThumb.style.display = 'none';
    } else {
      casePreviewImage.removeAttribute('src');
      casePreviewImage.style.display = 'none';
      if (casePreviewAutoThumb) casePreviewAutoThumb.style.display = 'flex';
    }
  }
  showCaseModal(casePreviewModal);
}

function closeCasePreview() {
  if (!casePreviewModal) return;
  hideCaseModal(casePreviewModal);
}

function publishCase(payload) {
  if (!payload) return false;
  const prev = readLiveCases();
  const exists = prev.find(item => String(item.case_no || '') === String(payload.case_no || ''));
  if (exists) {
    alert('This case is already published.');
    return false;
  }

  const next = [{
    ...payload,
    published_at: new Date().toLocaleString(),
  }, ...prev];
  writeLiveCases(next);
  renderLivePublishedBoard();

  if (allCasesModal) {
    syncPublishedCaseButtons();
  }

  alert('Case published to Live Board (simulation).');
  return true;
}

window.openCasePreviewFromRow = function (buttonEl) {
  const payload = getCasePayloadFromButton(buttonEl);
  openCasePreview(payload);
};

window.publishCaseFromRow = function (buttonEl) {
  const payload = getCasePayloadFromButton(buttonEl);
  const publishedNow = publishCase(payload);
  if (!publishedNow) return;
  if (buttonEl) {
    buttonEl.disabled = true;
    buttonEl.textContent = 'Published';
    buttonEl.style.background = '#16a34a';
    buttonEl.style.cursor = 'not-allowed';
    const previewBtn = buttonEl.parentElement?.querySelector('.js-case-preview-btn');
    if (previewBtn) {
      previewBtn.disabled = true;
      previewBtn.textContent = 'Preview Off';
      previewBtn.style.background = '#e5e7eb';
      previewBtn.style.color = '#6b7280';
      previewBtn.style.cursor = 'not-allowed';
    }
  }
};

if (openAllCasesBtn && allCasesModal) {
  openAllCasesBtn.addEventListener('click', function () {
    showCaseModal(allCasesModal);
    renderLivePublishedBoard();
    renderSolvedCasesTable();
    syncPublishedCaseButtons();
    applyCaseSourceFilters();
    syncClosedCasesFromServer();
  });
}

if (openSolvedCasesBtn && solvedCasesModal) {
  openSolvedCasesBtn.addEventListener('click', async function () {
    showCaseModal(solvedCasesModal);
    await pruneSolvedCasesAgainstServer();
    renderSolvedCasesTable();
  });
}

if (closeSolvedCasesBtn && solvedCasesModal) {
  closeSolvedCasesBtn.addEventListener('click', function () {
    hideCaseModal(solvedCasesModal);
  });
}

if (closeAllCasesBtn && allCasesModal) {
  closeAllCasesBtn.addEventListener('click', function () {
    hideCaseModal(allCasesModal);
  });
}

document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape' && allCasesModal && allCasesModal.style.display === 'flex') {
    hideCaseModal(allCasesModal);
  }
  if (event.key === 'Escape' && casePreviewModal && casePreviewModal.style.display === 'flex') {
    closeCasePreview();
  }
  if (event.key === 'Escape' && solvedCasesModal && solvedCasesModal.style.display === 'flex') {
    hideCaseModal(solvedCasesModal);
  }
});

if (allCasesModal) {
  allCasesModal.addEventListener('click', function (event) {
    if (event.target === allCasesModal) {
      hideCaseModal(allCasesModal);
      return;
    }

    const solvedBtn = event.target.closest('.js-mark-solved-btn');
    if (solvedBtn) {
      const caseNo = String(solvedBtn.getAttribute('data-case-no') || '');
      markCaseAsSolved(caseNo);
      return;
    }

    const previewBtn = event.target.closest('.js-case-preview-btn');
    if (previewBtn) {
      if (previewBtn.hasAttribute('onclick')) return;
      const payload = getCasePayloadFromButton(previewBtn);
      openCasePreview(payload);
      return;
    }

    const publishBtn = event.target.closest('.js-case-publish-btn');
    if (publishBtn) {
      if (publishBtn.hasAttribute('onclick')) return;
      const payload = getCasePayloadFromButton(publishBtn);
      const publishedNow = publishCase(payload);
      if (!publishedNow) return;
      publishBtn.disabled = true;
      publishBtn.textContent = 'Published';
    }
  });
}

if (solvedCasesModal) {
  solvedCasesModal.addEventListener('click', function (event) {
    if (event.target === solvedCasesModal) {
      hideCaseModal(solvedCasesModal);
    }
  });
}

if (casePreviewModal) {
  casePreviewModal.addEventListener('click', function (event) {
    if (event.target === casePreviewModal) {
      closeCasePreview();
    }
  });
}

if (casePreviewClose) {
  casePreviewClose.addEventListener('click', closeCasePreview);
}
if (casePreviewCancel) {
  casePreviewCancel.addEventListener('click', closeCasePreview);
}
if (casePreviewPublish) {
  casePreviewPublish.addEventListener('click', function () {
    if (!previewCasePayload) return;
    const publishedNow = publishCase(previewCasePayload);
    if (!publishedNow) return;
    closeCasePreview();
  });
}

renderLivePublishedBoard();
renderSolvedCasesTable();
syncPublishedCaseButtons();
purgeStoredDummyCases();
renderLivePublishedBoard();
renderSolvedCasesTable();
syncPublishedCaseButtons();
syncClosedCasesFromServer();
pruneSolvedCasesAgainstServer();
setInterval(syncClosedCasesFromServer, 30000);
if (caseFilterPost) caseFilterPost.addEventListener('change', applyCaseSourceFilters);
if (caseFilterMissing) caseFilterMissing.addEventListener('change', applyCaseSourceFilters);
applyCaseSourceFilters();


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

const monthYear = document.getElementById('monthYear');
const calendarGrid = document.getElementById('calendarGrid');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');

const eventModal = document.getElementById('myEventModal');
const closeModalBtn = document.getElementById('closeMyModal');
const selectedDateText = document.getElementById('selectedDateText');
const eventInput = document.getElementById('eventInput');
const saveEventBtn = document.getElementById('saveEventBtn');

if (monthYear && calendarGrid && prevMonthBtn && nextMonthBtn && eventModal && closeModalBtn && selectedDateText && eventInput && saveEventBtn) {

const now = new Date();
let activeDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
let selectedDate = null;

// Store events by date string "YYYY-MM-DD"
let events = {};

function daysInMonth(year, month) {
  return new Date(year, month + 1, 0).getDate();
}

function formatDate(year, month, day) {
  return `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
}

function renderWeekdays() {
  let weekdaysContainer = document.querySelector('.calendar-weekdays');
  if (!weekdaysContainer) {
    weekdaysContainer = document.createElement('div');
    weekdaysContainer.className = 'calendar-weekdays';
    monthYear.parentNode.insertBefore(weekdaysContainer, calendarGrid);
  }

  const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  weekdaysContainer.innerHTML = '';

  weekdays.forEach(day => {
    const dayDiv = document.createElement('div');
    dayDiv.textContent = day;
    dayDiv.style.textAlign = 'center';
    dayDiv.style.fontWeight = 'bold';
    weekdaysContainer.appendChild(dayDiv);
  });

  weekdaysContainer.style.display = 'grid';
  weekdaysContainer.style.gridTemplateColumns = 'repeat(7, 1fr)';
  weekdaysContainer.style.marginBottom = '8px';
  weekdaysContainer.style.color = '#666';
}

function renderCalendar() {
  const year = activeDate.getFullYear();
  const month = activeDate.getMonth();

  monthYear.textContent = activeDate.toLocaleString('default', { month: 'long', year: 'numeric' });

  renderWeekdays();

  calendarGrid.classList.add('fade-out');
  setTimeout(() => {
    calendarGrid.innerHTML = '';

    const firstDay = new Date(year, month, 1).getDay();
    const totalDays = daysInMonth(year, month);

    // Empty cells before first day
    for(let i = 0; i < firstDay; i++) {
      const emptyCell = document.createElement('div');
      emptyCell.classList.add('empty-cell');
      calendarGrid.appendChild(emptyCell);
    }

    // Days of month
    for(let day = 1; day <= totalDays; day++) {
      const dayDiv = document.createElement('div');
      dayDiv.classList.add('calendar-day');
      dayDiv.textContent = day;

      const dateKey = formatDate(year, month, day);

      // Highlight today
      const today = new Date();
      if (
        day === today.getDate() &&
        month === today.getMonth() &&
        year === today.getFullYear()
      ) {
        dayDiv.classList.add('today');
      }

      // Event icon
      if (events[dateKey]) {
        const img = document.createElement('img');
        img.src = '../Images/calendar.gif';  // Replace with correct path
        img.alt = 'Event';
        img.classList.add('event-icon');
        dayDiv.appendChild(img);
      }

      dayDiv.addEventListener('click', () => {
        selectedDate = dateKey;
        selectedDateText.textContent = `Selected date: ${selectedDate}`;
        eventInput.value = events[selectedDate] ? events[selectedDate].join(', ') : '';
        eventModal.style.display = 'flex';
        eventInput.focus();
      });

      calendarGrid.appendChild(dayDiv);
    }

    calendarGrid.classList.remove('fade-out');
  }, 300);
}

// Handle month navigation with animation
prevMonthBtn.onclick = () => {
  activeDate.setMonth(activeDate.getMonth() - 1);
  renderCalendar();
};

nextMonthBtn.onclick = () => {
  activeDate.setMonth(activeDate.getMonth() + 1);
  renderCalendar();
};

closeModalBtn.onclick = () => {
  eventModal.style.display = 'none';
};

saveEventBtn.onclick = () => {
  if (!selectedDate) return;
  const val = eventInput.value.trim();
  if (val) {
    events[selectedDate] = val.split(',').map(s => s.trim());
  } else {
    delete events[selectedDate];
  }
  eventModal.style.display = 'none';
  renderCalendar();
};

window.onclick = (e) => {
  if (e.target === eventModal) {
    eventModal.style.display = 'none';
  }
};

// Auto update at midnight
setInterval(() => {
  const now = new Date();
  if (now.getHours() === 0 && now.getMinutes() === 0) {
    activeDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    renderCalendar();
  }
}, 60000);

// Initial render
renderCalendar();
}

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

(function initPoliceAdminChatDrawer() {
  const launcher = document.getElementById('police-admin-chat-launcher');
  const drawer = document.getElementById('police-admin-chat-drawer');
  const closeBtn = document.getElementById('police-admin-chat-close');
  const input = document.getElementById('police-admin-chat-input');

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

(function initPoliceAdminDbChat() {
  const feed = document.getElementById('police-admin-chat-feed');
  const input = document.getElementById('police-admin-chat-input');
  const sendBtn = document.getElementById('police-admin-chat-send');

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
      feed.innerHTML = '<div class="police-admin-chat-date">No messages yet</div>';
      return;
    }

    feed.innerHTML = messages.map(message => {
      const mine = Boolean(message.is_mine);
      const avatar = mine ? '' : '<img src="../Images/default-profile.gif" alt="">';
      return `
        <div class="police-admin-chat-row ${mine ? 'outgoing' : 'incoming'}">
          ${avatar}
          <div class="police-admin-chat-stack">
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
      feed.innerHTML = `<div class="police-admin-chat-date">${escapeHtml(error.message)}</div>`;
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
