<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/glucose.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/dates.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$value = (int) ($input['glucose_mg_dl'] ?? 0);
$timing = $input['measurement_timing'] ?? '';

if ($value < 1) {
    json_error('Glucose must be a whole number above 0 mg/dL', 422);
}
if (!in_array($timing, GLUCOSE_TIMINGS, true)) {
    json_error('Choose when the reading was taken', 422);
}

$stamp = parse_measured_at((string) ($input['measured_at'] ?? ''), date('Y-m-d H:i:s'));
if ($stamp === false) {
    json_error('Invalid measurement time', 422);
}

$status = classifyGlucose($value, $timing);
$timingSql = $conn->real_escape_string($timing);
$statusSql = $conn->real_escape_string($status);
$stampSql = $conn->real_escape_string($stamp);

$ok = $conn->query(
    "INSERT INTO blood_glucose_records (user_id, glucose_mg_dl, measurement_timing, status_category, measured_at)
     VALUES ($userId, $value, '$timingSql', '$statusSql', '$stampSql')"
);

if (!$ok) {
    json_error('Could not save the reading', 500);
}

json_ok([
    'record' => [
        'id' => $conn->insert_id,
        'user_id' => $userId,
        'glucose_mg_dl' => $value,
        'measurement_timing' => $timing,
        'status_category' => $status,
        'notes' => null,
        'measured_at' => $stamp,
    ],
], 201);
