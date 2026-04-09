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

    $sessionToken = trim((string)($_GET['session_token'] ?? ''));
    $lastId = (int)($_GET['last_id'] ?? 0);

    if ($sessionToken === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'session_token required', 'data' => []]);
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT id, reply_text, DATE_FORMAT(created_at, "%Y-%m-%dT%H:%i:%s") AS time
         FROM chatbot_admin_replies
         WHERE session_token = :token AND id > :last_id
         ORDER BY id ASC
         LIMIT 50'
    );
    $stmt->bindValue(':token', $sessionToken, PDO::PARAM_STR);
    $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        $ids = array_map(static fn($r) => (int)$r['id'], $rows);
        $idList = implode(',', $ids);
        if ($idList !== '') {
            $pdo->exec("UPDATE chatbot_admin_replies SET is_delivered = 1, delivered_at = NOW() WHERE id IN ($idList)");
        }
    }

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'admin reply read failed', 'data' => []]);
}
