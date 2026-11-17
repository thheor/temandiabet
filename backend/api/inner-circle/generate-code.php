<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const MAX_PENDING_CODES = 5;
// No 0/O/1/I so a code read aloud or copied by hand is not misread.
const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function make_invite_code(): string
{
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= CODE_ALPHABET[random_int(0, strlen(CODE_ALPHABET) - 1)];
    }

    return 'GLK-' . $code;
}

$user = require_auth();
$patientId = $user['id'];

$stmt = db()->prepare(
    "SELECT COUNT(*) AS total FROM inner_circles WHERE patient_id = ? AND status = 'pending'",
);
$stmt->bind_param('i', $patientId);
$stmt->execute();
if ((int) $stmt->get_result()->fetch_assoc()['total'] >= MAX_PENDING_CODES) {
    json_error('You already have ' . MAX_PENDING_CODES . ' unused codes. Remove one first.', 409);
}

// Generating a code is the patient's explicit consent to share data with
// whoever claims it (UU PDP No. 27/2022). The relationship is declared by the
// caregiver on joining; the NOT NULL column holds '' until then.
$relationship = '';

for ($attempt = 0; $attempt < 10; $attempt++) {
    $code = make_invite_code();

    $stmt = db()->prepare(
        "INSERT INTO inner_circles (patient_id, caregiver_id, relationship, invite_code, status)
         VALUES (?, NULL, ?, ?, 'pending')",
    );
    $stmt->bind_param('iss', $patientId, $relationship, $code);

    if ($stmt->execute()) {
        json_ok([
            'invite' => [
                'id' => db()->insert_id,
                'invite_code' => $code,
                'status' => 'pending',
            ],
        ], 201);
    }

    // 1062 = duplicate invite_code: draw another. Anything else is a real failure.
    if ($stmt->errno !== 1062) {
        json_error('Could not create the code', 500);
    }
}

json_error('Could not create a unique code, try again', 500);
