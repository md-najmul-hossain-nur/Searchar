(function () {
  const recentNotificationsList = document.getElementById('recentNotificationsList');
  const allNotificationsList = document.getElementById('allNotificationsList');
  const notificationsSeeMoreBtn = document.getElementById('notificationsSeeMore');
  const notificationsDrawer = document.getElementById('notificationsDrawer');
  const notificationsDrawerBackdrop = document.getElementById('notificationsDrawerBackdrop');
  const notificationsDrawerClose = document.getElementById('notificationsDrawerClose');
  const notificationsDrawerFooter = notificationsDrawer ? notificationsDrawer.querySelector('.notifications-drawer-footer') : null;

  if (!recentNotificationsList || !allNotificationsList) {
    return;
  }

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

  function normalizeNotificationText(value) {
    const text = String(value || '');
    if (!text) return '';

    // Decode common mojibake when UTF-8 bytes were interpreted as latin1.
    const looksBroken = /\u00f0\u0178|\u00c3.|\u00e2.|\u00ef\u00b8|\u00c2./.test(text);
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

  function notificationIconBySource(source) {
    if (source === 'admin') return '<img src="../Images/businessman.gif" alt="Admin" style="width:22px;height:22px;border-radius:999px;object-fit:cover;">';
    if (source === 'police') return '👮';
    if (source === 'comment') return '💬';
    if (source === 'like') return '❤️';
    if (source === 'share') return '🔁';
    if (source === 'sms') return '📩';
    return '🔔';
  }

  function renderNotificationItems(items, options) {
    const compact = options && options.compact === true;
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
    await markNotificationRead(notificationId);

    if (targetPostId > 0) {
      closeNotificationsDrawer();
      if (window.SearcharPostInteractions && typeof window.SearcharPostInteractions.goToTarget === 'function') {
        window.SearcharPostInteractions.goToTarget(targetPostId, targetCommentId || 0);
      } else {
        goToTargetPost(targetPostId);
      }
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

  recentNotificationsList.addEventListener('click', function (event) {
    const row = event.target.closest('[data-notification-id]');
    if (!row) return;
    handleNotificationClick(row);
  });

  allNotificationsList.addEventListener('click', function (event) {
    const row = event.target.closest('[data-notification-id]');
    if (!row) return;
    handleNotificationClick(row);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeNotificationsDrawer();
    }
  });

  loadUserNotifications();
  setInterval(loadUserNotifications, 30000);
})();
