<?php
require_once "../Php/db.php";

function save_upload($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $dest = '../uploads/volunteer/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return $filename;
}

try {
    $required = [
        'fullname', 'email', 'mobile', 'nid', 'dob', 'gender', 
        'password', 'confirm_password', 'occupation', 'availability'
    ];
    foreach ($required as $k) {
        if (empty($_POST[$k])) throw new Exception("Missing field: $k");
    }

    $email  = $_POST['email'];
    $mobile = $_POST['mobile'];
    $nid    = $_POST['nid'];

    // Check duplicate entry
    $exists = $pdo->prepare("SELECT 1 FROM volunteers WHERE email=? OR mobile=? OR nid_number=?");
    $exists->execute([$email, $mobile, $nid]);
    if ($exists->fetch()) throw new Exception("This Email, Mobile, or NID is already registered!");

    if ($_POST['password'] !== $_POST['confirm_password']) throw new Exception("Passwords do not match!");
    if (strlen($_POST['password']) < 6) throw new Exception("Password must be at least 6 characters!");

    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // File uploads
    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_');
    if (!$nid_photo) throw new Exception("NID photo upload failed!");

    $profile_photo     = save_upload($_FILES['profile_photo'], 'profile_');
    $police_clearance  = save_upload($_FILES['police_clearance'], 'clearance_');

    // Address fields (postal → postal_code)
    $fields = ['street', 'city', 'postal', 'country', 'latitude', 'longitude'];
    $addr = [];
    foreach ($fields as $f) $addr[$f] = $_POST[$f] ?? null;

    $geo_permission = isset($_POST['geo_permission']) && $_POST['geo_permission'] == 'yes' ? 1 : 0;

    // Insert into DB
    $stmt = $pdo->prepare("INSERT INTO volunteers
        (full_name,email,mobile,nid_number,nid_photo,profile_photo,date_of_birth,gender,
        street,city,postal_code,country,latitude,longitude,password_hash,
        occupation,availability,police_clearance,geo_permission)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

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
        $addr['postal'],   // ← goes into postal_code column
        $addr['country'],
        $addr['latitude'],
        $addr['longitude'],
        $password_hash,
        $_POST['occupation'],
        $_POST['availability'],
        $police_clearance,
        $geo_permission
    ]);

    // ✅ Success → alert + redirect
    echo "<script>
        alert('✅ Signup Successful!');
        window.location.href = '../Html/log.html';
    </script>";
    exit;

} catch (Exception $ex) {
    // ❌ Error → alert + go back
    echo "<script>
        alert('❌ " . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
