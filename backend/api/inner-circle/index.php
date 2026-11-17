<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';

$user = require_auth();
$me = (int) $user['id'];

function circle_rows($sql) {
    global $conn;

    $result = $conn->query($sql);
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $rows[] = $row;
    }

    return $rows;
}

$caregivers = circle_rows(
    "SELECT ic.id, ic.relationship, ic.accepted_at, u.id AS user_id, u.full_name, u.email, u.avatar_path
     FROM inner_circles ic
     JOIN users u ON u.id = ic.caregiver_id
     WHERE ic.patient_id = $me AND ic.status = 'accepted'
     ORDER BY ic.accepted_at DESC, ic.id DESC"
);

$patients = circle_rows(
    "SELECT ic.id, ic.relationship, ic.accepted_at, u.id AS user_id, u.full_name, u.email, u.avatar_path
     FROM inner_circles ic
     JOIN users u ON u.id = ic.patient_id
     WHERE ic.caregiver_id = $me AND ic.status = 'accepted'
     ORDER BY ic.accepted_at DESC, ic.id DESC"
);

$result = $conn->query(
    "SELECT id, invite_code, created_at FROM inner_circles
     WHERE patient_id = $me AND status = 'pending' ORDER BY id DESC"
);

$pending = [];
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $pending[] = $row;
}

json_ok([
    'caregivers_of_me' => $caregivers,
    'patients_i_monitor' => $patients,
    'pending_codes' => $pending,
]);
