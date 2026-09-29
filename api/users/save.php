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

$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$username = trim((string) ($input['username'] ?? ''));
$fullName = trim((string) ($input['full_name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$role = (string) ($input['role'] ?? '');
$status = (string) ($input['status'] ?? 'ACTIVE');
$password = (string) ($input['password'] ?? '');

if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username) || $fullName === '' || mb_strlen($fullName) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190 || !in_array($role, USER_ROLES, true) || !in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
    json_response(false, 'Check the user details and try again.', [], 422);
}
if (!$id && $password === '') {
    json_response(false, 'A password is required for a new user.', [], 422);
}

try {
    $pdo = db();
    if ($id) {
        if ($id === (int) $actor['id'] && ($status !== 'ACTIVE' || $role !== 'SYSTEM ADMINISTRATOR')) {
            json_response(false, 'You cannot deactivate or demote your own administrator account.', [], 422);
        }
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id, role, status FROM users WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        $existing = $lock->fetch();
        if (!$existing) {
            $pdo->rollBack();
            json_response(false, 'User not found.', [], 404);
        }
        if ($existing['role'] === 'SYSTEM ADMINISTRATOR' && $existing['status'] === 'ACTIVE' && ($role !== 'SYSTEM ADMINISTRATOR' || $status !== 'ACTIVE')) {
            $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'SYSTEM ADMINISTRATOR' AND status = 'ACTIVE'")->fetchColumn();
            if ($count <= 1) {
                $pdo->rollBack();
                json_response(false, 'At least one active system administrator must remain.', [], 422);
            }
        }
        if ($password !== '') {
            $statement = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ?, password_hash = ? WHERE id = ?');
            $statement->execute([$username, $fullName, $email, $role, $status, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $statement = $pdo->prepare('UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ? WHERE id = ?');
            $statement->execute([$username, $fullName, $email, $role, $status, $id]);
        }
        $pdo->commit();
    } else {
        $statement = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $email, $role, $status]);
    }
    json_response(true, $id ? 'User updated successfully.' : 'User created successfully.');
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('User save failed: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        json_response(false, 'That username or email address is already in use.', [], 409);
    }
    json_response(false, 'Unable to save the user right now.', [], 500);
}
