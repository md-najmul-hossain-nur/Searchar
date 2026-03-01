(function () {
  const apiUrl = '../Php/post_interactions.php';
  const postStateCache = new Map();

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
            <img src="${escapeHtml(comment.actor_photo || '../Images/default_profile.png')}" alt="">
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

  async function addComment(postElement, text, parentCommentId) {
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
        parent_comment_id: parentCommentId || null
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
      <button class="comment-send-btn reply-send-btn" type="button">
        <img src="../Images/send.png" alt="Send">
      </button>
    `;

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

        const text = (editor && editor.innerText ? editor.innerText.trim() : '');
        if (!text) return;

        sendBtn.setAttribute('disabled', 'disabled');
        try {
          await addComment(post, text, parentCommentId || null);
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
  }

  function bootstrap() {
    injectCommentFlashStyle();
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
