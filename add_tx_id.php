<?php
require_once __DIR__ . '/Website/Php/db.php';
try {
    $pdo->exec("ALTER TABLE withdraw_requests ADD COLUMN tx_id VARCHAR(100) DEFAULT NULL AFTER request_note");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
