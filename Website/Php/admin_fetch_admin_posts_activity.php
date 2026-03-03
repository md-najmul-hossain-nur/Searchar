<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

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

function canonicalRole(string $role): string {
    $r = strtolower(trim($role));
    return match ($r) {
        'user', 'users' => 'user',
        'police', 'policeman', 'policemen' => 'police',
        'volunteer', 'volunteers' => 'volunteer',
        'contributor', 'camera_contributor', 'camera_contributors' => 'contributor',
        default => 'user',
    };
}

function roleSqlMap(string $canonicalRole): array {
    return match ($canonicalRole) {
        'user' => ['table' => 'users', 'id_col' => 'user_id'],
        'police' => ['table' => 'policemen', 'id_col' => 'police_id'],
        'volunteer' => ['table' => 'volunteers', 'id_col' => 'volunteer_id'],
        'contributor' => ['table' => 'camera_contributors', 'id_col' => 'camera_id'],
        default => ['table' => 'users', 'id_col' => 'user_id'],
    };
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    if (!tableExists($pdo, 'posts')) {
        echo json_encode(['success' => true, 'rows' => []]);
        exit;
    }

    $hasStatus = (bool)$pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'status' LIMIT 1")->fetchColumn();
    $where = $hasStatus
        ? "WHERE LOWER(COALESCE(p.author_role,'')) = 'admin' AND LOWER(COALESCE(p.status,'approved')) = 'approved'"
        : "WHERE LOWER(COALESCE(p.author_role,'')) = 'admin'";

    $likesJoin = tableExists($pdo, 'post_likes')
        ? "LEFT JOIN (SELECT post_id, COUNT(*) AS likes_count FROM post_likes GROUP BY post_id) l ON l.post_id = p.id"
        : "LEFT JOIN (SELECT 0 AS post_id, 0 AS likes_count) l ON l.post_id = p.id";

    $commentsJoin = tableExists($pdo, 'post_comments')
        ? "LEFT JOIN (SELECT post_id, COUNT(*) AS comments_count FROM post_comments GROUP BY post_id) c ON c.post_id = p.id"
        : "LEFT JOIN (SELECT 0 AS post_id, 0 AS comments_count) c ON c.post_id = p.id";

    $sql = "SELECT p.id, p.text, p.category, p.share_facebook, p.created_at,
                   COALESCE(l.likes_count, 0) AS likes_count,
                   COALESCE(c.comments_count, 0) AS comments_count
            FROM posts p
            {$likesJoin}
            {$commentsJoin}
            {$where}
            ORDER BY p.id DESC
            LIMIT 100";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$rows) {
        echo json_encode(['success' => true, 'rows' => []]);
        exit;
    }

    $postIds = array_map(static fn(array $r): int => (int)($r['id'] ?? 0), $rows);
    $postIds = array_values(array_filter($postIds, static fn(int $v): bool => $v > 0));

    $commentsByPost = [];
    if ($postIds && tableExists($pdo, 'post_comments')) {
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $commentSql = "SELECT comment_id, post_id, actor_role, actor_id, comment_text, created_at
                       FROM post_comments
                       WHERE post_id IN ({$placeholders})
                       ORDER BY created_at DESC, comment_id DESC
                       LIMIT 600";
        $commentStmt = $pdo->prepare($commentSql);
        $commentStmt->execute($postIds);
        $commentRows = $commentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $roleBuckets = ['user' => [], 'police' => [], 'volunteer' => [], 'contributor' => []];
        foreach ($commentRows as $commentRow) {
            $role = canonicalRole((string)($commentRow['actor_role'] ?? 'user'));
            $actorId = (int)($commentRow['actor_id'] ?? 0);
            if ($actorId > 0) {
                $roleBuckets[$role][$actorId] = true;
            }
        }

        $profiles = [];
        foreach ($roleBuckets as $role => $idsMap) {
            $ids = array_keys($idsMap);
            if (!$ids) {
                continue;
            }
            $map = roleSqlMap($role);
            $idsPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            $profileStmt = $pdo->prepare("SELECT {$map['id_col']} AS person_id, full_name FROM {$map['table']} WHERE {$map['id_col']} IN ({$idsPlaceholders})");
            $profileStmt->execute($ids);
            foreach ($profileStmt->fetchAll(PDO::FETCH_ASSOC) as $profileRow) {
                $personId = (int)($profileRow['person_id'] ?? 0);
                if ($personId <= 0) continue;
                $profiles[$role . ':' . $personId] = trim((string)($profileRow['full_name'] ?? '')) ?: 'Someone';
            }
        }

        foreach ($commentRows as $commentRow) {
            $postId = (int)($commentRow['post_id'] ?? 0);
            if ($postId <= 0) continue;
            if (!isset($commentsByPost[$postId])) {
                $commentsByPost[$postId] = [];
            }
            if (count($commentsByPost[$postId]) >= 3) {
                continue;
            }

            $role = canonicalRole((string)($commentRow['actor_role'] ?? 'user'));
            $actorId = (int)($commentRow['actor_id'] ?? 0);
            $actorName = $profiles[$role . ':' . $actorId] ?? 'Someone';

            $commentsByPost[$postId][] = [
                'actor_name' => $actorName,
                'comment_text' => (string)($commentRow['comment_text'] ?? ''),
                'created_at' => (string)($commentRow['created_at'] ?? ''),
            ];
        }
    }

    $out = [];
    foreach ($rows as $row) {
        $postId = (int)($row['id'] ?? 0);
        $out[] = [
            'id' => $postId,
            'text' => (string)($row['text'] ?? ''),
            'category' => (string)($row['category'] ?? 'general'),
            'share_facebook' => (int)($row['share_facebook'] ?? 0),
            'created_at' => (string)($row['created_at'] ?? ''),
            'likes_count' => (int)($row['likes_count'] ?? 0),
            'comments_count' => (int)($row['comments_count'] ?? 0),
            'comments' => $commentsByPost[$postId] ?? [],
        ];
    }

    echo json_encode([
        'success' => true,
        'rows' => $out,
    ]);
} catch (Throwable $e) {
    error_log('admin_fetch_admin_posts_activity error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load admin post activity']);
}
