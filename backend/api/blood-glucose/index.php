<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

$user = require_auth();

$range = $_GET['range'] ?? '7d';
$days = $range === '30d' ? 30 : 7;
$userId = $user['id'];

$stmt = db()->prepare(
    "SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
     FROM blood_glucose_records
     WHERE user_id = ? AND measured_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
     ORDER BY measured_at DESC, id DESC",
);
$stmt->bind_param('i', $userId);
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
    'range' => $days === 30 ? '30d' : '7d',
    'records' => $records,
    'summary' => [
        'count' => $count,
        'avg' => $count ? round($total / $count, 1) : null,
        'min' => $count ? min($values) : null,
        'max' => $count ? max($values) : null,
    ],
]);
