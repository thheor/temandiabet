<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/dates.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$name = trim($input['medication_name'] ?? '');
$dosage = trim($input['dosage'] ?? '');
$time = parse_time($input['scheduled_time'] ?? null);
$date = isset($input['scheduled_date']) && $input['scheduled_date'] !== ''
    ? parse_date($input['scheduled_date'])
    : date('Y-m-d');

if ($name === '' || strlen($name) > 120) {
    json_error('Medication name must be 1-120 characters', 422);
}
if ($dosage === '' || strlen($dosage) > 60) {
    json_error('Enter the dosage exactly as prescribed (up to 60 characters)', 422);
}
if ($time === null) {
    json_error('Use scheduled_time as HH:MM', 422);
}
if ($date === null) {
    json_error('Use scheduled_date as YYYY-MM-DD', 422);
}

$nameSql = $conn->real_escape_string($name);
$dosageSql = $conn->real_escape_string($dosage);
$dateSql = $conn->real_escape_string($date);

$ok = $conn->query(
    "INSERT INTO medication_schedules
        (user_id, medication_name, dosage, scheduled_time, scheduled_date)
     VALUES ($userId, '$nameSql', '$dosageSql', '$time', '$dateSql')"
);

if (!$ok) {
    json_error('Could not save the entry', 500);
}

json_ok([
    'item' => [
        'id' => $conn->insert_id,
        'user_id' => $userId,
        'medication_name' => $name,
        'dosage' => $dosage,
        'scheduled_time' => substr($time, 0, 5),
        'is_taken' => 0,
        'scheduled_date' => $date,
    ],
], 201);
