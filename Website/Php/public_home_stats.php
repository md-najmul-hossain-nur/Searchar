<?php
// Public endpoint for homepage live stats.

declare(strict_types=1);
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t LIMIT 1");
        $stmt->execute([':t' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
        $stmt->execute([':t' => $table, ':c' => $column]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function safeCount(PDO $pdo, string $table): int {
    if (!tableExists($pdo, $table)) {
        return 0;
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `{$table}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

function safeDonationSum(PDO $pdo): float {
    if (!tableExists($pdo, 'donations')) {
        return 0.0;
    }

    try {
        $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total_amount FROM donations");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['total_amount'] ?? 0);
    } catch (Throwable $e) {
        return 0.0;
    }
}

function safeSolvedCases(PDO $pdo): int {
    if (!tableExists($pdo, 'missing_person_reports')) {
        return 0;
    }

    $hasResolvedAt = columnExists($pdo, 'missing_person_reports', 'resolved_at');
    $hasStatus = columnExists($pdo, 'missing_person_reports', 'status');

    if (!$hasResolvedAt && !$hasStatus) {
        return 0;
    }

    $conditions = [];
    if ($hasResolvedAt) {
        $conditions[] = 'resolved_at IS NOT NULL';
    }
    if ($hasStatus) {
        $conditions[] = "LOWER(COALESCE(status, 'open')) IN ('resolved','found','closed','completed','reunited')";
    }

    try {
        $sql = 'SELECT COUNT(*) AS c FROM missing_person_reports WHERE ' . implode(' OR ', $conditions);
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    } catch (Throwable $e) {
        return 0;
    }
}

try {
    $totalUsers = safeCount($pdo, 'users');
    $totalPolicemen = safeCount($pdo, 'policemen');
    $totalVolunteers = safeCount($pdo, 'volunteers');
    $totalCameras = safeCount($pdo, 'camera_contributors');
    $peopleImpacted = $totalUsers + $totalPolicemen + $totalVolunteers + $totalCameras;
    $moneyDonated = safeDonationSum($pdo);
    $solvedCases = safeSolvedCases($pdo);

    echo json_encode([
        'success' => true,
        'data' => [
            'solvedCases' => $solvedCases,
            'peopleImpacted' => $peopleImpacted,
            'totalVolunteers' => $totalVolunteers,
            'moneyDonated' => $moneyDonated,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load homepage stats']);
}
