console.log("Server file is running...");
const express = require("express");
const axios = require("axios");
const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
require("dotenv").config();

const app = express();
app.use(express.json());

const PAGE_ID = process.env.PAGE_ID;
const ACCESS_TOKEN = process.env.PAGE_ACCESS_TOKEN;

function normalizeChatRole(role) {
  const value = String(role || '').trim().toLowerCase();
  if (!value) return 'user';
  if (value === 'policeman') return 'police';
  if (value === 'camera_man' || value === 'cameraman' || value === 'camera_contributor') return 'contributor';
  return value;
}

// DB connection config (match PHP `db.php`)
const DB_CONFIG = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'searchar',
  waitForConnections: true,
  connectionLimit: 10,
};

const server = http.createServer(app);
const io = new Server(server, { cors: { origin: '*' } });

let pool;

(async () => {
  try {
    pool = mysql.createPool(DB_CONFIG);
    // ensure tables exist
    await pool.query(`
      CREATE TABLE IF NOT EXISTS conversations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        role VARCHAR(80) NOT NULL,
        last_message_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_conversation_user_role (user_id, role),
        INDEX idx_user (user_id),
        INDEX idx_role (role)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    await pool.query("ALTER TABLE conversations ADD COLUMN role VARCHAR(80) NOT NULL DEFAULT 'user' AFTER user_id").catch(() => {});
    await pool.query("ALTER TABLE conversations ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at").catch(() => {});
    await pool.query("ALTER TABLE conversations ADD UNIQUE KEY uq_conversation_user_role (user_id, role)").catch(() => {});

    await pool.query(`
      CREATE TABLE IF NOT EXISTS messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id BIGINT UNSIGNED NOT NULL,
        sender_role VARCHAR(80) NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        receiver_role VARCHAR(80) NOT NULL DEFAULT 'admin',
        receiver_id INT UNSIGNED NOT NULL DEFAULT 0,
        message TEXT DEFAULT NULL,
        content TEXT DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_conv (conversation_id),
        INDEX idx_sender (sender_id),
        INDEX idx_receiver (receiver_role, receiver_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    await pool.query("ALTER TABLE messages ADD COLUMN receiver_role VARCHAR(80) NOT NULL DEFAULT 'admin' AFTER sender_id").catch(() => {});
    await pool.query("ALTER TABLE messages ADD COLUMN receiver_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER receiver_role").catch(() => {});
    await pool.query("ALTER TABLE messages ADD COLUMN message TEXT DEFAULT NULL AFTER receiver_id").catch(() => {});
    await pool.query("ALTER TABLE messages ADD COLUMN content TEXT DEFAULT NULL AFTER message").catch(() => {});

    console.log('DB pool initialized and tables ensured');
  } catch (err) {
    console.error('DB init error', err);
    process.exit(1);
  }
})();

// Simple API to post on Facebook Page (kept for backward compatibility)
app.post("/post", async (req, res) => {
  const { message } = req.body;

  try {
    const response = await axios.post(
      `https://graph.facebook.com/v18.0/${PAGE_ID}/feed`,
      {
        message: message,
        access_token: ACCESS_TOKEN,
      }
    );

    res.json({
      success: true,
      postId: response.data.id,
    });
  } catch (error) {
    console.error(error.response?.data || error.message);

    res.status(500).json({
      success: false,
      error: error.response?.data || error.message,
    });
  }
});

app.post('/broadcast-chat', (req, res) => {
  try {
    const secret = String(req.header('x-internal-secret') || '');
    const expected = String(process.env.CHAT_BROADCAST_SECRET || '');
    if (expected && secret !== expected) {
      res.status(403).json({ success: false, error: 'Forbidden' });
      return;
    }

    const payload = req.body || {};
    const conversationId = Number(payload.conversation_id || 0);
    if (!conversationId) {
      res.status(400).json({ success: false, error: 'Missing conversation_id' });
      return;
    }

    const eventPayload = {
      conversation_id: conversationId,
      sender_role: String(payload.sender_role || 'user'),
      sender_id: Number(payload.sender_id || 0),
      receiver_role: String(payload.receiver_role || 'admin'),
      receiver_id: Number(payload.receiver_id || 0),
      content: String(payload.content || ''),
      created_at: String(payload.created_at || new Date().toISOString()),
      is_read: Number(payload.is_read || 0),
      id: Number(payload.id || 0),
    };

    io.to(`chat:${eventPayload.receiver_role}:${eventPayload.receiver_id}`).emit('message', eventPayload);
    io.to(`chat:${eventPayload.sender_role}:${eventPayload.sender_id}`).emit('message', eventPayload);
    io.to('admin').emit('message', eventPayload);

    res.json({ success: true });
  } catch (error) {
    console.error('broadcast-chat error', error);
    res.status(500).json({ success: false, error: 'Broadcast failed' });
  }
});

// Socket.io chat handling
const users = new Map(); // socketId -> {userId, role}

io.on('connection', (socket) => {
  console.log('socket connected', socket.id);

  socket.on('identify', async (payload) => {
    const { userId, role } = payload || {};
    if (!userId) return;
    const normalizedRole = normalizeChatRole(role);
    users.set(socket.id, { userId: Number(userId), role: normalizedRole });
    socket.join(`chat:${normalizedRole}:${userId}`);
    if (normalizedRole === 'admin') {
      socket.join('admin');
    }
    console.log('identify', socket.id, userId, role);
  });

  socket.on('send_message', async (msg) => {
    // msg: { toUserId, toRole, content }
    try {
      const sender = users.get(socket.id);
      if (!sender) return;
      const toUserId = Number(msg.toUserId || 0);
      const toRole = normalizeChatRole(msg.toRole || 'admin');
      const content = String(msg.content || '');

      const convOwnerId = sender.role === 'admin' ? toUserId : sender.userId;
      const convOwnerRole = sender.role === 'admin' ? toRole : sender.role;
      const receiverRole = sender.role === 'admin' ? toRole : 'admin';
      const receiverId = sender.role === 'admin' ? toUserId : 0;

      if (!convOwnerId || !convOwnerRole) {
        return;
      }

      // find or create conversation for convOwnerId
      const [rows] = await pool.query('SELECT id FROM conversations WHERE user_id = ? AND role = ? LIMIT 1', [convOwnerId, convOwnerRole]);
      let conversationId;
      if (rows.length > 0) {
        conversationId = rows[0].id;
        await pool.query('UPDATE conversations SET last_message_at = NOW() WHERE id = ?', [conversationId]);
      } else {
        const [r] = await pool.query('INSERT INTO conversations (user_id, role, last_message_at) VALUES (?, ?, NOW())', [convOwnerId, convOwnerRole]);
        conversationId = r.insertId;
      }

      // insert message
      await pool.query('INSERT INTO messages (conversation_id, sender_role, sender_id, receiver_role, receiver_id, message, content, is_read) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [conversationId, sender.role, sender.userId, receiverRole, receiverId, content, content, 0]);

      const out = {
        conversation_id: conversationId,
        sender_role: sender.role,
        sender_id: sender.userId,
        receiver_role: receiverRole,
        receiver_id: receiverId,
        content,
        created_at: new Date().toISOString(),
      };

      if (sender.role === 'admin') {
        io.to(`chat:${toRole}:${toUserId}`).emit('message', out);
        io.to('admin').emit('message', out);
      } else {
        io.to('admin').emit('message', out);
        io.to(`chat:${sender.role}:${sender.userId}`).emit('message', out);
      }
    } catch (err) {
      console.error('send_message error', err);
    }
  });

  socket.on('disconnect', () => {
    users.delete(socket.id);
    console.log('socket disconnected', socket.id);
  });
});

server.listen(3000, () => {
  console.log("Server running on http://localhost:3000");
});
console.log("PAGE_ID:", PAGE_ID);
console.log("TOKEN:", ACCESS_TOKEN ? "Loaded" : "Missing");