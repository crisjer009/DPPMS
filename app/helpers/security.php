<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('DPPMSSESSID');
    // Use the positional signature for compatibility with the PHP 7.2 runtime
    // currently bundled with this local XAMPP installation.
    session_set_cookie_params(
        0,
        '/; samesite=Lax',
        '',
        filter_var(env_value('SESSION_SECURE_COOKIE', 'false'), FILTER_VALIDATE_BOOLEAN),
        true
    );
    session_start();
}

function app_log(string $message): void
{
    error_log('[DPPMS] ' . $message);
}
