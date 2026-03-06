<?php
require __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

$zones = [
    'banani'      => ['lat' => 23.7937, 'lng' => 90.4066],
    'dhanmondi'   => ['lat' => 23.7465, 'lng' => 90.3760],
    'farmgate'    => ['lat' => 23.7580, 'lng' => 90.3890],
    'uttara'      => ['lat' => 23.8759, 'lng' => 90.3795],
    'mirpur'      => ['lat' => 23.8223, 'lng' => 90.3654],
    'jatrabari'   => ['lat' => 23.7099, 'lng' => 90.4344],
    'mohammadpur' => ['lat' => 23.7660, 'lng' => 90.3584],
    'badda'       => ['lat' => 23.7808, 'lng' => 90.4263],
    'bashundhara' => ['lat' => 23.8151, 'lng' => 90.4254],
    'kawranbazar' => ['lat' => 23.7515, 'lng' => 90.3943],
    'kawran bazar'=> ['lat' => 23.7515, 'lng' => 90.3943],
    'gulshan'     => ['lat' => 23.7925, 'lng' => 90.4078],
    'dhaka'       => ['lat' => 23.8103, 'lng' => 90.4125],
];

function pickZone(string $text, array $zones): array {
    $hay = strtolower($text);
    foreach ($zones as $key => $coord) {
        if ($key !== '' && strpos($hay, $key) !== false) {
            return $coord;
        }
    }
    return $zones['dhaka'];
}

$pdo->beginTransaction();

try {
    $volRows = $pdo->query("SELECT volunteer_id, city, street, country FROM volunteers")->fetchAll(PDO::FETCH_ASSOC);
    $updVol = $pdo->prepare("UPDATE volunteers SET latitude = :lat, longitude = :lng WHERE volunteer_id = :id");

    $volUpdated = 0;
    foreach ($volRows as $row) {
        $source = trim(($row['city'] ?? '') . ' ' . ($row['street'] ?? '') . ' ' . ($row['country'] ?? ''));
        $coord = pickZone($source, $zones);
        $updVol->execute([
            ':lat' => $coord['lat'],
            ':lng' => $coord['lng'],
            ':id'  => $row['volunteer_id'],
        ]);
        $volUpdated += $updVol->rowCount();
    }

    $camRows = $pdo->query("SELECT camera_id, city, street, country FROM camera_contributors")->fetchAll(PDO::FETCH_ASSOC);
    $updCam = $pdo->prepare("UPDATE camera_contributors SET latitude = :lat, longitude = :lng WHERE camera_id = :id");

    $camUpdated = 0;
    foreach ($camRows as $row) {
        $source = trim(($row['city'] ?? '') . ' ' . ($row['street'] ?? '') . ' ' . ($row['country'] ?? ''));
        $coord = pickZone($source, $zones);
        $updCam->execute([
            ':lat' => $coord['lat'],
            ':lng' => $coord['lng'],
            ':id'  => $row['camera_id'],
        ]);
        $camUpdated += $updCam->rowCount();
    }

    $pdo->commit();

    $volTotal = (int)$pdo->query("SELECT COUNT(*) FROM volunteers")->fetchColumn();
    $volWithCoords = (int)$pdo->query("SELECT COUNT(*) FROM volunteers WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();

    $camTotal = (int)$pdo->query("SELECT COUNT(*) FROM camera_contributors")->fetchColumn();
    $camWithCoords = (int)$pdo->query("SELECT COUNT(*) FROM camera_contributors WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();

    echo "Location update completed\n";
    echo "Volunteers updated rows: {$volUpdated}\n";
    echo "Cameramen updated rows: {$camUpdated}\n";
    echo "Volunteers with coordinates: {$volWithCoords}/{$volTotal}\n";
    echo "Cameramen with coordinates: {$camWithCoords}/{$camTotal}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Failed: " . $e->getMessage() . "\n";
}
