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

    $sql = "SELECT crime_id, case_ref, report_type, severity, status, landmark, reporter_name,
                   anonymous, anon_token, description, media_path, media_json, lat, lng,
                   submitted_at, updated_at
            FROM crime_reports
            ORDER BY submitted_at DESC, crime_id DESC
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
                    $candidate = is_array($item) ? (string)($item['path'] ?? $item['url'] ?? '') : (string)$item;
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
