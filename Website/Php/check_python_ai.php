<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$port = 5001;
$timeout = 1; // 1 second timeout

$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);

if (is_resource($connection)) {
    fclose($connection);
    echo json_encode(['success' => true, 'status' => 'online']);
} else {
    echo json_encode(['success' => true, 'status' => 'offline']);
}
?>
