<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Html/User_Home.php');
    exit();
}

function getReturnPage(): string {
    $allowed = [
        'User_Home.php',
        'Policeman_Home.php',
    ];

    $requested = trim((string)($_POST['return_to'] ?? ''));
    if ($requested !== '' && in_array($requested, $allowed, true)) {
        return $requested;
    }

    return 'User_Home.php';
}

function redirectWithStatus(string $status, string $message = ''): void {
    $returnPage = getReturnPage();
    $url = '../Html/' . $returnPage . '?missing_report=' . urlencode($status);
    if ($message !== '') {
        $url .= '&msg=' . urlencode($message);
    }
    header('Location: ' . $url);
    exit();
}

$requiredFields = [
    'full_name',
    'gender',
    'age',
    'last_seen_date',
    'last_seen_location',
    'reporter_name',
    'reporter_mobile',
    'consent'
];

foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        redirectWithStatus('error', 'Please fill all required fields.');
    }
}

if (!isset($_FILES['person_photo']) || ($_FILES['person_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    redirectWithStatus('error', 'Photo upload failed. Please upload a clear photo.');
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$photo = $_FILES['person_photo'];
$ext = strtolower((string)pathinfo((string)$photo['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    redirectWithStatus('error', 'Only JPG, PNG, or WEBP images are allowed.');
}

if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, (string)$photo['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if (!is_string($mime) || strpos($mime, 'image/') !== 0) {
        redirectWithStatus('error', 'Uploaded file is not a valid image.');
    }
}

$uploadDir = __DIR__ . '/../uploads/missing_person/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    redirectWithStatus('error', 'Could not create upload directory.');
}

$photoName = 'missing_' . uniqid('', true) . '.' . $ext;
$photoPath = $uploadDir . $photoName;
if (!move_uploaded_file((string)$photo['tmp_name'], $photoPath)) {
    redirectWithStatus('error', 'Could not save uploaded photo.');
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS missing_person_reports (
        report_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        reporter_user_id INT UNSIGNED DEFAULT NULL,
        full_name VARCHAR(150) NOT NULL,
        nickname VARCHAR(150) DEFAULT NULL,
        gender VARCHAR(20) NOT NULL,
        age INT UNSIGNED NOT NULL,
        physical_description VARCHAR(500) DEFAULT NULL,
        photo_filename VARCHAR(255) NOT NULL,
        last_seen_date DATE NOT NULL,
        last_seen_location VARCHAR(255) NOT NULL,
        last_seen_time VARCHAR(60) DEFAULT NULL,
        mental_condition VARCHAR(120) DEFAULT NULL,
        medical_notes VARCHAR(500) DEFAULT NULL,
        reporter_name VARCHAR(150) NOT NULL,
        reporter_mobile VARCHAR(30) NOT NULL,
        relationship VARCHAR(120) DEFAULT NULL,
        consent TINYINT(1) NOT NULL DEFAULT 1,
        status VARCHAR(40) NOT NULL DEFAULT 'open',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (report_id),
        KEY idx_missing_status (status),
        KEY idx_missing_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->prepare("INSERT INTO missing_person_reports
        (reporter_user_id, full_name, nickname, gender, age, physical_description, photo_filename,
         last_seen_date, last_seen_location, last_seen_time, mental_condition, medical_notes,
         reporter_name, reporter_mobile, relationship, consent)
        VALUES
        (:reporter_user_id, :full_name, :nickname, :gender, :age, :physical_description, :photo_filename,
         :last_seen_date, :last_seen_location, :last_seen_time, :mental_condition, :medical_notes,
         :reporter_name, :reporter_mobile, :relationship, :consent)");

    $stmt->execute([
        ':reporter_user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        ':full_name' => trim((string)$_POST['full_name']),
        ':nickname' => trim((string)($_POST['nickname'] ?? '')),
        ':gender' => trim((string)$_POST['gender']),
        ':age' => (int)$_POST['age'],
        ':physical_description' => trim((string)($_POST['physical_description'] ?? '')),
        ':photo_filename' => $photoName,
        ':last_seen_date' => trim((string)$_POST['last_seen_date']),
        ':last_seen_location' => trim((string)$_POST['last_seen_location']),
        ':last_seen_time' => trim((string)($_POST['last_seen_time'] ?? '')),
        ':mental_condition' => trim((string)($_POST['mental_condition'] ?? '')),
        ':medical_notes' => trim((string)($_POST['medical_notes'] ?? '')),
        ':reporter_name' => trim((string)$_POST['reporter_name']),
        ':reporter_mobile' => trim((string)$_POST['reporter_mobile']),
        ':relationship' => trim((string)($_POST['relationship'] ?? '')),
        ':consent' => 1,
    ]);

    $reportId = (int)$pdo->lastInsertId();
    
    // Automatically escalate to crime_reports (Active Investigations / Crime Case Table)
    $caseRef = 'MP' . str_pad((string)$reportId, 4, '0', STR_PAD_LEFT);
    $mediaPath = $photoName !== '' ? ('uploads/missing_person/' . $photoName) : null;
    $mediaJson = $mediaPath ? json_encode([$mediaPath], JSON_UNESCAPED_SLASHES) : null;

    $upsertCrime = $pdo->prepare("INSERT INTO crime_reports
        (case_ref, source_type, source_ref_id, report_type, severity, status, landmark, reporter_name, anonymous, description, media_path, media_json, submitted_at, updated_at, lat, lng)
        VALUES
        (:case_ref, 'missing_person', :source_ref_id, 'missing_person', 'high', 'new', :landmark, :reporter_name, 0, :description, :media_path, :media_json, NOW(), NOW(), :lat, :lng)
    ");

    $upsertCrime->execute([
        ':case_ref' => $caseRef,
        ':source_ref_id' => $reportId,
        ':landmark' => trim((string)$_POST['last_seen_location']) !== '' ? trim((string)$_POST['last_seen_location']) : 'Unknown location',
        ':reporter_name' => trim((string)$_POST['reporter_name']) !== '' ? trim((string)$_POST['reporter_name']) : 'Unknown',
        ':description' => 'Automatically escalated from Missing Persons',
        ':media_path' => $mediaPath,
        ':media_json' => $mediaJson,
        ':lat' => 23.8103, // Default lat (or you can capture from frontend if available)
        ':lng' => 90.4125, // Default lng
    ]);

    redirectWithStatus('success', 'Missing person report submitted successfully.');
} catch (Throwable $e) {
    error_log('Missing report save error: ' . $e->getMessage());
    redirectWithStatus('error', 'Database error. Please try again.');
}
