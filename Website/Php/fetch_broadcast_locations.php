<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

function respond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $table]);
    return (bool)$stmt->fetchColumn();
}

function normalizeText(string $value): string {
    return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
}

function extractLocationParts(array $row): array {
    $rawLocation = trim((string)($row['camera_location'] ?? ''));
    $city = trim((string)($row['city'] ?? ''));
    $street = trim((string)($row['street'] ?? ''));
    $country = trim((string)($row['country'] ?? ''));

    $tokens = [];
    if ($rawLocation !== '') {
        $parts = preg_split('/\s*,\s*/', $rawLocation) ?: [];
        foreach ($parts as $p) {
            $t = trim((string)$p);
            if ($t !== '') {
                $tokens[] = $t;
            }
        }
    }

    $divisionToken = '';
    $districtToken = '';
    $areaToken = '';
    $tokenCount = count($tokens);
    if ($tokenCount >= 1) {
        $divisionToken = trim((string)$tokens[$tokenCount - 1]);
    }
    if ($tokenCount >= 2) {
        $districtToken = trim((string)$tokens[$tokenCount - 2]);
    }
    if ($tokenCount >= 3) {
        $areaToken = trim((string)$tokens[$tokenCount - 3]);
    }

    $division = $divisionToken !== '' ? $divisionToken : ($country !== '' ? $country : 'Bangladesh');
    $district = $districtToken !== '' ? $districtToken : ($city !== '' ? $city : $division);
    $area = $areaToken !== '' ? $areaToken : ($street !== '' ? $street : ($rawLocation !== '' ? $rawLocation : $district));

    return [
        'division' => $division,
        'district' => $district,
        'area' => $area,
    ];
}

try {
    if (!tableExists($pdo, 'camera_cctv_feeds')) {
        respond(['success' => true, 'tree' => new stdClass()]);
    }

    $sql = "
        SELECT
            f.camera_location,
            f.stream_scope,
            f.allow_public_viewing,
            f.is_active,
            c.city,
            c.street,
            c.country
        FROM camera_cctv_feeds f
        LEFT JOIN camera_contributors c ON c.camera_id = f.camera_id
                WHERE f.is_active = 1
        ORDER BY f.feed_id DESC
        LIMIT 2000
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

    $tree = [];
    $seen = [];

    foreach ($rows as $row) {
        $loc = extractLocationParts($row);
        $division = trim((string)$loc['division']);
        $district = trim((string)$loc['district']);
        $area = trim((string)$loc['area']);

        if ($division === '' || $district === '' || $area === '') {
            continue;
        }

        $sig = normalizeText($division) . '|' . normalizeText($district) . '|' . normalizeText($area);
        if (isset($seen[$sig])) {
            continue;
        }
        $seen[$sig] = true;

        if (!isset($tree[$division])) {
            $tree[$division] = [];
        }
        if (!isset($tree[$division][$district])) {
            $tree[$division][$district] = [];
        }
        $tree[$division][$district][] = $area;
    }

    ksort($tree, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($tree as $division => $districts) {
        ksort($districts, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($districts as $district => $areas) {
            $areas = array_values(array_unique($areas));
            sort($areas, SORT_NATURAL | SORT_FLAG_CASE);
            $districts[$district] = $areas;
        }
        $tree[$division] = $districts;
    }

    respond(['success' => true, 'tree' => $tree]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to fetch broadcast locations'], 500);
}
