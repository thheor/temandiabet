<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$userId = $user['id'];

$date = isset($_GET['scheduled_date']) ? parse_date($_GET['scheduled_date']) : date('Y-m-d');
if ($date === null) {
    json_error('Use scheduled_date as YYYY-MM-DD', 422);
}

$stmt = db()->prepare(
    'SELECT id, user_id, medication_name, dosage, scheduled_time, is_taken, scheduled_date, created_at
     FROM medication_schedules
     WHERE user_id = ? AND scheduled_date = ?
     ORDER BY scheduled_time, id',
);
$stmt->bind_param('is', $userId, $date);
$stmt->execute();

$items = [];
$taken = 0;
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['user_id'] = (int) $row['user_id'];
    $row['is_taken'] = (int) $row['is_taken'];
    $row['scheduled_time'] = substr($row['scheduled_time'], 0, 5);
    $taken += $row['is_taken'];
    $items[] = $row;
}

json_ok([
    'scheduled_date' => $date,
    'items' => $items,
    'progress' => ['taken' => $taken, 'total' => count($items)],
]);
