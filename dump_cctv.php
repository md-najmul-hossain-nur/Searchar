<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;
$stmt = $pdo->query('SELECT feed_id, feed_type, is_active, video_path FROM camera_cctv_feeds');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
