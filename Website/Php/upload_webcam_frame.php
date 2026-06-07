<?php
header('Content-Type: application/json');

if (!isset($_FILES['frame']) || empty($_POST['feed_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing frame or feed_id']);
    exit;
}

$feedId = (int)$_POST['feed_id'];
$uploadDir = '../uploads/cctv_snapshots/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Overwrite the same file so we only keep the latest frame for this feed
$fileName = 'feed_' . $feedId . '_latest.jpg';
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['frame']['tmp_name'], $targetPath)) {
    // Update the database to point video_path to this snapshot
    require 'db.php';
    global $pdo;
    
    // We update video_path with the relative path so the AI can scan it
    $dbPath = 'uploads/cctv_snapshots/' . $fileName;
    
    $stmt = $pdo->prepare("UPDATE camera_cctv_feeds SET video_path = :path WHERE feed_id = :id AND feed_type = 'webcam'");
    $stmt->execute(['path' => $dbPath, 'id' => $feedId]);
    
    echo json_encode(['success' => true, 'path' => $dbPath]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save frame']);
}
