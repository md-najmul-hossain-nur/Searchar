<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function ensureDonationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS donations (
        donation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        donor_name VARCHAR(150) NOT NULL,
        sender_mobile VARCHAR(30) DEFAULT NULL,
        receiver_number VARCHAR(30) DEFAULT NULL,
        tx_id VARCHAR(120) DEFAULT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        message TEXT NULL,
        date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (donation_id),
        INDEX idx_donation_date (date),
        INDEX idx_tx_id (tx_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!columnExists($pdo, 'donations', 'sender_mobile')) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN sender_mobile VARCHAR(30) DEFAULT NULL AFTER donor_name");
    }
    if (!columnExists($pdo, 'donations', 'receiver_number')) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN receiver_number VARCHAR(30) DEFAULT NULL AFTER sender_mobile");
    }
    if (!columnExists($pdo, 'donations', 'tx_id')) {
        $pdo->exec("ALTER TABLE donations ADD COLUMN tx_id VARCHAR(120) DEFAULT NULL AFTER receiver_number");
    }
}

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'user' || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request payload']);
    exit;
}

$donorName = trim((string)($data['donor_name'] ?? ''));
$senderMobile = trim((string)($data['donor_phone'] ?? ''));
$receiverNumber = trim((string)($data['receiver_number'] ?? ''));
$txId = trim((string)($data['tx_id'] ?? ''));
$amount = (float)($data['amount'] ?? 0);

if ($donorName === '' || $senderMobile === '' || $txId === '' || $amount < 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Please provide valid name, mobile, amount and TxID']);
    exit;
}

try {
    ensureDonationsTable($pdo);

    $message = 'TxID: ' . $txId . ' | Sent from: ' . $senderMobile . ' | Receiver: ' . ($receiverNumber !== '' ? $receiverNumber : 'N/A');

    $stmt = $pdo->prepare('INSERT INTO donations (donor_name, sender_mobile, receiver_number, tx_id, amount, anonymous, message, date) VALUES (:donor_name, :sender_mobile, :receiver_number, :tx_id, :amount, 0, :message, NOW())');
    $stmt->execute([
        ':donor_name' => $donorName,
        ':sender_mobile' => $senderMobile,
        ':receiver_number' => $receiverNumber !== '' ? $receiverNumber : null,
        ':tx_id' => $txId,
        ':amount' => $amount,
        ':message' => $message,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save donation']);
}
