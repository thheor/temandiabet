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
$userId = $user['id'];

$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    json_error('Missing reading id', 422);
}

$stmt = db()->prepare('DELETE FROM blood_glucose_records WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $userId);

if (!$stmt->execute()) {
    json_error('Could not delete the reading', 500);
}
if ($stmt->affected_rows === 0) {
    json_error('Reading not found', 404);
}

json_ok(['deleted' => $id]);
