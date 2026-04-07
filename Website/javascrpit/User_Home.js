const chatWindow = document.getElementById('chatWindow');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');

const volunteerApplyModal = document.getElementById('volunteerApplyModal');
const volunteerApplyNoteInput = document.getElementById('volunteerApplyNote');
const volunteerApplyReady = volunteerApplyModal
  ? volunteerApplyModal.getAttribute('data-profile-ready') === '1'
  : true;
const volunteerApplyMissing = volunteerApplyModal
  ? String(volunteerApplyModal.getAttribute('data-profile-missing') || '').trim()
  : '';

function getVolunteerProfileIncompleteMessage() {
  return 'Please complete your profile first before applying as volunteer. Missing: ' + (volunteerApplyMissing || 'required details') + '.';
}

if (volunteerApplyModal && volunteerApplyModal.parentElement !== document.body) {
  // Keep modal at document root to avoid sidebar stacking-context issues.
  document.body.appendChild(volunteerApplyModal);
}

function openVolunteerApplyModal() {
  if (!volunteerApplyModal) return;
  if (!volunteerApplyReady) {
    alert(getVolunteerProfileIncompleteMessage());
    window.location.href = '../Html/User_Edit_profile.php';
    return;
  }
  document.body.classList.add('volunteer-apply-open');
  volunteerApplyModal.style.display = 'flex';
  volunteerApplyModal.setAttribute('aria-hidden', 'false');
}

function closeVolunteerApplyModal() {
  if (!volunteerApplyModal) return;
  document.body.classList.remove('volunteer-apply-open');
  volunteerApplyModal.style.display = 'none';
  volunteerApplyModal.setAttribute('aria-hidden', 'true');
}

async function submitVolunteerApplication() {
  if (!volunteerApplyReady) {
    alert(getVolunteerProfileIncompleteMessage());
    window.location.href = '../Html/User_Edit_profile.php';
    return;
  }

  const note = String(volunteerApplyNoteInput?.value || '').trim();
  const submitBtn = document.querySelector('.volunteer-apply-submit');
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
  }

  try {
    const res = await fetch('../Php/volunteer_apply.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ note })
    });

    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.error || 'Could not submit volunteer application.');
    }

    alert(json?.message || 'Volunteer application submitted successfully.');
    closeVolunteerApplyModal();
    window.location.reload();
  } catch (error) {
    alert(error?.message || 'Could not submit volunteer application right now.');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Application';
    }
  }
}

if (volunteerApplyModal) {
  volunteerApplyModal.addEventListener('click', function (event) {
    if (event.target === volunteerApplyModal) {
      closeVolunteerApplyModal();
    }
  });
}

window.openVolunteerApplyModal = openVolunteerApplyModal;
window.closeVolunteerApplyModal = closeVolunteerApplyModal;
window.submitVolunteerApplication = submitVolunteerApplication;

function openVolunteerInfo() {
  openVolunteerApplyModal();
}

window.openVolunteerInfo = openVolunteerInfo;

const donationModal = document.getElementById('donationModal');
const donationForm = document.getElementById('donationForm');
const donationAmountInput = document.getElementById('donationAmount');
const donationTxIdInput = document.getElementById('donationTxId');
const donationNumberEl = document.getElementById('donationReceiverNumber');
const copyDonationNumberBtn = document.getElementById('copyDonationNumberBtn');

function openDonationPopup() {
  if (!donationModal) return;
  donationModal.style.display = 'flex';
  donationModal.setAttribute('aria-hidden', 'false');
  document.body.classList.add('donation-modal-open');
}

function closeDonationPopup() {
  if (!donationModal) return;
  donationModal.style.display = 'none';
  donationModal.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('donation-modal-open');
}

window.openDonationPopup = openDonationPopup;
window.closeDonationPopup = closeDonationPopup;

if (donationModal) {
  donationModal.addEventListener('click', function (event) {
    if (event.target === donationModal) {
      closeDonationPopup();
    }
  });
}

if (donationAmountInput) {
  document.querySelectorAll('.donation-quick-amounts button[data-amount]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const amount = btn.getAttribute('data-amount') || '';
      donationAmountInput.value = amount;
      donationAmountInput.focus();
    });
  });
}

if (donationForm) {
  donationForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const amount = Number(donationAmountInput ? donationAmountInput.value : 0);
    const txId = String(donationTxIdInput ? donationTxIdInput.value : '').trim();
    const donorName = String(document.getElementById('donationName')?.value || '').trim();
    const donorPhone = String(document.getElementById('donationPhone')?.value || '').trim();
    const receiverNumber = String(donationNumberEl ? donationNumberEl.textContent : '').trim();
    if (!amount || amount < 50) {
      alert('Please enter a valid donation amount (minimum 50 BDT).');
      return;
    }

    if (!donorName) {
      alert('Please enter your full name.');
      return;
    }

    if (!donorPhone) {
      alert('Please enter your mobile number.');
      return;
    }

    if (txId.length < 6) {
      alert('Please enter a valid Transaction ID (TxID).');
      return;
    }

    const submitBtn = donationForm.querySelector('.donation-submit');
    if (submitBtn) submitBtn.disabled = true;

    try {
      const res = await fetch('../Php/save_donation.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          donor_name: donorName,
          donor_phone: donorPhone,
          amount,
          tx_id: txId,
          receiver_number: receiverNumber
        })
      });

      const json = await res.json();
      if (!json?.success) {
        throw new Error(json?.error || 'Could not submit donation.');
      }

      alert('Thank you. Your donation request with TxID has been submitted successfully.');
      donationForm.reset();
      closeDonationPopup();
    } catch (error) {
      alert(error?.message || 'Could not submit donation right now.');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

if (copyDonationNumberBtn && donationNumberEl) {
  copyDonationNumberBtn.addEventListener('click', async function () {
    const number = String(donationNumberEl.textContent || '').trim();
    if (!number) return;

    try {
      await navigator.clipboard.writeText(number);
      copyDonationNumberBtn.textContent = 'Copied';
      setTimeout(() => {
        copyDonationNumberBtn.textContent = 'Copy';
      }, 1200);
    } catch (_) {
      alert('Could not copy number automatically. Please copy manually: ' + number);
    }
  });
}

if (sendBtn && chatInput && chatWindow) {
  sendBtn.addEventListener('click', () => {
    const msg = chatInput.value.trim();
    if (msg === '') return;

    const msgDiv = document.createElement('div');
    msgDiv.classList.add('message', 'sent');
    msgDiv.textContent = msg;

    chatWindow.appendChild(msgDiv);
    chatInput.value = '';
    chatWindow.scrollTop = chatWindow.scrollHeight;
  });

  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      sendBtn.click();
    }
  });
}

const modal = document.getElementById("postModal");
const feed = document.getElementById("post-feed");
const mediaPreview = document.getElementById("mediaPreview");
let selectedImages = [];
let selectedVideo = null;
let isShareMode = false;
let shareContext = null;

function imageFileKey(file) {
  return `${file.name}__${file.size}__${file.lastModified}`;
}

function renderSelectedImagesPreview() {
  if (!mediaPreview) return;
  if (!selectedImages.length) {
    mediaPreview.innerHTML = '';
    return;
  }

  const countLabel = selectedImages.length > 1 ? `<p class="post-media-hint">${selectedImages.length} photos selected</p>` : '';
  mediaPreview.innerHTML = `
    ${countLabel}
    <div class="post-media-grid">
      ${selectedImages.map((file, index) => `
        <div class="post-media-item">
          <img src="${URL.createObjectURL(file)}" alt="Preview image">
          <button type="button" class="post-media-remove-btn" data-image-index="${index}" aria-label="Remove image">&times;</button>
        </div>
      `).join('')}
    </div>
  `;
}

if (mediaPreview) {
  mediaPreview.addEventListener('click', function (event) {
    const removeBtn = event.target.closest('.post-media-remove-btn');
    if (!removeBtn) return;

    const index = Number(removeBtn.getAttribute('data-image-index'));
    if (!Number.isInteger(index) || index < 0 || index >= selectedImages.length) return;

    selectedImages.splice(index, 1);
    renderSelectedImagesPreview();
  });
}

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

function getCurrentUserDisplayName() {
  const bodyName = String(document.body?.dataset?.currentUserName || '').trim();
  if (bodyName) return bodyName;
  const profileName = String(document.querySelector('.profile-card h2 span')?.textContent || '').trim();
  return profileName || 'You';
}

function getCurrentUserPhoto() {
  const profilePhoto = String(document.querySelector('.profile-card .profile-pic')?.getAttribute('src') || '').trim();
  return profilePhoto || '../Images/default-profile.gif';
}

function escapeWithLineBreaks(value) {
  return escapeHtml(value).replace(/\n/g, '<br>');
}

function prependPendingPostToFeed(postData) {
  const filterBar = document.querySelector('.filter-bar-section');
  if (!filterBar) return;

  const nowIso = new Date().toISOString();
  const tempId = `local-pending-${Date.now()}`;
  const safeText = String(postData?.text || '').trim();
  const textHtml = safeText ? `<p>${escapeWithLineBreaks(safeText)}</p>` : '';
  const imageUrls = Array.isArray(postData?.imageUrls) ? postData.imageUrls : [];
  const videoUrl = String(postData?.videoUrl || '').trim();

  let mediaHtml = '';
  if (imageUrls.length > 1) {
    mediaHtml = `
      <div class="post-image-grid">
        ${imageUrls.map((url) => `<img src="${escapeHtml(url)}" class="post-grid-img" alt="Post Image">`).join('')}
      </div>
    `;
  } else if (imageUrls.length === 1) {
    mediaHtml = `<img src="${escapeHtml(imageUrls[0])}" class="post-img" alt="Post Image">`;
  } else if (videoUrl) {
    mediaHtml = `
      <video class="post-video" controls controlsList="nodownload nofullscreen noplaybackrate" disablePictureInPicture oncontextmenu="return false;" preload="metadata">
        <source src="${escapeHtml(videoUrl)}" type="video/mp4">
        Your browser does not support video.
      </video>
    `;
  }

  const displayName = postData?.isAnonymous ? 'Anonymous' : getCurrentUserDisplayName();
  const displayPhoto = postData?.isAnonymous ? '../Images/anonymously.gif' : getCurrentUserPhoto();

  const article = document.createElement('div');
  article.className = 'post';
  article.id = tempId;
  article.setAttribute('data-post-id', tempId);
  article.setAttribute('data-category', String(postData?.category || 'general'));
  article.setAttribute('data-status', 'pending');
  article.setAttribute('data-share-anonymous', postData?.isAnonymous ? '1' : '0');
  article.innerHTML = `
    <div class="post-header">
      <img src="${escapeHtml(displayPhoto)}" alt="Author Photo" onerror="this.onerror=null;this.src='../Images/default-profile.gif';">
      <div>
        <h5>${escapeHtml(displayName)}</h5>
        <small class="post-time" data-created-at="${escapeHtml(nowIso)}">Just now</small>
      </div>
    </div>
    <div style="margin-top:6px; font-size:12px; font-weight:700; color:#9a3412;">Pending admin review</div>
    ${textHtml}
    ${mediaHtml}
    <div class="post-actions">
      <span class="like-btn"><i class="fa fa-heart"></i> Like</span>
      <span class="comment-btn"><i class="fa fa-comment"></i> Comment</span>
    </div>
    <section class="comment-module" style="display:none;">
      <div class="comment-input-area">
        <div class="comment-editor" contenteditable="true" data-placeholder="Write a comment..."></div>
        <button class="comment-send-btn">
          <img src="../Images/send.png" alt="Send">
        </button>
      </div>
      <h4 class="comments-title">All Comments</h4>
      <ul></ul>
    </section>
  `;

  const placeholder = Array.from(document.querySelectorAll('.post')).find((el) => {
    const text = String(el.textContent || '').toLowerCase();
    return text.includes('no published posts yet');
  });
  if (placeholder) {
    placeholder.remove();
  }

  const firstPost = filterBar.parentElement?.querySelector('.post');
  if (firstPost) {
    firstPost.parentElement.insertBefore(article, firstPost);
  } else {
    filterBar.insertAdjacentElement('afterend', article);
  }

  initFeedVideoCenterPlayButtons();
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
      sharedPostAuthorImage.src = shareContext?.authorImage || '../Images/default-profile.gif';
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
  if (!files.length) {
    this.value = '';
    return;
  }

  const invalid = files.find(file => !file.type || !file.type.startsWith('image/'));
  if (invalid) {
    alert('Only image files are allowed in Photo upload.');
    this.value = '';
    return;
  }

  const dedupe = new Map(selectedImages.map(file => [imageFileKey(file), file]));
  files.forEach(file => dedupe.set(imageFileKey(file), file));
  const merged = Array.from(dedupe.values());

  if (merged.length > 5) {
    alert('You can upload maximum 5 images in one post.');
    selectedImages = merged.slice(0, 5);
  } else {
    selectedImages = merged;
  }

  selectedVideo = null;
  document.getElementById("videoUpload").value = "";
  renderSelectedImagesPreview();
  this.value = '';
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
  const shareAnonymous = document.getElementById('anonymousShareToggle')?.checked ? '1' : '0';
  fd.append('share_facebook', shareFb);
  fd.append('share_anonymous', shareAnonymous);
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
        const pendingPost = {
          text: finalText,
          category,
          isAnonymous: shareAnonymous === '1',
          imageUrls: selectedImages.map((file) => URL.createObjectURL(file)),
          videoUrl: selectedVideo ? URL.createObjectURL(selectedVideo) : ''
        };
        closeModal();
        prependPendingPostToFeed(pendingPost);
        alert('Saved successfully. Post added instantly (pending admin review).');
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

const comboMissionsList = document.getElementById('comboMissionsList');
const comboRankTitle = document.getElementById('comboRankTitle');
const comboRankXp = document.getElementById('comboRankXp');
const comboRankProgressText = document.getElementById('comboRankProgressText');
const comboRankNext = document.getElementById('comboRankNext');
const comboRankNeed = document.getElementById('comboRankNeed');
const comboRankProgressBar = document.getElementById('comboRankProgressBar');
const comboMissionStats = document.getElementById('comboMissionStats');
const volunteerMissionModal = document.getElementById('volunteerMissionModal');
const missionProofFileInput = document.getElementById('mission-proof-file');
const missionProofPreview = document.getElementById('mission-proof-preview');
const missionProofStatus = document.getElementById('mission-proof-status');
const missionProofSubmitBtn = document.querySelector('[data-mission-proof-submit="1"]');
const missionHistoryList = document.getElementById('mission-history-list');
const missionHistoryEmpty = document.getElementById('mission-history-empty');
const missionAssignedList = document.getElementById('mission-assigned-list');
const missionAssignedEmpty = document.getElementById('mission-assigned-empty');
const comboCertificateUnlock = document.getElementById('comboCertificateUnlock');
const comboCertificateMessage = document.getElementById('comboCertificateMessage');
const comboViewCertificateBtn = document.getElementById('comboViewCertificateBtn');

if (volunteerMissionModal && volunteerMissionModal.parentElement !== document.body) {
  // Keep modal at document root so fixed backdrop covers the full page.
  document.body.appendChild(volunteerMissionModal);
}

function syncMissionModalOffset() {
  if (!volunteerMissionModal) return;
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  const rect = navbar.getBoundingClientRect();
  const offset = Math.max(0, Math.ceil(rect.bottom));
  volunteerMissionModal.style.setProperty('--modal-navbar-offset', `${offset}px`);
}

let comboMissionsCache = [];
let comboCertificateSnapshot = {
  unlocked: false,
  rank: 'Bronze Volunteer',
  points: 0,
  completedMissions: 0,
  volunteerName: 'Volunteer'
};

const COMBO_RANKS = [
  { key: 'bronze', title: 'Bronze Volunteer', nextTitle: 'Silver Responder', minXp: 0, nextXp: 380 },
  { key: 'silver', title: 'Silver Responder', nextTitle: 'Gold Guardian', minXp: 380, nextXp: 900 },
  { key: 'gold', title: 'Gold Guardian', nextTitle: 'Platinum Sentinel', minXp: 900, nextXp: 1700 },
  { key: 'platinum', title: 'Platinum Sentinel', nextTitle: null, minXp: 1700, nextXp: null }
];

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getComboCertificateVolunteerName() {
  const dataName = String(comboCertificateUnlock?.dataset?.volunteerName || '').trim();
  if (dataName) return dataName;
  const profileName = document.querySelector('.profile-card h2')?.childNodes?.[0]?.textContent;
  return String(profileName || '').trim() || 'Volunteer';
}

function getComboRankUnlockMessage(rankRaw) {
  const rank = String(rankRaw || '').trim();
  const safeRank = escapeHtml(rank);
  if (rank === 'Silver Responder') {
    return `🎉 Congratulations! You’ve reached <strong>${safeRank}</strong>! Certificate unlocked.`;
  }
  if (rank === 'Gold Guardian') {
    return `🏆 Incredible progress! You are now <strong>${safeRank}</strong>. Your upgraded certificate is ready.`;
  }
  if (rank === 'Platinum Sentinel') {
    return `👑 Legendary achievement! You reached <strong>${safeRank}</strong>. Your elite certificate is ready.`;
  }
  return '';
}

function getComboCertificateRankTheme(rankRaw) {
  const rank = String(rankRaw || '').toLowerCase();
  if (rank.includes('platinum')) {
    return { key: 'platinum', ribbon: 'PLATINUM', bg: [246, 251, 255], primary: [39, 84, 122], secondary: [146, 186, 214], nameColor: [26, 78, 125], bodyColor: [23, 37, 51], divider: [147, 197, 253] };
  }
  if (rank.includes('gold')) {
    return { key: 'gold', ribbon: 'GOLD', bg: [255, 252, 241], primary: [161, 98, 7], secondary: [253, 224, 71], nameColor: [146, 64, 14], bodyColor: [69, 26, 3], divider: [251, 191, 36] };
  }
  if (rank.includes('silver')) {
    return { key: 'silver', ribbon: 'SILVER', bg: [248, 250, 252], primary: [71, 85, 105], secondary: [203, 213, 225], nameColor: [51, 65, 85], bodyColor: [30, 41, 59], divider: [148, 163, 184] };
  }
  return { key: 'bronze', ribbon: 'BRONZE', bg: [255, 248, 244], primary: [146, 64, 14], secondary: [253, 186, 116], nameColor: [180, 83, 9], bodyColor: [67, 20, 7], divider: [234, 179, 122] };
}

function buildComboCertificatePdf() {
  const jsPdfLib = window.jspdf?.jsPDF;
  if (!jsPdfLib) {
    throw new Error('Certificate PDF library is not loaded.');
  }

  const doc = new jsPdfLib({ orientation: 'landscape', unit: 'pt', format: 'a4' });
  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();
  const theme = getComboCertificateRankTheme(comboCertificateSnapshot.rank);

  doc.setFillColor(...theme.bg);
  doc.rect(0, 0, pageWidth, pageHeight, 'F');

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
  doc.setFont('times', 'normal');
  doc.setFontSize(17);
  doc.text('Certificate of Achievement', pageWidth / 2, 132, { align: 'center' });

  doc.setTextColor(...theme.nameColor);
  doc.setFont('times', 'bold');
  doc.setFontSize(36);
  doc.text(comboCertificateSnapshot.volunteerName, pageWidth / 2, 238, { align: 'center' });

  doc.setTextColor(...theme.bodyColor);
  doc.setFont('times', 'normal');
  doc.setFontSize(16);
  doc.text(`in recognition of outstanding service as a ${comboCertificateSnapshot.rank}`, pageWidth / 2, 286, { align: 'center' });
  doc.text(`and successfully completing ${comboCertificateSnapshot.completedMissions} mission(s) with dedication and courage`, pageWidth / 2, 314, { align: 'center' });

  doc.setDrawColor(...theme.divider);
  doc.setLineWidth(1.1);
  doc.line((pageWidth / 2) - 215, 332, (pageWidth / 2) + 215, 332);

  const issuedOn = new Date().toLocaleDateString();
  doc.setTextColor(31, 41, 55);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(12);
  doc.text(`Issued on: ${issuedOn}`, 78, pageHeight - 114);

  doc.setDrawColor(120, 120, 120);
  doc.line(pageWidth - 288, pageHeight - 120, pageWidth - 82, pageHeight - 120);
  doc.setTextColor(...theme.bodyColor);
  doc.setFont('times', 'italic');
  doc.setFontSize(14);
  doc.text('SEARCHAR Admin', pageWidth - 182, pageHeight - 128, { align: 'center' });

  return doc;
}

function bindComboCertificateActions() {
  if (!comboViewCertificateBtn) return;
  comboViewCertificateBtn.disabled = !comboCertificateSnapshot.unlocked;
  comboViewCertificateBtn.onclick = () => {
    if (!comboCertificateSnapshot.unlocked) {
      alert('Complete more XP to unlock the certificate.');
      return;
    }
    try {
      const doc = buildComboCertificatePdf();
      doc.output('dataurlnewwindow');
    } catch (e) {
      alert(e?.message || 'Could not open certificate PDF.');
    }
  };
}

function renderComboCertificate(rank, xp, summary) {
  if (!comboCertificateUnlock || !comboCertificateMessage) return;

  const completed = Number(summary?.completed || 0);
  const unlocked = rank && rank.title !== 'Bronze Volunteer' && completed > 0;

  comboCertificateSnapshot = {
    unlocked,
    rank: String(rank?.title || 'Bronze Volunteer'),
    points: Number(xp || 0),
    completedMissions: completed,
    volunteerName: getComboCertificateVolunteerName()
  };

  if (!unlocked) {
    comboCertificateUnlock.classList.add('hidden');
    comboCertificateUnlock.setAttribute('hidden', 'hidden');
    bindComboCertificateActions();
    return;
  }

  const unlockMessage = getComboRankUnlockMessage(comboCertificateSnapshot.rank)
    || `🎉 Congratulations! You reached <strong>${escapeHtml(comboCertificateSnapshot.rank)}</strong>! Certificate unlocked.`;

  comboCertificateMessage.innerHTML = unlockMessage;
  comboCertificateUnlock.classList.remove('hidden');
  comboCertificateUnlock.removeAttribute('hidden');
  bindComboCertificateActions();
}

function missionCounts(items) {
  const summary = { accepted: 0, completed: 0, busy: 0, autoClosed: 0 };
  if (!Array.isArray(items)) return summary;

  for (const item of items) {
    const status = String(item?.status || '').toLowerCase();
    const response = String(item?.response_status || '').toLowerCase();
    const flags = [status, response];

    if (flags.includes('accepted')) summary.accepted += 1;
    if (flags.includes('completed')) summary.completed += 1;
    if (flags.includes('rejected_busy')) summary.busy += 1;
    if (flags.includes('closed_by_police')) summary.autoClosed += 1;
  }

  return summary;
}

function computeXp(summary) {
  return (summary.accepted * 10) + (summary.completed * 20) + (summary.autoClosed * 2);
}

function resolveRank(xp) {
  let current = COMBO_RANKS[0];
  for (const rank of COMBO_RANKS) {
    if (xp >= rank.minXp) current = rank;
  }

  if (!current.nextXp || !current.nextTitle) {
    return {
      ...current,
      progressPercent: 100,
      needXp: 0
    };
  }

  const span = Math.max(1, current.nextXp - current.minXp);
  const clamped = Math.max(0, Math.min(span, xp - current.minXp));
  const progressPercent = Math.round((clamped / span) * 100);
  const needXp = Math.max(0, current.nextXp - xp);

  return {
    ...current,
    progressPercent,
    needXp
  };
}

function renderComboRank(items) {
  if (!comboRankTitle || !comboRankXp || !comboRankProgressText || !comboRankNext || !comboRankNeed || !comboRankProgressBar || !comboMissionStats) {
    return;
  }

  const summary = missionCounts(items);
  const xp = computeXp(summary);
  const rank = resolveRank(xp);

  comboRankTitle.textContent = rank.title;
  comboRankXp.textContent = `${xp} XP`;
  comboRankProgressText.textContent = `${rank.progressPercent}%`;

  if (rank.nextTitle && rank.nextXp) {
    comboRankNext.textContent = `Next: ${rank.nextTitle}`;
    comboRankNeed.textContent = `Need ${rank.needXp} XP`;
  } else {
    comboRankNext.textContent = 'Top Rank Achieved';
    comboRankNeed.textContent = 'No next rank';
  }

  comboRankProgressBar.style.width = `${rank.progressPercent}%`;
  comboMissionStats.textContent = `Accepted ${summary.accepted} • Completed ${summary.completed} • Busy ${summary.busy}`;
  renderComboCertificate(rank, xp, summary);
}

function missionStatusLabel(item) {
  const status = String(item?.status || '').toLowerCase();
  const response = String(item?.response_status || '').toLowerCase();
  if (response === 'completed' || status === 'completed') return 'Completed';
  if (response === 'accepted') return 'Accepted';
  if (response === 'rejected_busy') return 'Rejected (Busy)';
  return 'Assigned';
}

function renderComboMissionResponseBadge(item) {
  const response = String(item?.response_status || '').toLowerCase();
  const status = String(item?.status || '').toLowerCase();

  if (response === 'accepted' || status === 'accepted') {
    return '<span class="mission-response accepted">Accepted</span>';
  }
  if (response === 'rejected_busy' || status === 'rejected_busy') {
    return '<span class="mission-response rejected">Rejected (Busy)</span>';
  }
  if (response === 'completed' || status === 'completed') {
    return '<span class="mission-response accepted">Completed</span>';
  }
  return '<span class="mission-response pending">Pending response</span>';
}

function isComboMissionAccepted(item) {
  const status = String(item?.status || '').toLowerCase();
  const response = String(item?.response_status || '').toLowerCase();
  return response === 'accepted' || status === 'accepted' || response === 'completed' || status === 'completed';
}

function isMissionClosed(item) {
  const status = String(item?.status || '').toLowerCase();
  const response = String(item?.response_status || '').toLowerCase();
  return ['completed', 'rejected_busy', 'closed_by_police'].includes(status)
    || ['completed', 'rejected_busy', 'closed_by_police'].includes(response);
}

function renderComboMissionMedia(metaJsonText) {
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
    if (t.includes('pdf') || /\.pdf(\?|#|$)/.test(u)) return 'pdf';
    return 'file';
  };

  const mediaHtml = media.map((item) => {
    const url = resolveMediaUrl(item?.url || '');
    if (!url) return '';
    const kind = inferKind(item?.type || '', url);

    if (kind === 'video') {
      return `<video class="assignment-media" controls preload="metadata" src="${escapeHtml(url)}"></video>`;
    }

    if (kind === 'image') {
      return `
        <div class="assignment-media-item">
          <img class="assignment-media" src="${escapeHtml(url)}" alt="Mission evidence" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
          <a class="assignment-media-fallback" href="${escapeHtml(url)}" target="_blank" rel="noopener" style="display:none;">Open evidence file</a>
        </div>
      `;
    }

    if (kind === 'pdf' || kind === 'file') {
      return `<a class="assignment-media-fallback" href="${escapeHtml(url)}" target="_blank" rel="noopener">Open evidence file</a>`;
    }

    return '';
  }).join('');

  if (!mediaHtml) return '';
  return `<div class="assignment-media-wrap"><div class="assignment-media-title">Evidence</div>${mediaHtml}</div>`;
}

function getCurrentComboMissionForProof() {
  if (!Array.isArray(comboMissionsCache) || comboMissionsCache.length === 0) return null;
  const accepted = comboMissionsCache.find(item => !isMissionClosed(item) && isComboMissionAccepted(item));
  if (accepted) return accepted;
  const active = comboMissionsCache.find(item => !isMissionClosed(item));
  return active || comboMissionsCache[0] || null;
}

async function submitComboMissionResponse(item, action) {
  const notificationId = Number(item?.source_notification_id || 0);
  const missionId = Number(item?.mission_id || 0);

  if (!notificationId && !missionId) {
    alert('Mission response metadata is missing.');
    return;
  }

  try {
    const res = await fetch('../Php/volunteer_assignment_response.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        notification_id: notificationId,
        mission_id: missionId,
        action
      })
    });

    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.error || 'Failed to update mission response.');
    }

    await loadComboMissions();
  } catch (error) {
    alert(error?.message || 'Could not update mission response right now.');
  }
}

function renderMissionHistoryFromCombo() {
  if (!missionHistoryList || !missionHistoryEmpty) return;

  const completed = (Array.isArray(comboMissionsCache) ? comboMissionsCache : []).filter(item => {
    const status = String(item?.status || '').toLowerCase();
    const response = String(item?.response_status || '').toLowerCase();
    return status === 'completed' || response === 'completed';
  });

  if (completed.length === 0) {
    missionHistoryList.innerHTML = '';
    missionHistoryEmpty.style.display = 'block';
    return;
  }

  missionHistoryEmpty.style.display = 'none';
  missionHistoryList.innerHTML = completed.map((item) => {
    const title = escapeHtml(String(item?.mission_title || 'Mission'));
    const caseRef = escapeHtml(String(item?.case_ref || '').trim());
    const assignedAt = escapeHtml(String(item?.assigned_at || '').trim());

    return `
      <div class="mission-history-item">
        <strong>${title}</strong>
        ${caseRef ? `<div>Case: ${caseRef}</div>` : ''}
        ${assignedAt ? `<small>Assigned: ${assignedAt}</small>` : ''}
      </div>
    `;
  }).join('');
}

function renderAssignedMissionsInModal() {
  if (!missionAssignedList || !missionAssignedEmpty) return;

  const missions = (Array.isArray(comboMissionsCache) ? comboMissionsCache : []).filter((item) => {
    const status = String(item?.status || '').toLowerCase();
    const response = String(item?.response_status || '').toLowerCase();

    // Keep only currently actionable missions in this panel.
    if (['completed', 'rejected_busy', 'closed_by_police'].includes(status)) return false;
    if (['completed', 'rejected_busy', 'closed_by_police'].includes(response)) return false;
    return response === 'pending' || response === 'accepted' || response === '';
  });

  if (missions.length === 0) {
    missionAssignedList.innerHTML = '';
    missionAssignedEmpty.style.display = 'block';
    missionAssignedEmpty.textContent = 'No active assigned mission right now.';
    return;
  }

  missionAssignedEmpty.style.display = 'none';
  missionAssignedList.innerHTML = missions.map((item) => {
    const title = escapeHtml(String(item?.mission_title || 'Mission'));
    const details = escapeHtml(String(item?.mission_details || '').trim());
    const location = escapeHtml(String(item?.mission_location || '').trim());
    const caseRef = escapeHtml(String(item?.case_ref || '').trim());
    const assignedAt = escapeHtml(String(item?.assigned_at || '').trim());
    const statusLabel = escapeHtml(missionStatusLabel(item));
    const mediaBlock = renderComboMissionMedia(String(item?.meta_json || ''));
    const missionId = Number(item?.mission_id || 0);
    const response = String(item?.response_status || '').toLowerCase();
    const status = String(item?.status || '').toLowerCase();
    const canRespond = response === 'pending' && !['completed', 'rejected_busy', 'closed_by_police'].includes(status);

    return `
      <article class="mission-assigned-item" data-combo-mission-id="${missionId}">
        <div class="mission-assigned-top">
          <strong>${title}</strong>
          <span>${statusLabel}</span>
        </div>
        ${caseRef ? `<p><strong>Case:</strong> ${caseRef}</p>` : ''}
        ${location ? `<p><strong>Location:</strong> ${location}</p>` : ''}
        ${details ? `<p>${details}</p>` : ''}
        <div class="mission-response-wrap">${renderComboMissionResponseBadge(item)}</div>
        <div class="mission-response-actions">
          <button type="button" class="submit-proof-btn" data-combo-mission-action="accept" ${canRespond ? '' : 'disabled'}>✅ Accept Mission</button>
          <button type="button" class="reject-mission-btn" data-combo-mission-action="reject" ${canRespond ? '' : 'disabled'}>⛔ Reject (Busy)</button>
        </div>
        ${mediaBlock}
        ${assignedAt ? `<small>Assigned: ${assignedAt}</small>` : ''}
      </article>
    `;
  }).join('');

  const cardNodes = missionAssignedList.querySelectorAll('[data-combo-mission-id]');
  cardNodes.forEach((card) => {
    const missionId = Number(card.getAttribute('data-combo-mission-id') || 0);
    const missionItem = missions.find(m => Number(m?.mission_id || 0) === missionId);
    if (!missionItem) return;

    card.querySelectorAll('[data-combo-mission-action]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const action = String(btn.getAttribute('data-combo-mission-action') || '');
        if (!['accept', 'reject'].includes(action)) return;

        card.querySelectorAll('[data-combo-mission-action]').forEach(b => {
          b.disabled = true;
        });

        await submitComboMissionResponse(missionItem, action);
      });
    });
  });
}

function updateMissionProofUiState() {
  if (!missionProofStatus || !missionProofSubmitBtn) return;

  const mission = getCurrentComboMissionForProof();
  if (!mission) {
    missionProofStatus.textContent = 'No mission available right now.';
    missionProofSubmitBtn.disabled = true;
    return;
  }

  if (isMissionClosed(mission)) {
    missionProofStatus.textContent = 'This mission is already closed/completed.';
    missionProofSubmitBtn.disabled = true;
    return;
  }

  if (!isComboMissionAccepted(mission)) {
    missionProofStatus.textContent = 'Please accept the mission first to submit proof.';
    missionProofSubmitBtn.disabled = true;
    return;
  }

  missionProofStatus.textContent = `Selected mission: ${String(mission?.mission_title || 'Mission')}`;
  missionProofSubmitBtn.disabled = false;
}

function openMissionModal() {
  if (!volunteerMissionModal) return;
  syncMissionModalOffset();
  volunteerMissionModal.classList.remove('hidden');
  volunteerMissionModal.focus();
  renderAssignedMissionsInModal();
  renderMissionHistoryFromCombo();
  updateMissionProofUiState();
}

function closeMissionModal() {
  if (!volunteerMissionModal) return;
  volunteerMissionModal.classList.add('hidden');
}

window.openMissionModal = openMissionModal;
window.closeMissionModal = closeMissionModal;

function renderComboMissions(items) {
  comboMissionsCache = Array.isArray(items) ? items : [];
  renderComboRank(items);

  if (!comboMissionsList) return;
  if (!Array.isArray(items) || items.length === 0) {
    comboMissionsList.innerHTML = '<p class="combo-missions-empty">No assigned crime mission right now.</p>';
    return;
  }

  const activeMission = getCurrentComboMissionForProof();
  if (!activeMission) {
    comboMissionsList.innerHTML = '<p class="combo-missions-empty">No active mission right now. Use View Missions for details.</p>';
  } else {
    const title = escapeHtml(String(activeMission?.mission_title || 'Assigned Mission'));
    const statusLabel = escapeHtml(missionStatusLabel(activeMission));
    comboMissionsList.innerHTML = `
      <article class="combo-mission-item">
        <div class="combo-mission-top">
          <strong>${title}</strong>
          <span>${statusLabel}</span>
        </div>
        <p>Open <strong>View Missions</strong> for full mission popup.</p>
      </article>
    `;
  }

  if (volunteerMissionModal && !volunteerMissionModal.classList.contains('hidden')) {
    renderAssignedMissionsInModal();
    renderMissionHistoryFromCombo();
    updateMissionProofUiState();
  }
}

async function loadComboMissions() {
  if (!comboMissionsList) return;
  try {
    const res = await fetch('../Php/fetch_combo_missions.php', {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    const json = await res.json();
    const missions = json && json.success && Array.isArray(json.data) ? json.data : [];
    renderComboMissions(missions);
  } catch (error) {
    comboMissionsCache = [];
    comboMissionsList.innerHTML = '<p class="combo-missions-empty">Could not load combo missions right now.</p>';
    updateMissionProofUiState();
  }
}

if (missionProofFileInput && missionProofPreview) {
  missionProofFileInput.addEventListener('change', function () {
    const file = missionProofFileInput.files && missionProofFileInput.files[0] ? missionProofFileInput.files[0] : null;
    missionProofPreview.innerHTML = '';
    if (!file) return;

    if (file.type.startsWith('image/')) {
      const img = document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Mission proof image';
      missionProofPreview.appendChild(img);
      return;
    }

    if (file.type.startsWith('video/')) {
      const video = document.createElement('video');
      video.src = URL.createObjectURL(file);
      video.controls = true;
      missionProofPreview.appendChild(video);
      return;
    }

    const p = document.createElement('p');
    p.textContent = `Selected file: ${file.name}`;
    missionProofPreview.appendChild(p);
  });
}

if (missionProofSubmitBtn) {
  missionProofSubmitBtn.addEventListener('click', async function () {
    const selectedFile = missionProofFileInput?.files && missionProofFileInput.files[0] ? missionProofFileInput.files[0] : null;
    if (!selectedFile) {
      alert('Please select a proof file first.');
      return;
    }

    const mission = getCurrentComboMissionForProof();
    const missionId = Number(mission?.mission_id || 0);
    if (!missionId) {
      alert('No active assigned mission found for proof submission.');
      return;
    }

    missionProofSubmitBtn.disabled = true;
    const prevText = missionProofSubmitBtn.textContent;
    missionProofSubmitBtn.textContent = 'Submitting...';
    if (missionProofStatus) missionProofStatus.textContent = 'Uploading proof...';

    try {
      const fd = new FormData();
      fd.append('mission_id', String(missionId));
      fd.append('proof_file', selectedFile, selectedFile.name);

      const res = await fetch('../Php/volunteer_submit_mission_proof.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd
      });
      const json = await res.json();

      if (!json?.success) {
        throw new Error(json?.error || 'Could not submit mission proof.');
      }

      if (missionProofStatus) missionProofStatus.textContent = 'Proof submitted successfully.';
      alert('Mission proof submitted successfully.');
      missionProofFileInput.value = '';
      if (missionProofPreview) missionProofPreview.innerHTML = '';
      await loadComboMissions();
    } catch (error) {
      if (missionProofStatus) missionProofStatus.textContent = error?.message || 'Could not submit mission proof.';
      alert(error?.message || 'Could not submit mission proof.');
    } finally {
      missionProofSubmitBtn.disabled = false;
      missionProofSubmitBtn.textContent = prevText || '✅ Submit Proof';
    }
  });
}

if (volunteerMissionModal) {
  syncMissionModalOffset();
  window.addEventListener('resize', syncMissionModalOffset);
  volunteerMissionModal.addEventListener('click', function (event) {
    if (event.target === volunteerMissionModal) {
      closeMissionModal();
    }
  });
}

const recentNotificationsList = document.getElementById('recentNotificationsList');
const allNotificationsList = document.getElementById('allNotificationsList');
const notificationsSeeMoreBtn = document.getElementById('notificationsSeeMore');
const notificationsDrawer = document.getElementById('notificationsDrawer');
const notificationsDrawerBackdrop = document.getElementById('notificationsDrawerBackdrop');
const notificationsDrawerClose = document.getElementById('notificationsDrawerClose');
const notificationsDrawerFooter = notificationsDrawer ? notificationsDrawer.querySelector('.notifications-drawer-footer') : null;
const messengerFab = document.getElementById('messengerFab');
const messengerDrawer = document.getElementById('messengerDrawer');
const messengerBackdrop = document.getElementById('messengerBackdrop');
const messengerClose = document.getElementById('messengerClose');
const messengerInput = document.getElementById('messengerInput');
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

function normalizeNotificationText(value) {
  const text = String(value || '');
  if (!text) return '';

  const looksBroken = /ðŸ|Ã.|â.|ï¸|Â./.test(text);
  if (!looksBroken || typeof TextDecoder === 'undefined') {
    return text;
  }

  try {
    const bytes = new Uint8Array(Array.from(text, ch => ch.charCodeAt(0) & 0xff));
    const decoded = new TextDecoder('utf-8', { fatal: false }).decode(bytes);
    return decoded || text;
  } catch (_) {
    return text;
  }
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
      let targetCommentId = '';
      try {
        const meta = item && item.meta_json ? JSON.parse(item.meta_json) : null;
        if (meta && Number(meta.comment_id) > 0) {
          targetCommentId = String(Number(meta.comment_id));
        }
      } catch (_) {}
      const levelClass = item.level === 'warning' || item.source === 'admin' || item.source === 'police'
        ? 'notification-item warning'
        : 'notification-item';
      const readClass = item.is_read ? 'is-read' : 'is-unread';
      return `
        <li class="${levelClass} ${readClass}" data-notification-id="${item.id || 0}" data-target-post-id="${item.target_post_id || ''}" data-target-comment-id="${targetCommentId}">
          <div class="notification-icon">${notificationIconBySource(item.source)}</div>
          <div class="notification-body">
            <div class="notification-title">${normalizeNotificationText(item.title || 'Notification')}</div>
            <div class="notification-message">${normalizeNotificationText(item.message || '')}</div>
          </div>
          <span class="notification-time">${formatRelativeTime(item.created_at, item.time_ago)}</span>
        </li>
      `;
    }).join('');
  }

  return list.map(item => {
    let targetCommentId = '';
    try {
      const meta = item && item.meta_json ? JSON.parse(item.meta_json) : null;
      if (meta && Number(meta.comment_id) > 0) {
        targetCommentId = String(Number(meta.comment_id));
      }
    } catch (_) {}
    const levelClass = item.level === 'warning' || item.source === 'admin' || item.source === 'police'
      ? 'drawer-notification warning'
      : 'drawer-notification';
    const readClass = item.is_read ? 'is-read' : 'is-unread';
    return `
      <article class="${levelClass} ${readClass}" data-notification-id="${item.id || 0}" data-target-post-id="${item.target_post_id || ''}" data-target-comment-id="${targetCommentId}">
        <div class="drawer-notification-icon">${notificationIconBySource(item.source)}</div>
        <div class="drawer-notification-content">
          <h4>${normalizeNotificationText(item.title || 'Notification')}</h4>
          <p>${normalizeNotificationText(item.message || '')}</p>
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
  const targetCommentId = Number(row.getAttribute('data-target-comment-id'));
  const notification = notificationsCache.find(item => Number(item.id) === notificationId) || null;

  await markNotificationRead(notificationId);

  const title = String(notification && notification.title ? notification.title : '').toLowerCase();
  const message = String(notification && notification.message ? notification.message : '').toLowerCase();
  const profileIncomplete = document.body && document.body.getAttribute('data-profile-incomplete') === '1';
  const isProfileReminder = profileIncomplete && title.includes('admin reminder') && message.includes('complete your profile');

  if (isProfileReminder) {
    closeNotificationsDrawer();
    window.location.href = '../Html/User_Edit_profile.php';
    return;
  }

  if (targetPostId > 0) {
    closeNotificationsDrawer();
    if (window.SearcharPostInteractions && typeof window.SearcharPostInteractions.goToTarget === 'function') {
      window.SearcharPostInteractions.goToTarget(targetPostId, targetCommentId || 0);
    } else {
      goToTargetPost(targetPostId);
    }
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

function openMessengerDrawer() {
  if (!messengerDrawer || !messengerBackdrop) return;
  messengerDrawer.classList.add('open');
  messengerBackdrop.classList.add('open');
  messengerDrawer.setAttribute('aria-hidden', 'false');
  if (messengerInput) {
    messengerInput.focus();
  }
}

function closeMessengerDrawer() {
  if (!messengerDrawer || !messengerBackdrop) return;
  messengerDrawer.classList.remove('open');
  messengerBackdrop.classList.remove('open');
  messengerDrawer.setAttribute('aria-hidden', 'true');
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

if (messengerFab) {
  messengerFab.addEventListener('click', openMessengerDrawer);
}

if (messengerClose) {
  messengerClose.addEventListener('click', closeMessengerDrawer);
}

if (messengerBackdrop) {
  messengerBackdrop.addEventListener('click', closeMessengerDrawer);
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
    closeVolunteerApplyModal();
    closeMissionModal();
    closeDonationPopup();
    closeNotificationsDrawer();
    closeMessengerDrawer();
  }
});

loadUserNotifications();
loadComboMissions();
setInterval(loadUserNotifications, 30000);
setInterval(loadComboMissions, 45000);
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