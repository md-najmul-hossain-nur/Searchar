<?php
require __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

$plainPassword = '12345678';
$passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
$batch = date('ymdHis');

function randomDigits(int $length): string {
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= (string)random_int(0, 9);
    }
    return $out;
}

function randomMobile(): string {
    return '01' . randomDigits(9);
}

function randomNid(): string {
    return randomDigits(13);
}

$users = [
    ['Imran Hossain', 'banani'],
    ['Sumaiya Rahman', 'dhanmondi'],
    ['Hasib Ahmed', 'mirpur'],
    ['Tahmina Islam', 'uttara'],
    ['Riad Karim', 'farmgate'],
];

$volunteers = [
    ['Rahim Uddin', 'banani'],
    ['Sadia Akter', 'dhanmondi'],
    ['Mahfuz Hasan', 'farmgate'],
    ['Nusrat Jahan', 'uttara'],
    ['Rafiul Islam', 'mirpur'],
];

$cameramen = [
    ['Kamal Hasan', 'banani'],
    ['Priya Das', 'dhanmondi'],
    ['Nayeem Khan', 'farmgate'],
    ['Tania Rahman', 'uttara'],
    ['Sakib Ahmed', 'mirpur'],
];

$insertUser = $pdo->prepare("INSERT INTO users
(full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash)
VALUES (:full_name,:email,:mobile,:nid_number,:nid_photo,:profile_photo,:cover_photo,:dob,:gender,:street,:city,:postal_code,:country,:latitude,:longitude,:password_hash)");

$insertVolunteer = $pdo->prepare("INSERT INTO volunteers
(full_name,email,mobile,nid_number,nid_photo,profile_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash,cover_photo,occupation,availability)
VALUES (:full_name,:email,:mobile,:nid_number,:nid_photo,:profile_photo,:dob,:gender,:street,:city,:postal_code,:country,:latitude,:longitude,:password_hash,:cover_photo,:occupation,:availability)");

$insertCamera = $pdo->prepare("INSERT INTO camera_contributors
(full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash,camera_type,payment_number)
VALUES (:full_name,:email,:mobile,:nid_number,:nid_photo,:profile_photo,:cover_photo,:dob,:gender,:street,:city,:postal_code,:country,:latitude,:longitude,:password_hash,:camera_type,:payment_number)");

$checkUser = $pdo->prepare("SELECT 1 FROM users WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1");
$checkVolunteer = $pdo->prepare("SELECT 1 FROM volunteers WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1");
$checkCamera = $pdo->prepare("SELECT 1 FROM camera_contributors WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1");

$userInserted = 0;
$userSkipped = 0;
$volInserted = 0;
$volSkipped = 0;
$camInserted = 0;
$camSkipped = 0;

foreach ($users as $i => [$name, $zone]) {
    $idx = $i + 1;
    $email = sprintf('demo.user.%s.%02d@searchar.local', $batch, $idx);
    $mobile = randomMobile();
    $nid = randomNid();

    $checkUser->execute([':email' => $email, ':mobile' => $mobile, ':nid' => $nid]);
    if ($checkUser->fetchColumn()) {
        $userSkipped++;
        continue;
    }

    $insertUser->execute([
        ':full_name' => $name,
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid_number' => $nid,
        ':nid_photo' => '',
        ':profile_photo' => '',
        ':cover_photo' => '',
        ':dob' => '1999-01-01',
        ':gender' => ($idx % 2 === 0 ? 'Female' : 'Male'),
        ':street' => ucfirst($zone) . ' Road',
        ':city' => 'Dhaka',
        ':postal_code' => '1200',
        ':country' => 'Bangladesh',
        ':latitude' => null,
        ':longitude' => null,
        ':password_hash' => $passwordHash,
    ]);
    $userInserted++;
}

foreach ($volunteers as $i => [$name, $zone]) {
    $idx = $i + 1;
    $email = sprintf('demo.volunteer.%s.%02d@searchar.local', $batch, $idx);
    $mobile = randomMobile();
    $nid = randomNid();

    $checkVolunteer->execute([':email' => $email, ':mobile' => $mobile, ':nid' => $nid]);
    if ($checkVolunteer->fetchColumn()) {
        $volSkipped++;
        continue;
    }

    $insertVolunteer->execute([
        ':full_name' => $name,
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid_number' => $nid,
        ':nid_photo' => '',
        ':profile_photo' => '',
        ':dob' => '1998-01-01',
        ':gender' => ($idx % 2 === 0 ? 'Female' : 'Male'),
        ':street' => ucfirst($zone) . ' Road',
        ':city' => 'Dhaka',
        ':postal_code' => '1200',
        ':country' => 'Bangladesh',
        ':latitude' => null,
        ':longitude' => null,
        ':password_hash' => $passwordHash,
        ':cover_photo' => '',
        ':occupation' => 'Volunteer',
        ':availability' => 'Weekdays',
    ]);
    $volInserted++;
}

foreach ($cameramen as $i => [$name, $zone]) {
    $idx = $i + 1;
    $email = sprintf('demo.cameraman.%s.%02d@searchar.local', $batch, $idx);
    $mobile = randomMobile();
    $nid = randomNid();

    $checkCamera->execute([':email' => $email, ':mobile' => $mobile, ':nid' => $nid]);
    if ($checkCamera->fetchColumn()) {
        $camSkipped++;
        continue;
    }

    $insertCamera->execute([
        ':full_name' => $name,
        ':email' => $email,
        ':mobile' => $mobile,
        ':nid_number' => $nid,
        ':nid_photo' => '',
        ':profile_photo' => '',
        ':cover_photo' => '',
        ':dob' => '1996-01-01',
        ':gender' => ($idx % 2 === 0 ? 'Female' : 'Male'),
        ':street' => ucfirst($zone) . ' Avenue',
        ':city' => 'Dhaka',
        ':postal_code' => '1200',
        ':country' => 'Bangladesh',
        ':latitude' => null,
        ':longitude' => null,
        ':password_hash' => $passwordHash,
        ':camera_type' => 'IP Camera',
        ':payment_number' => '01700000000',
    ]);
    $camInserted++;
}

echo "Seed completed\n";
echo "Users inserted: {$userInserted}, skipped: {$userSkipped}\n";
echo "Volunteers inserted: {$volInserted}, skipped: {$volSkipped}\n";
echo "Cameramen inserted: {$camInserted}, skipped: {$camSkipped}\n";
echo "Password for all demo accounts: {$plainPassword}\n";
