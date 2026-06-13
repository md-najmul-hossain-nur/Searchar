<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/facebook_share.php';

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

function normalizeRecipientEntity(string $authorRole): string {
    return match (strtolower($authorRole)) {
        'police' => 'policeman',
        'volunteer' => 'volunteer',
        'contributor', 'camera_contributor' => 'camera_contributor',
        default => 'user',
    };
}

function ensureNotificationsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_notifications (
        notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        recipient_entity VARCHAR(60) NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        level VARCHAR(30) NOT NULL DEFAULT 'info',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        target_post_id INT UNSIGNED DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (notification_id),
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasTarget = $pdo->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_notifications' AND COLUMN_NAME = 'target_post_id' LIMIT 1")->fetchColumn();
    if (!$hasTarget) {
        $pdo->exec("ALTER TABLE user_notifications ADD COLUMN target_post_id INT UNSIGNED DEFAULT NULL AFTER is_read");
    }
}

function ensureCrimeReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crime_reports (
        crime_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        case_ref VARCHAR(80) NOT NULL,
        source_type VARCHAR(40) NOT NULL DEFAULT 'missing_person',
        source_ref_id BIGINT UNSIGNED DEFAULT NULL,
        report_type VARCHAR(60) NOT NULL DEFAULT 'missing_person',
        severity VARCHAR(20) NOT NULL DEFAULT 'high',
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        landmark VARCHAR(255) DEFAULT NULL,
        reporter_name VARCHAR(150) DEFAULT NULL,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        anon_token VARCHAR(80) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        media_path VARCHAR(255) DEFAULT NULL,
        media_json TEXT DEFAULT NULL,
        lat DECIMAL(10,7) DEFAULT NULL,
        lng DECIMAL(10,7) DEFAULT NULL,
        submitted_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        closed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (crime_id),
        UNIQUE KEY uq_crime_reports_case_ref (case_ref),
        KEY idx_crime_reports_status (status),
        KEY idx_crime_reports_source (source_type, source_ref_id),
        KEY idx_crime_reports_submitted (submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

$allowedActions = [
    'approve' => 'approved',
    'reject'  => 'rejected',
    'make_report' => 'reported',
    'close_case' => 'closed',
    'approve_share' => 'approved',
];

try {
    $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $action = $_POST['action'] ?? '';

    if ($postId <= 0 || !isset($allowedActions[$action])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid post_id or action']);
        exit;
    }

    if (!tableExists($pdo, 'posts')) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Posts table not found']);
        exit;
    }

    if (!columnExists($pdo, 'posts', 'status')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'pending' AFTER share_anonymous");
    }
    if (!columnExists($pdo, 'posts', 'report_status')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN report_status VARCHAR(20) DEFAULT 'not_reported' AFTER status");
    }
    if (!columnExists($pdo, 'posts', 'reported_at')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN reported_at DATETIME DEFAULT NULL AFTER report_status");
    }
    if (!columnExists($pdo, 'posts', 'report_closed_at')) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN report_closed_at DATETIME DEFAULT NULL AFTER reported_at");
    }

    $postStmt = $pdo->prepare('SELECT id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, share_facebook, share_anonymous, status, report_status, created_at FROM posts WHERE id = :id LIMIT 1');
    $postStmt->execute([':id' => $postId]);
    $postRow = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$postRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    $authorRole = (string)($postRow['author_role'] ?? 'user');
    $authorId = (int)($postRow['author_id'] ?? 0);
    $recipientEntity = normalizeRecipientEntity($authorRole);

    ensureNotificationsTable($pdo);

    $targetStatus = $allowedActions[$action];

    if ($action === 'make_report') {
        $stmt = $pdo->prepare("UPDATE posts SET report_status = 'reported', reported_at = NOW() WHERE id = :id AND LOWER(COALESCE(report_status, 'not_reported')) <> 'reported'");
        $stmt->execute([':id' => $postId]);

        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT report_status FROM posts WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $postId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Post not found']);
                exit;
            }

            echo json_encode([
                'success' => false,
                'error' => 'This post is already reported',
                'report_status' => $row['report_status'] ?? 'reported',
            ]);
            exit;
        }

        ensureCrimeReportsTable($pdo);

        $caseRef = 'PT' . str_pad((string)$postId, 4, '0', STR_PAD_LEFT);
        $category = trim((string)($postRow['category'] ?? ''));
        $reportType = $category !== '' ? strtolower($category) : 'post_report';
        $description = trim((string)($postRow['text'] ?? ''));
        $submittedAt = trim((string)($postRow['created_at'] ?? ''));
        if ($submittedAt === '') {
            $submittedAt = date('Y-m-d H:i:s');
        }

        $mediaPathRaw = trim((string)($postRow['media_path'] ?? ''));
        $mediaPath = $mediaPathRaw !== '' ? ltrim($mediaPathRaw, '/') : null;

        $mediaJsonRaw = trim((string)($postRow['media_json'] ?? ''));
        $mediaJson = null;
        if ($mediaJsonRaw !== '') {
            $parsed = json_decode($mediaJsonRaw, true);
            if (is_array($parsed)) {
                $normalized = [];
                foreach ($parsed as $item) {
                    if (is_string($item)) {
                        $v = trim($item);
                    } elseif (is_array($item)) {
                        $v = trim((string)($item['url'] ?? $item['path'] ?? $item['media_path'] ?? $item['src'] ?? ''));
                    } else {
                        $v = '';
                    }
                    if ($v !== '') {
                        $normalized[] = ltrim($v, '/');
                    }
                }
                if ($normalized) {
                    $mediaJson = json_encode($normalized, JSON_UNESCAPED_SLASHES);
                    if (!$mediaPath) {
                        $mediaPath = $normalized[0];
                    }
                }
            }
        }

        if (!$mediaJson && $mediaPath) {
            $mediaJson = json_encode([$mediaPath], JSON_UNESCAPED_SLASHES);
        }

        $upsertCrime = $pdo->prepare("INSERT INTO crime_reports
            (case_ref, source_type, source_ref_id, report_type, severity, status, landmark, reporter_name, anonymous, description, media_path, media_json, submitted_at, updated_at, lat, lng)
            VALUES
            (:case_ref, 'post', :source_ref_id, :report_type, 'medium', 'new', :landmark, :reporter_name, :anonymous, :description, :media_path, :media_json, :submitted_at, NOW(), :lat, :lng)
            ON DUPLICATE KEY UPDATE
              source_ref_id = VALUES(source_ref_id),
              report_type = VALUES(report_type),
              severity = VALUES(severity),
              status = 'new',
              landmark = VALUES(landmark),
              reporter_name = VALUES(reporter_name),
              anonymous = VALUES(anonymous),
              description = VALUES(description),
              media_path = VALUES(media_path),
              media_json = VALUES(media_json),
              submitted_at = VALUES(submitted_at),
              updated_at = NOW(),
              closed_at = NULL");

        $upsertCrime->execute([
            ':case_ref' => $caseRef,
            ':source_ref_id' => $postId,
            ':report_type' => $reportType,
            ':landmark' => $category !== '' ? $category : 'Post report',
            ':reporter_name' => trim((string)($postRow['author_name'] ?? '')) ?: 'Unknown',
            ':anonymous' => ((int)($postRow['share_anonymous'] ?? 0) === 1) ? 1 : 0,
            ':description' => $description !== '' ? $description : 'Escalated from Post Report',
            ':media_path' => $mediaPath,
            ':media_json' => $mediaJson,
            ':submitted_at' => $submittedAt,
            ':lat' => 23.8103,
            ':lng' => 90.4125,
        ]);

        $notifyPolice = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
        $notifyPolice->execute([
            ':entity' => 'policeman',
            ':rid' => 0,
            ':title' => 'Admin Case Alert',
            ':message' => 'Admin marked a post as reported. Please review it in All Cases.',
            ':level' => 'warning',
            ':post_id' => $postId,
        ]);

        echo json_encode([
            'success' => true,
            'status' => (string)($postRow['status'] ?? 'pending'),
            'report_status' => 'reported',
        ]);
        exit;
    }

    if ($action === 'close_case') {
        $stmt = $pdo->prepare("UPDATE posts SET report_status = 'closed', report_closed_at = NOW() WHERE id = :id AND LOWER(COALESCE(report_status, 'not_reported')) IN ('reported','under_review')");
        $stmt->execute([':id' => $postId]);

        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT report_status FROM posts WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $postId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Post not found']);
                exit;
            }

            $currentReportStatus = strtolower((string)($row['report_status'] ?? 'not_reported'));
            if ($currentReportStatus === 'closed') {
                echo json_encode([
                    'success' => true,
                    'status' => (string)($postRow['status'] ?? 'approved'),
                    'report_status' => 'closed',
                    'already_closed' => true,
                ]);
                exit;
            }

            echo json_encode([
                'success' => false,
                'error' => 'This post case is not in reported state',
                'report_status' => $row['report_status'] ?? 'not_reported',
            ]);
            exit;
        }

        if (tableExists($pdo, 'crime_reports')) {
            $caseRef = 'PT' . str_pad((string)$postId, 4, '0', STR_PAD_LEFT);
            $closeCrime = $pdo->prepare("UPDATE crime_reports
                SET status = 'closed', closed_at = NOW(), updated_at = NOW()
                WHERE case_ref = :case_ref OR (source_type = 'post' AND source_ref_id = :post_id)");
            $closeCrime->execute([
                ':case_ref' => $caseRef,
                ':post_id' => $postId,
            ]);
        }

        $notifyPolice = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
        $notifyPolice->execute([
            ':entity' => 'policeman',
            ':rid' => 0,
            ':title' => 'Case Closed by Admin',
            ':message' => 'Admin closed a reported post case. Live board will auto-sync to solved history.',
            ':level' => 'info',
            ':post_id' => $postId,
        ]);

        echo json_encode([
            'success' => true,
            'status' => (string)($postRow['status'] ?? 'approved'),
            'report_status' => 'closed',
        ]);
        exit;
    }

    if ($action === 'reject') {
        // Delete only if still pending/null
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id AND LOWER(COALESCE(status, 'pending')) = 'pending'");
        $stmt->execute([':id' => $postId]);

        if ($stmt->rowCount() === 0) {
            $chk = $pdo->prepare("SELECT status FROM posts WHERE id = :id LIMIT 1");
            $chk->execute([':id' => $postId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Post not found']);
                exit;
            }
            echo json_encode([
                'success' => false,
                'error' => 'Post already decided',
                'status' => $row['status'] ?? 'pending',
            ]);
            exit;
        }

        if ($authorId > 0) {
            $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
            $notify->execute([
                ':entity' => $recipientEntity,
                ':rid' => $authorId,
                ':title' => 'Your post was rejected',
                ':message' => 'An admin rejected your post and it has been removed.',
                ':level' => 'warning',
                ':post_id' => $postId,
            ]);
        }

        if (isset($_SESSION['user_id'])) {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_type, details) VALUES (?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], 'Rejected Post', "Rejected post ID: $postId"]);
        }

        echo json_encode([
            'success' => true,
            'status' => 'deleted',
            'deleted' => true,
        ]);
        exit;
    }

    // Approve path: update only if still pending/null
    $stmt = $pdo->prepare("UPDATE posts SET status = :status WHERE id = :id AND LOWER(COALESCE(status, 'pending')) = 'pending'");
    $stmt->execute([':status' => $targetStatus, ':id' => $postId]);

    $proceedWithShare = false;
    if ($stmt->rowCount() === 0) {
        // Check existing status to give a friendly message
        $chk = $pdo->prepare("SELECT status FROM posts WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $postId]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            exit;
        }

        $current = $row['status'] ?? 'pending';
        if ($action === 'approve_share' && strtolower((string)$current) === 'approved') {
            $proceedWithShare = true;
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Post already decided',
                'status' => $current,
            ]);
            exit;
        }
    } else {
        $proceedWithShare = true;
        if ($authorId > 0) {
            $notify = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, level, is_read, target_post_id) VALUES (:entity, :rid, :title, :message, :level, 0, :post_id)');
            $notify->execute([
                ':entity' => $recipientEntity,
                ':rid' => $authorId,
                ':title' => 'Your post was approved',
                ':message' => 'An admin approved your post.',
                ':level' => 'success',
                ':post_id' => $postId,
            ]);
        }
        if (isset($_SESSION['user_id'])) {
            $logStmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action_type, details) VALUES (?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], 'Approved Post', "Approved post ID: $postId ($targetStatus)"]);
        }
    }

    $facebookShareResult = [
        'attempted' => false,
        'shared' => false,
        'skipped' => true,
    ];

    $shareRequested = $action === 'approve_share';
    $shareAnonymous = (int)($postRow['share_anonymous'] ?? 0) === 1;
    
    if ($action === 'approve_share' && isset($_POST['custom_caption'])) {
        $postRow['text'] = $_POST['custom_caption'];
    }
    if ($shareRequested && !$shareAnonymous) {
        try {
            $facebookShareResult = publishPostToFacebook($postRow, loadFacebookPageConfig());
        } catch (Throwable $facebookError) {
            $facebookShareResult = [
                'attempted' => true,
                'shared' => false,
                'error' => $facebookError->getMessage(),
            ];
            error_log('admin_update_post_status: Facebook publish failed for post ' . $postId . ' - ' . $facebookError->getMessage());
        }
        // Persist shared post id/payload when available
        try {
            if (is_array($facebookShareResult) && !empty($facebookShareResult['shared']) && !empty($facebookShareResult['post_id'])) {
                $upd = $pdo->prepare("UPDATE posts SET is_share = 1, shared_post_id = :spid, shared_payload = :payload WHERE id = :id");
                $payloadJson = json_encode($facebookShareResult, JSON_UNESCAPED_UNICODE);
                $upd->execute([':spid' => $facebookShareResult['post_id'], ':payload' => $payloadJson, ':id' => $postId]);
            }
        } catch (Throwable $persistErr) {
            error_log('admin_update_post_status: Failed to persist Facebook share for post ' . $postId . ' - ' . $persistErr->getMessage());
        }
    } elseif ($shareRequested && $shareAnonymous) {
        error_log('admin_update_post_status: Facebook share skipped for anonymous post ' . $postId);
    }



    echo json_encode([
        'success' => true,
        'status' => $targetStatus,
        'facebook_share' => $facebookShareResult,
    ]);
} catch (Throwable $e) {
    error_log('admin_update_post_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update status',
    ]);
}
