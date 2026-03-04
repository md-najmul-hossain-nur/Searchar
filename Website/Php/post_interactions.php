<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
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

function isPostAnonymous(PDO $pdo, int $postId): bool {
    if ($postId <= 0) {
        return false;
    }

    static $hasShareAnonymous = null;
    if ($hasShareAnonymous === null) {
        $hasShareAnonymous = (bool)$pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'share_anonymous' LIMIT 1")->fetchColumn();
    }

    if (!$hasShareAnonymous) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT share_anonymous FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $postId]);
    return (int)($stmt->fetchColumn() ?: 0) === 1;
}

function recipientEntityForRole(string $canonicalRole): string {
    return match ($canonicalRole) {
        'user' => 'user',
        'police' => 'policeman',
        'volunteer' => 'volunteer',
        'contributor' => 'camera_contributor',
        default => 'user',
    };
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

function ensureInteractionTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_likes (
        like_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL,
        actor_role VARCHAR(50) NOT NULL,
        actor_id INT UNSIGNED NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (like_id),
        UNIQUE KEY uq_post_actor (post_id, actor_role, actor_id),
        KEY idx_post_likes_post (post_id),
        KEY idx_post_likes_actor (actor_role, actor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS post_comments (
        comment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL,
        parent_comment_id BIGINT UNSIGNED DEFAULT NULL,
        actor_role VARCHAR(50) NOT NULL,
        actor_id INT UNSIGNED NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (comment_id),
        KEY idx_post_comments_post (post_id),
        KEY idx_post_comments_parent (parent_comment_id),
        KEY idx_post_comments_actor (actor_role, actor_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        meta_json TEXT DEFAULT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        KEY idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }

    $hasMeta = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'meta_json' LIMIT 1")->fetchColumn();
    if (!$hasMeta) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN meta_json TEXT NULL AFTER message");
    }
}

function actorSnapshot(PDO $pdo, string $canonicalRole, int $actorId): array {
    if ($actorId <= 0) {
        return ['name' => 'Someone', 'photo' => '../Images/default_profile.png'];
    }

    $map = roleSqlMap($canonicalRole);
    $stmt = $pdo->prepare("SELECT full_name, profile_photo FROM {$map['table']} WHERE {$map['id_col']} = :id LIMIT 1");
    $stmt->execute([':id' => $actorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $photoRaw = trim((string)($row['profile_photo'] ?? ''));
    return [
        'name' => trim((string)($row['full_name'] ?? '')) ?: 'Someone',
        'photo' => $photoRaw !== '' ? '../uploads/user/' . $photoRaw : '../Images/default_profile.png',
    ];
}

function createInteractionNotification(PDO $pdo, int $postId, string $actionType, string $actorRole, int $actorId, string $actorName, string $commentText = '', int $commentId = 0): void {
    $postStmt = $pdo->prepare('SELECT id, author_role, author_id FROM posts WHERE id = :id LIMIT 1');
    $postStmt->execute([':id' => $postId]);
    $post = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        return;
    }

    $authorRole = canonicalRole((string)($post['author_role'] ?? ''));
    $authorId = (int)($post['author_id'] ?? 0);
    if ($authorId <= 0) {
        return;
    }

    if ($authorRole === $actorRole && $authorId === $actorId) {
        return;
    }

    $title = $actionType === 'like' ? 'Someone liked your post' : 'New comment on your post';
    $message = $actionType === 'like'
        ? ($actorName . ' liked your post.')
        : ($actorName . ' commented on your post: "' . mb_substr(trim($commentText), 0, 120) . '"');

    $meta = [
        'action_type' => $actionType,
        'post_id' => $postId,
        'actor_role' => $actorRole,
        'actor_id' => $actorId,
    ];
    if ($commentId > 0) {
        $meta['comment_id'] = $commentId;
    }

    $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :meta_json, :level, 0, :post_id)');
    $ins->execute([
        ':entity' => recipientEntityForRole($authorRole),
        ':rid' => $authorId,
        ':title' => $title,
        ':message' => $message,
        ':meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ':level' => 'info',
        ':post_id' => $postId,
    ]);
}

function fetchComments(PDO $pdo, int $postId): array {
    $stmt = $pdo->prepare('SELECT comment_id, post_id, parent_comment_id, actor_role, actor_id, comment_text, created_at FROM post_comments WHERE post_id = :post_id ORDER BY created_at ASC, comment_id ASC');
    $stmt->execute([':post_id' => $postId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $roleBuckets = [
        'user' => [],
        'police' => [],
        'volunteer' => [],
        'contributor' => [],
    ];

    foreach ($rows as $r) {
        $role = canonicalRole((string)($r['actor_role'] ?? ''));
        $id = (int)($r['actor_id'] ?? 0);
        if ($id > 0) {
            $roleBuckets[$role][$id] = true;
        }
    }

    $profiles = [];
    foreach ($roleBuckets as $role => $idsMap) {
        $ids = array_keys($idsMap);
        if (!$ids) {
            continue;
        }

        $map = roleSqlMap($role);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sel = $pdo->prepare("SELECT {$map['id_col']} AS person_id, full_name, profile_photo FROM {$map['table']} WHERE {$map['id_col']} IN ({$placeholders})");
        $sel->execute($ids);
        foreach ($sel->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $personId = (int)($p['person_id'] ?? 0);
            if ($personId <= 0) continue;
            $photoRaw = trim((string)($p['profile_photo'] ?? ''));
            $profiles[$role . ':' . $personId] = [
                'name' => trim((string)($p['full_name'] ?? '')) ?: 'Someone',
                'photo' => $photoRaw !== '' ? '../uploads/user/' . $photoRaw : '../Images/default_profile.png',
            ];
        }
    }

    $postAnonymous = isPostAnonymous($pdo, $postId);
    $anonymousName = 'Anonymous';
    $anonymousPhoto = '../Images/anonymously.gif';

    $comments = [];
    foreach ($rows as $row) {
        $role = canonicalRole((string)($row['actor_role'] ?? ''));
        $actorId = (int)($row['actor_id'] ?? 0);
        $profile = $profiles[$role . ':' . $actorId] ?? ['name' => 'Someone', 'photo' => '../Images/default_profile.png'];

        $actorName = $postAnonymous ? $anonymousName : (string)$profile['name'];
        $actorPhoto = $postAnonymous ? $anonymousPhoto : (string)$profile['photo'];

        $comments[] = [
            'comment_id' => (int)($row['comment_id'] ?? 0),
            'post_id' => (int)($row['post_id'] ?? 0),
            'parent_comment_id' => isset($row['parent_comment_id']) && is_numeric($row['parent_comment_id']) ? (int)$row['parent_comment_id'] : null,
            'actor_role' => $role,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_photo' => $actorPhoto,
            'actor_is_anonymous' => $postAnonymous,
            'comment_text' => (string)($row['comment_text'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'time_ago' => timeAgo((string)($row['created_at'] ?? '')),
        ];
    }

    return $comments;
}

try {
    ensureInteractionTables($pdo);

    $actorRole = canonicalRole((string)$_SESSION['role']);
    $actorId = (int)$_SESSION['user_id'];
    $actor = actorSnapshot($pdo, $actorRole, $actorId);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $postId = (int)($_GET['post_id'] ?? 0);
        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid post id']);
            exit;
        }

        $existsStmt = $pdo->prepare('SELECT id FROM posts WHERE id = :id LIMIT 1');
        $existsStmt->execute([':id' => $postId]);
        if (!$existsStmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $likesStmt = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
        $likesStmt->execute([':post_id' => $postId]);
        $likesCount = (int)$likesStmt->fetchColumn();

        $likedStmt = $pdo->prepare('SELECT 1 FROM post_likes WHERE post_id = :post_id AND actor_role = :actor_role AND actor_id = :actor_id LIMIT 1');
        $likedStmt->execute([
            ':post_id' => $postId,
            ':actor_role' => $actorRole,
            ':actor_id' => $actorId,
        ]);

        $comments = fetchComments($pdo, $postId);

        echo json_encode([
            'success' => true,
            'data' => [
                'post_id' => $postId,
                'likes_count' => $likesCount,
                'liked_by_me' => (bool)$likedStmt->fetchColumn(),
                'comments_count' => count($comments),
                'comments' => $comments,
            ],
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    $action = strtolower(trim((string)($payload['action'] ?? '')));
    $postId = (int)($payload['post_id'] ?? 0);

    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid post id']);
        exit;
    }

    $existsStmt = $pdo->prepare('SELECT id FROM posts WHERE id = :id LIMIT 1');
    $existsStmt->execute([':id' => $postId]);
    if (!$existsStmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    if ($action === 'toggle_like') {
        $pdo->beginTransaction();

        $check = $pdo->prepare('SELECT like_id FROM post_likes WHERE post_id = :post_id AND actor_role = :actor_role AND actor_id = :actor_id LIMIT 1');
        $check->execute([
            ':post_id' => $postId,
            ':actor_role' => $actorRole,
            ':actor_id' => $actorId,
        ]);
        $likeId = (int)($check->fetchColumn() ?: 0);

        $likedByMe = false;
        if ($likeId > 0) {
            $del = $pdo->prepare('DELETE FROM post_likes WHERE like_id = :like_id LIMIT 1');
            $del->execute([':like_id' => $likeId]);
            $likedByMe = false;
        } else {
            $ins = $pdo->prepare('INSERT INTO post_likes (post_id, actor_role, actor_id) VALUES (:post_id, :actor_role, :actor_id)');
            $ins->execute([
                ':post_id' => $postId,
                ':actor_role' => $actorRole,
                ':actor_id' => $actorId,
            ]);
            $likedByMe = true;
            createInteractionNotification($pdo, $postId, 'like', $actorRole, $actorId, (string)$actor['name']);
        }

        $likesStmt = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
        $likesStmt->execute([':post_id' => $postId]);
        $likesCount = (int)$likesStmt->fetchColumn();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'data' => [
                'post_id' => $postId,
                'liked_by_me' => $likedByMe,
                'likes_count' => $likesCount,
            ],
        ]);
        exit;
    }

    if ($action === 'add_comment') {
        $commentText = trim((string)($payload['comment_text'] ?? ''));
        $parentCommentId = (int)($payload['parent_comment_id'] ?? 0);

        if ($commentText === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
            exit;
        }

        if (mb_strlen($commentText) > 1000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment is too long']);
            exit;
        }

        $parentNullable = null;
        if ($parentCommentId > 0) {
            $parentCheck = $pdo->prepare('SELECT comment_id FROM post_comments WHERE comment_id = :comment_id AND post_id = :post_id LIMIT 1');
            $parentCheck->execute([
                ':comment_id' => $parentCommentId,
                ':post_id' => $postId,
            ]);
            $validParent = (int)($parentCheck->fetchColumn() ?: 0);
            if ($validParent <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid parent comment']);
                exit;
            }
            $parentNullable = $validParent;
        }

        $ins = $pdo->prepare('INSERT INTO post_comments (post_id, parent_comment_id, actor_role, actor_id, comment_text) VALUES (:post_id, :parent_comment_id, :actor_role, :actor_id, :comment_text)');
        $ins->bindValue(':post_id', $postId, PDO::PARAM_INT);
        if ($parentNullable === null) {
            $ins->bindValue(':parent_comment_id', null, PDO::PARAM_NULL);
        } else {
            $ins->bindValue(':parent_comment_id', $parentNullable, PDO::PARAM_INT);
        }
        $ins->bindValue(':actor_role', $actorRole, PDO::PARAM_STR);
        $ins->bindValue(':actor_id', $actorId, PDO::PARAM_INT);
        $ins->bindValue(':comment_text', $commentText, PDO::PARAM_STR);
        $ins->execute();

        $commentId = (int)$pdo->lastInsertId();

        $postAnonymous = isPostAnonymous($pdo, $postId);
        $actorPublicName = $postAnonymous ? 'Anonymous' : (string)$actor['name'];
        $actorPublicPhoto = $postAnonymous ? '../Images/anonymously.gif' : (string)$actor['photo'];

        createInteractionNotification($pdo, $postId, 'comment', $actorRole, $actorId, $actorPublicName, $commentText, $commentId);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM post_comments WHERE post_id = :post_id');
        $countStmt->execute([':post_id' => $postId]);
        $commentsCount = (int)$countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'data' => [
                'comment' => [
                    'comment_id' => $commentId,
                    'post_id' => $postId,
                    'parent_comment_id' => $parentNullable,
                    'actor_role' => $actorRole,
                    'actor_id' => $actorId,
                    'actor_name' => $actorPublicName,
                    'actor_photo' => $actorPublicPhoto,
                    'actor_is_anonymous' => $postAnonymous,
                    'comment_text' => $commentText,
                    'created_at' => date('Y-m-d H:i:s'),
                    'time_ago' => 'Just now',
                ],
                'comments_count' => $commentsCount,
            ],
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported action']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('post_interactions error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process request']);
}
