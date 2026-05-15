<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'contributor' || empty($_SESSION['user_id'])) {
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

if ($method === '' || $account === '' || $amount <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'missing_fields']);
  exit();
}

$userId = (int) $_SESSION['user_id'];

try {
  // Ensure table exists
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
