<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
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

// read share_anonymous flag
$share_anonymous = 0;
if (isset($_POST['share_anonymous'])) {
    $share_anonymous = ($_POST['share_anonymous'] === '1' || $_POST['share_anonymous'] === 'true') ? 1 : 0;
}

if ($share_anonymous === 1 && $share_fb === 1) {
    $share_fb = 0;
}

// handle file upload (optional)
$media_path = null;
$media_type = null;
$media_json = null;

$maxImage = 10 * 1024 * 1024; // 10 MB
$maxVideo = 50 * 1024 * 1024; // 50 MB
$maxImageCount = 5;
$allowedImage = ['image/png','image/jpeg','image/gif','image/webp'];
$allowedVideo = ['video/mp4','video/quicktime','video/webm'];

$uploadDir = __DIR__ . '/../uploads/posts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$hasVideo = !empty($_FILES['media_video']) && is_uploaded_file($_FILES['media_video']['tmp_name']);
$hasImageArray = !empty($_FILES['media_images']) && isset($_FILES['media_images']['tmp_name']) && is_array($_FILES['media_images']['tmp_name']);
$hasLegacySingle = !empty($_FILES['media']) && is_uploaded_file($_FILES['media']['tmp_name']);

if (($hasVideo && $hasImageArray) || ($hasVideo && $hasLegacySingle)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload either images or one video, not both']);
    exit;
}

if ($hasVideo) {
    $file = $_FILES['media_video'];
    $fsize = (int)$file['size'];
    $mime = (string)$file['type'];

    if (!in_array($mime, $allowedVideo, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unsupported video type']);
        exit;
    }
    if ($fsize > $maxVideo) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Video too large']);
        exit;
    }

    $ext = pathinfo((string)$file['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(10));
    $target = $uploadDir . $basename . '.' . $ext;

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded video']);
        exit;
    }

    $media_type = 'video';
    $media_path = 'uploads/posts/' . $basename . '.' . $ext;
} elseif ($hasImageArray) {
    $imageNames = $_FILES['media_images']['name'];
    $imageTypes = $_FILES['media_images']['type'];
    $imageSizes = $_FILES['media_images']['size'];
    $imageTmp = $_FILES['media_images']['tmp_name'];

    $validIndexes = [];
    foreach ($imageTmp as $idx => $tmpPath) {
        if (is_uploaded_file((string)$tmpPath)) {
            $validIndexes[] = $idx;
        }
    }

    if (count($validIndexes) > $maxImageCount) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Maximum 5 images allowed']);
        exit;
    }

    $storedImages = [];
    foreach ($validIndexes as $idx) {
        $mime = (string)($imageTypes[$idx] ?? '');
        $size = (int)($imageSizes[$idx] ?? 0);
        $tmpPath = (string)($imageTmp[$idx] ?? '');
        $name = (string)($imageNames[$idx] ?? '');

        if (!in_array($mime, $allowedImage, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unsupported image type']);
            exit;
        }
        if ($size > $maxImage) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Image too large']);
            exit;
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(10));
        $target = $uploadDir . $basename . '.' . $ext;

        if (!move_uploaded_file($tmpPath, $target)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded image']);
            exit;
        }

        $storedImages[] = 'uploads/posts/' . $basename . '.' . $ext;
    }

    if (!empty($storedImages)) {
        $media_type = 'image';
        $media_path = $storedImages[0];
        $media_json = json_encode($storedImages, JSON_UNESCAPED_SLASHES);
    }
} elseif ($hasLegacySingle) {
    $file = $_FILES['media'];
    $fsize = (int) $file['size'];
    $mime = (string) $file['type'];

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

    $ext = pathinfo((string)$file['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(10));
    $target = $uploadDir . $basename . '.' . $ext;

    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        exit;
    }

    $media_path = 'uploads/posts/' . $basename . '.' . $ext;
    if ($media_type === 'image') {
        $media_json = json_encode([$media_path], JSON_UNESCAPED_SLASHES);
    }
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
        `media_json` TEXT DEFAULT NULL,
        `media_type` ENUM('image','video','file') DEFAULT NULL,
        `share_facebook` TINYINT(1) DEFAULT 0,
        `share_anonymous` TINYINT(1) DEFAULT 0,
        `status` VARCHAR(20) DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $columnStmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'media_json'");
    if (!$columnStmt || !$columnStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN media_json TEXT DEFAULT NULL AFTER media_path");
    }

    $anonStmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'share_anonymous'");
    if (!$anonStmt || !$anonStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN share_anonymous TINYINT(1) DEFAULT 0 AFTER share_facebook");
    }

    $statusStmt = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'");
    if (!$statusStmt || !$statusStmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER share_anonymous");
    }

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

    $ins = $pdo->prepare('INSERT INTO posts (case_id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, share_facebook, share_anonymous, status) VALUES (:case_id, :author_role, :author_id, :author_name, :category, :text, :media_path, :media_json, :media_type, :share_facebook, :share_anonymous, :status)');
    $ins->execute([
        'case_id' => $case_id,
        'author_role' => $role,
        'author_id' => $user_id,
        'author_name' => $author_name,
        'category' => $category,
        'text' => $text ?: null,
        'media_path' => $media_path,
        'media_json' => $media_json,
        'media_type' => $media_type,
        'share_facebook' => $share_fb,
        'share_anonymous' => $share_anonymous,
        'status' => 'pending',
    ]);

    $postId = (int)$pdo->lastInsertId();

    // Automatically escalate to crime_reports (Active Investigations / Crime Case Table)
    $caseRef = 'PT' . str_pad((string)$postId, 4, '0', STR_PAD_LEFT);
    $reportType = $category !== '' ? strtolower($category) : 'post_report';
    $description = $text !== '' ? $text : 'Automatically escalated from Post';
    
    // Ensure media variables are properly formatted
    $mediaPathCrime = $media_path ? ltrim($media_path, '/') : null;
    $mediaJsonCrime = $media_json;
    
    // Ensure crime_reports table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS crime_reports (
        crime_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        case_ref VARCHAR(80) NOT NULL,
        source_type VARCHAR(40) NOT NULL DEFAULT 'missing_person',
        source_ref_id BIGINT UNSIGNED DEFAULT NULL,
        report_type VARCHAR(60) NOT NULL DEFAULT 'missing_person',
        severity VARCHAR(20) NOT NULL DEFAULT 'high',
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        landmark VARCHAR(255) DEFAULT NULL,
        reporter_name VARCHAR(150) DEFAULT NULL,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        anon_token VARCHAR(80) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        media_path VARCHAR(255) DEFAULT NULL,
        media_json TEXT DEFAULT NULL,
        lat DECIMAL(10,7) DEFAULT NULL,
        lng DECIMAL(10,7) DEFAULT NULL,
        submitted_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        closed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (crime_id),
        UNIQUE KEY uq_crime_reports_case_ref (case_ref),
        KEY idx_crime_reports_status (status),
        KEY idx_crime_reports_source (source_type, source_ref_id),
        KEY idx_crime_reports_submitted (submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $upsertCrime = $pdo->prepare("INSERT INTO crime_reports
        (case_ref, source_type, source_ref_id, report_type, severity, status, landmark, reporter_name, anonymous, description, media_path, media_json, submitted_at, updated_at, lat, lng)
        VALUES
        (:case_ref, 'post', :source_ref_id, :report_type, 'medium', 'new', :landmark, :reporter_name, :anonymous, :description, :media_path, :media_json, NOW(), NOW(), :lat, :lng)
    ");
    
    $upsertCrime->execute([
        ':case_ref' => $caseRef,
        ':source_ref_id' => $postId,
        ':report_type' => $reportType,
        ':landmark' => $category !== '' ? $category : 'Post report',
        ':reporter_name' => $author_name ?: 'Unknown',
        ':anonymous' => $share_anonymous,
        ':description' => $description,
        ':media_path' => $mediaPathCrime,
        ':media_json' => $mediaJsonCrime,
        ':lat' => 23.8103,
        ':lng' => 90.4125,
    ]);

    // Notify the author that the post is submitted and waiting for admin review
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }

    $recipientEntity = match ($role) {
        'police' => 'policeman',
        'volunteer' => 'volunteer',
        'contributor' => 'camera_contributor',
        default => 'user',
    };

    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
    $notify->execute([
        ':entity' => $recipientEntity,
        ':rid' => $user_id,
        ':title' => 'Post submitted for review',
        ':message' => 'We received your post. An admin will review it shortly.',
        ':level' => 'info',
        ':post_id' => $postId,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Saved',
        'post_id' => $postId,
        'share_facebook' => $share_fb,
        'share_anonymous' => $share_anonymous,
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

?>
