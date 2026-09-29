<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_active_task_user();
$taskId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$taskId) {
    json_response(false, 'Invalid task id.', [], 422);
}

try {
    $pdo = db();
    if (!task_is_visible_to($pdo, (int) $taskId, $user)) {
        json_response(false, 'Task not found.', [], 404);
    }
    $statement = $pdo->prepare("SELECT t.*, p.project_code, p.project_name,
                                       assignee.full_name AS assigned_developer,
                                       creator.full_name AS created_by_name
                                FROM tasks t
                                JOIN projects p ON p.id = t.project_id
                                LEFT JOIN users assignee ON assignee.id = t.assigned_to
                                JOIN users creator ON creator.id = t.created_by
                                WHERE t.id = ?");
    $statement->execute([(int) $taskId]);
    $task = $statement->fetch();
} catch (Throwable $exception) {
    app_log('Task detail request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load task details right now.', [], 500);
}
if (!$task) {
    json_response(false, 'Task not found.', [], 404);
}
json_response(true, 'Task loaded.', ['task' => $task]);
