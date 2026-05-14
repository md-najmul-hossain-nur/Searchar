<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Dhaka');

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1');
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1');
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function ensureDonationsTable(PDO $pdo): void {
    if (tableExists($pdo, 'donations')) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE donations (
            donation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            donor_name VARCHAR(150) DEFAULT NULL,
            donor_email VARCHAR(255) DEFAULT NULL,
            sender_mobile VARCHAR(30) DEFAULT NULL,
            tx_id VARCHAR(120) DEFAULT NULL,
            receiver_number VARCHAR(40) DEFAULT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            anonymous TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT DEFAULT NULL,
            PRIMARY KEY (donation_id),
            KEY idx_donations_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function ensureColumn(PDO $pdo, string $columnName, string $definition): void {
    if (columnExists($pdo, 'donations', $columnName)) {
        return;
    }

    $pdo->exec("ALTER TABLE donations ADD COLUMN {$definition}");
}

try {
    $donorName = trim((string)($_POST['name'] ?? ''));
    $donorEmail = trim((string)($_POST['email'] ?? ''));
    $senderMobile = trim((string)($_POST['mobile'] ?? ''));
    $amountRaw = trim((string)($_POST['amount'] ?? ''));
    $txId = trim((string)($_POST['txid'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $anonymous = !empty($_POST['anonymous']) ? 1 : 0;
    $receiverNumber = trim((string)($_POST['receiver_number'] ?? ''));

    if ($donorName === '' || mb_strlen($donorName) < 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name is required.']);
        exit;
    }

    if (!filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid email is required.']);
        exit;
    }

    if ($senderMobile === '' || mb_strlen($senderMobile) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid mobile number is required.']);
        exit;
    }

    if ($amountRaw === '' || !is_numeric($amountRaw) || (float)$amountRaw <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid amount is required.']);
        exit;
    }

    if ($txId === '' || mb_strlen($txId) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Valid TXID is required.']);
        exit;
    }

    ensureDonationsTable($pdo);
    ensureColumn($pdo, 'donor_email', 'donor_email VARCHAR(255) DEFAULT NULL');
    ensureColumn($pdo, 'sender_mobile', 'sender_mobile VARCHAR(30) DEFAULT NULL');
    ensureColumn($pdo, 'tx_id', 'tx_id VARCHAR(120) DEFAULT NULL');
    ensureColumn($pdo, 'receiver_number', 'receiver_number VARCHAR(40) DEFAULT NULL');

    $columns = ['donor_name', 'amount', 'anonymous', 'message'];
    $placeholders = [':donor_name', ':amount', ':anonymous', ':message'];
    $values = [
        ':donor_name' => $donorName,
        ':amount' => (float)$amountRaw,
        ':anonymous' => $anonymous,
        ':message' => $message !== '' ? $message : null,
    ];

    if (columnExists($pdo, 'donations', 'donor_email')) {
        $columns[] = 'donor_email';
        $placeholders[] = ':donor_email';
        $values[':donor_email'] = $donorEmail;
    }

    if (columnExists($pdo, 'donations', 'sender_mobile')) {
        $columns[] = 'sender_mobile';
        $placeholders[] = ':sender_mobile';
        $values[':sender_mobile'] = $senderMobile;
    }

    if (columnExists($pdo, 'donations', 'tx_id')) {
        $columns[] = 'tx_id';
        $placeholders[] = ':tx_id';
        $values[':tx_id'] = $txId;
    }

    if (columnExists($pdo, 'donations', 'receiver_number')) {
        $columns[] = 'receiver_number';
        $placeholders[] = ':receiver_number';
        $values[':receiver_number'] = $receiverNumber !== '' ? $receiverNumber : null;
    }

    $sql = 'INSERT INTO donations (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to save donation right now.']);
}
