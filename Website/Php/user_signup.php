<?php
require_once "../Php/db.php";

// ===============================
// Helper to move files
// ===============================
function save_upload($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("❌ File upload error! Error code: " . ($file['error'] ?? 'unknown'));
    }

    // Upload folder path
    $uploadDir = __DIR__ . '/../uploads/user/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception("❌ Upload folder তৈরি করা যায়নি: $uploadDir");
        }
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception("❌ move_uploaded_file ব্যর্থ! Path: $dest");
    }

    // শুধু filename DB তে রাখব
    return $filename;
}

try {
    // ===============================
    // Required field check
    // ===============================
    $required = ['fullname', 'email', 'mobile', 'nid', 'dob', 'gender', 'password', 'confirm_password'];
    foreach ($required as $k) {
        if (empty($_POST[$k])) throw new Exception("Missing field: $k");
    }

    $email  = $_POST['email'];
    $mobile = $_POST['mobile'];
    $nid    = $_POST['nid'];

    // ===============================
    // Uniqueness check
    // ===============================
    $exists = $pdo->prepare("SELECT 1 FROM users WHERE email=? OR mobile=? OR nid_number=?");
    $exists->execute([$email, $mobile, $nid]);
    if ($exists->fetch()) {
        throw new Exception("এই Email/Mobile/NID ইতিমধ্যেই রেজিস্টার করা আছে!");
    }

    // ===============================
    // Password validation
    // ===============================
    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception("Password মেলেনি!");
    }
    if (strlen($_POST['password']) < 6) {
        throw new Exception("Password অন্তত 6 অক্ষরের হতে হবে!");
    }
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // ===============================
    // File Uploads
    // ===============================
    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_');
    $cover_photo = (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK)
    ? save_upload($_FILES['cover_photo'], 'cover_') : null;
    $profile_photo = (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
        ? save_upload($_FILES['profile_photo'], 'profile_') : null;

    // ===============================
    // Address fields
    // ===============================
    $fields = ['street', 'city', 'postal', 'country', 'latitude', 'longitude'];
    $addr = [];
    foreach ($fields as $f) {
        $addr[$f] = !empty($_POST[$f]) ? $_POST[$f] : null;
    }

    // ===============================
    // Insert Query (fixed: postal_code)
    // ===============================
    $stmt = $pdo->prepare("INSERT INTO users
    (full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$stmt->execute([
    $_POST['fullname'],
    $email,
    $mobile,
    $nid,
    $nid_photo,
    $profile_photo,
    $cover_photo,
    $_POST['dob'],
    $_POST['gender'],
    $addr['street'],
    $addr['city'],
    $addr['postal'],
    $addr['country'],
    $addr['latitude'],
    $addr['longitude'],
    $password_hash
]);

    // ===============================
    // Success
    // ===============================
    echo "<script>
        alert('✅ Signup Successful!');
        window.location.href = '../Html/login.html';
    </script>";
    exit;

} catch (Exception $ex) {
    // ===============================
    // Error
    // ===============================
    echo "<script>
        alert('❌ " . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
