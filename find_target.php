<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;

$stmt = $pdo->query('SELECT * FROM missing_person_reports ORDER BY created_at DESC LIMIT 1');
print_r("Missing persons: \n");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $pdo->query('SELECT * FROM crime_reports ORDER BY created_at DESC LIMIT 1');
print_r("Crimes: \n");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

$stmt3 = $pdo->query('SELECT * FROM posts WHERE status="approved" ORDER BY created_at DESC LIMIT 1');
print_r("Posts: \n");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
?>
