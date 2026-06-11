<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT l.log_id, l.action_type, l.details, l.created_at, a.full_name as admin_name, a.email as admin_email
        FROM admin_logs l
        JOIN admins a ON l.admin_id = a.admin_id
        ORDER BY l.created_at DESC
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'logs' => $logs]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
