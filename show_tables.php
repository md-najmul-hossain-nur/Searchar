<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;
$stmt = $pdo->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
