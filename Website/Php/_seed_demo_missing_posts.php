<?php
declare(strict_types=1);

require __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

$requiredTables = ['posts', 'missing_person_reports'];
foreach ($requiredTables as $table) {
    if (!tableExists($pdo, $table)) {
        http_response_code(500);
        echo "Required table missing: {$table}\n";
        exit;
    }
}

$authors = [];

$userRows = $pdo->query("SELECT user_id AS id, full_name FROM users ORDER BY user_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($userRows as $row) {
    $authors[] = [
        'role' => 'user',
        'entity' => 'users',
        'id' => (int)$row['id'],
        'name' => (string)($row['full_name'] ?? 'User'),
    ];
}

$volRows = $pdo->query("SELECT volunteer_id AS id, full_name FROM volunteers ORDER BY volunteer_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($volRows as $row) {
    $authors[] = [
        'role' => 'volunteer',
        'entity' => 'volunteers',
        'id' => (int)$row['id'],
        'name' => (string)($row['full_name'] ?? 'Volunteer'),
    ];
}

$camRows = $pdo->query("SELECT camera_id AS id, full_name FROM camera_contributors ORDER BY camera_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($camRows as $row) {
    $authors[] = [
        'role' => 'contributor',
        'entity' => 'camera_contributors',
        'id' => (int)$row['id'],
        'name' => (string)($row['full_name'] ?? 'Contributor'),
    ];
}

if (!$authors) {
    http_response_code(500);
    echo "No authors found in users/volunteers/camera_contributors tables.\n";
    exit;
}

$postTexts = [
    'Suspicious bike seen near market area. Please stay alert.',
    'Crowd gathering detected near main road intersection.',
    'Streetlight outage and low visibility near lane 3.',
    'Accident risk due to broken divider on service road.',
    'Missing child awareness post: shared photo at local point.',
    'Volunteer team reached checkpoint and started search.',
    'Camera feed captured unusual movement around midnight.',
    'Public requested emergency patrol in this zone.',
];

$categories = ['general', 'mission', 'disaster'];
$statuses = ['approved', 'pending', 'approved', 'pending', 'approved', 'approved', 'pending', 'approved'];

$insertPost = $pdo->prepare(
    "INSERT INTO posts (case_id, author_role, author_id, author_name, category, text, media_path, media_json, media_type, share_facebook, share_anonymous, status, created_at)
     VALUES (:case_id, :author_role, :author_id, :author_name, :category, :text, NULL, NULL, NULL, :share_facebook, :share_anonymous, :status, :created_at)"
);

$missingPeople = [
    ['Rafiq Hasan', 'Male', 14, 'Mirpur 10 Bus Stand', 'Karim Uddin', '01711000001', 'Brother'],
    ['Nusrat Jahan', 'Female', 19, 'Dhanmondi 27', 'Selina Akter', '01711000002', 'Mother'],
    ['Sabbir Ahmed', 'Male', 11, 'Uttara Sector 7', 'Monir Hossain', '01711000003', 'Father'],
    ['Tania Sultana', 'Female', 32, 'Farmgate Foot Overbridge', 'Rina Begum', '01711000004', 'Sister'],
    ['Mizanur Rahman', 'Male', 67, 'Banani Rail Crossing', 'Rasheda Khatun', '01711000005', 'Spouse'],
];

$insertMissing = $pdo->prepare(
    "INSERT INTO missing_person_reports
    (reporter_user_id, full_name, nickname, gender, age, physical_description, photo_filename, last_seen_date, last_seen_location, last_seen_time, mental_condition, medical_notes, reporter_name, reporter_mobile, relationship, consent, status, created_at)
    VALUES
    (:reporter_user_id, :full_name, :nickname, :gender, :age, :physical_description, :photo_filename, :last_seen_date, :last_seen_location, :last_seen_time, :mental_condition, :medical_notes, :reporter_name, :reporter_mobile, :relationship, 1, :status, :created_at)"
);

$postInserted = 0;
$missingInserted = 0;

try {
    $pdo->beginTransaction();

    for ($i = 0; $i < 8; $i++) {
        $author = $authors[$i % count($authors)];
        $createdAt = (new DateTimeImmutable('now'))->sub(new DateInterval('PT' . (20 * ($i + 1)) . 'M'))->format('Y-m-d H:i:s');

        $insertPost->execute([
            ':case_id' => 1,
            ':author_role' => $author['role'],
            ':author_id' => $author['id'],
            ':author_name' => $author['name'],
            ':category' => $categories[$i % count($categories)],
            ':text' => $postTexts[$i],
            ':share_facebook' => ($i % 3 === 0) ? 1 : 0,
            ':share_anonymous' => ($i % 4 === 0) ? 1 : 0,
            ':status' => $statuses[$i],
            ':created_at' => $createdAt,
        ]);
        $postInserted++;
    }

    $reporterUserId = isset($userRows[0]['id']) ? (int)$userRows[0]['id'] : null;

    foreach ($missingPeople as $index => $person) {
        [$name, $gender, $age, $location, $reporterName, $reporterMobile, $relationship] = $person;
        $createdAt = (new DateTimeImmutable('now'))->sub(new DateInterval('P' . ($index + 1) . 'D'))->format('Y-m-d H:i:s');
        $lastSeenDate = (new DateTimeImmutable('today'))->sub(new DateInterval('P' . ($index + 1) . 'D'))->format('Y-m-d');

        $insertMissing->execute([
            ':reporter_user_id' => $reporterUserId,
            ':full_name' => $name,
            ':nickname' => null,
            ':gender' => $gender,
            ':age' => $age,
            ':physical_description' => 'Auto-seeded demo report for admin testing.',
            ':photo_filename' => 'demo_missing_' . ($index + 1) . '.jpg',
            ':last_seen_date' => $lastSeenDate,
            ':last_seen_location' => $location,
            ':last_seen_time' => '08:30 PM',
            ':mental_condition' => null,
            ':medical_notes' => null,
            ':reporter_name' => $reporterName,
            ':reporter_mobile' => $reporterMobile,
            ':relationship' => $relationship,
            ':status' => ($index % 2 === 0) ? 'open' : 'under_review',
            ':created_at' => $createdAt,
        ]);
        $missingInserted++;
    }

    $pdo->commit();

    echo "Demo seed completed\n";
    echo "Posts inserted: {$postInserted}\n";
    echo "Missing reports inserted: {$missingInserted}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo 'Failed: ' . $e->getMessage() . "\n";
}
