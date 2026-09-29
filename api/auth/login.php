<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_post_json();
$input = request_data();
require_valid_csrf($input['csrf_token'] ?? null);

$username = trim((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
if ($username === '' || $password === '' || mb_strlen($username) > 80) {
    json_response(false, 'Enter your username and password.', [], 422);
}

start_secure_session();
$now = time();
$attempts = $_SESSION['_login_attempts'] ?? [];
$attempts = array_values(array_filter($attempts, static function ($attempt) use ($now): bool {
    return is_int($attempt) && $attempt > $now - 900;
}));
if (count($attempts) >= 10) {
    json_response(false, 'Too many sign-in attempts. Wait 15 minutes and try again.', [], 429);
}
$_SESSION['_login_attempts'] = $attempts;

try {
    $statement = db()->prepare('SELECT id, username, password_hash, full_name, email, role, status FROM users WHERE username = ? LIMIT 1');
    $statement->execute([$username]);
    $user = $statement->fetch();
} catch (Throwable $exception) {
    app_log('Login unavailable because the user lookup failed: ' . $exception->getMessage());
    json_response(false, 'Sign-in is temporarily unavailable. Check the DPPMS database configuration and try again.', [], 503);
}
if (!$user || $user['status'] !== 'ACTIVE' || !password_verify($password, $user['password_hash'])) {
    $attempts[] = $now;
    $_SESSION['_login_attempts'] = $attempts;
    app_log('Authentication failure for username ' . preg_replace('/[^A-Za-z0-9._-]/', '?', $username));
    json_response(false, 'The username or password is incorrect.', [], 401);
}

session_regenerate_id(true);
unset($_SESSION['_login_attempts']);
try {
    $update = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $update->execute([$user['id']]);
} catch (Throwable $exception) {
    app_log('Login unavailable because last-login update failed: ' . $exception->getMessage());
    json_response(false, 'Sign-in is temporarily unavailable. Check the DPPMS database configuration and try again.', [], 503);
}
unset($user['password_hash']);
$user['last_login_at'] = date('Y-m-d H:i:s');
$_SESSION['user'] = $user;
$_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
json_response(true, 'Signed in successfully.', ['redirect' => 'dashboard.php']);
