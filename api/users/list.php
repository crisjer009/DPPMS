<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_auth();
refresh_session_user();
$user = current_user();
if ($user === null) {
    json_response(false, 'Authentication required.', [], 401);
}
if ($user['role'] === 'SYSTEM ADMINISTRATOR') {
    $rows = db()->query('SELECT id, username, full_name, email, role, status, last_login_at, created_at FROM users ORDER BY full_name, id')->fetchAll();
} else {
    $statement = db()->prepare('SELECT id, username, full_name, email, role, status, last_login_at, created_at FROM users WHERE id = ?');
    $statement->execute([$user['id']]);
    $rows = $statement->fetchAll();
}
json_response(true, 'Users loaded.', ['users' => $rows]);
