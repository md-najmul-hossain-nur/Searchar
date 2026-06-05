(function initSharedAdminChats() {
  const drawers = Array.from(document.querySelectorAll('[data-admin-chat]'));
  if (!drawers.length) return;

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[ch]));
  }

  function formatChatTime(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  async function fetchJson(url, options) {
    const res = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const json = await res.json().catch(() => ({}));
    if (!res.ok || !json.success) throw new Error(json.error || 'Chat request failed');
    return json;
  }

  drawers.forEach((drawer) => {
    const prefix = drawer.id.replace(/-drawer$/, '');
    const launcher = document.getElementById(`${prefix}-launcher`);
    const closeBtn = document.getElementById(`${prefix}-close`);
    const feed = drawer.querySelector('[data-admin-chat-feed]');
    const input = drawer.querySelector('[data-admin-chat-input]');
    const sendBtn = drawer.querySelector('[data-admin-chat-send]');
    const rowClass = `${prefix}-row`;
    const stackClass = `${prefix}-stack`;
    const dateClass = `${prefix}-date`;
    const senderClass = `${prefix}-sender`;
    const timeClass = `${prefix}-time`;

    if (!launcher || !closeBtn || !feed || !input || !sendBtn) return;

    function setOpen(isOpen) {
      drawer.classList.toggle('is-open', isOpen);
      drawer.setAttribute('aria-hidden', String(!isOpen));
      launcher.setAttribute('aria-expanded', String(isOpen));
      if (isOpen) {
        setTimeout(() => input.focus(), 180);
      } else {
        launcher.focus();
      }
    }

    function renderMessages(messages) {
      if (!messages.length) {
        feed.innerHTML = `<div class="${dateClass}">No messages yet</div>`;
        return;
      }

      feed.innerHTML = messages.map(message => {
        const mine = Boolean(message.is_mine);
        const avatar = mine ? '' : '<img src="../Images/default-profile.gif" alt="">';
        const senderLabel = mine ? 'You' : 'Admin';
        const sentAt = formatChatTime(message.created_at);
        return `
          <div class="${rowClass} ${mine ? 'outgoing' : 'incoming'}">
            ${avatar}
            <div class="${stackClass}">
              <span class="${senderClass}">${senderLabel}</span>
              <p>${escapeHtml(message.message_text)}</p>
              <small class="${timeClass}">${sentAt}</small>
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
        feed.innerHTML = `<div class="${dateClass}">${escapeHtml(error.message)}</div>`;
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

    launcher.addEventListener('click', () => {
      const nextOpen = !drawer.classList.contains('is-open');
      setOpen(nextOpen);
      if (nextOpen) loadMessages();
    });
    closeBtn.addEventListener('click', () => setOpen(false));
    drawer.addEventListener('click', event => {
      if (event.target === drawer) setOpen(false);
    });
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keydown', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
      }
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        setOpen(false);
      }
    });

    loadMessages();
    setInterval(loadMessages, 4000);
  });
})();
