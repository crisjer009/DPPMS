<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$actor = require_task_manager();
$projectId = filter_var($_GET['project_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$projectId) {
    json_response(false, 'A valid project is required.', [], 422);
}

try {
    $project = db()->prepare('SELECT id FROM projects WHERE id = ?');
    $project->execute([$projectId]);
    if (!$project->fetch()) {
        json_response(false, 'Project not found.', [], 404);
    }
    $statement = db()->prepare("SELECT u.id, u.full_name, u.role, u.status
                                FROM project_members pm JOIN users u ON u.id = pm.user_id
                                WHERE pm.project_id = ? AND u.status = 'ACTIVE'
                                ORDER BY CASE u.role WHEN 'DEVELOPER' THEN 1 WHEN 'DEVELOPMENT OFFICER' THEN 2 WHEN 'SYSTEM ADMINISTRATOR' THEN 3 ELSE 4 END, u.full_name");
    $statement->execute([(int) $projectId]);
    $members = $statement->fetchAll();
} catch (Throwable $exception) {
    app_log('Task assignment options request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load project members right now.', [], 500);
}
json_response(true, 'Project members loaded.', ['members' => $members]);
