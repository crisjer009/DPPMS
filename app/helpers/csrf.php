<?php
declare(strict_types=1);

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function verify_csrf(?string $token): bool
{
    start_secure_session();
    return is_string($token) && isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

function require_valid_csrf(?string $token = null): void
{
    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    if (!verify_csrf($token)) {
        json_response(false, 'Your session token is invalid or expired. Refresh the page and try again.', [], 419);
    }
}
