<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

function ensureTable(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS chatbot_comment_templates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            comment_text VARCHAR(300) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_chatbot_comment_templates_text (comment_text),
            KEY idx_chatbot_comment_templates_active (is_active),
            KEY idx_chatbot_comment_templates_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $count = (int)$pdo->query('SELECT COUNT(*) FROM chatbot_comment_templates WHERE is_active = 1')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $defaults = [
        'Thanks for your message. Our team is checking now.',
        'Your report has been received and forwarded to the support team.',
        'Please share location and time details for faster action.',
        'We could not verify this yet. Please provide a clear photo or reference.',
        'This issue has been noted and marked for follow-up.',
        'In an emergency, please call 999 immediately.'
    ];

    $insert = $pdo->prepare('INSERT IGNORE INTO chatbot_comment_templates (comment_text, sort_order, is_active) VALUES (:text, :sort_order, 1)');
    foreach ($defaults as $index => $text) {
        $insert->execute([
            ':text' => $text,
            ':sort_order' => $index + 1,
        ]);
    }

    // Auto-migrate legacy Bangla template text to the current English version.
    $migrateStmt = $pdo->prepare(
        'UPDATE chatbot_comment_templates
         SET comment_text = :new_text
         WHERE comment_text = :old_text'
    );
    $migrateStmt->execute([
        ':new_text' => 'In an emergency, please call 999 immediately.',
        ':old_text' => 'Emergency হলে সাথে সাথে 999 এ call করুন।',
    ]);
}

try {
    ensureTable($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->query('SELECT id, comment_text FROM chatbot_comment_templates WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode((string)$raw, true);

    $action = trim((string)($data['action'] ?? ''));
    if ($action === 'add') {
        $commentText = trim((string)($data['comment_text'] ?? ''));
        if ($commentText === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'comment_text required']);
            exit;
        }

        $textLength = function_exists('mb_strlen') ? mb_strlen($commentText) : strlen($commentText);
        if ($textLength > 300) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'comment_text too long']);
            exit;
        }

        $existsStmt = $pdo->prepare('SELECT id FROM chatbot_comment_templates WHERE comment_text = :text LIMIT 1');
        $existsStmt->execute([':text' => $commentText]);
        $existingId = (int)$existsStmt->fetchColumn();
        if ($existingId > 0) {
            echo json_encode(['success' => true, 'message' => 'duplicate', 'id' => $existingId]);
            exit;
        }

        $maxSort = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM chatbot_comment_templates')->fetchColumn();
        $stmt = $pdo->prepare('INSERT IGNORE INTO chatbot_comment_templates (comment_text, sort_order, is_active) VALUES (:text, :sort_order, 1)');
        $stmt->execute([
            ':text' => $commentText,
            ':sort_order' => $maxSort + 1,
        ]);

        echo json_encode(['success' => true, 'message' => 'added']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'valid id required']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM chatbot_comment_templates WHERE id = :id');
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'chatbot template api failed']);
}
