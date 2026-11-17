<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$userId = $user['id'];

// ?from=YYYY-MM-DD&to=YYYY-MM-DD (inclusive) drives the printable report;
// otherwise ?range=7d|30d is a rolling window from now.
$from = parse_date($_GET['from'] ?? null);
$to = parse_date($_GET['to'] ?? null);

if (isset($_GET['from']) || isset($_GET['to'])) {
    if ($from === null || $to === null) {
        json_error('Use from and to as YYYY-MM-DD', 422);
    }
    if ($from > $to) {
        json_error('The start date must not be after the end date', 422);
    }

    $stmt = db()->prepare(
        'SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
         FROM blood_glucose_records
         WHERE user_id = ? AND measured_at >= ? AND measured_at < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY measured_at DESC, id DESC',
    );
    $stmt->bind_param('iss', $userId, $from, $to);
    $rangeLabel = 'custom';
} else {
    $range = $_GET['range'] ?? '7d';
    $days = $range === '30d' ? 30 : 7;

    $stmt = db()->prepare(
        "SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
         FROM blood_glucose_records
         WHERE user_id = ? AND measured_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
         ORDER BY measured_at DESC, id DESC",
    );
    $stmt->bind_param('i', $userId);
    $rangeLabel = $days === 30 ? '30d' : '7d';
}

$stmt->execute();

$records = [];
$total = 0;
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['glucose_mg_dl'] = (int) $row['glucose_mg_dl'];
    $records[] = $row;
    $total += $row['glucose_mg_dl'];
}

$count = count($records);
$values = array_column($records, 'glucose_mg_dl');

json_ok([
    'range' => $rangeLabel,
    'records' => $records,
    'summary' => [
        'count' => $count,
        'avg' => $count ? round($total / $count, 1) : null,
        'min' => $count ? min($values) : null,
        'max' => $count ? max($values) : null,
    ],
]);
