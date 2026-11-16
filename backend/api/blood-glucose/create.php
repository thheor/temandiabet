<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/glucose.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();

$value = filter_var($input['glucose_mg_dl'] ?? null, FILTER_VALIDATE_INT);
$timing = (string) ($input['measurement_timing'] ?? '');

if ($value === false || $value < 1) {
    json_error('Glucose must be a whole number above 0 mg/dL', 422);
}
if (!in_array($timing, GLUCOSE_TIMINGS, true)) {
    json_error('Choose when the reading was taken', 422);
}

$measuredAt = (string) ($input['measured_at'] ?? '');
$when = new DateTimeImmutable();
if ($measuredAt !== '') {
    $when = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $measuredAt)
        ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $measuredAt)
        ?: null;
    if ($when === null) {
        json_error('Invalid measurement time', 422);
    }
}

$status = classifyGlucose($value, $timing);
$stamp = $when->format('Y-m-d H:i:s');
$userId = $user['id'];

$stmt = db()->prepare(
    'INSERT INTO blood_glucose_records (user_id, glucose_mg_dl, measurement_timing, status_category, measured_at) VALUES (?, ?, ?, ?, ?)',
);
$stmt->bind_param('iisss', $userId, $value, $timing, $status, $stamp);

if (!$stmt->execute()) {
    json_error('Could not save the reading', 500);
}

json_ok([
    'record' => [
        'id' => db()->insert_id,
        'user_id' => $userId,
        'glucose_mg_dl' => $value,
        'measurement_timing' => $timing,
        'status_category' => $status,
        'notes' => null,
        'measured_at' => $stamp,
    ],
], 201);
