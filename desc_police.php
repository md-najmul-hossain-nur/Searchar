<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;
print_r($pdo->query('DESCRIBE policemen')->fetchAll(PDO::FETCH_ASSOC));
?>
