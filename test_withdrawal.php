<?php
require_once __DIR__ . '/Website/Php/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM withdraw_requests');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
