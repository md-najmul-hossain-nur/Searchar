<?php
// Exports donations data as CSV (Excel-compatible without extension mismatch warning)

declare(strict_types=1);
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Dhaka');

header('Content-Type: text/csv; charset=utf-8');
$filename = 'donations_report_' . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename=' . $filename);

// Emit BOM so Excel opens UTF-8 correctly (à§³, Bangla)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Donor Name', 'Email', 'Mobile', 'TxID', 'Amount', 'Date', 'Anonymous']);

function columnExists(PDO $pdo, string $tableName, string $columnName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c LIMIT 1");
    $stmt->execute([':t' => $tableName, ':c' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

try {
    $hasSenderMobile = columnExists($pdo, 'donations', 'sender_mobile');
    $hasTxId = columnExists($pdo, 'donations', 'tx_id');
    $hasDonorEmail = columnExists($pdo, 'donations', 'donor_email');
    $selectParts = ['donor_name', 'amount', 'date', 'anonymous'];
    $selectParts[] = $hasSenderMobile ? 'sender_mobile' : 'NULL AS sender_mobile';
    $selectParts[] = $hasTxId ? 'tx_id' : 'NULL AS tx_id';
    $selectParts[] = $hasDonorEmail ? 'donor_email' : 'NULL AS donor_email';

    $stmt = $pdo->query('SELECT ' . implode(', ', $selectParts) . ' FROM donations ORDER BY date DESC');
    $found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found = true;
        $amountRaw = $row['amount'] ?? '';
        $amountFormatted = is_numeric($amountRaw) ? 'à§³' . number_format((float)$amountRaw, 0) : (string)$amountRaw;

        $rawDate = (string)($row['date'] ?? '');
        $formattedDate = $rawDate;
        if ($rawDate !== '') {
            try {
                $formattedDate = (new DateTime($rawDate))->format('d M Y, h:i A');
            } catch (Throwable $e) {
                $formattedDate = $rawDate;
            }
        }

        fputcsv($out, [
            $row['donor_name'] ?? '',
            $row['donor_email'] ?? '',
            $row['sender_mobile'] ?? '',
            $row['tx_id'] ?? '',
            $amountFormatted,
            $formattedDate,
            $row['anonymous'] ?? '',
        ]);
    }

    if (!$found) {
        fputcsv($out, ['No data', '', '', '', '', '', '']);
    }
} catch (Throwable $e) {
    fputcsv($out, ['Error', 'Unable to fetch donations', '', '', '', '', '']);
}

fclose($out);
exit;
