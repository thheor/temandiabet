<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const MAX_PENDING_CODES = 5;
const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function make_invite_code() {
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= CODE_ALPHABET[random_int(0, strlen(CODE_ALPHABET) - 1)];
    }

    return 'GLK-' . $code;
}

$user = require_auth();
$patientId = (int) $user['id'];

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM inner_circles WHERE patient_id = $patientId AND status = 'pending'"
);
$pending = $result->fetch_assoc();

if ((int) $pending['total'] >= MAX_PENDING_CODES) {
    json_error('You already have ' . MAX_PENDING_CODES . ' unused codes. Remove one first.', 409);
}

$relationship = '';

for ($attempt = 0; $attempt < 10; $attempt++) {
    $code = make_invite_code();

    $ok = $conn->query(
        "INSERT INTO inner_circles (patient_id, caregiver_id, relationship, invite_code, status)
         VALUES ($patientId, NULL, '$relationship', '$code', 'pending')"
    );

    if ($ok) {
        json_ok([
            'invite' => [
                'id' => $conn->insert_id,
                'invite_code' => $code,
                'status' => 'pending',
            ],
        ], 201);
    }

    if ($conn->errno !== 1062) {
        json_error('Could not create the code', 500);
    }
}

json_error('Could not create a unique code, try again', 500);
