<?php
require 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
$stmt = $pdo->query("SELECT * FROM camera_cctv_feeds");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
