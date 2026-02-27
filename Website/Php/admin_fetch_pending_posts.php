<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

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

try {
	if (!tableExists($pdo, 'posts')) {
		echo json_encode(['success' => true, 'rows' => []]);
		exit;
	}

	$hasStatus = columnExists($pdo, 'posts', 'status');
	$hasReportStatus = columnExists($pdo, 'posts', 'report_status');
	$hasShareAnonymous = columnExists($pdo, 'posts', 'share_anonymous');
	$hasIsShare = columnExists($pdo, 'posts', 'is_share');
	$hasSharedPostId = columnExists($pdo, 'posts', 'shared_post_id');
	$hasSharedPayload = columnExists($pdo, 'posts', 'shared_payload');

	$selectSql = "
	  SELECT
		id,
		case_id,
		author_role,
		author_id,
		author_name,
		category,
		text,
		media_path,
		media_json,
		media_type,
		" . ($hasStatus ? "status" : "'pending' AS status") . ",
		" . ($hasReportStatus ? "report_status" : "'not_reported' AS report_status") . ",
		share_facebook,
		" . ($hasShareAnonymous ? "share_anonymous" : "0 AS share_anonymous") . ",
		" . ($hasIsShare ? "is_share" : "0 AS is_share") . ",
		" . ($hasSharedPostId ? "shared_post_id" : "NULL AS shared_post_id") . ",
		" . ($hasSharedPayload ? "shared_payload" : "NULL AS shared_payload") . ",
		created_at
	  FROM posts
	  ORDER BY id DESC
	  LIMIT 1000
	";

	$rows = $pdo->query($selectSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

	echo json_encode([
		'success' => true,
		'rows' => $rows,
	]);
} catch (Throwable $e) {
	error_log('admin_fetch_pending_posts error: ' . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'error' => 'Failed to fetch posts data',
	]);
}

