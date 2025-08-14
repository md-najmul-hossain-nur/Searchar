<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$input = json_decode(file_get_contents('php://input'), true);

$fb_id = $input['fb_id'] ?? '';
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$picture = $input['picture'] ?? '';
$role = strtolower($input['role'] ?? '');

$allowed_roles = ['user','police','volunteer','contributor'];

if ($fb_id && $email && in_array($role, $allowed_roles)) {
    // Database e user create ba login koro (optional)
    echo json_encode([
        'success' => true,
        'role' => $role,
        'name' => $name,
        'email' => $email,
        'picture' => $picture
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Missing Facebook info or invalid role']);
}
?>