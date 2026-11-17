<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/glucose.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/dates.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
$value = (int) ($input['glucose_mg_dl'] ?? 0);
$timing = $input['measurement_timing'] ?? '';

if ($id < 1) {
    json_error('Missing reading id', 422);
}
if ($value < 1) {
    json_error('Glucose must be a whole number above 0 mg/dL', 422);
}
if (!in_array($timing, GLUCOSE_TIMINGS, true)) {
    json_error('Choose when the reading was taken', 422);
}

$result = $conn->query(
    "SELECT measured_at, notes FROM blood_glucose_records WHERE id = $id AND user_id = $userId"
);
$existing = $result->fetch_assoc();

if (!$existing) {
    json_error('Reading not found', 404);
}

$stamp = parse_measured_at((string) ($input['measured_at'] ?? ''), $existing['measured_at']);
if ($stamp === false) {
    json_error('Invalid measurement time', 422);
}

$notes = $existing['notes'];
if (array_key_exists('notes', $input)) {
    $notes = trim((string) $input['notes']);
    if (strlen($notes) > 255) {
        json_error('Notes must be 255 characters or fewer', 422);
    }
    $notes = $notes === '' ? null : $notes;
}

$status = classifyGlucose($value, $timing);
$timingSql = $conn->real_escape_string($timing);
$statusSql = $conn->real_escape_string($status);
$stampSql = $conn->real_escape_string($stamp);
$notesSql = $notes === null ? 'NULL' : "'" . $conn->real_escape_string($notes) . "'";

$ok = $conn->query(
    "UPDATE blood_glucose_records
     SET glucose_mg_dl = $value, measurement_timing = '$timingSql', status_category = '$statusSql',
         notes = $notesSql, measured_at = '$stampSql'
     WHERE id = $id AND user_id = $userId"
);

if (!$ok) {
    json_error('Could not update the reading', 500);
}

json_ok([
    'record' => [
        'id' => $id,
        'user_id' => $userId,
        'glucose_mg_dl' => $value,
        'measurement_timing' => $timing,
        'status_category' => $status,
        'notes' => $notes,
        'measured_at' => $stamp,
    ],
]);
