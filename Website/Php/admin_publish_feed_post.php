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

if ($text === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post text is required']);
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

    $ins = $pdo->prepare('INSERT INTO posts (case_id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, share_facebook, share_anonymous, status) VALUES (1, :author_role, 0, :author_name, :category, :text, NULL, NULL, NULL, 0, 0, :status)');
    $ins->execute([
        ':author_role' => 'admin',
        ':author_name' => 'Admin',
        ':category' => $category,
        ':text' => $text,
        ':status' => 'approved',
    ]);

    echo json_encode(['success' => true, 'post_id' => (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
    error_log('admin_publish_feed_post error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to publish admin post']);
}
