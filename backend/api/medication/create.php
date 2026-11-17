<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';
require_once __DIR__ . '/../../helpers/dates.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = $user['id'];

// A reminder checklist only: `dosage` is free text copied from the doctor's
// prescription. Nothing here suggests, checks or recalculates a dose.
$name = trim((string) ($input['medication_name'] ?? ''));
$dosage = trim((string) ($input['dosage'] ?? ''));
$time = parse_time((string) ($input['scheduled_time'] ?? ''));
$date = isset($input['scheduled_date']) && $input['scheduled_date'] !== ''
    ? parse_date((string) $input['scheduled_date'])
    : date('Y-m-d');

if (mb_strlen($name) < 1 || mb_strlen($name) > 120) {
    json_error('Medication name must be 1-120 characters', 422);
}
if (mb_strlen($dosage) < 1 || mb_strlen($dosage) > 60) {
    json_error('Enter the dosage exactly as prescribed (up to 60 characters)', 422);
}
if ($time === null) {
    json_error('Use scheduled_time as HH:MM', 422);
}
if ($date === null) {
    json_error('Use scheduled_date as YYYY-MM-DD', 422);
}

$stmt = db()->prepare(
    'INSERT INTO medication_schedules (user_id, medication_name, dosage, scheduled_time, scheduled_date)
     VALUES (?, ?, ?, ?, ?)',
);
$stmt->bind_param('issss', $userId, $name, $dosage, $time, $date);

if (!$stmt->execute()) {
    json_error('Could not save the entry', 500);
}

json_ok([
    'item' => [
        'id' => db()->insert_id,
        'user_id' => $userId,
        'medication_name' => $name,
        'dosage' => $dosage,
        'scheduled_time' => substr($time, 0, 5),
        'is_taken' => 0,
        'scheduled_date' => $date,
    ],
], 201);
