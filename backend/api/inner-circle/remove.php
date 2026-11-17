<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'DELETE'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$me = $user['id'];

$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    json_error('Missing link id', 422);
}

// Either side may revoke at any time; a pending code (no caregiver yet) can
// only be removed by the patient who made it. Revoking is immediate: monitor.php
// reads this table on every request.
$stmt = db()->prepare('DELETE FROM inner_circles WHERE id = ? AND (patient_id = ? OR caregiver_id = ?)');
$stmt->bind_param('iii', $id, $me, $me);

if (!$stmt->execute()) {
    json_error('Could not remove the link', 500);
}
if ($stmt->affected_rows === 0) {
    json_error('Link not found', 404);
}

json_ok(['removed' => $id]);
