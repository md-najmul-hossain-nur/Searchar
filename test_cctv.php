<?php
require_once 'c:/xampp/htdocs/Searchar/Website/Php/db.php';
global $pdo;

$targetImgPath = "c:/xampp/htdocs/Searchar/Website/uploads/cases/target.jpg"; // we'll check what target image exists
$baseDir = realpath(__DIR__ . '/../../');

$stmt = $pdo->query("SELECT feed_id, video_path FROM camera_cctv_feeds WHERE feed_type IN ('recorded', 'webcam') AND is_active = 1");
$feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$videoPaths = [];
foreach ($feeds as $feed) {
    if (!empty($feed['video_path'])) {
        $absPath = realpath($baseDir . '/' . ltrim($feed['video_path'], './'));
        if ($absPath && file_exists($absPath)) {
            $videoPaths[] = $absPath;
        }
    }
}

echo "Video Paths found: \n";
print_r($videoPaths);

$payload = json_encode([
    'target_image' => $targetImgPath,
    'video_paths' => $videoPaths
]);

$ch = curl_init('http://127.0.0.1:5001/api/search_cctv');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
echo "\nResponse: " . $response;
?>
