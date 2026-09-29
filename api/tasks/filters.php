<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_active_task_user();
list($scopeSql, $scopeParameters) = task_view_scope($user, 't');

try {
    $projectsStatement = db()->prepare("SELECT DISTINCT p.id, p.project_code, p.project_name
                                        FROM projects p JOIN tasks t ON t.project_id = p.id
                                        WHERE 1 = 1 {$scopeSql}
                                        ORDER BY p.project_name");
    $projectsStatement->execute($scopeParameters);
    $projects = $projectsStatement->fetchAll();

    $usersStatement = db()->prepare("SELECT DISTINCT u.id, u.full_name
                                     FROM users u JOIN tasks t ON t.assigned_to = u.id
                                     WHERE 1 = 1 {$scopeSql}
                                     ORDER BY u.full_name");
    $usersStatement->execute($scopeParameters);
    $users = $usersStatement->fetchAll();
} catch (Throwable $exception) {
    app_log('Task filter options request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load task filters right now.', [], 500);
}

json_response(true, 'Task filters loaded.', ['projects' => $projects, 'users' => $users]);
