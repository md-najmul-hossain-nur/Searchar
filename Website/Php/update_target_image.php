<?php
header('Content-Type: application/json');

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded']);
    exit;
}

$uploadDir = '../uploads/ai_targets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['image']['name']));
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
    echo json_encode(['success' => true, 'new_image_url' => '../uploads/ai_targets/' . $fileName]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save image']);
}
