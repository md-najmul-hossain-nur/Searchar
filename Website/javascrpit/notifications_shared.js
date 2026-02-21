(function () {
  const recentNotificationsList = document.getElementById('recentNotificationsList');
  const allNotificationsList = document.getElementById('allNotificationsList');
  const notificationsSeeMoreBtn = document.getElementById('notificationsSeeMore');
  const notificationsDrawer = document.getElementById('notificationsDrawer');
  const notificationsDrawerBackdrop = document.getElementById('notificationsDrawerBackdrop');
  const notificationsDrawerClose = document.getElementById('notificationsDrawerClose');

  if (!recentNotificationsList || !allNotificationsList) {
    return;
  }

  let notificationsCache = [];

  function notificationIconBySource(source) {
    if (source === 'admin') return '🛡️';
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
            <span class="notification-time">${item.time_ago || ''}</span>
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
            <small>${item.time_ago || ''}</small>
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
    closeNotificationsDrawer();
    goToTargetPost(targetPostId);
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

  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const post = this.closest('.post');
      notifyPostInteraction(post && post.dataset ? post.dataset.postId : null, 'like');
    });
  });

  document.querySelectorAll('.comment-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const post = this.closest('.post');
      notifyPostInteraction(post && post.dataset ? post.dataset.postId : null, 'comment');
    });
  });

  document.querySelectorAll('.share-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const post = this.closest('.post');
      notifyPostInteraction(post && post.dataset ? post.dataset.postId : null, 'share');
    });
  });

  loadUserNotifications();
  setInterval(loadUserNotifications, 30000);
})();
