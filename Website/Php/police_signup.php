<?php
require_once "../Php/db.php";

function save_upload($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $dest = '../uploads/police/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
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

    // Check uniqueness
    $exists = $pdo->prepare("SELECT 1 FROM policemen WHERE email=? OR mobile=? OR nid_number=?");
    $exists->execute([$email, $mobile, $nid]);
    if ($exists->fetch()) throw new Exception("This Email, Mobile, or NID is already registered!");

    // Password checks
    if ($_POST['password'] !== $_POST['confirm_password']) throw new Exception("Passwords do not match!");
    if (strlen($_POST['password']) < 6) throw new Exception("Password must be at least 6 characters!");

    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // File uploads
    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_');
    if (!$nid_photo) throw new Exception("NID photo upload failed!");

    $profile_photo = (!empty($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
        ? save_upload($_FILES['profile_photo'], 'profile_') : null;

    $cover_photo = (!empty($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK)
        ? save_upload($_FILES['cover_photo'], 'cover_') : null;

    $official_id = save_upload($_FILES['official_id'], 'official_');
    if (!$official_id) throw new Exception("Official letter upload failed!");

    // Address fields
    $fields = ['street', 'city', 'postal', 'country', 'latitude', 'longitude'];
    $addr = [];
    foreach ($fields as $f) $addr[$f] = $_POST[$f] ?? null;

    // Insert into DB (no bio column)
    $stmt = $pdo->prepare("INSERT INTO policemen
        (full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,
        date_of_birth,gender,street,city,postal_code,country,latitude,longitude,
        password_hash,badge_id,designation,station,official_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

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
        $_POST['station'],
        $official_id
    ]);

    echo "<script>
        alert('✅ Signup Successful!');
        window.location.href = '../Html/login.html';
    </script>";
    exit;

} catch (Exception $ex) {
    echo "<script>
        alert('❌ " . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
