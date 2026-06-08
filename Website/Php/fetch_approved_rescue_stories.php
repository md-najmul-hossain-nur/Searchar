<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT r.author_name, r.author_role, r.story_text, u.profile_photo
        FROM rescue_stories r
        LEFT JOIN users u ON r.user_id = u.user_id
        WHERE r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stories' => $stories
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
