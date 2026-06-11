<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chatbot_admin_replies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_token VARCHAR(128) NOT NULL,
            reply_text TEXT NOT NULL,
            is_delivered TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            delivered_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_chatbot_admin_replies_session (session_token),
            KEY idx_chatbot_admin_replies_delivered (is_delivered)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);

    $sessionToken = trim((string)($data['session_token'] ?? ''));
    $replyText = trim((string)($data['reply_text'] ?? $data['reply'] ?? ''));

    if ($sessionToken === '' || $replyText === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'session_token and reply_text required']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO chatbot_admin_replies (session_token, reply_text) VALUES (:token, :reply)');
    $stmt->execute([
        ':token' => $sessionToken,
        ':reply' => $replyText,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'admin reply write failed']);
}
