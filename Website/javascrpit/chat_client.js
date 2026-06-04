// Minimal chat client using socket.io
(function () {
  const SOCKET_URL = 'http://localhost:3000';
  let socket = null;
  let me = null;
  let currentConversation = null;

  async function whoami() {
    const res = await fetch('../Php/chat_whoami.php', { credentials: 'same-origin' });
    return res.json();
  }

  async function ensureSocket() {
    if (socket && me) return;
    const info = await whoami();
    if (!info || !info.success) return;
    me = { user_id: info.user_id, role: info.role };
    socket = io(SOCKET_URL);
    socket.on('connect', () => {
      socket.emit('identify', { userId: me.user_id, role: me.role });
    });
    socket.on('message', (m) => {
      // if message belongs to currentConversation, append
      if (!m) return;
      appendMessageToWindow(m);
    });
  }

  function appendMessageToWindow(m) {
    const box = document.getElementById('chat-messages');
    if (!box) return;
    const el = document.createElement('div');
    el.className = m.sender_id === me.user_id ? 'chat-msg me' : 'chat-msg them';
    el.textContent = `${m.sender_role}: ${m.content}`;
    box.appendChild(el);
    box.scrollTop = box.scrollHeight;
  }

  async function openChat() {
    await ensureSocket();
    // create simple modal if not present
    if (!document.getElementById('chat-modal')) {
      const modal = document.createElement('div');
      modal.id = 'chat-modal';
      modal.innerHTML = `
        <div id="chat-window" style="position:fixed;right:20px;bottom:20px;width:320px;height:420px;background:#fff;border:1px solid #ccc;z-index:9999;display:flex;flex-direction:column;">
          <div style="padding:8px;background:#333;color:#fff;">Chat with Admin <button id="chat-close" style="float:right;">×</button></div>
          <div id="chat-messages" style="flex:1;overflow:auto;padding:8px;background:#f7f7f7;"></div>
          <div style="padding:8px;border-top:1px solid #eee;"><input id="chat-input" style="width:78%" placeholder="Message..."> <button id="chat-send">Send</button></div>
        </div>`;
      document.body.appendChild(modal);
      document.getElementById('chat-close').addEventListener('click', () => modal.remove());
      document.getElementById('chat-send').addEventListener('click', sendMessage);
      document.getElementById('chat-input').addEventListener('keydown', (e) => { if (e.key === 'Enter') sendMessage(); });
    }

    // fetch existing conversation/messages
    const convRes = await fetch('../Php/chat_fetch_conversations.php', { credentials: 'same-origin' });
    const convJson = await convRes.json();
    if (!convJson || !convJson.success) return;
    let conv = null;
    if (Array.isArray(convJson.rows) && convJson.rows.length > 0) {
      conv = convJson.rows[0];
    } else if (convJson.conversation) {
      conv = convJson.conversation;
    }
    currentConversation = conv;
    const msgs = convJson.messages || [];
    const box = document.getElementById('chat-messages');
    box.innerHTML = '';
    msgs.forEach(m => appendMessageToWindow(m));
  }

  function sendMessage() {
    const input = document.getElementById('chat-input');
    if (!input || !input.value) return;
    const content = input.value.trim();
    if (!content) return;
    const toUserId = me.user_id; // send to admin: server expects toUserId; admin listens on room 'admin'
    // for simplicity, send to user id 0 meaning admin receiver
    socket.emit('send_message', { toUserId: 0, content });
    input.value = '';
  }

  window.openChat = openChat;
})();
