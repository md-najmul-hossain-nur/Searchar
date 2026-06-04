(function () {
  const SOCKET_URL = 'http://localhost:3000';
  const state = {
    me: null,
    socket: null,
    conversations: [],
    currentConversationId: null,
    currentConversation: null,
    messages: [],
    ready: false,
  };

  const el = {
    fab: document.getElementById('messengerFab'),
    drawer: document.getElementById('messengerDrawer'),
    backdrop: document.getElementById('messengerBackdrop'),
    close: document.getElementById('messengerClose'),
    input: document.getElementById('messengerInput'),
    send: document.querySelector('.messenger-send'),
    search: document.querySelector('.messenger-search'),
    list: document.querySelector('.messenger-list'),
    contact: document.querySelector('.messenger-contact'),
    chat: document.querySelector('.messenger-chat'),
    feed: document.querySelector('.messenger-chat-feed'),
    top: document.querySelector('.messenger-chat-top'),
    title: document.querySelector('.messenger-chat-top strong'),
    subtitle: document.querySelector('.messenger-chat-top small'),
  };

  const admin = {
    section: document.getElementById('chat-management'),
    list: document.getElementById('chat-management-list'),
    feed: document.getElementById('chat-management-feed'),
    input: document.getElementById('chat-management-input'),
    send: document.getElementById('chat-management-send'),
    search: document.getElementById('chat-management-search'),
    roleFilter: document.getElementById('chat-management-role-filter'),
    refresh: document.getElementById('chat-management-refresh'),
    title: document.getElementById('chat-management-title'),
    subtitle: document.getElementById('chat-management-subtitle'),
  };

  if (!el.fab || !el.drawer || !el.backdrop || !el.close) {
    return;
  }

  function esc(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fmtTime(value) {
    if (!value) return '';
    const dt = new Date(value);
    if (Number.isNaN(dt.getTime())) return '';
    return dt.toLocaleString([], { hour: 'numeric', minute: '2-digit', month: 'short', day: 'numeric' });
  }

  function ensureBadge() {
    if (!el.fab || el.fab.querySelector('.messenger-badge')) return;
    const badge = document.createElement('span');
    badge.className = 'messenger-badge';
    badge.textContent = '0';
    badge.hidden = true;
    el.fab.appendChild(badge);
  }

  function setBadge(count) {
    ensureBadge();
    const badge = el.fab.querySelector('.messenger-badge');
    if (!badge) return;
    const value = Number(count || 0);
    badge.textContent = String(value > 99 ? '99+' : value);
    badge.hidden = value <= 0;
  }

  async function loadSocketClient() {
    if (window.io) return;
    await new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = `${SOCKET_URL}/socket.io/socket.io.js`;
      script.async = true;
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('Unable to load Socket.IO client'));
      document.head.appendChild(script);
    });
  }

  async function fetchJson(url, options) {
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const json = await response.json().catch(() => ({}));
    if (!response.ok || !json || json.success === false) {
      throw new Error(json?.error || `Request failed: ${response.status}`);
    }
    return json;
  }

  async function whoAmI() {
    if (state.me) return state.me;
    const json = await fetchJson('../Php/chat_whoami.php');
    state.me = {
      user_id: Number(json.user_id || 0),
      role: String(json.role || 'user').toLowerCase(),
    };
    return state.me;
  }

  function displayNameForConversation(conversation) {
    if (!conversation) return 'Admin Desk';
    if (state.me?.role === 'admin') {
      return conversation.display_name || `${String(conversation.role || 'user')} #${conversation.user_id}`;
    }
    return 'Admin Desk';
  }

  function conversationSubtitle(conversation) {
    if (!conversation) return 'Active now';
    const count = Number(conversation.unread_count || 0);
    const lastAt = conversation.last_message_at || conversation.updated_at || '';
    const prefix = count > 0 ? `${count} unread` : 'Active now';
    return lastAt ? `${prefix} • ${fmtTime(lastAt)}` : prefix;
  }

  function renderConversationList(conversations) {
    if (!el.list) return;

    const listPane = el.list.querySelector('.messenger-contact') ? el.list : el.list;
    const searchValue = String(el.search?.value || '').trim().toLowerCase();
    const filtered = (Array.isArray(conversations) ? conversations : []).filter((item) => {
      if (!searchValue) return true;
      const haystack = `${displayNameForConversation(item)} ${item.role || ''} ${item.user_id || ''}`.toLowerCase();
      return haystack.includes(searchValue);
    });

    const html = filtered.map((item) => {
      const active = Number(item.id) === Number(state.currentConversationId);
      const unread = Number(item.unread_count || 0);
      const avatarLabel = state.me?.role === 'admin' ? String(item.role || 'user').toUpperCase().slice(0, 2) : 'AD';
      return `
        <button type="button" class="messenger-contact messenger-contact--list${active ? ' is-active' : ''}" data-conversation-id="${esc(item.id)}" data-conversation-user-id="${esc(item.user_id)}" data-conversation-role="${esc(item.role || 'user')}">
          <div class="avatar messenger-avatar-fallback">${esc(avatarLabel)}</div>
          <div class="messenger-contact-copy">
            <strong>${esc(displayNameForConversation(item))}</strong>
            <small>${esc(conversationSubtitle(item))}</small>
          </div>
          ${unread > 0 ? `<span class="messenger-unread-badge">${esc(unread)}</span>` : ''}
        </button>
      `;
    }).join('');

    const empty = filtered.length === 0 ? '<div class="messenger-empty-state">No conversations yet.</div>' : '';
    listPane.innerHTML = `
      <div class="messenger-list-title">${state.me?.role === 'admin' ? 'Conversations' : 'Chat with Admin'}</div>
      <input type="text" class="messenger-search" placeholder="Search" aria-label="Search chats">
      <div class="messenger-conversation-list">${html || empty}</div>
    `;

    el.search = listPane.querySelector('.messenger-search');

    if (state.me?.role === 'admin') {
      const list = listPane.querySelector('.messenger-conversation-list');
      if (list) {
        list.addEventListener('click', (event) => {
          const btn = event.target.closest('[data-conversation-id]');
          if (!btn) return;
          openConversation(Number(btn.getAttribute('data-conversation-id')) || 0);
        });
      }
      if (el.search) {
        el.search.addEventListener('input', () => renderConversationList(state.conversations));
      }
    }
  }

  function renderAdminSection(conversations) {
    if (!admin.section || !admin.list) return;
    const searchValue = String(admin.search?.value || '').trim().toLowerCase();
    const roleFilter = String(admin.roleFilter?.value || 'all').toLowerCase();
    const filtered = (Array.isArray(conversations) ? conversations : []).filter((item) => {
      const display = displayNameForConversation(item).toLowerCase();
      const role = String(item.role || '').toLowerCase();
      const matchSearch = !searchValue || `${display} ${role} ${item.user_id || ''}`.includes(searchValue);
      const matchRole = roleFilter === 'all' || role === roleFilter;
      return matchSearch && matchRole;
    });

    admin.list.innerHTML = filtered.length
      ? filtered.map((item) => {
          const active = Number(item.id) === Number(state.currentConversationId);
          const unread = Number(item.unread_count || 0);
          return `
            <button type="button" class="chat-management-list-item${active ? ' is-active' : ''}" data-conversation-id="${esc(item.id)}">
              <div class="chat-management-list-item__title">${esc(displayNameForConversation(item))}</div>
              <div class="chat-management-list-item__meta">${esc(String(item.role || 'user'))} #${esc(item.user_id)}</div>
              <div class="chat-management-list-item__footer">
                <span>${esc(conversationSubtitle(item))}</span>
                ${unread > 0 ? `<span class="messenger-unread-badge">${esc(unread)}</span>` : ''}
              </div>
            </button>
          `;
        }).join('')
      : '<div class="messenger-empty-state">No conversations found.</div>';

    admin.list.querySelectorAll('[data-conversation-id]').forEach((btn) => {
      btn.addEventListener('click', () => openConversation(Number(btn.getAttribute('data-conversation-id')) || 0));
    });

    if (admin.search) {
      admin.search.oninput = () => renderAdminSection(state.conversations);
    }
    if (admin.roleFilter) {
      admin.roleFilter.onchange = () => renderAdminSection(state.conversations);
    }
    if (admin.refresh) {
      admin.refresh.onclick = () => loadConversations();
    }
  }

  function renderMessages(messages) {
    const feedEl = el.feed || admin.feed;
    if (!feedEl) return;
    const me = state.me || { user_id: 0, role: 'user' };
    const rows = Array.isArray(messages) ? messages : [];
    feedEl.innerHTML = rows.length
      ? rows.map((message) => {
          const mine = Number(message.sender_id || 0) === Number(me.user_id) && String(message.sender_role || '').toLowerCase() === String(me.role || '').toLowerCase();
          const time = fmtTime(message.created_at);
          return `
            <div class="messenger-bubble ${mine ? 'me' : 'them'}">
              <div class="messenger-bubble__text">${esc(message.content || '')}</div>
              <div class="messenger-bubble__meta">${esc(time)}</div>
            </div>
          `;
        }).join('')
      : '<div class="messenger-empty-state">No messages yet. Start the conversation.</div>';

    requestAnimationFrame(() => {
      feedEl.scrollTop = feedEl.scrollHeight;
    });

    if (admin.feed && feedEl !== admin.feed) {
      admin.feed.innerHTML = feedEl.innerHTML;
    }
  }

  async function markRead(conversationId) {
    if (!conversationId) return;
    try {
      await fetchJson('../Php/chat_mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ conversation_id: String(conversationId) }),
      });
    } catch (error) {
      console.warn('chat mark read failed', error);
    }
  }

  async function loadConversations() {
    const me = await whoAmI();
    const json = await fetchJson('../Php/chat_fetch_conversations.php');
    state.conversations = Array.isArray(json.rows) ? json.rows : [];

    if (me.role !== 'admin' && state.conversations.length === 0) {
      const created = await fetchJson('../Php/chat_open_or_create.php');
      if (created?.conversation) {
        state.conversations = [created.conversation];
      }
    }

    if (me.role === 'admin' && state.conversations.length > 0 && !state.currentConversationId) {
      state.currentConversationId = Number(state.conversations[0].id || 0);
      state.currentConversation = state.conversations[0];
    }

    if (me.role !== 'admin' && state.conversations.length > 0 && !state.currentConversationId) {
      state.currentConversationId = Number(state.conversations[0].id || 0);
      state.currentConversation = state.conversations[0];
    }

    renderConversationList(state.conversations);
    renderAdminSection(state.conversations);
    updateHeader();
    updateUnreadBadge();
    return state.conversations;
  }

  async function loadMessages(conversationId) {
    const id = Number(conversationId || 0);
    if (!id) return [];
    const json = await fetchJson(`../Php/chat_fetch_messages.php?conversation_id=${encodeURIComponent(String(id))}`);
    state.messages = Array.isArray(json.messages) ? json.messages : [];
    renderMessages(state.messages);
    await markRead(id);
    await loadConversations();
    return state.messages;
  }

  function updateHeader() {
    const active = state.currentConversation || null;
    if (state.me?.role === 'admin') {
      if (el.title) {
        el.title.textContent = active ? displayNameForConversation(active) : 'Chat Management';
      }
      if (el.subtitle) {
        el.subtitle.textContent = active ? conversationSubtitle(active) : 'All conversations';
      }
      if (admin.title) {
        admin.title.textContent = active ? displayNameForConversation(active) : 'Select a conversation';
      }
      if (admin.subtitle) {
        admin.subtitle.textContent = active ? conversationSubtitle(active) : 'All user chats appear here.';
      }
      return;
    }
    if (el.title) {
      el.title.textContent = 'Admin Desk';
    }
    if (el.subtitle) {
      el.subtitle.textContent = active ? conversationSubtitle(active) : 'Active now';
    }
    if (admin.title) {
      admin.title.textContent = 'Chat Management';
    }
    if (admin.subtitle) {
      admin.subtitle.textContent = 'All user chats appear here.';
    }
  }

  function updateUnreadBadge() {
    const total = (state.conversations || []).reduce((sum, item) => sum + Number(item.unread_count || 0), 0);
    setBadge(total);
  }

  async function openConversation(conversationId) {
    const id = Number(conversationId || 0);
    if (!id) return;
    const found = state.conversations.find((item) => Number(item.id) === id) || null;
    state.currentConversationId = id;
    state.currentConversation = found;
    updateHeader();
    renderConversationList(state.conversations);
    renderAdminSection(state.conversations);
    await loadMessages(id);
  }

  async function ensureConversationReady() {
    const me = await whoAmI();
    if (me.role === 'admin') {
      state.currentConversationId = state.currentConversationId || Number(state.conversations[0]?.id || 0);
      return;
    }
    const init = await fetchJson('../Php/chat_open_or_create.php');
    if (init?.conversation) {
      state.currentConversationId = Number(init.conversation.id || 0);
      state.currentConversation = init.conversation;
      if (!state.conversations.length) {
        state.conversations = [init.conversation];
      }
      updateHeader();
      renderConversationList(state.conversations);
      renderAdminSection(state.conversations);
    }
  }

  async function sendMessage() {
    const preferredInput = admin.section && admin.input && String(admin.input.value || '').trim() !== '' ? admin.input : el.input;
    const text = String(preferredInput?.value || '').trim();
    if (!text) return;
    const me = await whoAmI();

    let payload;
    if (me.role === 'admin') {
      const active = state.currentConversation || state.conversations.find((item) => Number(item.id) === Number(state.currentConversationId));
      if (!active) {
        alert('Select a conversation first.');
        return;
      }
      payload = { conversation_id: Number(active.id || 0), message: text };
    } else {
      await ensureConversationReady();
      payload = { conversation_id: Number(state.currentConversationId || 0), message: text };
    }

    const response = await fetchJson('../Php/chat_send_message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(payload),
    });

    if (response?.message) {
      state.messages.push(response.message);
      renderMessages(state.messages);
      if (state.currentConversationId) {
        await loadConversations();
      }
    }

    if (admin.input) admin.input.value = '';
    if (el.input) el.input.value = '';
  }

  function bindDrawerEvents() {
    if (!hasMessengerUi) return;
    el.fab.addEventListener('click', async () => {
      openDrawer();
      await boot();
      if (state.currentConversationId) {
        await loadMessages(state.currentConversationId);
      }
    });

    el.close.addEventListener('click', closeDrawer);
    el.backdrop.addEventListener('click', closeDrawer);

    if (el.send) {
      el.send.addEventListener('click', sendMessage);
    }
    if (el.input) {
      el.input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          sendMessage();
        }
      });
    }

    if (el.search) {
      el.search.addEventListener('input', () => renderConversationList(state.conversations));
    }
  }

  function bindAdminEvents() {
    if (!admin.section) return;

    if (admin.send) {
      admin.send.addEventListener('click', sendMessage);
    }
    if (admin.input) {
      admin.input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          sendMessage();
        }
      });
    }
    if (admin.refresh) {
      admin.refresh.addEventListener('click', () => loadConversations().catch(() => {}));
    }
    if (admin.search) {
      admin.search.addEventListener('input', () => renderAdminSection(state.conversations));
    }
    if (admin.roleFilter) {
      admin.roleFilter.addEventListener('change', () => renderAdminSection(state.conversations));
    }
  }

  function openDrawer() {
    if (!hasMessengerUi) return;
    el.drawer.classList.add('open');
    el.backdrop.classList.add('open');
    el.drawer.setAttribute('aria-hidden', 'false');
    if (el.input && typeof el.input.focus === 'function') {
      el.input.focus();
    }
  }

  function closeDrawer() {
    if (!hasMessengerUi) return;
    el.drawer.classList.remove('open');
    el.backdrop.classList.remove('open');
    el.drawer.setAttribute('aria-hidden', 'true');
  }

  async function setupSocket() {
    await loadSocketClient();
    if (state.socket || !window.io) return;
    const me = await whoAmI();
    state.socket = window.io(SOCKET_URL, { transports: ['websocket', 'polling'] });
    state.socket.on('connect', () => {
      state.socket.emit('identify', { userId: me.user_id, role: me.role });
    });
    state.socket.on('message', async (message) => {
      const currentId = Number(state.currentConversationId || 0);
      const belongsToCurrent = currentId && Number(message.conversation_id || 0) === currentId;
      if (belongsToCurrent) {
        state.messages.push(message);
        renderMessages(state.messages);
        await markRead(currentId);
      }
      await loadConversations();
      if (!belongsToCurrent && document.visibilityState === 'visible') {
        updateUnreadBadge();
      }
    });
    state.socket.on('disconnect', () => {
      console.warn('chat socket disconnected');
    });
  }

  async function boot() {
    if (state.ready) return;
    state.ready = true;
    ensureBadge();
    bindDrawerEvents();
    bindAdminEvents();
    await whoAmI();
    await setupSocket();
    await loadConversations();
    if (!state.currentConversationId && state.conversations.length > 0) {
      state.currentConversationId = Number(state.conversations[0].id || 0);
      state.currentConversation = state.conversations[0];
    }
    updateHeader();
    if (state.currentConversationId) {
      await loadMessages(state.currentConversationId);
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      loadConversations().catch(() => {});
    }
  });

  window.openChat = async function () {
    openDrawer();
    await boot();
    if (state.currentConversationId) {
      await loadMessages(state.currentConversationId);
    }
  };

  window.SearcharChat = {
    boot,
    openDrawer,
    closeDrawer,
    loadConversations,
    openConversation,
    sendMessage,
    refresh: loadConversations,
    state,
  };

  boot().catch((error) => {
    console.error('chat boot failed', error);
  });
})();
