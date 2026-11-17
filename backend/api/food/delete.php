<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';
require_once __DIR__ . '/../../helpers/upload.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'DELETE'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = $user['id'];

$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    json_error('Missing meal id', 422);
}

$stmt = db()->prepare('SELECT photo_path FROM food_logs WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$meal = $stmt->get_result()->fetch_assoc();

if (!$meal) {
    json_error('Meal not found', 404);
}

$stmt = db()->prepare('DELETE FROM food_logs WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $userId);

if (!$stmt->execute()) {
    json_error('Could not delete the meal', 500);
}

// Row first, file second: a leftover file is harmless, a dangling path is not.
delete_upload($meal['photo_path']);

json_ok(['deleted' => $id]);
