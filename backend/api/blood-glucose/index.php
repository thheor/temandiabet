<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$userId = (int) $user['id'];

$from = parse_date($_GET['from'] ?? null);
$to = parse_date($_GET['to'] ?? null);

$columns = 'id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at';

if (isset($_GET['from']) || isset($_GET['to'])) {
    if ($from === null || $to === null) {
        json_error('Use from and to as YYYY-MM-DD', 422);
    }
    if ($from > $to) {
        json_error('The start date must not be after the end date', 422);
    }

    $fromSql = $conn->real_escape_string($from);
    $toSql = $conn->real_escape_string($to);

    $sql = "SELECT $columns
            FROM blood_glucose_records
            WHERE user_id = $userId AND measured_at >= '$fromSql'
              AND measured_at < DATE_ADD('$toSql', INTERVAL 1 DAY)
            ORDER BY measured_at DESC, id DESC";
    $rangeLabel = 'custom';
} else {
    $range = $_GET['range'] ?? '7d';
    $days = $range === '30d' ? 30 : 7;

    $sql = "SELECT $columns
            FROM blood_glucose_records
            WHERE user_id = $userId AND measured_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
            ORDER BY measured_at DESC, id DESC";
    $rangeLabel = $days === 30 ? '30d' : '7d';
}

$result = $conn->query($sql);

$records = [];
$total = 0;
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
