<?php
require_once "../Php/db.php";

// -----------------------------
// Helper: Save uploaded files
// -----------------------------
function save_upload($file, $prefix = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;

    // Ensure upload folder exists
    $uploadDir = '../uploads/volunteer/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // MIME type validation
    $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        return null; // Invalid file type
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid('_', true) . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;

    return $filename;
}

try {
    // -----------------------------
    // Required fields
    // -----------------------------
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

    // -----------------------------
    // Duplicate check
    // -----------------------------
    $exists = $pdo->prepare("SELECT 1 FROM volunteers WHERE email=? OR mobile=? OR nid_number=?");
    $exists->execute([$email, $mobile, $nid]);
    if ($exists->fetch()) throw new Exception("This Email, Mobile, or NID is already registered!");

    // -----------------------------
    // Password validation
    // -----------------------------
    if ($_POST['password'] !== $_POST['confirm_password']) throw new Exception("Passwords do not match!");
    if (strlen($_POST['password']) < 6) throw new Exception("Password must be at least 6 characters!");

    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // -----------------------------
    // File uploads
    // -----------------------------
    if (!isset($_FILES['nid_photo'])) {
        throw new Exception("NID photo upload failed! No file received.");
    }

    $nid_photo = save_upload($_FILES['nid_photo'], 'nid_');

    if (!$nid_photo) {
        $errCode = $_FILES['nid_photo']['error'];
        $errMsg = match ($errCode) {
            UPLOAD_ERR_INI_SIZE   => "The file is too large (php.ini upload_max_filesize).",
            UPLOAD_ERR_FORM_SIZE  => "The file exceeds MAX_FILE_SIZE from the form.",
            UPLOAD_ERR_PARTIAL    => "The file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder on the server.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
            default               => "Unknown error (code: $errCode) or invalid file type."
        };
        throw new Exception("NID photo upload failed! " . $errMsg);
    }

    $profile_photo    = save_upload($_FILES['profile_photo'] ?? null, 'profile_');
    $cover_photo      = save_upload($_FILES['cover_photo'] ?? null, 'cover_');
    $police_clearance = save_upload($_FILES['police_clearance'] ?? null, 'clearance_');

    // -----------------------------
    // Address fields
    // -----------------------------
    $addr = [
        'street'      => $_POST['street'] ?? null,
        'city'        => $_POST['city'] ?? null,
        'postal_code' => $_POST['postal_code'] ?? null,
        'country'     => $_POST['country'] ?? null,
        'latitude'    => $_POST['latitude'] ?? null,
        'longitude'   => $_POST['longitude'] ?? null
    ];

    $geo_permission = isset($_POST['geo_permission']) && $_POST['geo_permission'] === 'yes' ? 1 : 0;

    // -----------------------------
    // Insert into DB
    // -----------------------------
    $stmt = $pdo->prepare("INSERT INTO volunteers
        (full_name,email,mobile,nid_number,nid_photo,profile_photo,date_of_birth,gender,
        street,city,postal_code,country,latitude,longitude,password_hash,cover_photo,
        occupation,availability,police_clearance,geo_permission)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $_POST['fullname'],       // full_name
        $email,                   // email
        $mobile,                  // mobile
        $nid,                     // nid_number
        $nid_photo,               // nid_photo
        $profile_photo,           // profile_photo
        $_POST['dob'],            // date_of_birth
        $_POST['gender'],         // gender
        $addr['street'],          // street
        $addr['city'],            // city
        $addr['postal_code'],     // postal_code
        $addr['country'],         // country
        $addr['latitude'],        // latitude
        $addr['longitude'],       // longitude
        $password_hash,           // password_hash
        $cover_photo,             // cover_photo 
        $_POST['occupation'],     // occupation
        $_POST['availability'],   // availability
        $police_clearance,        // police_clearance
        $geo_permission           // geo_permission
    ]);

    // -----------------------------
    // Success
    // -----------------------------
    echo "<script>
        alert('✅ Signup Successful!');
        window.location.href = '../Html/login.html';
    </script>";
    exit;

} catch (Exception $ex) {
    // -----------------------------
    // Error
    // -----------------------------
    echo "<script>
        alert('❌ " . addslashes($ex->getMessage()) . "');
        window.history.back();
    </script>";
    exit;
}
?>
