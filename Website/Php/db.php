<?php
$host = 'localhost';
$db   = 'searchar'; 
$user = 'root';        
$pass = '';            
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed!']);
    exit;
}

if (!function_exists('isDuplicateContact')) {
    function isDuplicateContact(PDO $pdo, ?string $email, ?string $mobile, ?string $nid = null): bool {
        $tables = ['users', 'policemen', 'volunteers', 'camera_contributors'];
        
        foreach ($tables as $table) {
            $conditions = [];
            $params = [];
            
            if (!empty($email)) {
                $conditions[] = "LOWER(email) = LOWER(?)";
                $params[] = $email;
            }
            if (!empty($mobile)) {
                $conditions[] = "mobile = ?";
                $params[] = $mobile;
            }
            if (!empty($nid)) {
                $conditions[] = "nid_number = ?";
                $params[] = $nid;
            }
            
            if (empty($conditions)) continue;
            
            $sql = "SELECT 1 FROM `{$table}` WHERE " . implode(" OR ", $conditions) . " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }
        
        // Check hardcoded admin email/phone
        $adminEmail = 'mnajmulhossainnur@gmail.com';
        $adminPhone = '01743094595';
        if (!empty($email) && strcasecmp($email, $adminEmail) === 0) return true;
        if (!empty($mobile) && $mobile === $adminPhone) return true;
        
        return false;
    }
}
?>