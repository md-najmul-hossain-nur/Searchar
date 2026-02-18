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
            'camera_id' => pickField($row, ['camera_id', 'id'], ''),
            'name'      => pickField($row, ['full_name', 'name'], ''),
            'location'  => pickField($row, ['city', 'station', 'address', 'street', 'location'], ''),
            'lat'       => $coords['lat'],
            'lon'       => $coords['lon'],
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load camera contributors']);
}
