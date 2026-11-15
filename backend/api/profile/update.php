<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';
require_once __DIR__ . '/../../helpers/upload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const DIABETES_TYPES = ['type_1', 'type_2', 'prediabetes', 'caregiver'];

$user = require_auth();
$input = json_input();

$fullName = trim((string) ($input['full_name'] ?? $user['full_name']));
$type = (string) ($input['diabetes_type'] ?? $user['diabetes_type']);
$min = filter_var($input['target_glucose_min'] ?? $user['target_glucose_min'], FILTER_VALIDATE_INT);
$max = filter_var($input['target_glucose_max'] ?? $user['target_glucose_max'], FILTER_VALIDATE_INT);

if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
    json_error('Full name must be 2-100 characters', 422);
}
if (!in_array($type, DIABETES_TYPES, true)) {
    json_error('Choose a valid diabetes type', 422);
}
if ($min === false || $max === false || $min < 20 || $max > 600 || $min >= $max) {
    json_error('Target range must be within 20-600 mg/dL with min below max', 422);
}

$avatarPath = $user['avatar_path'];
if (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $avatarPath = save_upload($_FILES['avatar'], 'avatars');
    delete_upload($user['avatar_path']);
}

$stmt = db()->prepare(
    'UPDATE users SET full_name = ?, diabetes_type = ?, target_glucose_min = ?, target_glucose_max = ?, avatar_path = ? WHERE id = ?',
);
$stmt->bind_param('ssiisi', $fullName, $type, $min, $max, $avatarPath, $user['id']);

if (!$stmt->execute()) {
    json_error('Could not update the profile', 500);
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();

json_ok(['user' => public_user($stmt->get_result()->fetch_assoc())]);
