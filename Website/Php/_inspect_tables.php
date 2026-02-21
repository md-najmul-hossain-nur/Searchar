<?php
require __DIR__ . '/db.php';

echo 'DB: ' . $pdo->query('SELECT DATABASE()')->fetchColumn() . PHP_EOL;
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
echo 'Table count: ' . count($tables) . PHP_EOL;
foreach ($tables as $row) {
    echo $row[0] . PHP_EOL;
}
