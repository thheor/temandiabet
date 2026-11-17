<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const RELATIONSHIPS = ['parent', 'child', 'spouse', 'sibling', 'friend', 'caregiver'];

$user = require_auth();
$input = json_input();
$caregiverId = $user['id'];

$code = strtoupper(trim((string) ($input['invite_code'] ?? '')));
$relationship = (string) ($input['relationship'] ?? '');

if (!preg_match('/^GLK-[A-Z0-9]{5}$/', $code)) {
    json_error('Enter a code like GLK-AB234', 422);
}
if (!in_array($relationship, RELATIONSHIPS, true)) {
    json_error('Choose your relationship to this person', 422);
}

$stmt = db()->prepare('SELECT id, patient_id, caregiver_id, status FROM inner_circles WHERE invite_code = ?');
$stmt->bind_param('s', $code);
$stmt->execute();
$invite = $stmt->get_result()->fetch_assoc();

if (!$invite) {
    json_error('That code does not exist', 404);
}
if ((int) $invite['patient_id'] === $caregiverId) {
    json_error('You cannot use your own code', 422);
}
if ($invite['status'] !== 'pending' || $invite['caregiver_id'] !== null) {
    json_error('That code has already been used', 409);
}

// The status/caregiver guard sits in the UPDATE itself, so two people racing
// for one code cannot both win.
$stmt = db()->prepare(
    "UPDATE inner_circles
     SET caregiver_id = ?, relationship = ?, status = 'accepted', accepted_at = NOW()
     WHERE id = ? AND status = 'pending' AND caregiver_id IS NULL",
);
$inviteId = (int) $invite['id'];
$stmt->bind_param('isi', $caregiverId, $relationship, $inviteId);

if (!$stmt->execute()) {
    // 1062 = uk_patient_caregiver: this pair is already linked through another code.
    if ($stmt->errno === 1062) {
        json_error('You are already linked to this person', 409);
    }
    json_error('Could not join with that code', 500);
}
if ($stmt->affected_rows === 0) {
    json_error('That code has already been used', 409);
}

$stmt = db()->prepare('SELECT id, full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $invite['patient_id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

json_ok([
    'link' => [
        'id' => $inviteId,
        'patient_id' => (int) $patient['id'],
        'patient_name' => $patient['full_name'],
        'relationship' => $relationship,
        'status' => 'accepted',
    ],
]);
