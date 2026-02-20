<?php
declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function pickField(array $row, array $keys, string $fallback = ''): string {
    foreach ($keys as $key) {
        if (isset($row[$key]) && $row[$key] !== '') {
            return (string)$row[$key];
        }
    }
    return $fallback;
}

function pickCoords(array $row): array {
    $lat = pickField($row, ['lat', 'latitude', 'location_lat', 'geo_lat'], '');
    $lon = pickField($row, ['lon', 'lng', 'longitude', 'location_lng', 'geo_lng'], '');
    return [
        'lat' => is_numeric($lat) ? (float)$lat : null,
        'lon' => is_numeric($lon) ? (float)$lon : null,
    ];
}

$data = [];

try {
    $stmt = $pdo->query('SELECT * FROM policemen ORDER BY police_id DESC LIMIT 300');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $coords = pickCoords($row);
        $data[] = [
            'police_id'      => pickField($row, ['police_id', 'id'], ''),
            'full_name'      => pickField($row, ['full_name', 'name'], ''),
            'name'           => pickField($row, ['full_name', 'name'], ''),
            'email'          => pickField($row, ['email'], ''),
            'mobile'         => pickField($row, ['mobile'], ''),
            'nid_number'     => pickField($row, ['nid_number'], ''),
            'nid_photo'      => pickField($row, ['nid_photo'], ''),
            'profile_photo'  => pickField($row, ['profile_photo'], ''),
            'cover_photo'    => pickField($row, ['cover_photo'], ''),
            'date_of_birth'  => pickField($row, ['date_of_birth', 'dob'], ''),
            'gender'         => pickField($row, ['gender'], ''),
            'street'         => pickField($row, ['street'], ''),
            'city'           => pickField($row, ['city'], ''),
            'postal_code'    => pickField($row, ['postal_code', 'postal'], ''),
            'country'        => pickField($row, ['country'], ''),
            'latitude'       => $coords['lat'],
            'longitude'      => $coords['lon'],
            'lat'            => $coords['lat'],
            'lon'            => $coords['lon'],
            'badge_id'       => pickField($row, ['badge_id'], ''),
            'designation'    => pickField($row, ['designation'], 'Officer'),
            'station'        => pickField($row, ['station', 'city'], ''),
            'official_id'    => pickField($row, ['official_id'], ''),
            'status'         => pickField($row, ['status'], 'Active'),
            'created_at'     => pickField($row, ['created_at'], ''),
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load policemen']);
}
