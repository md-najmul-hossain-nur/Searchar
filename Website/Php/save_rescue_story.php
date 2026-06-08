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

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$authorName = trim((string)($payload['author_name'] ?? ''));
$authorRole = trim((string)($payload['author_role'] ?? ''));
$storyText = trim((string)($payload['story_text'] ?? ''));

if ($authorName === '' || $authorRole === '' || $storyText === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

try {
    $stmt = $pdo->prepare("INSERT INTO rescue_stories (user_id, author_name, author_role, story_text, status) VALUES (:uid, :name, :role, :text, 'pending')");
    $stmt->execute([
        ':uid' => $userId,
        ':name' => $authorName,
        ':role' => $authorRole,
        ':text' => $storyText
    ]);

    // Notify Admin via user_notifications (recipient_entity = 'admins')
    // We can assume admin user_id is 1 or we can just insert a general notification for admins
    $notify = $pdo->prepare("INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES ('admins', 0, 'New Rescue Story', :msg, 'info')");
    $notify->execute([
        ':msg' => "A new rescue story has been submitted by $authorName for review."
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('save_rescue_story error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save your story. Please try again.']);
}
