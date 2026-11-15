<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const DIABETES_TYPES = ['type_1', 'type_2', 'prediabetes', 'caregiver'];

$input = json_input();
$fullName = trim((string) ($input['full_name'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$type = (string) ($input['diabetes_type'] ?? '');

if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
    json_error('Full name must be 2-100 characters', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
    json_error('Enter a valid email address', 422);
}
if (strlen($password) < 8) {
    json_error('Password must be at least 8 characters', 422);
}
if (!in_array($type, DIABETES_TYPES, true)) {
    json_error('Choose a valid diabetes type', 422);
}

$stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    json_error('Email is already registered', 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = db()->prepare('INSERT INTO users (full_name, email, password_hash, diabetes_type) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $fullName, $email, $hash, $type);

if (!$stmt->execute()) {
    // 1062 = duplicate key, i.e. the same email won the race.
    if (mysqli_errno(db()) === 1062) {
        json_error('Email is already registered', 409);
    }
    json_error('Could not create the account', 500);
}

json_ok(['id' => db()->insert_id], 201);
