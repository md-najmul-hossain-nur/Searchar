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
    $stmt = $pdo->query('SELECT * FROM volunteers LIMIT 300');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $coords = pickCoords($row);
        $data[] = [
            'name'          => pickField($row, ['full_name', 'name'], ''),
            'location'      => pickField($row, ['city', 'station', 'address', 'street', 'location'], ''),
            'contact'       => pickField($row, ['mobile', 'phone', 'phone_number', 'contact'], ''),
            'join_date'     => pickField($row, ['join_date', 'joined_at', 'created_at'], ''),
            'last_active'   => pickField($row, ['last_active', 'last_active_at', 'updated_at'], ''),
            'total_missions'=> pickField($row, ['total_missions', 'missions_completed', 'missions'], ''),
            'rank'          => pickField($row, ['rank', 'level'], ''),
            'lat'           => $coords['lat'],
            'lon'           => $coords['lon'],
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load volunteers']);
}
