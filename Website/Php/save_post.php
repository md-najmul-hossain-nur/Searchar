<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Authentication required
if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$role = (string) $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

// Collect and validate inputs
$text = trim((string) ($_POST['text'] ?? ''));
$category = trim((string) ($_POST['category'] ?? 'general'));
$allowedCats = ['mission','disaster','general'];
if ($category === '' || !in_array($category, $allowedCats, true)) {
    $category = 'general';
}

// single case id: default to 1 unless explicitly set and valid integer
$case_id = isset($_POST['case_id']) ? (int) $_POST['case_id'] : 1;
if ($case_id <= 0) $case_id = 1;

// Limit text length
if (mb_strlen($text) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Text too long']);
    exit;
}

// read share_facebook flag
$share_fb = 0;
if (isset($_POST['share_facebook'])) {
    $share_fb = ($_POST['share_facebook'] === '1' || $_POST['share_facebook'] === 'true') ? 1 : 0;
}

// handle file upload (optional)
$media_path = null;
$media_type = null;
if (!empty($_FILES['media']) && is_uploaded_file($_FILES['media']['tmp_name'])) {
    $file = $_FILES['media'];
    // Basic server-side validation
    $maxImage = 10 * 1024 * 1024; // 10 MB
    $maxVideo = 50 * 1024 * 1024; // 50 MB
    $allowedImage = ['image/png','image/jpeg','image/gif','image/webp'];
    $allowedVideo = ['video/mp4','video/quicktime','video/webm'];

    $fsize = (int) $file['size'];
    $mime = $file['type'];

    if (in_array($mime, $allowedImage, true)) {
        if ($fsize > $maxImage) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Image too large']);
            exit;
        }
        $media_type = 'image';
    } elseif (in_array($mime, $allowedVideo, true)) {
        if ($fsize > $maxVideo) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Video too large']);
            exit;
        }
        $media_type = 'video';
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unsupported media type']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(10));
    $target = $uploadDir . $basename . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        exit;
    }

    // store web-relative path to media
    $media_path = 'uploads/posts/' . $basename . '.' . $ext;
}

try {
    // Ensure posts table exists (safe-guard migration)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `case_id` INT NOT NULL DEFAULT 1,
        `author_role` VARCHAR(50) NOT NULL,
        `author_id` INT NOT NULL,
        `author_name` VARCHAR(255) DEFAULT NULL,
        `category` VARCHAR(50) DEFAULT 'general',
        `text` TEXT,
        `media_path` VARCHAR(512) DEFAULT NULL,
        `media_type` ENUM('image','video','file') DEFAULT NULL,
        `share_facebook` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Try to fetch author's display name (best-effort)
    $author_name = null;
    if ($role === 'user') {
        $s = $pdo->prepare('SELECT full_name FROM users WHERE user_id = :id LIMIT 1');
        $s->execute(['id' => $user_id]);
        $author_name = $s->fetchColumn() ?: null;
    } elseif ($role === 'police') {
        $s = $pdo->prepare('SELECT full_name FROM policemen WHERE police_id = :id LIMIT 1');
        $s->execute(['id' => $user_id]);
        $author_name = $s->fetchColumn() ?: null;
    } elseif ($role === 'volunteer') {
        $s = $pdo->prepare('SELECT full_name FROM volunteers WHERE volunteer_id = :id LIMIT 1');
        $s->execute(['id' => $user_id]);
        $author_name = $s->fetchColumn() ?: null;
    } elseif ($role === 'contributor') {
        $s = $pdo->prepare('SELECT full_name FROM camera_contributors WHERE camera_id = :id LIMIT 1');
        $s->execute(['id' => $user_id]);
        $author_name = $s->fetchColumn() ?: null;
    }

    $ins = $pdo->prepare('INSERT INTO posts (case_id, author_role, author_id, author_name, category, text, media_path, media_type, share_facebook) VALUES (:case_id, :author_role, :author_id, :author_name, :category, :text, :media_path, :media_type, :share_facebook)');
    $ins->execute([
        'case_id' => $case_id,
        'author_role' => $role,
        'author_id' => $user_id,
        'author_name' => $author_name,
        'category' => $category,
        'text' => $text ?: null,
        'media_path' => $media_path,
        'media_type' => $media_type,
        'share_facebook' => $share_fb,
    ]);

    echo json_encode(['success' => true, 'message' => 'Saved']);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

?>
