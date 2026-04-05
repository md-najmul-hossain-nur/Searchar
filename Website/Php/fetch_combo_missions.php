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

try {
    if (empty($_SESSION['role']) || strtolower((string)$_SESSION['role']) !== 'user' || empty($_SESSION['user_id'])) {
        http_response_code(401);
        throw new RuntimeException('Unauthorized');
    }

    if (!tableExists($pdo, 'volunteer_applications') || !tableExists($pdo, 'volunteer_missions')) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    $app = $pdo->prepare("SELECT volunteer_id
                          FROM volunteer_applications
                          WHERE user_id = :uid
                            AND LOWER(COALESCE(status, 'pending')) = 'approved'
                            AND volunteer_id IS NOT NULL
                            AND volunteer_id > 0
                          ORDER BY application_id DESC
                          LIMIT 1");
    $app->execute([':uid' => $userId]);
    $volunteerId = (int)($app->fetchColumn() ?: 0);

    if ($volunteerId <= 0) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT mission_id, mission_title, mission_details, mission_location, status, response_status, case_ref, assigned_at
                           FROM volunteer_missions
                           WHERE volunteer_id = :vid
                           ORDER BY assigned_at DESC, mission_id DESC
                           LIMIT 20");
    $stmt->execute([':vid' => $volunteerId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $data = array_map(static function (array $row): array {
        return [
            'mission_id' => (int)($row['mission_id'] ?? 0),
            'mission_title' => (string)($row['mission_title'] ?? 'Mission'),
            'mission_details' => (string)($row['mission_details'] ?? ''),
            'mission_location' => (string)($row['mission_location'] ?? ''),
            'status' => (string)($row['status'] ?? 'assigned'),
            'response_status' => (string)($row['response_status'] ?? 'pending'),
            'case_ref' => (string)($row['case_ref'] ?? ''),
            'assigned_at' => (string)($row['assigned_at'] ?? ''),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
