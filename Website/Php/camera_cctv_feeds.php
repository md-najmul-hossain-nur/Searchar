<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$allowedRoles = ['contributor', 'camera_contributor', 'camera'];
$role = (string)($_SESSION['role'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Camera contributor access required']);
    exit;
}

function isAjaxRequest(): bool {
    $xrw = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return $xrw === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function ensureCctvTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS camera_cctv_feeds (
        feed_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        camera_id INT UNSIGNED NOT NULL,
        feed_label VARCHAR(150) NOT NULL,
        feed_type VARCHAR(20) NOT NULL DEFAULT 'live',
        stream_scope VARCHAR(20) NOT NULL DEFAULT 'private',
        live_url VARCHAR(1200) DEFAULT NULL,
        video_path VARCHAR(600) DEFAULT NULL,
        camera_location VARCHAR(255) DEFAULT NULL,
        streaming_hours VARCHAR(80) DEFAULT 'continuous',
        allow_ai_detection TINYINT(1) NOT NULL DEFAULT 0,
        allow_public_viewing TINYINT(1) NOT NULL DEFAULT 0,
        ai_alerts_to_volunteers TINYINT(1) NOT NULL DEFAULT 0,
        accumulated_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
        active_started_at DATETIME DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (feed_id),
        KEY idx_camera_active (camera_id, is_active),
        KEY idx_camera_created (camera_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $scopeCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'stream_scope'");
    if (!$scopeCol || !$scopeCol->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN stream_scope VARCHAR(20) NOT NULL DEFAULT 'private' AFTER feed_type");
    }

    $accCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'accumulated_seconds'");
    if (!$accCol || !$accCol->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN accumulated_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER ai_alerts_to_volunteers");
    }

    $startedCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'active_started_at'");
    if (!$startedCol || !$startedCol->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN active_started_at DATETIME DEFAULT NULL AFTER accumulated_seconds");
    }

    $payoutCol = $pdo->query("SHOW COLUMNS FROM camera_cctv_feeds LIKE 'payout_count'");
    if (!$payoutCol || !$payoutCol->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN payout_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER accumulated_seconds");
    }
}

function normalizeHours(string $raw): string {
    $v = strtolower(trim($raw));
    $allowed = ['30min', '1to2h', '2to6h', '6to12h', '12to24h', '24plus', 'continuous', 'scheduled'];
    return in_array($v, $allowed, true) ? $v : 'continuous';
}

function normalizeScope(string $raw): string {
    $v = strtolower(trim($raw));
    return in_array($v, ['public', 'private'], true) ? $v : 'private';
}

function generateNextFeedLabel(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare('SELECT feed_label FROM camera_cctv_feeds WHERE camera_id = :camera_id');
    $stmt->execute(['camera_id' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $max = 0;
    foreach ($rows as $label) {
        $txt = trim((string)$label);
        if (preg_match('/(\d+)$/', $txt, $m) === 1) {
            $n = (int)$m[1];
            if ($n > $max) {
                $max = $n;
            }
        }
    }

    return 'Camera ' . ($max + 1);
}

function hourlyRateForFeedType(string $feedType): int {
    // 20 BDT per 30 minutes = 40 BDT per hour
    return 40;
}

function computeAccruedSeconds(array $row): int {
    $acc = (int)($row['accumulated_seconds'] ?? 0);
    $isActive = (int)($row['is_active'] ?? 0) === 1;
    if (!$isActive) {
        return max(0, $acc);
    }

    $startRaw = (string)($row['active_started_at'] ?? '');
    if ($startRaw === '') {
        $startRaw = (string)($row['created_at'] ?? '');
    }
    $startTs = strtotime($startRaw ?: 'now');
    if (!$startTs) {
        return max(0, $acc);
    }
    $elapsed = max(0, time() - $startTs);
    return max(0, $acc + $elapsed);
}

function isPrivateOrLocalHost(string $url): bool {
    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host === '') {
        return true;
    }

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    if (str_ends_with($host, '.local') || str_ends_with($host, '.lan')) {
        return true;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $publicIp = filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
        return $publicIp === false;
    }

    return false;
}

function mapFeedRow(array $row): array {
    $videoPath = (string)($row['video_path'] ?? '');
    return [
        'feed_id' => (int)$row['feed_id'],
        'feed_label' => (string)$row['feed_label'],
        'feed_type' => (string)$row['feed_type'],
        'stream_scope' => (string)($row['stream_scope'] ?? 'private'),
        'live_url' => (string)($row['live_url'] ?? ''),
        'video_path' => $videoPath,
        'video_url' => $videoPath !== '' ? '../' . ltrim($videoPath, '/') : '',
        'camera_location' => (string)($row['camera_location'] ?? ''),
        'streaming_hours' => (string)($row['streaming_hours'] ?? 'continuous'),
        'allow_ai_detection' => (int)($row['allow_ai_detection'] ?? 0),
        'allow_public_viewing' => (int)($row['allow_public_viewing'] ?? 0),
        'ai_alerts_to_volunteers' => (int)($row['ai_alerts_to_volunteers'] ?? 0),
        'accumulated_seconds' => (int)($row['accumulated_seconds'] ?? 0),
        'active_started_at' => (string)($row['active_started_at'] ?? ''),
        'is_active' => (int)($row['is_active'] ?? 0),
        'hourly_rate' => hourlyRateForFeedType((string)($row['feed_type'] ?? 'recorded')),
        'accrued_seconds' => computeAccruedSeconds($row),
        'created_at' => (string)($row['created_at'] ?? ''),
    ];
}

try {
    ensureCctvTable($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT * FROM camera_cctv_feeds WHERE camera_id = :camera_id ORDER BY feed_id DESC');
        $stmt->execute(['camera_id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $feeds = array_map('mapFeedRow', $rows);
        respond(['success' => true, 'feeds' => $feeds]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    $action = strtolower(trim((string)($_POST['action'] ?? 'create')));

    if ($action === 'toggle') {
        $feedId = (int)($_POST['feed_id'] ?? 0);
        $newState = isset($_POST['is_active']) ? (int)$_POST['is_active'] : -1;
        if ($feedId <= 0 || ($newState !== 0 && $newState !== 1)) {
            respond(['success' => false, 'error' => 'Invalid toggle request'], 400);
        }

        $sel = $pdo->prepare('SELECT is_active, accumulated_seconds, active_started_at, created_at FROM camera_cctv_feeds WHERE feed_id = :feed_id AND camera_id = :camera_id LIMIT 1');
        $sel->execute(['feed_id' => $feedId, 'camera_id' => $userId]);
        $current = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            respond(['success' => false, 'error' => 'Feed not found'], 404);
        }

        $currentActive = (int)($current['is_active'] ?? 0) === 1;
        $accSeconds = (int)($current['accumulated_seconds'] ?? 0);
        $activeStartedAt = (string)($current['active_started_at'] ?? '');

        if ($newState === 0 && $currentActive) {
            $startRaw = $activeStartedAt !== '' ? $activeStartedAt : (string)($current['created_at'] ?? '');
            $startTs = strtotime($startRaw ?: 'now');
            if ($startTs) {
                $accSeconds += max(0, time() - $startTs);
            }
            $activeStartedAt = '';
        } elseif ($newState === 1 && !$currentActive) {
            $activeStartedAt = date('Y-m-d H:i:s');
        }

        $upd = $pdo->prepare('UPDATE camera_cctv_feeds SET is_active = :is_active, accumulated_seconds = :accumulated_seconds, active_started_at = :active_started_at WHERE feed_id = :feed_id AND camera_id = :camera_id LIMIT 1');
        $upd->execute([
            'is_active' => $newState,
            'accumulated_seconds' => max(0, $accSeconds),
            'active_started_at' => $activeStartedAt !== '' ? $activeStartedAt : null,
            'feed_id' => $feedId,
            'camera_id' => $userId,
        ]);

        if (!isAjaxRequest() && !empty($_POST['return_to'])) {
            header('Location: ' . (string)$_POST['return_to']);
            exit;
        }

        respond(['success' => true]);
    }

    if ($action === 'delete') {
        $feedId = (int)($_POST['feed_id'] ?? 0);
        if ($feedId <= 0) {
            respond(['success' => false, 'error' => 'Invalid feed id'], 400);
        }

        $sel = $pdo->prepare('SELECT video_path FROM camera_cctv_feeds WHERE feed_id = :feed_id AND camera_id = :camera_id LIMIT 1');
        $sel->execute(['feed_id' => $feedId, 'camera_id' => $userId]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            respond(['success' => false, 'error' => 'Feed not found'], 404);
        }

        $del = $pdo->prepare('DELETE FROM camera_cctv_feeds WHERE feed_id = :feed_id AND camera_id = :camera_id LIMIT 1');
        $del->execute(['feed_id' => $feedId, 'camera_id' => $userId]);

        $videoPath = (string)($row['video_path'] ?? '');
        if ($videoPath !== '') {
            $full = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($videoPath, '/\\'));
            if ($full && is_file($full)) {
                @unlink($full);
            }
        }

        if (!isAjaxRequest() && !empty($_POST['return_to'])) {
            header('Location: ' . (string)$_POST['return_to']);
            exit;
        }

        respond(['success' => true]);
    }

    if ($action !== 'create') {
        respond(['success' => false, 'error' => 'Unknown action'], 400);
    }

    $feedLabel = trim((string)($_POST['feed_label'] ?? ''));
    $feedType = strtolower(trim((string)($_POST['feed_type'] ?? 'webcam')));
    $streamScope = normalizeScope((string)($_POST['stream_scope'] ?? 'private'));
    $cameraLocation = trim((string)($_POST['camera_location'] ?? ''));
    $streamingHours = normalizeHours((string)($_POST['streaming_hours'] ?? 'continuous'));
    $allowAi = !empty($_POST['allow_ai_detection']) ? 1 : 0;
    $allowPublic = !empty($_POST['allow_public_viewing']) ? 1 : 0;
    $aiAlerts = !empty($_POST['ai_alerts_to_volunteers']) ? 1 : 0;
    $permissionConfirmed = !empty($_POST['permission_confirmed']);

    if (!$permissionConfirmed) {
        respond(['success' => false, 'error' => 'Owner permission confirmation is required'], 400);
    }

    if ($feedLabel === '') {
        $feedLabel = generateNextFeedLabel($pdo, $userId);
    }
    if (mb_strlen($feedLabel) > 150) {
        respond(['success' => false, 'error' => 'Camera name is too long (max 150 chars)'], 400);
    }

    if (!in_array($feedType, ['webcam', 'live', 'recorded'], true)) {
        $feedType = 'webcam';
    }

    if ($cameraLocation === '') {
        $locStmt = $pdo->prepare('SELECT city, street, country FROM camera_contributors WHERE camera_id = :camera_id LIMIT 1');
        $locStmt->execute(['camera_id' => $userId]);
        $locRow = $locStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $cameraLocation = trim(implode(' ', array_filter([
            (string)($locRow['city'] ?? ''),
            (string)($locRow['street'] ?? ''),
            (string)($locRow['country'] ?? ''),
        ])));
    }

    $liveUrl = null;
    $videoPath = null;

    if ($feedType === 'webcam') {
        $streamScope = 'private';
    } elseif ($feedType === 'live') {
        $candidateUrl = trim((string)($_POST['live_url'] ?? ''));
        if ($candidateUrl === '' || !filter_var($candidateUrl, FILTER_VALIDATE_URL)) {
            respond(['success' => false, 'error' => 'Valid live URL is required'], 400);
        }
        if ($streamScope === 'public' && isPrivateOrLocalHost($candidateUrl)) {
            respond(['success' => false, 'error' => 'Private/local CCTV URL is not allowed'], 400);
        }
        $liveUrl = $candidateUrl;
    } else {
        if (empty($_FILES['recorded_video']) || !is_uploaded_file((string)$_FILES['recorded_video']['tmp_name'])) {
            respond(['success' => false, 'error' => 'Recorded video file is required'], 400);
        }

        $file = $_FILES['recorded_video'];
        $mime = (string)($file['type'] ?? '');
        $size = (int)($file['size'] ?? 0);
        $allowedVideo = ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska', 'application/octet-stream'];
        if (!in_array($mime, $allowedVideo, true)) {
            respond(['success' => false, 'error' => 'Unsupported video format'], 400);
        }
        $maxVideoSize = 2 * 1024 * 1024 * 1024;
        if ($size <= 0 || $size > $maxVideoSize) {
            respond(['success' => false, 'error' => 'Video must be less than 2GB'], 400);
        }

        $uploadDir = __DIR__ . '/../uploads/cctv/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'mp4';
        }
        $fileName = 'cctv_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target = $uploadDir . $fileName;
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            respond(['success' => false, 'error' => 'Failed to upload video'], 500);
        }

        $videoPath = 'uploads/cctv/' . $fileName;
    }

    $ins = $pdo->prepare('INSERT INTO camera_cctv_feeds (camera_id, feed_label, feed_type, stream_scope, live_url, video_path, camera_location, streaming_hours, allow_ai_detection, allow_public_viewing, ai_alerts_to_volunteers, accumulated_seconds, active_started_at, is_active) VALUES (:camera_id, :feed_label, :feed_type, :stream_scope, :live_url, :video_path, :camera_location, :streaming_hours, :allow_ai_detection, :allow_public_viewing, :ai_alerts_to_volunteers, 0, :active_started_at, 1)');
    $ins->execute([
        'camera_id' => $userId,
        'feed_label' => $feedLabel,
        'feed_type' => $feedType,
        'stream_scope' => $streamScope,
        'live_url' => $liveUrl,
        'video_path' => $videoPath,
        'camera_location' => $cameraLocation !== '' ? $cameraLocation : null,
        'streaming_hours' => $streamingHours,
        'allow_ai_detection' => $allowAi,
        'allow_public_viewing' => $allowPublic,
        'ai_alerts_to_volunteers' => $aiAlerts,
        'active_started_at' => date('Y-m-d H:i:s'),
    ]);

    $feedId = (int)$pdo->lastInsertId();
    $sel = $pdo->prepare('SELECT * FROM camera_cctv_feeds WHERE feed_id = :feed_id AND camera_id = :camera_id LIMIT 1');
    $sel->execute(['feed_id' => $feedId, 'camera_id' => $userId]);
    $row = $sel->fetch(PDO::FETCH_ASSOC) ?: [];

    respond(['success' => true, 'feed' => mapFeedRow($row)]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to process CCTV feed request'], 500);
}
