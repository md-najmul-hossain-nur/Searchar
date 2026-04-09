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

    $colsStmt = $pdo->query('SHOW COLUMNS FROM chatbot_logs');
    $existingCols = [];
    if ($colsStmt) {
        foreach ($colsStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $existingCols[] = (string)($col['Field'] ?? '');
        }
    }

    $insertCols = ['question', 'reply'];
    $insertVals = [':q' => $question, ':r' => $reply];

    if (in_array('source_page', $existingCols, true)) {
        $insertCols[] = 'source_page';
        $insertVals[':s'] = $sourcePage;
    }
    if (in_array('session_token', $existingCols, true)) {
        $insertCols[] = 'session_token';
        $insertVals[':t'] = $sessionToken;
    }
    if (in_array('ip_address', $existingCols, true)) {
        $insertCols[] = 'ip_address';
        $insertVals[':ip'] = $ipAddress;
    }

    $params = [];
    if (isset($insertVals[':q'])) $params[] = ':q';
    if (isset($insertVals[':r'])) $params[] = ':r';
    if (isset($insertVals[':s'])) $params[] = ':s';
    if (isset($insertVals[':t'])) $params[] = ':t';
    if (isset($insertVals[':ip'])) $params[] = ':ip';

    $sql = 'INSERT INTO chatbot_logs (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $params) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($insertVals);

    // Keep only latest 2000 logs to avoid unbounded growth.
    $pdo->exec(
        'DELETE FROM chatbot_logs WHERE id NOT IN (SELECT id FROM (SELECT id FROM chatbot_logs ORDER BY id DESC LIMIT 2000) keep_ids)'
    );

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'chatbot log write failed']);
}
