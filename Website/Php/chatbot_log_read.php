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

    $stmt = $pdo->query(
        'SELECT id, question, reply, DATE_FORMAT(created_at, "%Y-%m-%dT%H:%i:%s") AS time FROM chatbot_logs ORDER BY id ASC LIMIT 2000'
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'chatbot log read failed', 'data' => []]);
}
