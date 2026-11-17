<?php
include_once __DIR__ . '/../../config/cors.php';
include_once __DIR__ . '/../../config/db.php';
include_once __DIR__ . '/../../helpers/auth.php';
include_once __DIR__ . '/../../helpers/response.php';
include_once __DIR__ . '/../../helpers/request.php';
include_once __DIR__ . '/../../helpers/upload.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'DELETE'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = (int) $user['id'];

$id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
if ($id < 1) {
    json_error('Missing meal id', 422);
}

$result = $conn->query("SELECT photo_path FROM food_logs WHERE id = $id AND user_id = $userId");
$meal = $result->fetch_assoc();

if (!$meal) {
    json_error('Meal not found', 404);
}

$ok = $conn->query("DELETE FROM food_logs WHERE id = $id AND user_id = $userId");

if (!$ok) {
    json_error('Could not delete the meal', 500);
}

delete_upload($meal['photo_path']);

json_ok(['deleted' => $id]);
