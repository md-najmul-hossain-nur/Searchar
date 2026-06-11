<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/db.php';

$allowedRoles = ['contributor', 'camera_contributor', 'camera'];
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true) || empty($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'unauthorized']);
  exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'invalid_payload']);
  exit();
}

$method = trim((string)($data['method'] ?? ''));
$account = trim((string)($data['accountNumber'] ?? ''));
$amount = (float)($data['amount'] ?? 0);

const MIN_WITHDRAW = 200.0;

if ($method === '' || $account === '' || $amount <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'missing_fields']);
  exit();
}

if ($amount < MIN_WITHDRAW) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'below_minimum', 'message' => 'Minimum withdrawal is BDT 200.']);
  exit();
}

$userId = (int) $_SESSION['user_id'];

try {
  // Verify available balance server-side
  $feedStmt = $pdo->prepare('SELECT accumulated_seconds, is_active, active_started_at, created_at FROM camera_cctv_feeds WHERE camera_id = :cid');
  $feedStmt->execute([':cid' => $userId]);
  $feeds = $feedStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  $totalEarned = 0.0;
  foreach ($feeds as $f) {
    $acc = (int)($f['accumulated_seconds'] ?? 0);
    if ((int)($f['is_active'] ?? 0) === 1) {
      $start = strtotime((string)($f['active_started_at'] ?? $f['created_at'] ?? 'now') ?: 'now');
      if ($start) $acc += max(0, time() - $start);
    }
    $totalEarned += ($acc / 3600) * 40;
  }
  // Ensure table exists before querying it
  $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contributor_id INT UNSIGNED NOT NULL,
    method VARCHAR(60) NOT NULL,
    account_number VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

  $wchk = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawal_requests WHERE contributor_id = :cid AND status IN ('approved', 'pending')");
  $wchk->execute([':cid' => $userId]);
  $totalDeducted = (float)$wchk->fetchColumn();
  $available = max(0.0, $totalEarned - $totalDeducted);

  if ($amount > $available) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'insufficient_balance', 'message' => 'Amount exceeds available balance.']);
    exit();
  }



  $stmt = $pdo->prepare('INSERT INTO withdrawal_requests (contributor_id, method, account_number, amount, status) VALUES (:cid, :method, :account, :amount, :status)');
  $stmt->execute([
    ':cid' => $userId,
    ':method' => $method,
    ':account' => $account,
    ':amount' => number_format($amount, 2, '.', ''),
    ':status' => 'pending'
  ]);

  $id = (int)$pdo->lastInsertId();

  echo json_encode(['success' => true, 'id' => $id]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'server_error']);
}
