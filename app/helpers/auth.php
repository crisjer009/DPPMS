<?php
declare(strict_types=1);

const USER_ROLES = ['SYSTEM ADMINISTRATOR', 'DEVELOPMENT OFFICER', 'DEVELOPER', 'VIEWER'];

function current_user(): ?array
{
    start_secure_session();
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_auth(): array
{
    $user = current_user();
    if ($user === null) {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            json_response(false, 'Authentication required.', [], 401);
        }
        header('Location: login.php');
        exit;
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_auth();
    if (!in_array($user['role'], $roles, true)) {
        json_response(false, 'You are not authorized to perform this action.', [], 403);
    }
    return $user;
}

function refresh_session_user(): void
{
    $user = current_user();
    if ($user === null) {
        return;
    }
    try {
        $statement = db()->prepare('SELECT id, username, full_name, email, role, status FROM users WHERE id = ?');
        $statement->execute([$user['id']]);
        $freshUser = $statement->fetch();
    } catch (Throwable $exception) {
        app_log('Active session refresh failed: ' . $exception->getMessage());
        throw new RuntimeException('Unable to verify the active session.');
    }
    if (!$freshUser || $freshUser['status'] !== 'ACTIVE') {
        logout_user();
        return;
    }
    $_SESSION['user'] = $freshUser;
}

function logout_user(): void
{
    start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        // PHP 7.2 (bundled XAMPP) accepts the positional signature only.
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
