<?php
// Exports donations data as CSV (Excel-compatible without extension mismatch warning)

declare(strict_types=1);
require_once __DIR__ . '/db.php';

header('Content-Type: text/csv; charset=utf-8');
$filename = 'donations_report_' . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename=' . $filename);

// Emit BOM so Excel opens UTF-8 correctly (৳, Bangla)
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Donor Name', 'Amount', 'Date', 'Anonymous', 'Message']);

try {
    $stmt = $pdo->query("SELECT donor_name, amount, date, anonymous, message FROM donations ORDER BY date DESC");
    $found = false;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $found = true;
        $amountRaw = $row['amount'] ?? '';
        $amountFormatted = is_numeric($amountRaw) ? '৳' . number_format((float)$amountRaw, 0) : (string)$amountRaw;

        fputcsv($out, [
            $row['donor_name'] ?? '',
            $amountFormatted,
            $row['date'] ?? '',
            $row['anonymous'] ?? '',
            $row['message'] ?? ''
        ]);
    }

    if (!$found) {
        fputcsv($out, ['No data', '', '', '', '']);
    }
} catch (Throwable $e) {
    fputcsv($out, ['Error', 'Unable to fetch donations', '', '', '']);
}

fclose($out);
exit;
