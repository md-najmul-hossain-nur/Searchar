<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
    $stmt->execute([':t' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

try {
    if (!tableExists($pdo, 'crime_reports')) {
        echo json_encode([
            'success' => true,
            'rows' => [],
        ]);
        exit;
    }

        $sql = "SELECT cr.crime_id, cr.case_ref, cr.source_type, cr.source_ref_id,
                 cr.report_type, cr.severity, cr.status, cr.landmark, cr.reporter_name,
                 cr.anonymous, cr.anon_token, cr.description, cr.media_path, cr.media_json,
                 cr.lat, cr.lng, cr.submitted_at, cr.updated_at,
                 mpr.photo_filename AS missing_photo_filename,
                 p.media_path AS post_media_path,
                 p.media_json AS post_media_json
             FROM crime_reports cr
             LEFT JOIN missing_person_reports mpr
                 ON cr.source_type = 'missing_person' AND cr.source_ref_id = mpr.report_id
             LEFT JOIN posts p
                 ON cr.source_type = 'post' AND cr.source_ref_id = p.id
            ORDER BY cr.submitted_at DESC, cr.crime_id DESC
            LIMIT 1000";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $payload = array_map(static function (array $row): array {
        $media = [];
        $mediaPath = trim((string)($row['media_path'] ?? ''));
        $mediaJson = trim((string)($row['media_json'] ?? ''));

        if ($mediaJson !== '') {
            $parsed = json_decode($mediaJson, true);
            if (is_array($parsed)) {
                foreach ($parsed as $item) {
                    $candidate = is_array($item)
                        ? (string)($item['path'] ?? $item['url'] ?? $item['media_path'] ?? $item['file'] ?? $item['src'] ?? '')
                        : (string)$item;
                    $candidate = trim($candidate);
                    if ($candidate !== '') {
                        $media[] = ['type' => 'media', 'url' => $candidate, 'hash' => ''];
                    }
                }
            }
        }

        if ($mediaPath !== '' && count($media) === 0) {
            $media[] = ['type' => 'media', 'url' => $mediaPath, 'hash' => ''];
        }

        if (count($media) === 0) {
            $sourceType = strtolower(trim((string)($row['source_type'] ?? '')));

            if ($sourceType === 'missing_person') {
                $photo = trim((string)($row['missing_photo_filename'] ?? ''));
                if ($photo !== '') {
                    $media[] = [
                        'type' => 'media',
                        'url' => 'uploads/missing_person/' . ltrim($photo, '/'),
                        'hash' => ''
                    ];
                }
            } elseif ($sourceType === 'post') {
                $postMediaJson = trim((string)($row['post_media_json'] ?? ''));
                $postMediaPath = trim((string)($row['post_media_path'] ?? ''));

                if ($postMediaJson !== '') {
                    $parsedPost = json_decode($postMediaJson, true);
                    if (is_array($parsedPost)) {
                        foreach ($parsedPost as $item) {
                            $candidate = is_array($item)
                                ? (string)($item['path'] ?? $item['url'] ?? $item['media_path'] ?? $item['file'] ?? $item['src'] ?? '')
                                : (string)$item;
                            $candidate = trim($candidate);
                            if ($candidate !== '') {
                                $media[] = ['type' => 'media', 'url' => ltrim($candidate, '/'), 'hash' => ''];
                            }
                        }
                    }
                }

                if (count($media) === 0 && $postMediaPath !== '') {
                    $media[] = ['type' => 'media', 'url' => ltrim($postMediaPath, '/'), 'hash' => ''];
                }
            }
        }

        return [
            'id' => trim((string)($row['case_ref'] ?? '')),
            'type' => trim((string)($row['report_type'] ?? 'other')) ?: 'other',
            'severity' => trim((string)($row['severity'] ?? 'medium')) ?: 'medium',
            'status' => trim((string)($row['status'] ?? 'new')) ?: 'new',
            'lat' => (float)($row['lat'] ?? 23.8103),
            'lng' => (float)($row['lng'] ?? 90.4125),
            'landmark' => trim((string)($row['landmark'] ?? '')) ?: 'Unknown location',
            'submitted' => (string)($row['submitted_at'] ?? date('c')),
            'updated_at' => (string)($row['updated_at'] ?? date('c')),
            'media' => $media,
            'reporter' => trim((string)($row['reporter_name'] ?? '')) ?: 'Unknown',
            'anonymous' => (int)($row['anonymous'] ?? 0) === 1,
            'token' => trim((string)($row['anon_token'] ?? '')),
            'description' => trim((string)($row['description'] ?? '')),
            'reward_paid' => false,
        ];
    }, $rows);

    echo json_encode([
        'success' => true,
        'rows' => $payload,
    ]);
} catch (Throwable $e) {
    error_log('fetch_crime_reports_admin error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch crime reports',
    ]);
}
