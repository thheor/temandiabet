<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/cors.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/request.php';
require_once __DIR__ . '/../../helpers/dates.php';
require_once __DIR__ . '/../../helpers/upload.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_error('Method not allowed', 405);
}

const MEAL_TYPES = ['breakfast', 'lunch', 'dinner', 'snack'];

$user = require_auth();
$input = json_input(); // multipart bodies fall through to $_POST
$userId = $user['id'];

$name = trim((string) ($input['food_name'] ?? ''));
$mealType = (string) ($input['meal_type'] ?? '');
$calories = filter_var($input['estimated_calories'] ?? 0, FILTER_VALIDATE_INT);
$carbs = filter_var($input['carbohydrates_grams'] ?? 0, FILTER_VALIDATE_FLOAT);
$rawDate = (string) ($input['eaten_date'] ?? '');
$date = $rawDate === '' ? date('Y-m-d') : parse_date($rawDate);

if (mb_strlen($name) < 1 || mb_strlen($name) > 150) {
    json_error('Food name must be 1-150 characters', 422);
}
if (!in_array($mealType, MEAL_TYPES, true)) {
    json_error('Choose a meal type', 422);
}
if ($calories === false || $calories < 0 || $calories > 10000) {
    json_error('Calories must be a whole number between 0 and 10000', 422);
}
if ($carbs === false || $carbs < 0 || $carbs > 1000) {
    json_error('Carbohydrates must be between 0 and 1000 g', 422);
}
if ($date === null) {
    json_error('Use eaten_date as YYYY-MM-DD', 422);
}

// Everything is validated before the file is stored, so a bad form never
// leaves an orphaned image behind.
$photoPath = null;
if (($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $photoPath = save_upload($_FILES['photo'], 'foods');
}

$stmt = db()->prepare(
    'INSERT INTO food_logs (user_id, food_name, meal_type, estimated_calories, carbohydrates_grams, photo_path, eaten_date)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
);
$stmt->bind_param('issidss', $userId, $name, $mealType, $calories, $carbs, $photoPath, $date);

if (!$stmt->execute()) {
    delete_upload($photoPath);
    json_error('Could not save the meal', 500);
}

json_ok([
    'meal' => [
        'id' => db()->insert_id,
        'user_id' => $userId,
        'food_name' => $name,
        'meal_type' => $mealType,
        'estimated_calories' => $calories,
        'carbohydrates_grams' => round((float) $carbs, 2),
        'photo_path' => $photoPath,
        'eaten_date' => $date,
    ],
], 201);
