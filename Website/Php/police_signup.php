<?php
require_once "../Php/db.php";

function save_upload($file, $prefix = '', $allowed = ['jpg','jpeg','png','pdf']) {
    if (!isset($file)) throw new Exception('No file provided for upload');
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $code = $file['error'] ?? 'missing';
        throw new Exception('Upload error (code: ' . $code . ')');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        throw new Exception('Invalid file type: .' . $ext);
    }

    // Validate MIME type for safety
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (in_array($ext, ['jpg','jpeg','png'])) {
            if (strpos($mime, 'image/') !== 0) throw new Exception('Uploaded file is not an image');
        } elseif ($ext === 'pdf') {
            if ($mime !== 'application/pdf') throw new Exception('Uploaded file is not a PDF');
        }
    }

    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $uploadDir = __DIR__ . '/../uploads/police/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to move uploaded file');
    }
    return $filename;
}

try {
    // Required fields
    $required = [
        'fullname', 'email', 'mobile', 'nid', 'dob',
        'gender', 'password', 'confirm_password',
        'badge_id', 'designation', 'station'
    ];
    foreach ($required as $k) {
        if (empty($_POST[$k])) throw new Exception("Missing field: $k");
    }

    $email  = $_POST['email'];
    $mobile = $_POST['mobile'];
    $nid    = $_POST['nid'];

    // Blocked account check (email/phone reuse prevention)
    $blkExists = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'signup_blacklist' LIMIT 1");
    $blkExists->execute();
    if ($blkExists->fetchColumn()) {
        $blk = $pdo->prepare("SELECT 1 FROM signup_blacklist WHERE email = ? OR mobile = ? LIMIT 1");
        $blk->execute([$email, $mobile]);
        if ($blk->fetch()) throw new Exception("This Email/Mobile has been blocked by admin.");
    }

    // Check uniqueness across all roles
    if (isDuplicateContact($pdo, $email, $mobile, $nid)) {
        throw new Exception("This Email, Mobile, or NID is already registered!");
    }

    // Password checks
    if ($_POST['password'] !== $_POST['confirm_password']) throw new Exception("Passwords do not match!");
    if (strlen($_POST['password']) < 6) throw new Exception("Password must be at least 6 characters!");

    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // File uploads (validate and save)
    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_', ['jpg','jpeg','png']);

    $profile_photo = null;
    if (!empty($_FILES['profile_photo']) && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $profile_photo = save_upload($_FILES['profile_photo'], 'profile_', ['jpg','jpeg','png']);
    }

    $cover_photo = null;
    if (!empty($_FILES['cover_photo']) && ($_FILES['cover_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $cover_photo = save_upload($_FILES['cover_photo'], 'cover_', ['jpg','jpeg','png']);
    }

    // Address fields
    $fields = ['street', 'city', 'postal', 'country', 'latitude', 'longitude'];
    $addr = [];
    foreach ($fields as $f) $addr[$f] = $_POST[$f] ?? null;

    // Insert into DB (no bio column)
    $stmt = $pdo->prepare("INSERT INTO policemen
        (full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,
        date_of_birth,gender,street,city,postal_code,country,latitude,longitude,
        password_hash,badge_id,designation,station)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

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
        $addr['postal'], // goes into postal_code column
        $addr['country'],
        $addr['latitude'],
        $addr['longitude'],
        $password_hash,
        $_POST['badge_id'],
        $_POST['designation'],
        $_POST['station']
    ]);

    echo "<script>
        alert(' Signup Successful!');
        window.location.href = '../Html/login.html';
    </script>";
    exit;

} catch (Exception $ex) {
    echo "<script>
        alert(' " . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
