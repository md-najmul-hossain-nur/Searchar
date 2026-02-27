<?php
declare(strict_types=1);

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

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS donations (
        donation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        donor_name VARCHAR(150) DEFAULT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        message TEXT DEFAULT NULL,
        PRIMARY KEY (donation_id),
        KEY idx_donations_date (date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS withdraw_requests (
        withdraw_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        requester_name VARCHAR(150) NOT NULL,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        PRIMARY KEY (withdraw_id),
        KEY idx_withdraw_status (status),
        KEY idx_withdraw_request_date (request_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (!columnExists($pdo, 'withdraw_requests', 'requester_name')) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN requester_name VARCHAR(150) NOT NULL AFTER withdraw_id");
    }
    if (!columnExists($pdo, 'withdraw_requests', 'amount')) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER requester_name");
    }
    if (!columnExists($pdo, 'withdraw_requests', 'status')) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER amount");
    }
    if (!columnExists($pdo, 'withdraw_requests', 'request_date')) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status");
    }
    if (!columnExists($pdo, 'withdraw_requests', 'updated_at')) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER request_date");
    }

    $donationRows = [
        ['donor_name' => 'Mahin Rahman', 'amount' => 1500.00, 'anonymous' => 0, 'message' => '[DEMO] For emergency rescue support.', 'offset' => 'INTERVAL 1 DAY'],
        ['donor_name' => 'Anonymous', 'amount' => 3000.00, 'anonymous' => 1, 'message' => '[DEMO] Hope this helps the mission team.', 'offset' => 'INTERVAL 16 HOUR'],
        ['donor_name' => 'Nafisa Ahmed', 'amount' => 750.00, 'anonymous' => 0, 'message' => '[DEMO] Support for volunteer operations.', 'offset' => 'INTERVAL 10 HOUR'],
        ['donor_name' => 'Siam Khan', 'amount' => 2200.00, 'anonymous' => 0, 'message' => '[DEMO] Keep up the great work.', 'offset' => 'INTERVAL 6 HOUR'],
        ['donor_name' => 'Anonymous', 'amount' => 500.00, 'anonymous' => 1, 'message' => '[DEMO] Small contribution from me.', 'offset' => 'INTERVAL 2 HOUR'],
    ];

    $withdrawRows = [
        ['requester_name' => 'Rafid Hasan', 'amount' => 350.00, 'status' => 'pending', 'offset' => 'INTERVAL 5 HOUR'],
        ['requester_name' => 'Nusrat Jahan', 'amount' => 500.00, 'status' => 'pending', 'offset' => 'INTERVAL 3 HOUR'],
        ['requester_name' => 'Sabbir Ahmed', 'amount' => 450.00, 'status' => 'approved', 'offset' => 'INTERVAL 20 HOUR'],
        ['requester_name' => 'Tanvir Alam', 'amount' => 300.00, 'status' => 'rejected', 'offset' => 'INTERVAL 1 DAY'],
        ['requester_name' => 'Arifa Noor', 'amount' => 650.00, 'status' => 'pending', 'offset' => 'INTERVAL 90 MINUTE'],
    ];

    $pdo->exec("DELETE FROM donations WHERE message LIKE '[DEMO] %'");

    if (columnExists($pdo, 'withdraw_requests', 'request_note')) {
        $pdo->exec("DELETE FROM withdraw_requests WHERE request_note = '[DEMO]'");
    }

    $insertDonation = $pdo->prepare(
        "INSERT INTO donations (donor_name, amount, date, anonymous, message)
         VALUES (:donor_name, :amount, DATE_SUB(NOW(), {{OFFSET}}), :anonymous, :message)"
    );

    foreach ($donationRows as $row) {
        $sql = str_replace('{{OFFSET}}', $row['offset'], $insertDonation->queryString);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':donor_name' => $row['donor_name'],
            ':amount' => $row['amount'],
            ':anonymous' => $row['anonymous'],
            ':message' => $row['message'],
        ]);
    }

    $hasRequestNote = columnExists($pdo, 'withdraw_requests', 'request_note');
    if (!$hasRequestNote) {
        $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN request_note VARCHAR(120) DEFAULT NULL AFTER request_date");
    }

    $insertWithdraw = $pdo->prepare(
        "INSERT INTO withdraw_requests (requester_name, amount, status, request_date, request_note, updated_at)
         VALUES (:requester_name, :amount, :status, DATE_SUB(NOW(), {{OFFSET}}), '[DEMO]', CASE WHEN :status = 'pending' THEN NULL ELSE NOW() END)"
    );

    foreach ($withdrawRows as $row) {
        $sql = str_replace('{{OFFSET}}', $row['offset'], $insertWithdraw->queryString);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':requester_name' => $row['requester_name'],
            ':amount' => $row['amount'],
            ':status' => $row['status'],
        ]);
    }

    echo "Inserted demo finance data successfully.\n";
    echo "Donations added: " . count($donationRows) . "\n";
    echo "Withdraw requests added: " . count($withdrawRows) . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Failed to seed demo finance data: ' . $e->getMessage() . "\n";
}
