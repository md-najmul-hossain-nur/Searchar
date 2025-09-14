<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents('debug_signin.txt', json_encode($input)); // Debugging

$credential = $input['credential'] ?? '';

if ($credential) {
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $credential;
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (isset($data['email'])) {
        $email = $data['email'];
        // Use your separate DB connection file
        require_once 'db_connect.php';

        try {
            $stmt = $pdo->prepare("SELECT role, name FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                echo json_encode([
                    'success' => true,
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'email' => $email
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Account not found. Please sign up first.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid token']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No credential provided']);
}
?>