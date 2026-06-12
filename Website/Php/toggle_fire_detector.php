<?php
session_start();
session_write_close(); // Prevent session lock
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'status';

// Using port 5002 for the dedicated fire_detector.py engine
$url = 'http://127.0.0.1:5002/api/fire_detect/' . $action;
$ch = curl_init($url);
if ($action !== 'status') {
    curl_setopt($ch, CURLOPT_POST, true);
}
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if($code == 200 && $res) {
    echo $res;
} else {
    echo json_encode(['success' => false, 'error' => 'Could not connect to AI Engine']);
}
?>
