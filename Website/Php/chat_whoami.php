<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

echo json_encode(['success' => true, 'user_id' => (int)$_SESSION['user_id'], 'role' => (string)$_SESSION['role']]);
