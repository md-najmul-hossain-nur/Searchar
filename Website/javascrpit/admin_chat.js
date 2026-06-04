// Minimal admin chat client
(function () {
  const SOCKET_URL = 'http://localhost:3000';
  let socket = null;

  async function initAdminChat() {
    socket = io(SOCKET_URL);
    socket.on('connect', () => {
      socket.emit('identify', { userId: 0, role: 'admin' });
    });

    socket.on('message', (m) => {
      // update admin UI if present
      console.info('admin message', m);
      // optionally refresh conversation list
      if (typeof window.refreshAdminConversations === 'function') window.refreshAdminConversations();
    });
  }

  window.initAdminChat = initAdminChat;
})();
