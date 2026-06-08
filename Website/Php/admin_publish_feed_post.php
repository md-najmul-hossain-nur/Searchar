<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
$isAdminSession = ($sessionRole === 'admin');

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$isAdminPanelRef = false;
if ($referer !== '') {
    $isAdminPanelRef = (
        stripos($referer, '/Website/Html/Admin.html') !== false ||
        stripos($referer, '/Website/Html/Admin.php') !== false
    );
}

if (!$isAdminSession && !$isAdminPanelRef) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$text = trim((string)($_POST['text'] ?? ''));
$category = strtolower(trim((string)($_POST['category'] ?? 'general')));
$shareFacebook = (int)((string)($_POST['share_facebook'] ?? '0') === '1');

$mediaPath = null;
$mediaJson = null;
$mediaType = null;

if (isset($_FILES['media_file']) && is_array($_FILES['media_file']) && (int)($_FILES['media_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['media_file'];
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Failed to upload media file']);
        exit;
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 20 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Media must be between 1B and 20MB']);
        exit;
    }

    $ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $imgExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $vidExt = ['mp4', 'mov', 'avi', 'webm', 'mkv'];

    if (in_array($ext, $imgExt, true)) {
        $mediaType = 'image';
    } elseif (in_array($ext, $vidExt, true)) {
        $mediaType = 'video';
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unsupported media type']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not create upload directory']);
        exit;
    }

    $basename = uniqid('admin_', true);
    $target = $uploadDir . $basename . '.' . $ext;
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Could not save media file']);
        exit;
    }

    $mediaPath = 'uploads/posts/' . $basename . '.' . $ext;
    if ($mediaType === 'image') {
        $mediaJson = json_encode([$mediaPath], JSON_UNESCAPED_SLASHES);
    }
}

if ($text === '' && $mediaPath === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post text or media is required']);
    exit;
}

if (mb_strlen($text) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Text too long']);
    exit;
}

$allowedCats = ['general', 'alert', 'missing_person', 'criminal_found'];
if (!in_array($category, $allowedCats, true)) {
    $category = 'general';
}

try {
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

    $hasMediaJson = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'media_json' LIMIT 1")->fetchColumn();
    if (!$hasMediaJson) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN media_json TEXT DEFAULT NULL AFTER media_path");
    }

    $hasShareAnon = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'share_anonymous' LIMIT 1")->fetchColumn();
    if (!$hasShareAnon) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN share_anonymous TINYINT(1) DEFAULT 0 AFTER share_facebook");
    }

    $hasStatus = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'status' LIMIT 1")->fetchColumn();
    if (!$hasStatus) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER share_anonymous");
    }

    $ins = $pdo->prepare('INSERT INTO posts (case_id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, share_facebook, share_anonymous, status) VALUES (1, :author_role, 0, :author_name, :category, :text, :media_path, :media_json, :media_type, :share_facebook, 0, :status)');
    $ins->execute([
        ':author_role' => 'admin',
        ':author_name' => 'Admin',
        ':category' => $category,
        ':text' => $text,
        ':media_path' => $mediaPath,
        ':media_json' => $mediaJson,
        ':media_type' => $mediaType,
        ':share_facebook' => $shareFacebook,
        ':status' => 'approved',
    ]);

    $postId = (int)$pdo->lastInsertId();

    $facebookShareResult = [
        'attempted' => false,
        'shared' => false,
        'skipped' => true,
    ];

    if ($shareFacebook) {
        require_once __DIR__ . '/facebook_share.php';
        $postRow = [
            'text' => $text,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'media_json' => $mediaJson
        ];
        try {
            $facebookShareResult = publishPostToFacebook($postRow, loadFacebookPageConfig());
        } catch (Throwable $facebookError) {
            $facebookShareResult = [
                'attempted' => true,
                'shared' => false,
                'error' => $facebookError->getMessage(),
            ];
            error_log('admin_publish_feed_post: Facebook publish failed - ' . $facebookError->getMessage());
        }

        try {
            if (is_array($facebookShareResult) && !empty($facebookShareResult['shared']) && !empty($facebookShareResult['post_id'])) {
                // Check if columns exist first, though they might not. The previous script assumes they might exist or skips.
                // Let's add them just in case.
                $hasIsShare = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'is_share' LIMIT 1")->fetchColumn();
                if (!$hasIsShare) {
                    $pdo->exec("ALTER TABLE posts ADD COLUMN is_share TINYINT(1) DEFAULT 0 AFTER share_anonymous");
                    $pdo->exec("ALTER TABLE posts ADD COLUMN shared_post_id VARCHAR(100) DEFAULT NULL AFTER is_share");
                    $pdo->exec("ALTER TABLE posts ADD COLUMN shared_payload TEXT DEFAULT NULL AFTER shared_post_id");
                }
                $upd = $pdo->prepare("UPDATE posts SET is_share = 1, shared_post_id = :spid, shared_payload = :payload WHERE id = :id");
                $payloadJson = json_encode($facebookShareResult, JSON_UNESCAPED_UNICODE);
                $upd->execute([':spid' => $facebookShareResult['post_id'], ':payload' => $payloadJson, ':id' => $postId]);
            }
        } catch (Throwable $persistErr) {
            error_log('admin_publish_feed_post: Failed to persist Facebook share - ' . $persistErr->getMessage());
        }
    }

    echo json_encode(['success' => true, 'post_id' => $postId, 'facebook_share' => $facebookShareResult]);
} catch (Throwable $e) {
    error_log('admin_publish_feed_post error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to publish admin post']);
}
