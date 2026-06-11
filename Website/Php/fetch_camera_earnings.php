<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

$allowedRoles = ['contributor', 'camera_contributor', 'camera'];
$role = (string)($_SESSION['role'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0 || !in_array($role, $allowedRoles, true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function ensureNotificationTable(PDO $pdo): void {
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
        INDEX idx_recipient (recipient_entity, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function computeAccruedSeconds(array $row): int {
    $acc = (int)($row['accumulated_seconds'] ?? 0);
    $isActive = (int)($row['is_active'] ?? 0) === 1;
    if (!$isActive) return max(0, $acc);

    $startRaw = (string)($row['active_started_at'] ?? '');
    if ($startRaw === '') {
        $startRaw = (string)($row['created_at'] ?? '');
    }
    $startTs = strtotime($startRaw ?: 'now');
    if (!$startTs) return max(0, $acc);
    return max(0, $acc + max(0, time() - $startTs));
}

try {
    // Ensure payout_count column
    if (tableExists($pdo, 'camera_cctv_feeds')) {
        if (!columnExists($pdo, 'camera_cctv_feeds', 'payout_count')) {
            $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN payout_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER accumulated_seconds");
        }
        if (!columnExists($pdo, 'camera_cctv_feeds', 'is_deleted')) {
            $pdo->exec("ALTER TABLE camera_cctv_feeds ADD COLUMN is_deleted TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active");
        }
    }

    ensureNotificationTable($pdo);

    $stmt = $pdo->prepare('SELECT feed_id, feed_type, is_active, is_deleted, accumulated_seconds, active_started_at, created_at, payout_count FROM camera_cctv_feeds WHERE camera_id = :camera_id');
    $stmt->execute([':camera_id' => $userId]);
    $feeds = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $totalFeeds = 0;
    $liveReady = 0;
    $totalEarned = 0.0;
    $runningHourlyRate = 0;

    foreach ($feeds as $feed) {
        $isDeleted = (int)($feed['is_deleted'] ?? 0) === 1;
        if (!$isDeleted) {
            $totalFeeds++;
            if ((int)($feed['is_active'] ?? 0) === 1) {
                $liveReady++;
                $runningHourlyRate += 40; // 20 BDT per 30 min -> 40 per hour
            }
        }

        $accrued = computeAccruedSeconds($feed);
        $earned = ($accrued / 3600) * 40;
        $totalEarned += $earned;

        // Payout notifications every 30 minutes
        $payoutCount = (int)($feed['payout_count'] ?? 0);
        $newCount = (int)floor($accrued / 1800);
        if ($newCount > $payoutCount) {
            $delta = $newCount - $payoutCount;
            $amount = $delta * 20;
            $meta = json_encode([
                'feed_id' => (int)($feed['feed_id'] ?? 0),
                'payout_count' => $newCount,
                'amount' => $amount
            ]);
            $msg = "You earned BDT {$amount} from your live feed. Keep streaming to earn more.";
            $ins = $pdo->prepare('INSERT INTO user_notifications (recipient_entity, recipient_id, title, message, meta_json, level, is_read) VALUES (:entity, :rid, :title, :message, :meta, :level, 0)');
            $ins->execute([
                ':entity' => 'contributor',
                ':rid' => $userId,
                ':title' => 'Admin: Stream Earnings',
                ':message' => $msg,
                ':meta' => $meta,
                ':level' => 'success'
            ]);

            $upd = $pdo->prepare('UPDATE camera_cctv_feeds SET payout_count = :cnt WHERE feed_id = :feed_id AND camera_id = :camera_id');
            $upd->execute([
                ':cnt' => $newCount,
                ':feed_id' => (int)($feed['feed_id'] ?? 0),
                ':camera_id' => $userId
            ]);
        }
    }

    // Withdrawals summary
    $withdrawTable = null;
    foreach (['withdrawal_requests'] as $t) {
        if (tableExists($pdo, $t)) {
            $withdrawTable = $t;
            break;
        }
    }

    $pendingCount = 0;
    $totalDeducted = 0.0;
    $lastWithdrawal = null;

    if ($withdrawTable) {
        $wstmt = $pdo->prepare("SELECT amount, status, created_at FROM `{$withdrawTable}` WHERE contributor_id = :cid ORDER BY created_at DESC");
        $wstmt->execute([':cid' => $userId]);
        $wrows = $wstmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($wrows as $row) {
            $status = strtolower((string)($row['status'] ?? 'pending'));
            $amount = (float)($row['amount'] ?? 0);
            
            if ($status === 'approved' || $status === 'pending') {
                $totalDeducted += $amount;
            }
            
            if ($status === 'approved') {
                if ($lastWithdrawal === null) {
                    $lastWithdrawal = (string)($row['created_at'] ?? '');
                }
            } elseif ($status === 'pending') {
                $pendingCount++;
            }
        }
    }

    $available = max(0, $totalEarned - $totalDeducted);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_streams' => $totalFeeds,
            'live_ready' => $liveReady,
            'total_earned' => round($totalEarned, 2),
            'running_rate' => $runningHourlyRate,
            'available_balance' => round($available, 2),
            'pending_withdrawals' => $pendingCount,
            'last_withdrawal_date' => $lastWithdrawal,
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'detail' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
