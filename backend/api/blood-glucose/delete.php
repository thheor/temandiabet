<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'DELETE'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
if ($id < 1) {
    json_error('Missing reading id', 422);
}

$ok = $conn->query("DELETE FROM blood_glucose_records WHERE id = $id AND user_id = $userId");

if (!$ok) {
    json_error('Could not delete the reading', 500);
}
if ($conn->affected_rows === 0) {
    json_error('Reading not found', 404);
}

json_ok(['deleted' => $id]);
