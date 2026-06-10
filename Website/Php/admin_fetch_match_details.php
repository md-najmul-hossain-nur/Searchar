<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

session_start();
$sessionRole = strtolower(trim((string)($_SESSION['role'] ?? '')));
if ($sessionRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$matchId = (int)($_GET['match_id'] ?? 0);
if ($matchId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid match ID']);
    exit;
}

try {
    // 1. Fetch match info
    $stmt = $pdo->prepare("SELECT report_id, post_id, camera_feed_id, match_status, matched_at, ai_confidence_score FROM ai_post_matches WHERE match_id = :mid LIMIT 1");
    $stmt->execute([':mid' => $matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        echo json_encode(['success' => false, 'error' => 'Match not found']);
        exit;
    }

    $response = [
        'success' => true,
        'match' => $match,
        'report' => null,
        'post' => null,
        'camera_feed' => null
    ];

    // 2. Fetch Report (Missing Person Case)
    if (!empty($match['report_id'])) {
        $repStmt = $pdo->prepare("SELECT report_id, full_name, last_seen_date, last_seen_location, status, person_photo FROM missing_person_reports WHERE report_id = :rid LIMIT 1");
        $repStmt->execute([':rid' => $match['report_id']]);
        $response['report'] = $repStmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Fetch Post (if matched with user post)
    if (!empty($match['post_id'])) {
        $postStmt = $pdo->prepare("SELECT id, author_name, text, media_path, status, created_at FROM posts WHERE id = :pid LIMIT 1");
        $postStmt->execute([':pid' => $match['post_id']]);
        $response['post'] = $postStmt->fetch(PDO::FETCH_ASSOC);
    }

    // 4. Fetch Camera Feed (if matched with CCTV/webcam)
    if (!empty($match['camera_feed_id'])) {
        $camStmt = $pdo->prepare("SELECT id, title, location, stream_url, type, is_active FROM camera_cctv_feeds WHERE id = :cid LIMIT 1");
        $camStmt->execute([':cid' => $match['camera_feed_id']]);
        $response['camera_feed'] = $camStmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode($response);

} catch (Throwable $e) {
    error_log('admin_fetch_match_details error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load match details']);
}
