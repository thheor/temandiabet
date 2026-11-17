<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/dates.php';

$user = require_auth();
$caregiverId = $user['id'];

$patientId = filter_var($_GET['patient_id'] ?? null, FILTER_VALIDATE_INT);
if ($patientId === false || $patientId < 1) {
    json_error('Missing patient_id', 422);
}

// The consent check runs on every request: an accepted link for exactly this
// (patient, caregiver) pair, or nothing is returned. Removing the link ends
// access immediately.
$stmt = db()->prepare(
    "SELECT relationship FROM inner_circles
     WHERE patient_id = ? AND caregiver_id = ? AND status = 'accepted'",
);
$stmt->bind_param('ii', $patientId, $caregiverId);
$stmt->execute();
$link = $stmt->get_result()->fetch_assoc();

if (!$link) {
    json_error('You do not have access to this patient', 403);
}

// Explicit column list: password_hash and remember_token are never selected.
$stmt = db()->prepare(
    'SELECT id, full_name, diabetes_type, target_glucose_min, target_glucose_max, avatar_path
     FROM users WHERE id = ?',
);
$stmt->bind_param('i', $patientId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    json_error('Patient not found', 404);
}

$patient['id'] = (int) $patient['id'];
$patient['target_glucose_min'] = (int) $patient['target_glucose_min'];
$patient['target_glucose_max'] = (int) $patient['target_glucose_max'];
$patient['relationship'] = $link['relationship'];

// Last 7 days, newest first (the same shape as blood-glucose/index.php).
$stmt = db()->prepare(
    'SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
     FROM blood_glucose_records
     WHERE user_id = ? AND measured_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY measured_at DESC, id DESC',
);
$stmt->bind_param('i', $patientId);
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

// The latest reading overall, which may be older than 7 days.
$stmt = db()->prepare(
    'SELECT id, glucose_mg_dl, measurement_timing, status_category, notes, measured_at
     FROM blood_glucose_records WHERE user_id = ? ORDER BY measured_at DESC, id DESC LIMIT 1',
);
$stmt->bind_param('i', $patientId);
$stmt->execute();
$latest = $stmt->get_result()->fetch_assoc() ?: null;
if ($latest) {
    $latest['id'] = (int) $latest['id'];
    $latest['glucose_mg_dl'] = (int) $latest['glucose_mg_dl'];
}

// Today's medication checklist, read-only.
$date = parse_date($_GET['scheduled_date'] ?? null) ?? date('Y-m-d');
$stmt = db()->prepare(
    'SELECT id, medication_name, dosage, scheduled_time, is_taken
     FROM medication_schedules WHERE user_id = ? AND scheduled_date = ?
     ORDER BY scheduled_time, id',
);
$stmt->bind_param('is', $patientId, $date);
$stmt->execute();

$items = [];
$taken = 0;
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $row['is_taken'] = (int) $row['is_taken'];
    $row['scheduled_time'] = substr($row['scheduled_time'], 0, 5);
    $taken += $row['is_taken'];
    $items[] = $row;
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
]);
