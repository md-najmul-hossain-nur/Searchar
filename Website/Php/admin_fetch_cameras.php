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
    $stmt = $pdo->query('SELECT * FROM camera_contributors LIMIT 300');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $coords = pickCoords($row);
        $data[] = [
            'camera_id'       => pickField($row, ['camera_id', 'id'], ''),
            'full_name'       => pickField($row, ['full_name', 'name'], ''),
            'name'            => pickField($row, ['full_name', 'name'], ''),
            'email'           => pickField($row, ['email'], ''),
            'mobile'          => pickField($row, ['mobile', 'phone', 'phone_number', 'contact'], ''),
            'nid_number'      => pickField($row, ['nid_number'], ''),
            'nid_photo'       => pickField($row, ['nid_photo'], ''),
            'profile_photo'   => pickField($row, ['profile_photo'], ''),
            'cover_photo'     => pickField($row, ['cover_photo'], ''),
            'date_of_birth'   => pickField($row, ['date_of_birth', 'dob'], ''),
            'gender'          => pickField($row, ['gender'], ''),
            'street'          => pickField($row, ['street'], ''),
            'city'            => pickField($row, ['city'], ''),
            'postal_code'     => pickField($row, ['postal_code', 'postal'], ''),
            'country'         => pickField($row, ['country'], ''),
            'latitude'        => $coords['lat'],
            'longitude'       => $coords['lon'],
            'lat'             => $coords['lat'],
            'lon'             => $coords['lon'],
            'camera_location' => pickField($row, ['camera_location', 'location'], ''),
            'camera_type'     => pickField($row, ['camera_type'], ''),
            'stream_type'     => pickField($row, ['stream_type'], ''),
            'bandwidth'       => pickField($row, ['bandwidth'], ''),
            'status'          => pickField($row, ['status', 'camera_status', 'account_status'], 'Online'),
            'fps'             => pickField($row, ['fps', 'frame_rate'], ''),
            'last_checked'    => pickField($row, ['last_checked', 'last_active', 'updated_at', 'created_at'], ''),
            'payment_number'  => pickField($row, ['payment_number'], ''),
            'agreement'       => pickField($row, ['agreement'], ''),
            'location'        => pickField($row, ['camera_location', 'city', 'station', 'address', 'street', 'location'], ''),
            'created_at'      => pickField($row, ['created_at'], ''),
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load camera contributors']);
}
