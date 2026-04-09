<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function ensurePostReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL,
        post_author_role VARCHAR(50) DEFAULT NULL,
        post_author_id INT UNSIGNED DEFAULT NULL,
        post_author_name VARCHAR(255) DEFAULT NULL,
        reporter_role VARCHAR(50) NOT NULL,
        reporter_id INT UNSIGNED NOT NULL,
        reporter_name VARCHAR(255) NOT NULL,
        report_category VARCHAR(80) NOT NULL,
        report_details TEXT DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        admin_action_note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actioned_at DATETIME DEFAULT NULL,
        PRIMARY KEY (report_id),
        KEY idx_post_reports_post (post_id),
        KEY idx_post_reports_status (status),
        KEY idx_post_reports_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensureCommentReportsTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comment_reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        comment_id BIGINT UNSIGNED NOT NULL,
        post_id INT UNSIGNED NOT NULL,
        comment_author_role VARCHAR(50) DEFAULT NULL,
        comment_author_id INT UNSIGNED DEFAULT NULL,
        comment_author_name VARCHAR(255) DEFAULT NULL,
        comment_text TEXT DEFAULT NULL,
        reporter_role VARCHAR(50) NOT NULL,
        reporter_id INT UNSIGNED NOT NULL,
        reporter_name VARCHAR(255) NOT NULL,
        report_category VARCHAR(80) NOT NULL,
        report_details TEXT DEFAULT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        admin_action_note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actioned_at DATETIME DEFAULT NULL,
        PRIMARY KEY (report_id),
        KEY idx_comment_reports_comment (comment_id),
        KEY idx_comment_reports_post (post_id),
        KEY idx_comment_reports_status (status),
        KEY idx_comment_reports_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

try {
    if (!tableExists($pdo, 'posts')) {
        echo json_encode(['success' => true, 'rows' => []]);
        exit;
    }

    ensurePostReportsTable($pdo);
    ensureCommentReportsTable($pdo);

    $sql = "SELECT
        r.report_id,
        r.post_id,
        r.post_author_role,
        r.post_author_id,
        r.post_author_name,
        r.reporter_role,
        r.reporter_id,
        r.reporter_name,
        r.report_category,
        r.report_details,
        r.status,
        r.admin_action_note,
        r.created_at AS report_created_at,
        r.actioned_at,
        p.category AS post_category,
        p.text AS post_text,
        p.media_path,
        p.media_json,
        p.media_type,
        p.created_at AS post_created_at
      FROM post_reports r
      LEFT JOIN posts p ON p.id = r.post_id
      ORDER BY r.created_at DESC, r.report_id DESC
      LIMIT 1000";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $commentSql = "SELECT
        'comment' AS report_source,
        r.report_id,
        r.post_id,
        r.comment_id,
        '' AS post_author_role,
        0 AS post_author_id,
        '' AS post_author_name,
        r.comment_author_role AS reported_role,
        r.comment_author_id AS reported_id,
        r.comment_author_name AS reported_name,
        r.reporter_role,
        r.reporter_id,
        r.reporter_name,
        r.report_category,
        r.report_details,
        r.status,
        r.admin_action_note,
        r.created_at AS report_created_at,
        r.actioned_at,
        p.category AS post_category,
        p.text AS post_text,
        p.media_path,
        p.media_json,
        p.media_type,
        p.created_at AS post_created_at,
        r.comment_text AS target_preview_text
      FROM comment_reports r
      LEFT JOIN posts p ON p.id = r.post_id";

    $commentRows = $pdo->query($commentSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $postRows = array_map(static function (array $row): array {
        $row['report_source'] = 'post';
        $row['comment_id'] = null;
        $row['reported_role'] = (string)($row['post_author_role'] ?? '');
        $row['reported_id'] = (int)($row['post_author_id'] ?? 0);
        $row['reported_name'] = (string)($row['post_author_name'] ?? '');
        $row['target_preview_text'] = (string)($row['post_text'] ?? '');
        return $row;
    }, $rows);

    $mergedRows = array_merge($postRows, $commentRows);
    usort($mergedRows, static function (array $a, array $b): int {
        $ta = strtotime((string)($a['report_created_at'] ?? '')) ?: 0;
        $tb = strtotime((string)($b['report_created_at'] ?? '')) ?: 0;
        if ($ta === $tb) {
            return (int)($b['report_id'] ?? 0) <=> (int)($a['report_id'] ?? 0);
        }
        return $tb <=> $ta;
    });

    echo json_encode([
        'success' => true,
        'rows' => $mergedRows,
    ]);
} catch (Throwable $e) {
    error_log('admin_fetch_post_reports error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch post reports'
    ]);
}
