<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';

$user = require_auth();
$me = $user['id'];

// Only name, email and avatar of the other person are exposed, never the
// users row itself.
function circle_rows(string $sql, int $me): array
{
    $stmt = db()->prepare($sql);
    $stmt->bind_param('i', $me);
    $stmt->execute();

    $rows = [];
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        $rows[] = $row;
    }

    return $rows;
}

// People who can see my data.
$caregivers = circle_rows(
    "SELECT ic.id, ic.relationship, ic.accepted_at, u.id AS user_id, u.full_name, u.email, u.avatar_path
     FROM inner_circles ic
     JOIN users u ON u.id = ic.caregiver_id
     WHERE ic.patient_id = ? AND ic.status = 'accepted'
     ORDER BY ic.accepted_at DESC, ic.id DESC",
    $me,
);

// People whose data I can see.
$patients = circle_rows(
    "SELECT ic.id, ic.relationship, ic.accepted_at, u.id AS user_id, u.full_name, u.email, u.avatar_path
     FROM inner_circles ic
     JOIN users u ON u.id = ic.patient_id
     WHERE ic.caregiver_id = ? AND ic.status = 'accepted'
     ORDER BY ic.accepted_at DESC, ic.id DESC",
    $me,
);

// Codes I generated that nobody has claimed yet.
$stmt = db()->prepare(
    "SELECT id, invite_code, created_at FROM inner_circles
     WHERE patient_id = ? AND status = 'pending' ORDER BY id DESC",
);
$stmt->bind_param('i', $me);
$stmt->execute();
$pending = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int) $row['id'];
    $pending[] = $row;
}

json_ok([
    'caregivers_of_me' => $caregivers,
    'patients_i_monitor' => $patients,
    'pending_codes' => $pending,
]);
