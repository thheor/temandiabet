<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const RELATIONSHIPS = ['parent', 'child', 'spouse', 'sibling', 'friend', 'caregiver'];

$user = require_auth();
$input = json_input();
$caregiverId = (int) $user['id'];

$code = strtoupper(trim($input['invite_code'] ?? ''));
$relationship = $input['relationship'] ?? '';

if (!preg_match('/^GLK-[A-Z0-9]{5}$/', $code)) {
    json_error('Enter a code like GLK-AB234', 422);
}
if (!in_array($relationship, RELATIONSHIPS, true)) {
    json_error('Choose your relationship to this person', 422);
}

$codeSql = $conn->real_escape_string($code);
$result = $conn->query(
    "SELECT id, patient_id, caregiver_id, status FROM inner_circles WHERE invite_code = '$codeSql'"
);
$invite = $result->fetch_assoc();

if (!$invite) {
    json_error('That code does not exist', 404);
}
if ((int) $invite['patient_id'] === $caregiverId) {
    json_error('You cannot use your own code', 422);
}
if ($invite['status'] !== 'pending' || $invite['caregiver_id'] !== null) {
    json_error('That code has already been used', 409);
}

$inviteId = (int) $invite['id'];
$relationshipSql = $conn->real_escape_string($relationship);

$ok = $conn->query(
    "UPDATE inner_circles
     SET caregiver_id = $caregiverId, relationship = '$relationshipSql', status = 'accepted',
         accepted_at = NOW()
     WHERE id = $inviteId AND status = 'pending' AND caregiver_id IS NULL"
);

if (!$ok) {
    if ($conn->errno === 1062) {
        json_error('You are already linked to this person', 409);
    }
    json_error('Could not join with that code', 500);
}
if ($conn->affected_rows === 0) {
    json_error('That code has already been used', 409);
}

$patientId = (int) $invite['patient_id'];
$result = $conn->query("SELECT id, full_name FROM users WHERE id = $patientId");
$patient = $result->fetch_assoc();

json_ok([
    'link' => [
        'id' => $inviteId,
        'patient_id' => (int) $patient['id'],
        'patient_name' => $patient['full_name'],
        'relationship' => $relationship,
        'status' => 'accepted',
    ],
]);
