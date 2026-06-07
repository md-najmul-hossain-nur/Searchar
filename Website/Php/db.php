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
        $tables = ['users', 'policemen', 'volunteers', 'camera_contributors', 'admins'];
        
        foreach ($tables as $table) {
            $columnStmt = $pdo->prepare("SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME IN ('email', 'mobile', 'nid_number')");
            $columnStmt->execute([$table]);
            $availableColumns = array_flip($columnStmt->fetchAll(PDO::FETCH_COLUMN));

            $conditions = [];
            $params = [];
            
            if (!empty($email) && isset($availableColumns['email'])) {
                $conditions[] = "LOWER(email) = LOWER(?)";
                $params[] = $email;
            }
            if (!empty($mobile) && isset($availableColumns['mobile'])) {
                $conditions[] = "mobile = ?";
                $params[] = $mobile;
            }
            if (!empty($nid) && isset($availableColumns['nid_number'])) {
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
        
        // Hardcoded checks removed since admin accounts are now in the admins table.
        return false;
    }
}
?>
