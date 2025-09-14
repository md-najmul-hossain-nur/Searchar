<?php
// Database credentials
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$database = "searchar"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection error
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}
?>