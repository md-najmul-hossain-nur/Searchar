<?php
declare(strict_types=1);
session_start();
set_time_limit(0);
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$targetImage = $_POST['target_image'] ?? '';

if (empty($action) || empty($targetImage)) {
    echo json_encode(['success' => false, 'error' => 'Missing action or target image']);
    exit();
}

// Convert relative target image path to absolute server path
// Assumes targetImage is like "../uploads/cases/image.jpg"
$baseDir = realpath(__DIR__ . '/../');
$targetImgPath = realpath($baseDir . '/' . ltrim($targetImage, './'));

if (!$targetImgPath || !file_exists($targetImgPath)) {
    echo json_encode(['success' => false, 'error' => 'Target image not found on server']);
    exit();
}

require_once 'db.php';
global $pdo;

$pythonApiUrl = 'http://127.0.0.1:5001/api/';

if ($action === 'search_posts') {
    // Fetch all approved posts that have media
    $stmt = $pdo->query("SELECT id, text, media_path, media_json, created_at, author_name, author_role FROM posts WHERE status = 'approved' ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $postImages = [];
    $postMap = []; // map absolute path to post details for merging later

    foreach ($posts as $post) {
        $path = '';
        if (!empty($post['media_path'])) {
            $path = $post['media_path'];
        } elseif (!empty($post['media_json'])) {
            $mediaArr = json_decode($post['media_json'], true);
            if (is_array($mediaArr) && count($mediaArr) > 0) {
                // Get first image
                $path = $mediaArr[0]['url'] ?? $mediaArr[0]['path'] ?? '';
            }
        }
        
        if (!empty($path)) {
            $absPath = realpath($baseDir . '/' . ltrim($path, './'));
            if ($absPath && file_exists($absPath)) {
                // Ensure it is an image
                $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $postImages[] = $absPath;
                    $postMap[$absPath] = [
                        'post_id' => $post['id'],
                        'author' => $post['author_name'] ?: 'Unknown',
                        'role' => $post['author_role'] ?: 'User',
                        'description' => $post['text'],
                        'time' => $post['created_at'],
                        'url' => $path
                    ];
                }
            }
        }
    }

    if (empty($postImages)) {
        echo json_encode(['success' => true, 'matches' => []]);
        exit();
    }

    // Call Python API
    $payload = json_encode([
        'target_image' => $targetImgPath,
        'post_images' => $postImages
    ]);

    $ch = curl_init($pythonApiUrl . 'search_posts');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout for model downloads and large searches
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        echo json_encode(['success' => false, 'error' => 'Failed to connect to Python AI Service']);
        exit();
    }

    $pyResult = json_decode($response, true);
    if (!$pyResult['success']) {
        echo json_encode(['success' => false, 'error' => $pyResult['error'] ?? 'Python error']);
        exit();
    }

    // Merge Python results with post data
    $finalMatches = [];
    foreach ($pyResult['matches'] as $match) {
        $absPath = $match['post_image'];
        if (isset($postMap[$absPath])) {
            $finalMatches[] = array_merge($match, $postMap[$absPath]);
        }
    }

    echo json_encode(['success' => true, 'matches' => $finalMatches]);

} elseif ($action === 'search_cctv') {
    // Fetch all recorded cctv feeds and active webcams
    $stmt = $pdo->query("SELECT feed_id, feed_label, camera_location, feed_type, video_path FROM camera_cctv_feeds WHERE feed_type IN ('recorded', 'webcam') AND is_active = 1");
    $feeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $videoPaths = [];
    $feedMap = [];

    foreach ($feeds as $feed) {
        if (!empty($feed['video_path'])) {
            $absPath = realpath($baseDir . '/' . ltrim($feed['video_path'], './'));
            if ($absPath && file_exists($absPath)) {
                $videoPaths[] = $absPath;
                $feedMap[$absPath] = [
                    'feed_id' => $feed['feed_id'],
                    'owner' => 'Camera System', // Placeholder since owner_name is not in table
                    'label' => $feed['feed_label'],
                    'location' => $feed['camera_location'],
                    'capture_time' => date('Y-m-d h:i A', filemtime($absPath))
                ];
            }
        }
    }

    if (empty($videoPaths)) {
        echo json_encode(['success' => true, 'matches' => []]);
        exit();
    }

    // Call Python API
    $payload = json_encode([
        'target_image' => $targetImgPath,
        'video_paths' => $videoPaths
    ]);

    $ch = curl_init($pythonApiUrl . 'search_cctv');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Give it more timeout since video parsing takes time
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); 
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        echo json_encode(['success' => false, 'error' => 'Failed to connect to Python AI Service. Please wait if it is still loading.']);
        exit();
    }

    $pyResult = json_decode($response, true);
    if (!$pyResult['success']) {
        echo json_encode(['success' => false, 'error' => $pyResult['error'] ?? 'Python error']);
        exit();
    }

    $finalMatches = [];
    foreach ($pyResult['matches'] as $match) {
        $absPath = $match['video_path'];
        if (isset($feedMap[$absPath])) {
            $finalMatches[] = array_merge($match, $feedMap[$absPath]);
        }
    }

    echo json_encode(['success' => true, 'matches' => $finalMatches]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
