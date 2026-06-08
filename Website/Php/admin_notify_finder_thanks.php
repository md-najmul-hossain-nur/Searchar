<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
$caseId = trim((string)($payload['case_id'] ?? ''));
$postId = (int)($payload['post_id'] ?? 0);

if ($caseId === '' || $postId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // Get post author details
    $stmt = $pdo->prepare("SELECT author_id, author_name, author_role FROM posts WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $postId]);
    $postRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$postRow || !$postRow['author_id']) {
        echo json_encode(['success' => false, 'error' => 'Finder details not found or anonymous']);
        exit;
    }

    $authorId = (int)$postRow['author_id'];
    $authorName = (string)$postRow['author_name'];
    $authorRole = strtolower((string)$postRow['author_role']);
    $email = '';

    // Look up email based on role
    if ($authorRole === 'user') {
        $uStmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :id LIMIT 1");
        $uStmt->execute([':id' => $authorId]);
        $email = $uStmt->fetchColumn();
    } elseif ($authorRole === 'volunteer') {
        $uStmt = $pdo->prepare("SELECT email FROM volunteers WHERE volunteer_id = :id LIMIT 1");
        $uStmt->execute([':id' => $authorId]);
        $email = $uStmt->fetchColumn();
    } elseif ($authorRole === 'police') {
        $uStmt = $pdo->prepare("SELECT email FROM policemen WHERE police_id = :id LIMIT 1");
        $uStmt->execute([':id' => $authorId]);
        $email = $uStmt->fetchColumn();
    }

    $entityMap = [
        'user' => 'users',
        'volunteer' => 'volunteer',
        'police' => 'policemen'
    ];
    $entity = $entityMap[$authorRole] ?? 'users';

    // Send website notification
    $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level) VALUES (:entity, :rid, :title, :message, :level)');
    $notify->execute([
        ':entity' => $entity,
        ':rid' => $authorId,
        ':title' => 'Thank You! (Case ' . $caseId . ')',
        ':message' => "Your post (ID: $postId) was matched by our AI and helped us find a missing person for Case $caseId. Thank you for your help!",
        ':level' => 'success'
    ]);

    // Send email if available
    if ($email) {
        sendEmailViaPHPMailer(
            $email,
            "Thank You! Your post helped solve Case $caseId",
            "Hello $authorName,<br><br>We have wonderful news! Your website post was matched by our AI engine and directly helped us locate a missing person for Case <b>$caseId</b>.<br><br>Thank you so much for being an active and helpful member of our community. Your efforts make a real difference.<br><br>Best Regards,<br>Searchar Team",
            "Hello $authorName,\n\nWe have wonderful news! Your website post was matched by our AI engine and directly helped us locate a missing person for Case $caseId.\n\nThank you so much for being an active and helpful member of our community. Your efforts make a real difference.\n\nBest Regards,\nSearchar Team"
        );
    }

    echo json_encode([
        'success' => true,
        'message' => 'Thank You notification sent'
    ]);

} catch (Throwable $e) {
    error_log('admin_notify_finder_thanks error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process request']);
}
