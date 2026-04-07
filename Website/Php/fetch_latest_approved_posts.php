<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['role']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $table, ':c' => $column]);
    return (bool)$stmt->fetchColumn();
}

function timeAgo(?string $datetime): string {
    if (!$datetime) return 'Just now';
    try {
        $created = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $created->getTimestamp();
        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return (int)floor($diff / 60) . ' min ago';
        if ($diff < 86400) return (int)floor($diff / 3600) . ' hr ago';
        if ($diff < 2592000) {
            $days = (int)floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        return $created->format('d M Y');
    } catch (Throwable $e) {
        return 'Just now';
    }
}

function getAuthorPhoto(PDO $pdo, string $authorRole, int $authorId): string {
    if (strtolower(trim($authorRole)) === 'admin') {
        return '../Images/businessman.gif';
    }

    $roleMap = [
        'user' => ['table' => 'users', 'id_col' => 'user_id', 'folder' => 'user'],
        'police' => ['table' => 'policemen', 'id_col' => 'police_id', 'folder' => 'police'],
        'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id', 'folder' => 'volunteer'],
        'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id', 'folder' => 'camera'],
    ];

    if (!isset($roleMap[$authorRole]) || $authorId <= 0) {
        return '../Images/demo_pic/profile.jpg';
    }

    $table = $roleMap[$authorRole]['table'];
    $idCol = $roleMap[$authorRole]['id_col'];
    $folder = $roleMap[$authorRole]['folder'];

    try {
        $stmt = $pdo->prepare("SELECT profile_photo FROM {$table} WHERE {$idCol} = :id LIMIT 1");
        $stmt->execute([':id' => $authorId]);
        $photo = (string)($stmt->fetchColumn() ?: '');
        if ($photo !== '') {
            return '../uploads/' . $folder . '/' . $photo;
        }
    } catch (Throwable $e) {
    }

    return '../Images/demo_pic/profile.jpg';
}

function anonymousDisplayName(): string {
    return 'Anonymous';
}

function anonymousDisplayPhoto(): string {
    return '../Images/anonymously.gif';
}

try {
    if (!tableExists($pdo, 'posts')) {
        echo json_encode(['success' => true, 'rows' => []]);
        exit;
    }

    $sinceId = isset($_GET['since_id']) ? (int)$_GET['since_id'] : 0;
    if ($sinceId < 0) $sinceId = 0;

    $hasMediaJson = columnExists($pdo, 'posts', 'media_json');
    $hasStatus = columnExists($pdo, 'posts', 'status');
    $hasShareAnonymous = columnExists($pdo, 'posts', 'share_anonymous');

    $selectCols = "id, author_role, author_id, author_name, category, text, media_path, media_type, created_at";
    if ($hasMediaJson) {
        $selectCols .= ", media_json";
    }
    if ($hasShareAnonymous) {
        $selectCols .= ", share_anonymous";
    }

    $whereParts = ["id > :since_id"];
    if ($hasStatus) {
        $whereParts[] = "status = 'approved'";
    }

    $sql = "SELECT {$selectCols} FROM posts WHERE " . implode(' AND ', $whereParts) . " ORDER BY id ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':since_id' => $sinceId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $payload = [];
    foreach ($rows as $row) {
        $authorRole = (string)($row['author_role'] ?? 'user');
        $authorId = (int)($row['author_id'] ?? 0);
        $isAnonymous = (int)($row['share_anonymous'] ?? 0) === 1;

        $authorName = $isAnonymous
            ? anonymousDisplayName()
            : (string)($row['author_name'] ?? 'Unknown User');
        $authorPhoto = $isAnonymous
            ? anonymousDisplayPhoto()
            : getAuthorPhoto($pdo, $authorRole, $authorId);

        $payload[] = [
            'id' => (int)($row['id'] ?? 0),
            'author_name' => $authorName,
            'author_role' => $authorRole,
            'author_id' => $authorId,
            'author_photo' => $authorPhoto,
            'share_anonymous' => $isAnonymous ? 1 : 0,
            'category' => (string)($row['category'] ?? 'general'),
            'text' => (string)($row['text'] ?? ''),
            'media_path' => (string)($row['media_path'] ?? ''),
            'media_json' => isset($row['media_json']) ? (string)$row['media_json'] : '',
            'media_type' => (string)($row['media_type'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'time_ago' => timeAgo((string)($row['created_at'] ?? '')),
            'status' => 'approved',
        ];
    }

    echo json_encode([
        'success' => true,
        'rows' => $payload,
    ]);
} catch (Throwable $e) {
    error_log('fetch_latest_approved_posts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch latest posts'
    ]);
}

