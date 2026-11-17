<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$caregiverId = (int) $user['id'];

$patientId = (int) ($_GET['patient_id'] ?? 0);
if ($patientId < 1) {
    json_error('Missing patient_id', 422);
}

$result = $conn->query(
    "SELECT relationship FROM inner_circles
     WHERE patient_id = $patientId AND caregiver_id = $caregiverId AND status = 'accepted'"
);
$link = $result->fetch_assoc();

if (!$link) {
    json_error('You do not have access to this patient', 403);
}

$result = $conn->query(
    "SELECT id, full_name, diabetes_type, target_glucose_min, target_glucose_max, avatar_path
     FROM users WHERE id = $patientId"
);
$patient = $result->fetch_assoc();

if (!$patient) {
    json_error('Patient not found', 404);
}

$patient['id'] = (int) $patient['id'];
$patient['target_glucose_min'] = (int) $patient['target_glucose_min'];
$patient['target_glucose_max'] = (int) $patient['target_glucose_max'];
$patient['relationship'] = $link['relationship'];

$result = $conn->query(
    "SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
     FROM blood_glucose_records
     WHERE user_id = $patientId AND measured_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY measured_at DESC, id DESC"
);

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

$result = $conn->query(
    "SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
     FROM blood_glucose_records WHERE user_id = $patientId
     ORDER BY measured_at DESC, id DESC LIMIT 1"
);
$latest = $result->fetch_assoc() ?: null;

if ($latest) {
    $latest['id'] = (int) $latest['id'];
    $latest['glucose_mg_dl'] = (int) $latest['glucose_mg_dl'];
}

$date = parse_date($_GET['scheduled_date'] ?? null);
if ($date === null) {
    $date = date('Y-m-d');
}
$dateSql = $conn->real_escape_string($date);

$result = $conn->query(
    "SELECT id, medication_name, dosage, scheduled_time, is_taken
     FROM medication_schedules WHERE user_id = $patientId AND scheduled_date = '$dateSql'
     ORDER BY scheduled_time, id"
);

$items = [];
$taken = 0;
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['is_taken'] = (int) $row['is_taken'];
    $row['scheduled_time'] = substr($row['scheduled_time'], 0, 5);
    $taken += $row['is_taken'];
    $items[] = $row;
}

$result = $conn->query(
    "SELECT id, food_name, meal_type, estimated_calories, carbohydrates_grams, photo_path
     FROM food_logs WHERE user_id = $patientId AND eaten_date = '$dateSql'
     ORDER BY FIELD(meal_type, 'breakfast', 'lunch', 'dinner', 'snack'), id"
);

$meals = [];
$calories = 0;
$carbs = 0.0;
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['estimated_calories'] = (int) $row['estimated_calories'];
    $row['carbohydrates_grams'] = (float) $row['carbohydrates_grams'];
    $calories += $row['estimated_calories'];
    $carbs += $row['carbohydrates_grams'];
    $meals[] = $row;
}

json_ok([
    'patient' => $patient,
    'latest' => $latest,
    'glucose' => [
        'records' => $records,
        'summary' => [
            'count' => $count,
            'avg' => $count ? round($total / $count, 1) : null,
            'min' => $count ? min($values) : null,
            'max' => $count ? max($values) : null,
        ],
    ],
    'medication' => [
        'scheduled_date' => $date,
        'items' => $items,
        'progress' => ['taken' => $taken, 'total' => count($items)],
    ],
    'food' => [
        'eaten_date' => $date,
        'meals' => $meals,
        'totals' => [
            'estimated_calories' => $calories,
            'carbohydrates_grams' => round($carbs, 2),
        ],
    ],
]);
