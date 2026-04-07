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

    $hasNotifications = tableExists($pdo, 'user_notifications');
    $query = "SELECT vm.mission_id, vm.mission_title, vm.mission_details, vm.mission_location, vm.status, vm.response_status, vm.case_ref, vm.assigned_at";
    if ($hasNotifications) {
        $query .= ", COALESCE(un.meta_json, '') AS meta_json";
    } else {
        $query .= ", '' AS meta_json";
    }

    $query .= " FROM volunteer_missions vm";
    if ($hasNotifications) {
        $query .= " LEFT JOIN user_notifications un ON un.notification_id = vm.source_notification_id";
    }

    $query .= " WHERE vm.volunteer_id = :vid
                ORDER BY vm.assigned_at DESC, vm.mission_id DESC
                LIMIT 20";

    $stmt = $pdo->prepare($query);
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
            'meta_json' => (string)($row['meta_json'] ?? ''),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
