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

function fetchWarnedIdMap(PDO $pdo, string $role, array $ids): array {
    $cleanIds = array_values(array_filter(array_map('intval', $ids), static fn($id) => $id > 0));
    if (!$cleanIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
    $sql = "SELECT DISTINCT recipient_id FROM user_notifications
            WHERE recipient_entity = ?
              AND level = 'warning'
              AND title = 'Admin Warning'
              AND recipient_id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$role], $cleanIds));
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $map = [];
    foreach ($rows as $rid) {
        $map[(int)$rid] = true;
    }
    return $map;
}

$data = [];

try {
    $stmt = $pdo->query('SELECT * FROM users LIMIT 300');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ids = array_map(static fn(array $row): int => (int)pickField($row, ['user_id', 'id'], '0'), $rows);
    $warnedMap = fetchWarnedIdMap($pdo, 'user', $ids);
    foreach ($rows as $row) {
        $coords = pickCoords($row);
        $recordId = (int)pickField($row, ['user_id', 'id'], '0');
        $data[] = [
            'user_id'       => (string)$recordId,
            'full_name'     => pickField($row, ['full_name', 'name'], ''),
            'name'          => pickField($row, ['full_name', 'name'], ''),
            'email'         => pickField($row, ['email'], ''),
            'mobile'        => pickField($row, ['mobile', 'phone', 'phone_number', 'contact'], ''),
            'phone'         => pickField($row, ['mobile', 'phone', 'phone_number', 'contact'], ''),
            'nid_number'    => pickField($row, ['nid_number'], ''),
            'nid_photo'     => pickField($row, ['nid_photo'], ''),
            'profile_photo' => pickField($row, ['profile_photo'], ''),
            'cover_photo'   => pickField($row, ['cover_photo'], ''),
            'date_of_birth' => pickField($row, ['date_of_birth', 'dob'], ''),
            'gender'        => pickField($row, ['gender'], ''),
            'street'        => pickField($row, ['street'], ''),
            'city'          => pickField($row, ['city'], ''),
            'postal_code'   => pickField($row, ['postal_code', 'postal'], ''),
            'country'       => pickField($row, ['country'], ''),
            'location'      => pickField($row, ['city', 'street', 'country', 'address', 'location'], ''),
            'latitude'      => $coords['lat'],
            'longitude'     => $coords['lon'],
            'lat'           => $coords['lat'],
            'lon'           => $coords['lon'],
            'created_at'    => pickField($row, ['created_at'], ''),
            'role'          => 'User',
            'warned_by_admin' => isset($warnedMap[$recordId]),
        ];
    }
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to load users']);
}
