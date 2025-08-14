<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents('debug.txt', json_encode($input)); // Debugging
$credential = $input['credential'] ?? '';
$role = strtolower($input['role'] ?? '');

$allowed_roles = ['user','police','volunteer','contributor'];

if ($credential && $role) {
    if (!in_array($role, $allowed_roles)) {
        echo json_encode(['success' => false, 'error' => 'Invalid role']);
        exit;
    }
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $credential;
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['email'])) {
        echo json_encode([
          'success' => true,
          'role' => $role,
          'name' => $data['name'] ?? '',
          'email' => $data['email']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No credential or no role']);
}
?>