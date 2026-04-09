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

    $stateToken = '';
    if (count($tokens) >= 2) {
        $stateToken = trim((string)$tokens[count($tokens) - 2]);
    }

    $cityToken = '';
    if (count($tokens) >= 3) {
        $cityToken = trim((string)$tokens[count($tokens) - 3]);
    }

    $streetToken = '';
    if (!empty($tokens)) {
        $streetToken = trim((string)$tokens[0]);
    }

    $division = $stateToken !== '' ? $stateToken : ($country !== '' ? $country : 'Bangladesh');
    $district = $city !== '' ? $city : ($cityToken !== '' ? $cityToken : $division);
    $area = $street !== '' ? $street : ($streetToken !== '' ? $streetToken : ($rawLocation !== '' ? $rawLocation : $district));

    return [
        'division' => $division,
        'district' => $district,
        'area' => $area,
        'full_location' => $rawLocation !== '' ? $rawLocation : trim(implode(', ', array_filter([$street, $district, $division, $country]))),
    ];
}

try {
    if (!tableExists($pdo, 'camera_cctv_feeds')) {
        respond(['success' => true, 'feeds' => []]);
    }

    $area = trim((string)($_GET['area'] ?? ''));
    $district = trim((string)($_GET['district'] ?? ''));
    $division = trim((string)($_GET['division'] ?? ''));

    $sql = "
        SELECT
            f.feed_id,
            f.camera_id,
            f.feed_label,
            f.feed_type,
            f.stream_scope,
            f.live_url,
            f.video_path,
            f.camera_location,
            f.allow_ai_detection,
            f.allow_public_viewing,
            f.is_active,
            f.created_at,
            c.full_name AS owner_name,
            c.city,
            c.street,
            c.country
        FROM camera_cctv_feeds f
        LEFT JOIN camera_contributors c ON c.camera_id = f.camera_id
        WHERE f.is_active = 1
          AND (LOWER(COALESCE(f.stream_scope, 'private')) = 'public' OR f.allow_public_viewing = 1)
        ORDER BY f.feed_id DESC
        LIMIT 400
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

    $needleArea = normalizeText($area);
    $needleDistrict = normalizeText($district);
    $needleDivision = normalizeText($division);

    $feeds = [];
    foreach ($rows as $row) {
        $loc = extractLocationParts($row);
        $divisionNorm = normalizeText((string)$loc['division']);
        $districtNorm = normalizeText((string)$loc['district']);
        $areaNorm = normalizeText((string)$loc['area']);

        if ($needleDivision !== '' && $divisionNorm !== $needleDivision) {
            continue;
        }
        if ($needleDistrict !== '' && $districtNorm !== $needleDistrict) {
            continue;
        }
        if ($needleArea !== '' && $areaNorm !== $needleArea) {
            continue;
        }

        $videoPath = trim((string)($row['video_path'] ?? ''));
        $liveUrl = trim((string)($row['live_url'] ?? ''));

        $feeds[] = [
            'feed_id' => (int)($row['feed_id'] ?? 0),
            'camera_id' => (int)($row['camera_id'] ?? 0),
            'feed_label' => (string)($row['feed_label'] ?? 'Camera Feed'),
            'feed_type' => (string)($row['feed_type'] ?? 'live'),
            'stream_scope' => (string)($row['stream_scope'] ?? 'private'),
            'live_url' => $liveUrl,
            'video_url' => $videoPath !== '' ? '../' . ltrim($videoPath, '/') : '',
            'camera_location' => (string)$loc['full_location'],
            'division' => (string)$loc['division'],
            'district' => (string)$loc['district'],
            'area' => (string)$loc['area'],
            'owner_name' => (string)($row['owner_name'] ?? 'Camera Contributor'),
            'allow_ai_detection' => (int)($row['allow_ai_detection'] ?? 0),
            'allow_public_viewing' => (int)($row['allow_public_viewing'] ?? 0),
            'is_active' => (int)($row['is_active'] ?? 0),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }

    respond(['success' => true, 'feeds' => $feeds]);
} catch (Throwable $e) {
    respond(['success' => false, 'error' => 'Failed to fetch broadcast feeds'], 500);
}
