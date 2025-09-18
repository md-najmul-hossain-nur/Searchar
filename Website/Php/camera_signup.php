<?php
require_once "../Php/db.php";

function save_upload($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return false;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $dest = '../uploads/camera/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return $filename;
}

try {
    $required = [
        'fullname', 'email', 'mobile', 'nid', 'dob', 'gender', 'password', 'confirm_password',
        'camera_location', 'camera_type', 'stream_type', 'bandwidth', 'payment_number'
    ];
    foreach ($required as $k) {
        if (empty($_POST[$k])) throw new Exception("Missing field: $k");
    }

    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $nid = $_POST['nid'];

    // Check uniqueness
    $exists = $pdo->prepare("SELECT 1 FROM camera_contributors WHERE email=? OR mobile=? OR nid_number=?");
    $exists->execute([$email, $mobile, $nid]);
    if ($exists->fetch()) throw new Exception("This Email, Mobile, or NID is already registered!");

    if ($_POST['password'] !== $_POST['confirm_password']) throw new Exception("Passwords do not match!");
    if (strlen($_POST['password']) < 6) throw new Exception("Password must be at least 6 characters!");

    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // File uploads
    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_');
    if (!$nid_photo) throw new Exception("NID photo upload failed!");
    
    $profile_photo = (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
        ? save_upload($_FILES['profile_photo'], 'profile_') : null;
    
    $agreement = save_upload($_FILES['agreement'], 'agreement_');
    if (!$agreement) throw new Exception("Agreement upload failed!");

    // Address fields (postal → postal_code fix)
    $fields = ['street', 'city', 'postal', 'country', 'latitude', 'longitude'];
    $addr = [];
    foreach ($fields as $f) $addr[$f] = $_POST[$f] ?? null;

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO camera_contributors
        (full_name,email,mobile,nid_number,nid_photo,profile_photo,date_of_birth,gender,
        street,city,postal_code,country,latitude,longitude,password_hash,
        camera_location,camera_type,stream_type,bandwidth,payment_number,agreement)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $_POST['fullname'],
        $email,
        $mobile,
        $nid,
        $nid_photo,
        $profile_photo,
        $_POST['dob'],
        $_POST['gender'],
        $addr['street'],
        $addr['city'],
        $addr['postal'],   // form field → goes to `postal_code` column
        $addr['country'],
        $addr['latitude'],
        $addr['longitude'],
        $password_hash,
        $_POST['camera_location'],
        $_POST['camera_type'],
        $_POST['stream_type'],
        $_POST['bandwidth'],
        $_POST['payment_number'],
        $agreement
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
