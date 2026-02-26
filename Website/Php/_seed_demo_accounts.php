<?php
require __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

$plainPassword = '12345678';
$passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

$volunteers = [
    ['Rahim Uddin', 'banani'],
    ['Sadia Akter', 'dhanmondi'],
    ['Mahfuz Hasan', 'farmgate'],
    ['Nusrat Jahan', 'uttara'],
    ['Rafiul Islam', 'mirpur'],
    ['Tanvir Ahmed', 'jatrabari'],
    ['Mim Chowdhury', 'mohammadpur'],
    ['Arif Hossain', 'badda'],
    ['Lamia Sultana', 'bashundhara'],
    ['Shuvo Roy', 'kawranbazar'],
];

$cameramen = [
    ['Kamal Hasan', 'banani'],
    ['Priya Das', 'dhanmondi'],
    ['Nayeem Khan', 'farmgate'],
    ['Tania Rahman', 'uttara'],
    ['Sakib Ahmed', 'mirpur'],
    ['Farzana Islam', 'jatrabari'],
    ['Rimon Chowdhury', 'mohammadpur'],
    ['Nabila Karim', 'badda'],
    ['Rasel Hossain', 'bashundhara'],
    ['Mithila Akter', 'kawranbazar'],
];

$insertVolunteer = $pdo->prepare("INSERT INTO volunteers
(full_name,email,mobile,nid_number,nid_photo,profile_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash,cover_photo,occupation,availability,police_clearance,geo_permission)
VALUES (:full_name,:email,:mobile,:nid_number,:nid_photo,:profile_photo,:dob,:gender,:street,:city,:postal_code,:country,:latitude,:longitude,:password_hash,:cover_photo,:occupation,:availability,:police_clearance,:geo_permission)");

$insertCamera = $pdo->prepare("INSERT INTO camera_contributors
(full_name,email,mobile,nid_number,nid_photo,profile_photo,cover_photo,date_of_birth,gender,street,city,postal_code,country,latitude,longitude,password_hash,camera_location,camera_type,stream_type,bandwidth,payment_number,agreement)
VALUES (:full_name,:email,:mobile,:nid_number,:nid_photo,:profile_photo,:cover_photo,:dob,:gender,:street,:city,:postal_code,:country,:latitude,:longitude,:password_hash,:camera_location,:camera_type,:stream_type,:bandwidth,:payment_number,:agreement)");

$checkVolunteer = $pdo->prepare("SELECT 1 FROM volunteers WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1");
$checkCamera = $pdo->prepare("SELECT 1 FROM camera_contributors WHERE email = :email OR mobile = :mobile OR nid_number = :nid LIMIT 1");

$volInserted = 0;
$volSkipped = 0;
$camInserted = 0;
$camSkipped = 0;

foreach ($volunteers as $i => [$name, $zone]) {
    $idx = $i + 1;
    $email = sprintf('demo.volunteer%02d@searchar.local', $idx);
    $mobile = sprintf('0171000%04d', $idx);
    $nid = sprintf('1999000000%04d', $idx);

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
        ':police_clearance' => '',
        ':geo_permission' => 1,
    ]);
    $volInserted++;
}

foreach ($cameramen as $i => [$name, $zone]) {
    $idx = $i + 1;
    $email = sprintf('demo.cameraman%02d@searchar.local', $idx);
    $mobile = sprintf('0182000%04d', $idx);
    $nid = sprintf('2999000000%04d', $idx);

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
        ':camera_location' => ucfirst($zone),
        ':camera_type' => 'IP Camera',
        ':stream_type' => 'Live',
        ':bandwidth' => '10 Mbps',
        ':payment_number' => '01700000000',
        ':agreement' => 1,
    ]);
    $camInserted++;
}

echo "Seed completed\n";
echo "Volunteers inserted: {$volInserted}, skipped: {$volSkipped}\n";
echo "Cameramen inserted: {$camInserted}, skipped: {$camSkipped}\n";
echo "Password for all demo accounts: {$plainPassword}\n";
