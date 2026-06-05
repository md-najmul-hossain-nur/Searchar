<?php
/**
 * fetch_redzone_incidents.php
 * Returns crime + fire incidents (with lat/lng) and missing person reports
 * for the RedZone heat map.
 *
 * Query params:
 *   hours   = 24 | 168 | 720 | all   (default: 24)
 *   cat     = all | crime | fire | missing_person  (default: all)
 *
 * Response JSON:
 * {
 *   "success": true,
 *   "incidents": [
 *     {
 *       "id": 1,
 *       "category": "crime",          // crime | fire | missing_person
 *       "lat": 23.7469,
 *       "lng": 90.3820,
 *       "intensity": 0.85,            // 0.0 – 1.0  (derived from severity)
 *       "place": "Chawkbazar",        // landmark text
 *       "note": "Snatching incident", // short description
 *       "timestamp_ms": 1718000000000 // JS-compatible ms timestamp
 *     }, ...
 *   ],
 *   "total": 12,
 *   "generated_at": "2026-06-05T10:00:00+06:00"
 * }
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// ── Allow cross-origin if frontend is on a different port during dev ──
// Remove or tighten in production.
header('Access-Control-Allow-Origin: *');

session_start();
require_once __DIR__ . '/db.php';

// ── Auth check ────────────────────────────────────────────────────────
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ── Input sanitisation ────────────────────────────────────────────────
$rawHours = trim((string)($_GET['hours'] ?? '24'));
$hours    = ($rawHours === 'all') ? 'all' : max(1, (int)$rawHours);

$allowedCats = ['all', 'crime', 'fire', 'missing_person'];
$cat         = in_array($_GET['cat'] ?? '', $allowedCats, true)
               ? $_GET['cat']
               : 'all';

// ── Severity → intensity map ─────────────────────────────────────────
// crime_reports.severity  →  float intensity for heatmap
function severityToIntensity(string $sev): float
{
    return match (strtolower(trim($sev))) {
        'critical', 'high'   => 0.90,
        'medium', 'moderate' => 0.65,
        'low'                => 0.40,
        default              => 0.55,
    };
}

// ── Build WHERE clause for time window ───────────────────────────────
$timeConditionCrime   = ($hours === 'all')
    ? '1=1'
    : "c.submitted_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";

$timeConditionMissing = ($hours === 'all')
    ? '1=1'
    : "m.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)";

$incidents = [];

try {

    // ══════════════════════════════════════════════════════════════════
    // 1.  CRIME REPORTS (has lat/lng in DB)
    //     report_type values in your DB: 'crime', 'fire', 'theft', etc.
    //     We map them to our three categories.
    // ══════════════════════════════════════════════════════════════════
    if ($cat === 'all' || $cat === 'crime' || $cat === 'fire') {

        // Build category filter
        $catFilter = '';
        if ($cat === 'crime') {
            $catFilter = "AND c.report_type NOT IN ('fire', 'arson', 'explosion')";
        } elseif ($cat === 'fire') {
            $catFilter = "AND c.report_type IN ('fire', 'arson', 'explosion')";
        }

        $sql = "
            SELECT
                c.crime_id,
                c.report_type,
                c.severity,
                c.landmark,
                c.description,
                c.lat,
                c.lng,
                c.submitted_at
            FROM crime_reports c
            WHERE
                c.lat IS NOT NULL
                AND c.lng IS NOT NULL
                AND c.status NOT IN ('closed', 'dismissed')
                AND $timeConditionCrime
                $catFilter
            ORDER BY c.submitted_at DESC
            LIMIT 200
        ";

        $stmt = $pdo->prepare($sql);
        if ($hours !== 'all') {
            $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            // Map DB report_type → frontend category
            $rt = strtolower(trim((string)($row['report_type'] ?? '')));
            $category = in_array($rt, ['fire', 'arson', 'explosion']) ? 'fire' : 'crime';

            // Truncate long descriptions to ~100 chars for the popup
            $note = trim((string)($row['description'] ?? ''));
            if (strlen($note) > 100) {
                $note = substr($note, 0, 97) . '…';
            }
            if ($note === '') {
                $note = ucfirst($rt) . ' incident reported.';
            }

            $place = trim((string)($row['landmark'] ?? ''));
            if ($place === '') {
                $place = 'Location #' . $row['crime_id'];
            }

            $incidents[] = [
                'id'           => (int)$row['crime_id'],
                'category'     => $category,
                'lat'          => (float)$row['lat'],
                'lng'          => (float)$row['lng'],
                'intensity'    => severityToIntensity((string)($row['severity'] ?? '')),
                'place'        => $place,
                'note'         => $note,
                'timestamp_ms' => strtotime((string)$row['submitted_at']) * 1000,
            ];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // 2.  MISSING PERSON REPORTS
    //     No lat/lng in DB — we geocode from last_seen_location using a
    //     pre-built area → coordinate lookup table for Dhaka.
    //     If location is unrecognised we place a slight random jitter
    //     around the city centre so the heatmap still shows something.
    // ══════════════════════════════════════════════════════════════════
    if ($cat === 'all' || $cat === 'missing_person') {

        // Static lookup: common Dhaka area keywords → approx centre coords
        // Extend this array as your data grows.
        $areaCoords = [
            'mirpur'        => [23.8223, 90.3654],
            'gulshan'       => [23.7806, 90.4150],
            'banani'        => [23.7937, 90.4066],
            'dhanmondi'     => [23.7461, 90.3742],
            'uttara'        => [23.8759, 90.3795],
            'mohammadpur'   => [23.7622, 90.3585],
            'farmgate'      => [23.7592, 90.3894],
            'motijheel'     => [23.7292, 90.4194],
            'wari'          => [23.7210, 90.4176],
            'lalbagh'       => [23.7192, 90.3869],
            'jatrabari'     => [23.7118, 90.4315],
            'khilgaon'      => [23.7460, 90.4295],
            'rampura'       => [23.7670, 90.4280],
            'badda'         => [23.7802, 90.4314],
            'demra'         => [23.7100, 90.4640],
            'kadamtali'     => [23.7250, 90.4450],
            'shyamoli'      => [23.7760, 90.3595],
            'agargaon'      => [23.7780, 90.3762],
            'pallabi'       => [23.8290, 90.3690],
            'tejgaon'       => [23.7540, 90.4009],
            'sadarghat'     => [23.7070, 90.4060],
            'chawkbazar'    => [23.7163, 90.3985],
            'old dhaka'     => [23.7163, 90.3985],
            'azimpur'       => [23.7255, 90.3877],
            'paltan'        => [23.7337, 90.4140],
            'shahbag'       => [23.7382, 90.3958],
            'baridhara'     => [23.8000, 90.4300],
            'kuril'         => [23.8160, 90.4280],
        ];

        $sqlM = "
            SELECT
                m.report_id,
                m.full_name,
                m.last_seen_location,
                m.last_seen_date,
                m.status,
                m.created_at
            FROM missing_person_reports m
            WHERE
                m.status = 'open'
                AND $timeConditionMissing
            ORDER BY m.created_at DESC
            LIMIT 100
        ";

        $stmtM = $pdo->prepare($sqlM);
        if ($hours !== 'all') {
            $stmtM->bindValue(':hours', $hours, PDO::PARAM_INT);
        }
        $stmtM->execute();
        $mrows = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mrows as $mrow) {
            $locText  = strtolower(trim((string)($mrow['last_seen_location'] ?? '')));
            $lat      = null;
            $lng      = null;

            // Try keyword match in our lookup table
            foreach ($areaCoords as $keyword => $coords) {
                if (str_contains($locText, $keyword)) {
                    $lat = $coords[0];
                    $lng = $coords[1];
                    break;
                }
            }

            // Fallback: city centre + small random jitter (±0.02°)
            if ($lat === null) {
                $lat = 23.8103 + (mt_rand(-200, 200) / 10000);
                $lng = 90.4125 + (mt_rand(-200, 200) / 10000);
            }

            $place = trim((string)($mrow['last_seen_location'] ?? ''));
            if ($place === '') {
                $place = 'Unknown location';
            }

            $incidents[] = [
                'id'           => (int)$mrow['report_id'],
                'category'     => 'missing_person',
                'lat'          => round($lat, 6),
                'lng'          => round($lng, 6),
                'intensity'    => 0.60,   // Fixed intensity for missing persons
                'place'        => $place,
                'note'         => 'Missing: ' . (string)($mrow['full_name'] ?? 'Unknown')
                                  . ' (last seen ' . (string)($mrow['last_seen_date'] ?? '') . ')',
                'timestamp_ms' => strtotime((string)$mrow['created_at']) * 1000,
            ];
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed: ' . $e->getMessage()]);
    exit;
}

// ── Response ─────────────────────────────────────────────────────────
echo json_encode([
    'success'      => true,
    'incidents'    => $incidents,
    'total'        => count($incidents),
    'generated_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Dhaka')))->format('c'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
