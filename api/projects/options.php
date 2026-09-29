<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_auth();
$user = active_session_user();
if ($user === null) {
    json_response(false, 'Authentication required.', [], 401);
}

try {
    $rows = db()->query("SELECT id, username, full_name, role FROM users WHERE status = 'ACTIVE' ORDER BY CASE role WHEN 'DEVELOPER' THEN 1 WHEN 'DEVELOPMENT OFFICER' THEN 2 WHEN 'SYSTEM ADMINISTRATOR' THEN 3 ELSE 4 END, full_name")->fetchAll();
} catch (Throwable $exception) {
    app_log('Project user options request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load active users right now.', [], 500);
}
json_response(true, 'Active users loaded.', ['users' => $rows]);
