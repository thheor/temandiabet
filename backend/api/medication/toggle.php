<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
if ($id < 1) {
    json_error('Missing entry id', 422);
}

$result = $conn->query(
    "SELECT is_taken FROM medication_schedules WHERE id = $id AND user_id = $userId"
);
$row = $result->fetch_assoc();

if (!$row) {
    json_error('Entry not found', 404);
}

if (array_key_exists('is_taken', $input)) {
    $wanted = filter_var($input['is_taken'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
} else {
    $wanted = 1 - (int) $row['is_taken'];
}

$ok = $conn->query(
    "UPDATE medication_schedules SET is_taken = $wanted WHERE id = $id AND user_id = $userId"
);

if (!$ok) {
    json_error('Could not update the entry', 500);
}

json_ok(['id' => $id, 'is_taken' => $wanted]);
