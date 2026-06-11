<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error']);
    exit;
}

$uploadDir = '../uploads/cctv_targets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

if (!in_array($fileExt, $allowedExts)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file extension']);
    exit;
}

$newFileName = uniqid('target_', true) . '.' . $fileExt;
$destination = $uploadDir . $newFileName;

if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
    // Return relative path to match expected format
    echo json_encode([
        'success' => true, 
        'new_image_url' => $destination
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
}
