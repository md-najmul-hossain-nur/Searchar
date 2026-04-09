<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name LIMIT 1");
    $stmt->execute([':name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    if (!tableExists($pdo, 'volunteer_applications')) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $status = strtolower(trim((string)($_GET['status'] ?? 'pending')));
    $allowed = ['pending', 'approved', 'rejected', 'all'];
    if (!in_array($status, $allowed, true)) {
        $status = 'pending';
    }

    $where = '';
    $params = [];
    if ($status !== 'all') {
        $where = "WHERE LOWER(COALESCE(va.status, 'pending')) = :status";
        $params[':status'] = $status;
    }

    $sql = "SELECT
              va.application_id,
              va.user_id,
              va.volunteer_id,
              va.full_name,
              va.email,
              va.mobile,
              va.note,
              va.status,
              va.review_note,
              va.created_at,
              va.reviewed_at,
              COALESCE(u.city, va.city, '') AS city,
              COALESCE(u.country, va.country, '') AS country
            FROM volunteer_applications va
            LEFT JOIN users u ON u.user_id = va.user_id
            {$where}
            ORDER BY va.created_at DESC, va.application_id DESC
            LIMIT 500";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $data = array_map(static function (array $row): array {
        return [
            'application_id' => (string)($row['application_id'] ?? ''),
            'user_id' => (string)($row['user_id'] ?? ''),
            'volunteer_id' => (string)($row['volunteer_id'] ?? ''),
            'full_name' => (string)($row['full_name'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'mobile' => (string)($row['mobile'] ?? ''),
            'city' => (string)($row['city'] ?? ''),
            'country' => (string)($row['country'] ?? ''),
            'note' => (string)($row['note'] ?? ''),
            'status' => (string)($row['status'] ?? 'pending'),
            'review_note' => (string)($row['review_note'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
            'reviewed_at' => (string)($row['reviewed_at'] ?? ''),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load volunteer applications']);
}
