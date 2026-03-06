(function () {
  const apiUrl = '../Php/post_interactions.php';
  const postStateCache = new Map();
  const reportCategories = [
    'Spam or misleading',
    'Harassment or hate speech',
    'Violence or dangerous content',
    'Sexual or explicit content',
    'Fraud or scam',
    'Privacy violation',
    'Other'
  ];

  function injectCommentFlashStyle() {
    if (document.getElementById('comment-target-flash-style')) return;
    const style = document.createElement('style');
    style.id = 'comment-target-flash-style';
    style.textContent = `
      .comment-target-flash {
        animation: commentTargetFlash 1.8s ease;
      }
      @keyframes commentTargetFlash {
        0% { background: rgba(255, 210, 105, .65); }
        100% { background: transparent; }
      }
      .comment-input-area,
      .reply-input-area {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
      }
      .comment-editor {
        flex: 1;
        min-height: 40px;
        max-height: 140px;
        overflow-y: auto;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 9px 12px;
        font-size: 14px;
        line-height: 1.35;
        color: #0f172a;
        background: #fff;
      }
      .comment-editor:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, .16);
      }
      .comment-editor:empty:before {
        content: attr(data-placeholder);
        color: #94a3b8;
        pointer-events: none;
      }
      .comment-visibility-select {
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 8px 10px;
        font-size: 13px;
        color: #0f172a;
        background: #fff;
        min-width: 108px;
        align-self: center;
        height: 40px;
      }
      .comment-visibility-select:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, .16);
      }
      .reply-input-area .comment-visibility-select {
        margin-right: 4px;
      }
    `;
    document.head.appendChild(style);
  }

  function injectPostReportStyle() {
    if (document.getElementById('post-report-modal-style')) return;
    const style = document.createElement('style');
    style.id = 'post-report-modal-style';
    style.textContent = `
      .report-btn { cursor: pointer; }
      .post-report-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,.45);
        z-index: 5000;
        padding: 16px;
      }
      .post-report-modal.open { display: flex; }
      .post-report-card {
        width: min(520px, 94vw);
        background: #fff;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 20px 44px rgba(15, 23, 42, .25);
        border: 1px solid #e2e8f0;
      }
      .post-report-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
      }
      .post-report-head h3 { margin: 0; font-size: 19px; color: #0f172a; }
      .post-report-close {
        border: none;
        background: #f1f5f9;
        border-radius: 8px;
        width: 32px;
        height: 32px;
        cursor: pointer;
        font-size: 18px;
      }
      .post-report-subtitle {
        margin: -4px 0 12px;
        color: #64748b;
        font-size: 13px;
      }
      .post-report-users {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 14px;
      }
      .post-report-user {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 10px;
      }
      .post-report-avatar {
        width: 42px;
        height: 42px;
        border-radius: 9999px;
        object-fit: cover;
        border: 1px solid #cbd5e1;
        background: #fff;
      }
      .post-report-user-meta { min-width: 0; }
      .post-report-user-label {
        font-size: 12px;
        color: #64748b;
        line-height: 1.2;
      }
      .post-report-user-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .post-report-field { margin-bottom: 10px; }
      .post-report-field label { display: block; margin-bottom: 6px; font-weight: 600; }
      .post-report-field select,
      .post-report-field textarea {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 10px;
        padding: 8px 10px;
        font-size: 14px;
        font-family: inherit;
        background: #fff;
      }
      .post-report-field textarea { min-height: 92px; resize: vertical; }
      .post-report-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 12px;
      }
      .post-report-actions button {
        border: none;
        border-radius: 10px;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: 600;
      }
      .post-report-cancel { background: #f1f5f9; color: #1e293b; }
      .post-report-send { background: #dc2626; color: #fff; box-shadow: 0 10px 20px rgba(220, 38, 38, .2); }
      @media (max-width: 520px) {
        .post-report-users { grid-template-columns: 1fr; }
      }
    `;
    document.head.appendChild(style);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

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

  async function getJson(url, options) {
    const res = await fetch(url, options);
    let json = null;
    try {
      json = await res.json();
    } catch (_) {
      json = null;
    }

    if (!res.ok || !json || json.success !== true) {
      const message = (json && json.error) ? json.error : 'Request failed';
      throw new Error(message);
    }

    return json;
  }

  function getPostId(postElement) {
    const raw = postElement && postElement.dataset ? postElement.dataset.postId : '';
    const id = Number(raw);
    return Number.isFinite(id) && id > 0 ? id : 0;
  }

  function getCommentModule(postElement) {
    return postElement ? postElement.querySelector('.comment-module') : null;
  }

  function getCommentList(postElement) {
    return postElement ? postElement.querySelector('.comment-module ul') : null;
  }

  function getTopEditor(postElement) {
    const module = getCommentModule(postElement);
    return module ? module.querySelector('.comment-input-area .comment-editor') : null;
  }

  function getTopVisibilitySelect(postElement) {
    const module = getCommentModule(postElement);
    return module ? module.querySelector('.comment-input-area .comment-visibility-select') : null;
  }

  function getDefaultCommentVisibility(postElement) {
    return 'normal';
  }

  function createCommentVisibilitySelect(defaultValue, normalLabel, anonymousLabel) {
    const select = document.createElement('select');
    select.className = 'comment-visibility-select';
    select.innerHTML = `
      <option value="normal">${escapeHtml(normalLabel || 'Normal')}</option>
      <option value="anonymous">${escapeHtml(anonymousLabel || 'Anonymous')}</option>
    `;
    select.value = defaultValue === 'anonymous' ? 'anonymous' : 'normal';
    return select;
  }

  function ensureCommentVisibilityControls() {
    document.querySelectorAll('.post[data-post-id]').forEach(postElement => {
      const module = getCommentModule(postElement);
      if (!module) return;

      const topInputArea = module.querySelector('.comment-input-area');
      if (!topInputArea || topInputArea.classList.contains('reply-input-area')) return;
      if (topInputArea.querySelector('.comment-visibility-select')) return;

      const sendBtn = topInputArea.querySelector('.comment-send-btn');
      const select = createCommentVisibilitySelect(getDefaultCommentVisibility(postElement), 'Normal', 'Anonymous');
      if (sendBtn) {
        topInputArea.insertBefore(select, sendBtn);
      } else {
        topInputArea.appendChild(select);
      }
    });
  }

  function updateLikeUi(postElement, likesCount, likedByMe) {
    const likeBtn = postElement.querySelector('.like-btn');
    if (!likeBtn) return;

    likeBtn.classList.toggle('active', Boolean(likedByMe));
    likeBtn.setAttribute('data-liked', likedByMe ? '1' : '0');

    const iconHtml = '<i class="fa fa-heart"></i>';
    const label = likesCount > 0 ? `${likesCount} Like${likesCount > 1 ? 's' : ''}` : 'Like';
    likeBtn.innerHTML = `${iconHtml} ${label}`;
  }

  function updateCommentButtonUi(postElement, commentsCount) {
    const commentBtn = postElement.querySelector('.comment-btn');
    if (!commentBtn) return;

    const iconHtml = '<i class="fa fa-comment"></i>';
    const label = commentsCount > 0 ? `${commentsCount} Comment${commentsCount > 1 ? 's' : ''}` : 'Comment';
    commentBtn.innerHTML = `${iconHtml} ${label}`;
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

  function getCurrentUserName() {
    const bodyName = String(document.body?.dataset?.currentUserName || '').trim();
    if (bodyName) return bodyName;

    const profileName = document.querySelector('.profile-card h2, .profile-card h3')?.childNodes?.[0]?.textContent;
    const cleanedProfile = String(profileName || '').trim();
    if (cleanedProfile) return cleanedProfile;

    const emailText = document.querySelector('.navbar span')?.textContent;
    const cleanedEmail = String(emailText || '').trim();
    if (cleanedEmail) return cleanedEmail;

    return 'Current User';
  }

  function getCurrentUserPhoto() {
    const bodyPhoto = String(document.body?.dataset?.currentUserPhoto || '').trim();
    if (bodyPhoto) return bodyPhoto;

    const profilePic = document.querySelector('.profile-card .profile-pic')?.getAttribute('src');
    const cleanedProfilePic = String(profilePic || '').trim();
    if (cleanedProfilePic) return cleanedProfilePic;

    const navbarAvatar = document.querySelector('.navbar .user, .navbar-avatar, .navbar img')?.getAttribute('src');
    const cleanedNavbarAvatar = String(navbarAvatar || '').trim();
    if (cleanedNavbarAvatar) return cleanedNavbarAvatar;

    return '../Images/default-profile.gif';
  }

  function normalizePhoto(value) {
    const src = String(value || '').trim();
    return src || '../Images/default-profile.gif';
  }

  function ensurePostReportModal() {
    let modal = document.getElementById('postReportModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'postReportModal';
    modal.className = 'post-report-modal';
    modal.innerHTML = `
      <div class="post-report-card" role="dialog" aria-modal="true" aria-labelledby="postReportTitle">
        <div class="post-report-head">
          <h3 id="postReportTitle">Report Post</h3>
          <button type="button" class="post-report-close" data-post-report-close="1">Ã—</button>
        </div>
        <p class="post-report-subtitle">Help keep the community safe by reporting harmful content.</p>
        <form id="postReportForm">
          <div class="post-report-users">
            <div class="post-report-user">
              <img id="postReportReporterPhoto" class="post-report-avatar" src="../Images/default-profile.gif" alt="Reporter Photo">
              <div class="post-report-user-meta">
                <div class="post-report-user-label">Reporter</div>
                <div class="post-report-user-name" id="postReportReporterName">â€”</div>
              </div>
            </div>
            <div class="post-report-user">
              <img id="postReportReportedPhoto" class="post-report-avatar" src="../Images/default-profile.gif" alt="Reported User Photo">
              <div class="post-report-user-meta">
                <div class="post-report-user-label">Reported User</div>
                <div class="post-report-user-name" id="postReportReportedName">â€”</div>
              </div>
            </div>
          </div>
          <div class="post-report-field">
            <label for="postReportCategory">Category</label>
            <select id="postReportCategory" name="report_category" required>
              ${reportCategories.map(category => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`).join('')}
            </select>
          </div>
          <div class="post-report-field">
            <label for="postReportDetails">Details (optional)</label>
            <textarea id="postReportDetails" name="report_details" placeholder="Write additional details..."></textarea>
          </div>
          <div class="post-report-actions">
            <button type="button" class="post-report-cancel" data-post-report-close="1">Cancel</button>
            <button type="submit" class="post-report-send">Send Report</button>
          </div>
        </form>
      </div>
    `;

    document.body.appendChild(modal);

    modal.addEventListener('click', function (event) {
      const closeBtn = event.target.closest('[data-post-report-close]');
      if (closeBtn || event.target === modal) {
        modal.classList.remove('open');
      }
    });

    return modal;
  }

  function ensureReportButtons() {
    document.querySelectorAll('.post .post-actions').forEach(actions => {
      if (actions.querySelector('.report-btn')) return;
      const reportBtn = document.createElement('span');
      reportBtn.className = 'report-btn';
      reportBtn.innerHTML = '<i class="fa fa-flag"></i> Report';
      actions.appendChild(reportBtn);
    });
  }

  function removeLegacyDemoPosts() {
    document.querySelectorAll('.post[data-post-id]').forEach(post => {
      const hasStatus = post.hasAttribute('data-status');
      const author = String(post.querySelector('.post-header h5')?.textContent || '').trim().toLowerCase();
      const text = String(post.querySelector('p')?.textContent || '').trim().toLowerCase();
      const looksDemo =
        author === 'merry watson'
        || text.includes('many desktop publishing packages and web page editors now use lorem ipsum');

      if (!hasStatus && looksDemo) {
        post.remove();
      }
    });
  }

  function getMaxPostIdInDom() {
    let maxId = 0;
    document.querySelectorAll('.post[data-post-id]').forEach(node => {
      const id = Number(node.getAttribute('data-post-id') || 0);
      if (Number.isFinite(id) && id > maxId) maxId = id;
    });
    return maxId;
  }

  function normalizeAssetPath(path) {
    const raw = String(path || '').trim();
    if (!raw) return '';
    if (/^https?:\/\//i.test(raw) || raw.startsWith('../') || raw.startsWith('./')) return raw;
    return `../${raw.replace(/^\/+/, '')}`;
  }

  function extractImageUrls(row) {
    const urls = [];
    const mediaJsonRaw = String(row?.media_json || '').trim();
    if (mediaJsonRaw) {
      try {
        const parsed = JSON.parse(mediaJsonRaw);
        if (Array.isArray(parsed)) {
          parsed.forEach(item => {
            if (typeof item === 'string' && item.trim() !== '') {
              urls.push(normalizeAssetPath(item));
            }
          });
        }
      } catch (_e) {}
    }

    const mediaType = String(row?.media_type || '').toLowerCase();
    const mediaPath = normalizeAssetPath(row?.media_path || '');
    if (!urls.length && mediaType === 'image' && mediaPath) {
      urls.push(mediaPath);
    }

    return urls;
  }

  function getActiveFeedCategory() {
    const activeBtn = document.querySelector('.post-filter-bar .filter-btn.active');
    if (!activeBtn) return 'all';
    const onclickRaw = String(activeBtn.getAttribute('onclick') || '');
    const match = onclickRaw.match(/filterPosts\('([^']+)'\)/);
    if (match && match[1]) return match[1];
    return 'all';
  }

  function buildPostCardHtml(row) {
    const postId = Number(row?.id || 0);
    if (!postId) return '';

    const authorName = escapeHtml(String(row?.author_name || 'Unknown User'));
    const authorPhoto = escapeHtml(normalizeAssetPath(row?.author_photo || '../Images/default-profile.gif'));
    const category = escapeHtml(String(row?.category || 'general'));
    const text = String(row?.text || '');
    const textHtml = text.trim() ? `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>` : '';
    const createdAt = String(row?.created_at || '');
    const timeAgoLabel = escapeHtml(String(row?.time_ago || formatRelativeTime(createdAt, 'Just now')));
    const isAnonymous = Number(row?.share_anonymous || 0) === 1;
    const defaultCommentMode = 'normal';

    const imageUrls = extractImageUrls(row);
    const mediaType = String(row?.media_type || '').toLowerCase();
    const mediaPath = normalizeAssetPath(row?.media_path || '');

    let mediaHtml = '';
    if (imageUrls.length === 1) {
      mediaHtml = `<img src="${escapeHtml(imageUrls[0])}" class="post-img" alt="Post Image">`;
    } else if (imageUrls.length > 1) {
      mediaHtml = `
        <div class="post-image-grid">
          ${imageUrls.map(url => `<img src="${escapeHtml(url)}" class="post-grid-img" alt="Post Image">`).join('')}
        </div>
      `;
    } else if (mediaType === 'video' && mediaPath) {
      mediaHtml = `
        <video class="post-video" controls preload="metadata">
          <source src="${escapeHtml(mediaPath)}" type="video/mp4">
          Your browser does not support video.
        </video>
      `;
    }

    return `
      <div class="post" id="post-${postId}" data-post-id="${postId}" data-category="${category}" data-status="approved" data-share-anonymous="${isAnonymous ? '1' : '0'}">
        <div class="post-header">
          <img src="${authorPhoto}" alt="Author Photo">
          <div>
            <h5>${authorName}</h5>
            <small class="post-time" data-created-at="${escapeHtml(createdAt)}">${timeAgoLabel}</small>
          </div>
        </div>
        ${textHtml}
        ${mediaHtml}
        <div class="post-actions">
          <span class="like-btn"><i class="fa fa-heart"></i> Like</span>
          <span class="comment-btn"><i class="fa fa-comment"></i> Comment</span>
          <span class="share-btn"><i class="fa fa-share"></i> Share</span>
        </div>
        <section class="comment-module" style="display:none;">
          <div class="comment-input-area">
            <div class="comment-editor" contenteditable="true" data-placeholder="Write a comment..."></div>
            <select class="comment-visibility-select">
              <option value="normal" ${defaultCommentMode === 'normal' ? 'selected' : ''}>Normal</option>
              <option value="anonymous" ${defaultCommentMode === 'anonymous' ? 'selected' : ''}>Anonymous</option>
            </select>
            <button class="comment-send-btn">
              <img src="../Images/send.png" alt="Send">
            </button>
          </div>
          <h4 class="comments-title">All Comments</h4>
          <ul></ul>
        </section>
      </div>
    `;
  }

  function insertNewPosts(rows) {
    if (!Array.isArray(rows) || rows.length === 0) return;
    const feedRoot = document.querySelector('.main-feed');
    if (!feedRoot) return;

    const activeCategory = getActiveFeedCategory();
    const sorted = [...rows].sort((a, b) => Number(a?.id || 0) - Number(b?.id || 0));
    const firstExistingPost = feedRoot.querySelector('.post[data-post-id]');

    sorted.forEach(row => {
      const postId = Number(row?.id || 0);
      if (!postId) return;
      if (document.querySelector(`.post[data-post-id="${postId}"]`)) return;

      const html = buildPostCardHtml(row);
      if (!html.trim()) return;

      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      const postEl = wrapper.firstElementChild;
      if (!postEl) return;

      if (firstExistingPost && firstExistingPost.parentNode === feedRoot) {
        feedRoot.insertBefore(postEl, firstExistingPost);
      } else {
        feedRoot.appendChild(postEl);
      }

      if (activeCategory !== 'all' && String(postEl.getAttribute('data-category') || '') !== activeCategory) {
        postEl.style.display = 'none';
      }

      loadPostState(postEl, { force: true, renderComments: false }).catch(() => {});
    });

    ensureReportButtons();
  }

  function setupApprovedPostAutoSync() {
    let pollInFlight = false;
    let knownMaxPostId = getMaxPostIdInDom();

    async function poll() {
      if (pollInFlight) return;
      pollInFlight = true;
      try {
        const res = await fetch(`../Php/fetch_latest_approved_posts.php?since_id=${knownMaxPostId}`, {
          credentials: 'same-origin',
          cache: 'no-store'
        });
        const json = await res.json().catch(() => null);
        if (!res.ok || !json?.success) return;

        const rows = Array.isArray(json.rows) ? json.rows : [];
        if (rows.length > 0) {
          insertNewPosts(rows);
          rows.forEach(row => {
            const id = Number(row?.id || 0);
            if (id > knownMaxPostId) knownMaxPostId = id;
          });
        }
      } catch (_e) {
      } finally {
        pollInFlight = false;
      }
    }

    setInterval(poll, 10000);
  }

  function openPostReportModal(postElement) {
    if (!postElement) return;
    const postId = getPostId(postElement);
    if (!postId) return;

    const modal = ensurePostReportModal();
    const titleEl = modal.querySelector('#postReportTitle');
    const reporterNameEl = modal.querySelector('#postReportReporterName');
    const reporterPhotoEl = modal.querySelector('#postReportReporterPhoto');
    const reportedNameEl = modal.querySelector('#postReportReportedName');
    const reportedPhotoEl = modal.querySelector('#postReportReportedPhoto');
    const formEl = modal.querySelector('#postReportForm');
    const categoryEl = modal.querySelector('#postReportCategory');
    const detailsEl = modal.querySelector('#postReportDetails');

    const postAuthor = String(postElement.querySelector('.post-header h5')?.textContent || 'Unknown User').trim() || 'Unknown User';
    const postAuthorPhoto = normalizePhoto(postElement.querySelector('.post-header img')?.getAttribute('src'));
    const reporterName = getCurrentUserName();
    const reporterPhoto = normalizePhoto(getCurrentUserPhoto());

    modal.dataset.targetType = 'post';
    modal.dataset.postId = String(postId);
    modal.dataset.commentId = '';
    modal.dataset.postAuthorName = postAuthor;
    if (titleEl) titleEl.textContent = 'Report Post';
    if (reporterNameEl) reporterNameEl.textContent = reporterName;
    if (reporterPhotoEl) reporterPhotoEl.src = reporterPhoto;
    if (reportedNameEl) reportedNameEl.textContent = postAuthor;
    if (reportedPhotoEl) reportedPhotoEl.src = postAuthorPhoto;
    if (formEl) formEl.reset();
    if (categoryEl && !categoryEl.value && categoryEl.options.length > 0) {
      categoryEl.selectedIndex = 0;
    }
    if (detailsEl) detailsEl.value = '';
    modal.classList.add('open');
  }

  function openCommentReportModal(postElement, commentLi) {
    if (!postElement || !commentLi) return;

    const postId = getPostId(postElement);
    const commentId = Number(commentLi.getAttribute('data-comment-id') || 0);
    if (!postId || !commentId) return;

    const modal = ensurePostReportModal();
    const titleEl = modal.querySelector('#postReportTitle');
    const reporterNameEl = modal.querySelector('#postReportReporterName');
    const reporterPhotoEl = modal.querySelector('#postReportReporterPhoto');
    const reportedNameEl = modal.querySelector('#postReportReportedName');
    const reportedPhotoEl = modal.querySelector('#postReportReportedPhoto');
    const formEl = modal.querySelector('#postReportForm');
    const categoryEl = modal.querySelector('#postReportCategory');
    const detailsEl = modal.querySelector('#postReportDetails');

    const commentAuthor = String(commentLi.querySelector('.comment-name')?.textContent || 'Unknown User').trim() || 'Unknown User';
    const commentAuthorPhoto = normalizePhoto(commentLi.querySelector('.comment-img img')?.getAttribute('src'));
    const reporterName = getCurrentUserName();
    const reporterPhoto = normalizePhoto(getCurrentUserPhoto());

    modal.dataset.targetType = 'comment';
    modal.dataset.postId = String(postId);
    modal.dataset.commentId = String(commentId);
    if (titleEl) titleEl.textContent = 'Report Comment / Reply';
    if (reporterNameEl) reporterNameEl.textContent = reporterName;
    if (reporterPhotoEl) reporterPhotoEl.src = reporterPhoto;
    if (reportedNameEl) reportedNameEl.textContent = commentAuthor;
    if (reportedPhotoEl) reportedPhotoEl.src = commentAuthorPhoto;
    if (formEl) formEl.reset();
    if (categoryEl && !categoryEl.value && categoryEl.options.length > 0) {
      categoryEl.selectedIndex = 0;
    }
    if (detailsEl) detailsEl.value = '';
    modal.classList.add('open');
  }

  async function submitPostReportFromModal() {
    const modal = ensurePostReportModal();
    const targetType = String(modal.dataset.targetType || 'post').toLowerCase();
    const postId = Number(modal.dataset.postId || 0);
    if (!postId) {
      throw new Error('Invalid post selected for report');
    }

    const commentId = Number(modal.dataset.commentId || 0);

    const categoryEl = modal.querySelector('#postReportCategory');
    const detailsEl = modal.querySelector('#postReportDetails');
    const sendBtn = modal.querySelector('.post-report-send');
    const payload = {
      post_id: postId,
      report_category: String(categoryEl?.value || ''),
      report_details: String(detailsEl?.value || '').trim()
    };

    if (targetType === 'comment') {
      payload.comment_id = commentId;
    }

    if (!payload.report_category) {
      throw new Error('Please select a report category');
    }

    if (sendBtn) {
      sendBtn.disabled = true;
      sendBtn.textContent = 'Sending...';
    }

    try {
      const endpoint = targetType === 'comment' ? '../Php/submit_comment_report.php' : '../Php/submit_post_report.php';
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });

      const json = await res.json().catch(() => null);
      if (!res.ok || !json || json.success !== true) {
        throw new Error(json?.error || 'Could not send report');
      }

      modal.classList.remove('open');
      const alreadyMsg = targetType === 'comment'
        ? 'You already reported this comment/reply. Admin will review it.'
        : 'You already reported this post. Admin will review it.';
      const successMsg = targetType === 'comment'
        ? 'Comment/reply report submitted successfully.'
        : 'Report submitted successfully.';
      alert(json?.already_exists ? alreadyMsg : successMsg);
    } finally {
      if (sendBtn) {
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Report';
      }
    }
  }

  function openShareModalFallback(postElement) {
    const modal = document.getElementById('postModal');
    if (!modal || !postElement) return;

    const text = postElement.querySelector('p')?.innerText || '';
    const imageSrc = postElement.querySelector('.post-img')?.getAttribute('src') || '';
    const videoSrc = getPostVideoSource(postElement);

    const sharedPostText = document.getElementById('sharedPostText');
    const sharedPostImage = document.getElementById('sharedPostImage');
    const sharedPostVideo = document.getElementById('sharedPostVideo');

    if (sharedPostText) {
      sharedPostText.innerText = text;
      sharedPostText.style.display = text.trim() ? 'block' : 'none';
    }

    if (sharedPostImage) {
      if (imageSrc) {
        sharedPostImage.src = imageSrc;
        sharedPostImage.style.display = 'block';
      } else {
        sharedPostImage.removeAttribute('src');
        sharedPostImage.style.display = 'none';
      }
    }

    if (sharedPostVideo) {
      if (videoSrc) {
        sharedPostVideo.src = videoSrc;
        sharedPostVideo.style.display = 'block';
      } else {
        sharedPostVideo.removeAttribute('src');
        sharedPostVideo.style.display = 'none';
      }
    }

    modal.style.display = 'flex';
  }

  function buildCommentTree(comments) {
    const byParent = new Map();
    const byId = new Map();

    comments.forEach(comment => {
      byId.set(Number(comment.comment_id), comment);
    });

    comments.forEach(comment => {
      const parentId = comment.parent_comment_id == null ? null : Number(comment.parent_comment_id);
      const parentExists = parentId != null && byId.has(parentId);
      const key = parentExists ? parentId : null;
      if (!byParent.has(key)) byParent.set(key, []);
      byParent.get(key).push(comment);
    });

    return byParent;
  }

  function renderCommentItem(comment, treeMap) {
    const commentId = Number(comment.comment_id);
    const children = treeMap.get(commentId) || [];
    const childHtml = children.length
      ? `<ul>${children.map(child => renderCommentItem(child, treeMap)).join('')}</ul>`
      : '';

    return `
      <li data-comment-id="${commentId}">
        <div class="comment" data-comment-id="${commentId}">
          <div class="comment-img">
            <img src="${escapeHtml(comment.actor_photo || '../Images/default-profile.gif')}" alt="">
          </div>
          <div class="comment-content">
            <div class="comment-details">
              <h4 class="comment-name">${escapeHtml(comment.actor_name || 'Someone')}</h4>
              <span class="comment-log">${escapeHtml(formatRelativeTime(comment.created_at, comment.time_ago))}</span>
            </div>
            <div class="comment-desc">
              <p>${escapeHtml(comment.comment_text || '')}</p>
            </div>
            <div class="comment-data">
              <div class="comment-reply">
                <a href="#!" class="comment-reply-action" data-comment-id="${commentId}">Reply</a>
              </div>
              <div class="comment-report">
                <a href="#!" class="comment-report-action" data-comment-id="${commentId}">Report</a>
              </div>
            </div>
          </div>
        </div>
        ${childHtml}
      </li>
    `;
  }

  function renderComments(postElement, comments) {
    const list = getCommentList(postElement);
    if (!list) return;

    if (!Array.isArray(comments) || comments.length === 0) {
      list.innerHTML = '<li class="notifications-empty">No comments yet.</li>';
      return;
    }

    const tree = buildCommentTree(comments);
    const root = tree.get(null) || [];
    list.innerHTML = root.map(comment => renderCommentItem(comment, tree)).join('');
  }

  async function loadPostState(postElement, options = {}) {
    const postId = getPostId(postElement);
    if (!postId) return null;

    if (!options.force && postStateCache.has(postId)) {
      const cached = postStateCache.get(postId);
      updateLikeUi(postElement, cached.likes_count, cached.liked_by_me);
      updateCommentButtonUi(postElement, cached.comments_count);
      if (options.renderComments) {
        renderComments(postElement, cached.comments || []);
      }
      return cached;
    }

    const json = await getJson(`${apiUrl}?post_id=${postId}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store'
    });

    const data = json.data || {};
    postStateCache.set(postId, data);
    updateLikeUi(postElement, Number(data.likes_count || 0), Boolean(data.liked_by_me));
    updateCommentButtonUi(postElement, Number(data.comments_count || 0));
    if (options.renderComments) {
      renderComments(postElement, data.comments || []);
    }
    return data;
  }

  function openCommentModule(postElement) {
    const module = getCommentModule(postElement);
    if (!module) return;
    module.style.display = 'block';
  }

  function toggleCommentModule(postElement) {
    const module = getCommentModule(postElement);
    if (!module) return;

    const visible = !(module.style.display === 'none' || module.style.display === '');
    module.style.display = visible ? 'none' : 'block';

    if (!visible) {
      loadPostState(postElement, { force: true, renderComments: true }).catch(error => {
        console.error('load comments failed', error);
      });
    }
  }

  async function toggleLike(postElement) {
    const postId = getPostId(postElement);
    if (!postId) return;

    const json = await getJson(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ action: 'toggle_like', post_id: postId })
    });

    const data = json.data || {};
    const merged = {
      ...(postStateCache.get(postId) || {}),
      likes_count: Number(data.likes_count || 0),
      liked_by_me: Boolean(data.liked_by_me)
    };
    postStateCache.set(postId, merged);
    updateLikeUi(postElement, merged.likes_count, merged.liked_by_me);
  }

  async function addComment(postElement, text, parentCommentId, commentVisibility) {
    const postId = getPostId(postElement);
    if (!postId) return;

    await getJson(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        action: 'add_comment',
        post_id: postId,
        comment_text: text,
        parent_comment_id: parentCommentId || null,
        comment_visibility: commentVisibility === 'anonymous' ? 'anonymous' : 'normal'
      })
    });

    const fresh = await loadPostState(postElement, { force: true, renderComments: true });
    updateCommentButtonUi(postElement, Number(fresh?.comments_count || 0));
  }

  function removeReplyEditor(container) {
    container.querySelectorAll('.reply-input-area').forEach(node => node.remove());
  }

  function appendReplyEditor(commentLi, parentCommentId) {
    removeReplyEditor(commentLi.closest('.comment-module') || document);

    const box = document.createElement('div');
    box.className = 'reply-input-area';
    box.setAttribute('data-parent-comment-id', String(parentCommentId));
    box.innerHTML = `
      <div class="comment-editor" contenteditable="true" data-placeholder="Write a reply..."></div>
      <select class="comment-visibility-select">
        <option value="normal">Normal</option>
        <option value="anonymous">Anonymous</option>
      </select>
      <button class="comment-send-btn reply-send-btn" type="button">
        <img src="../Images/send.png" alt="Send">
      </button>
    `;

    const visibilitySelect = box.querySelector('.comment-visibility-select');
    if (visibilitySelect) {
      visibilitySelect.value = getDefaultCommentVisibility(commentLi.closest('.post'));
    }

    commentLi.appendChild(box);
    const editor = box.querySelector('.comment-editor');
    if (editor) editor.focus();
  }

  async function goToTarget(postId, commentId) {
    const id = Number(postId || 0);
    if (!id) return;

    const postElement = document.querySelector(`.post[data-post-id="${id}"]`) || document.getElementById(`post-${id}`);
    if (!postElement) return;

    postElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    postElement.classList.add('post-target-flash');
    setTimeout(() => postElement.classList.remove('post-target-flash'), 1800);

    const commentTargetId = Number(commentId || 0);
    if (!commentTargetId) return;

    openCommentModule(postElement);
    await loadPostState(postElement, { force: true, renderComments: true });

    const targetComment = postElement.querySelector(`[data-comment-id="${commentTargetId}"]`);
    if (!targetComment) return;

    targetComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
    targetComment.classList.add('comment-target-flash');
    setTimeout(() => targetComment.classList.remove('comment-target-flash'), 2000);
  }

  function wireEvents() {
    document.addEventListener('click', async function (event) {
      const target = event.target instanceof Element
        ? event.target
        : (event.target && event.target.parentElement ? event.target.parentElement : null);
      if (!target) return;

      const likeBtn = target.closest('.like-btn');
      if (likeBtn) {
        const post = likeBtn.closest('.post');
        if (!post) return;
        event.preventDefault();
        try {
          await toggleLike(post);
        } catch (error) {
          console.error('toggle like failed', error);
          alert('Could not update like right now.');
        }
        return;
      }

      const commentBtn = target.closest('.comment-btn');
      if (commentBtn) {
        const post = commentBtn.closest('.post');
        if (!post) return;
        event.preventDefault();
        toggleCommentModule(post);
        return;
      }

      const shareBtn = target.closest('.share-btn');
      if (shareBtn) {
        const post = shareBtn.closest('.post');
        if (!post) return;
        event.preventDefault();
        openShareModalFallback(post);
        return;
      }

      const reportBtn = target.closest('.report-btn');
      if (reportBtn) {
        const post = reportBtn.closest('.post');
        if (!post) return;
        event.preventDefault();
        openPostReportModal(post);
        return;
      }

      const replyAction = target.closest('.comment-reply-action');
      if (replyAction) {
        event.preventDefault();
        const commentLi = replyAction.closest('li[data-comment-id]');
        if (!commentLi) return;
        const parentCommentId = Number(replyAction.getAttribute('data-comment-id') || 0);
        if (!parentCommentId) return;
        appendReplyEditor(commentLi, parentCommentId);
        return;
      }

      const reportCommentAction = target.closest('.comment-report-action');
      if (reportCommentAction) {
        event.preventDefault();
        const commentLi = reportCommentAction.closest('li[data-comment-id]');
        const post = reportCommentAction.closest('.post');
        if (!commentLi || !post) return;
        openCommentReportModal(post, commentLi);
        return;
      }

      const sendBtn = target.closest('.comment-send-btn');
      if (sendBtn) {
        const post = sendBtn.closest('.post');
        if (!post) return;

        event.preventDefault();

        const replyBox = sendBtn.closest('.reply-input-area');
        const parentCommentId = replyBox ? Number(replyBox.getAttribute('data-parent-comment-id') || 0) : 0;
        const editor = replyBox
          ? replyBox.querySelector('.comment-editor')
          : getTopEditor(post);
        const visibilitySelect = replyBox
          ? replyBox.querySelector('.comment-visibility-select')
          : getTopVisibilitySelect(post);
        const visibility = String(visibilitySelect?.value || getDefaultCommentVisibility(post)).toLowerCase() === 'anonymous'
          ? 'anonymous'
          : 'normal';

        const text = (editor && editor.innerText ? editor.innerText.trim() : '');
        if (!text) return;

        sendBtn.setAttribute('disabled', 'disabled');
        try {
          await addComment(post, text, parentCommentId || null, visibility);
          if (editor) editor.innerText = '';
          if (replyBox) replyBox.remove();
        } catch (error) {
          console.error('add comment failed', error);
          alert('Could not send comment right now.');
        } finally {
          sendBtn.removeAttribute('disabled');
        }
      }
    });

    document.addEventListener('submit', async function (event) {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (form.id !== 'postReportForm') return;

      event.preventDefault();
      try {
        await submitPostReportFromModal();
      } catch (error) {
        console.error('post report submit failed', error);
        alert(error?.message || 'Could not send report now.');
      }
    });
  }

  function bootstrap() {
    injectCommentFlashStyle();
    injectPostReportStyle();
    ensurePostReportModal();
    removeLegacyDemoPosts();
    ensureReportButtons();
    ensureCommentVisibilityControls();
    setupApprovedPostAutoSync();
    wireEvents();

    document.querySelectorAll('.post[data-post-id]').forEach(post => {
      loadPostState(post, { force: true, renderComments: false }).catch(error => {
        console.error('initial post state load failed', error);
      });
    });

    window.SearcharPostInteractions = {
      goToTarget,
      reloadPost(postId) {
        const id = Number(postId || 0);
        if (!id) return Promise.resolve(null);
        const postElement = document.querySelector(`.post[data-post-id="${id}"]`) || document.getElementById(`post-${id}`);
        if (!postElement) return Promise.resolve(null);
        return loadPostState(postElement, { force: true, renderComments: true });
      }
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
