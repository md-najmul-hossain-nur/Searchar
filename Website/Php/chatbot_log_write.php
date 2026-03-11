<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chatbot_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            reply TEXT NOT NULL,
            source_page VARCHAR(64) NOT NULL DEFAULT 'index',
            session_token VARCHAR(128) DEFAULT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_chatbot_logs_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if (isset($_GET['clear']) && $_GET['clear'] === '1') {
        $pdo->exec('DELETE FROM chatbot_logs');
        echo json_encode(['success' => true]);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);

    $question = trim((string)($data['question'] ?? ''));
    $reply = trim((string)($data['reply'] ?? ''));
    $sourcePage = trim((string)($data['source_page'] ?? 'index'));
    $sessionToken = trim((string)($data['session_token'] ?? ''));

    if ($question === '' || $reply === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'question/reply required']);
        exit;
    }

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($sessionToken === '') {
        $sessionToken = null;
    }
    if ($sourcePage === '') {
        $sourcePage = 'index';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO chatbot_logs (question, reply, source_page, session_token, ip_address) VALUES (:q, :r, :s, :t, :ip)'
    );
    $stmt->execute([
        ':q' => $question,
        ':r' => $reply,
        ':s' => $sourcePage,
        ':t' => $sessionToken,
        ':ip' => $ipAddress,
    ]);

    // Keep only latest 2000 logs to avoid unbounded growth.
    $pdo->exec(
        'DELETE FROM chatbot_logs WHERE id NOT IN (SELECT id FROM (SELECT id FROM chatbot_logs ORDER BY id DESC LIMIT 2000) keep_ids)'
    );

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'chatbot log write failed']);
}
