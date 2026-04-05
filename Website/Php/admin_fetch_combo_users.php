<?php
declare(strict_types=1);

header('Content-Type: application/json');
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

    $sql = "SELECT
              va.application_id,
              va.user_id,
              va.volunteer_id,
              va.status,
              va.review_note,
              va.reviewed_at,
              va.created_at,
              COALESCE(u.full_name, va.full_name, '') AS full_name,
              COALESCE(u.email, va.email, '') AS email,
              COALESCE(u.mobile, va.mobile, '') AS mobile,
              COALESCE(u.city, va.city, '') AS city,
              COALESCE(u.country, va.country, '') AS country,
              COALESCE(v.occupation, '') AS occupation,
              COALESCE(v.availability, '') AS availability
            FROM volunteer_applications va
            LEFT JOIN users u ON u.user_id = va.user_id
            LEFT JOIN volunteers v ON v.volunteer_id = va.volunteer_id
            WHERE LOWER(COALESCE(va.status, 'pending')) = 'approved'
            ORDER BY COALESCE(va.reviewed_at, va.updated_at, va.created_at) DESC, va.application_id DESC
            LIMIT 400";

    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

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
            'occupation' => (string)($row['occupation'] ?? ''),
            'availability' => (string)($row['availability'] ?? ''),
            'status' => 'approved',
            'review_note' => (string)($row['review_note'] ?? ''),
            'reviewed_at' => (string)($row['reviewed_at'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load combo users']);
}
