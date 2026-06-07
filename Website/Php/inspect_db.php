<?php
require 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
$stmt = $pdo->query("SELECT id, photo FROM crime_reports WHERE id = 'MP0001'");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
