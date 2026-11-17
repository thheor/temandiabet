<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT'], true)) {
    json_error('Method not allowed', 405);
}

$user = require_auth();
$input = json_input();
$userId = $user['id'];

$id = filter_var($input['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    json_error('Missing entry id', 422);
}

$stmt = db()->prepare('SELECT is_taken FROM medication_schedules WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    json_error('Entry not found', 404);
}

// An explicit is_taken wins; without one the entry flips.
$wanted = array_key_exists('is_taken', $input)
    ? (int) filter_var($input['is_taken'], FILTER_VALIDATE_BOOLEAN)
    : 1 - (int) $row['is_taken'];

$stmt = db()->prepare('UPDATE medication_schedules SET is_taken = ? WHERE id = ? AND user_id = ?');
$stmt->bind_param('iii', $wanted, $id, $userId);

if (!$stmt->execute()) {
    json_error('Could not update the entry', 500);
}

json_ok(['id' => $id, 'is_taken' => $wanted]);
