<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

$url = "http://127.0.0.1:5001/api/fire_detect/scan_now";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
if(curl_errno($ch)){
    echo json_encode(['success' => false, 'error' => 'Python Server Offline: ' . curl_error($ch)]);
} else {
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo $response;
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid response from AI Server']);
    }
}
curl_close($ch);
?>
