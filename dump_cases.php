<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;
$stmt = $pdo->query('SELECT * FROM cases ORDER BY created_at DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
