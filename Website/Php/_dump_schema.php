<?php
require __DIR__ . '/db.php';

$outputFile = __DIR__ . '/../db_schema_dump.sql';

$tablesStmt = $pdo->query('SHOW TABLES');
$tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

$lines = [];
$lines[] = '-- Database schema dump generated on ' . date('Y-m-d H:i:s');
$lines[] = '-- Database: ' . $pdo->query('SELECT DATABASE()')->fetchColumn();
$lines[] = '';

foreach ($tables as $table) {
    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $createSql = $createStmt['Create Table'] ?? '';
    if ($createSql !== '') {
        $lines[] = "-- ----------------------------";
        $lines[] = "-- Table structure for `{$table}`";
        $lines[] = "-- ----------------------------";
        $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
        $lines[] = $createSql . ';';
        $lines[] = '';
    }
}

file_put_contents($outputFile, implode(PHP_EOL, $lines));
echo "Schema dump written to: {$outputFile}" . PHP_EOL;
