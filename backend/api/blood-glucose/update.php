<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/glucose.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = $user['id'];

$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$value = filter_var($input['glucose_mg_dl'] ?? null, FILTER_VALIDATE_INT);
$timing = (string) ($input['measurement_timing'] ?? '');

if ($id === false || $id < 1) {
    json_error('Missing reading id', 422);
}
if ($value === false || $value < 1) {
    json_error('Glucose must be a whole number above 0 mg/dL', 422);
}
if (!in_array($timing, GLUCOSE_TIMINGS, true)) {
    json_error('Choose when the reading was taken', 422);
}

// Owner check first: a foreign or missing id is a 404, never a 403, so ids of
// other users' readings cannot be probed.
$stmt = db()->prepare('SELECT measured_at, notes FROM blood_glucose_records WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if (!$existing) {
    json_error('Reading not found', 404);
}

$stamp = $existing['measured_at'];
$measuredAt = (string) ($input['measured_at'] ?? '');
if ($measuredAt !== '') {
    $when = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $measuredAt)
        ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $measuredAt)
        ?: null;
    if ($when === null) {
        json_error('Invalid measurement time', 422);
    }
    $stamp = $when->format('Y-m-d H:i:s');
}

$notes = $existing['notes'];
if (array_key_exists('notes', $input)) {
    $notes = trim((string) $input['notes']);
    if (mb_strlen($notes) > 255) {
        json_error('Notes must be 255 characters or fewer', 422);
    }
    $notes = $notes === '' ? null : $notes;
}

// The status always comes from the single classifier, never from the client.
$status = classifyGlucose($value, $timing);

$stmt = db()->prepare(
    'UPDATE blood_glucose_records
     SET glucose_mg_dl = ?, measurement_timing = ?, status_category = ?, notes = ?, measured_at = ?
     WHERE id = ? AND user_id = ?',
);
$stmt->bind_param('issssii', $value, $timing, $status, $notes, $stamp, $id, $userId);

if (!$stmt->execute()) {
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
