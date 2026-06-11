<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request method');
    }

    $raw = file_get_contents('php://input');
    $payload = json_decode($raw ?: '', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Invalid JSON payload');
    }

    $entity = (string)($payload['entity'] ?? '');
    $id = $payload['id'] ?? null;
    $updates = $payload['updates'] ?? null;

    if ($entity === '' || $id === null || !is_array($updates)) {
        throw new RuntimeException('Missing required fields');
    }

    $config = [
        'users' => [
            'table' => 'users',
            'id_col' => 'user_id',
            'allowed' => ['full_name', 'email', 'mobile', 'gender', 'street', 'city', 'postal_code', 'country']
        ],
        'volunteers' => [
            'table' => 'volunteers',
            'id_col' => 'volunteer_id',
            'allowed' => ['full_name', 'email', 'mobile', 'gender', 'street', 'city', 'postal_code', 'country', 'occupation', 'availability']
        ],
        'camera_contributors' => [
            'table' => 'camera_contributors',
            'id_col' => 'camera_id',
            'allowed' => ['full_name', 'email', 'mobile', 'gender', 'street', 'city', 'postal_code', 'country', 'camera_type', 'payment_number']
        ],
        'policemen' => [
            'table' => 'policemen',
            'id_col' => 'police_id',
            'allowed' => ['full_name', 'email', 'mobile', 'gender', 'street', 'city', 'postal_code', 'country']
        ],
    ];

    if (!isset($config[$entity])) {
        throw new RuntimeException('Unsupported entity type');
    }

    $table = $config[$entity]['table'];
    $idCol = $config[$entity]['id_col'];
    $allowed = array_flip($config[$entity]['allowed']);

    $setParts = [];
    $params = [':id' => (int)$id];

    foreach ($updates as $column => $value) {
        if (!isset($allowed[$column])) continue;
        $placeholder = ':v_' . $column;
        $setParts[] = "`{$column}` = {$placeholder}";
        $params[$placeholder] = is_string($value) ? trim($value) : $value;
    }

    if (!$setParts) {
        throw new RuntimeException('No editable fields provided');
    }

    $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE `{$idCol}` = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
