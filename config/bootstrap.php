<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

function load_environment(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($name !== '') {
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

load_environment(BASE_PATH . '/.env');
date_default_timezone_set((string) (getenv('APP_TIMEZONE') ?: 'Asia/Manila'));

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function app_config(string $key, ?string $default = null): ?string
{
    return env_value($key, $default);
}

require_once BASE_PATH . '/app/helpers/security.php';
require_once BASE_PATH . '/app/helpers/response.php';
require_once BASE_PATH . '/app/helpers/csrf.php';
require_once BASE_PATH . '/app/helpers/auth.php';
require_once BASE_PATH . '/app/helpers/projects.php';
require_once BASE_PATH . '/app/helpers/tasks.php';
require_once BASE_PATH . '/config/database.php';
