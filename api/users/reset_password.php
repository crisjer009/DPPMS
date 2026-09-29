<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_post_json();
$actor = require_role(['SYSTEM ADMINISTRATOR']);
refresh_session_user();
$actor = current_user();
if ($actor === null || $actor['role'] !== 'SYSTEM ADMINISTRATOR') {
    json_response(false, 'You are not authorized to perform this action.', [], 403);
}
$input = request_data();
require_valid_csrf($input['csrf_token'] ?? null);
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$password = (string) ($input['password'] ?? '');
if (!$id || $password === '') {
    json_response(false, 'Enter a non-empty password.', [], 422);
}
$statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$statement->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
if ($statement->rowCount() === 0) {
    $exists = db()->prepare('SELECT id FROM users WHERE id = ?');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        json_response(false, 'User not found.', [], 404);
    }
}
json_response(true, 'Password reset successfully.');
