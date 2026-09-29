<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_active_task_user();
$taskId = filter_var($_GET['task_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$taskId) {
    json_response(false, 'Invalid task id.', [], 422);
}
try {
    $pdo = db();
    if (!task_is_visible_to($pdo, (int) $taskId, $user)) {
        json_response(false, 'Task not found.', [], 404);
    }
    $statement = $pdo->prepare("SELECT h.id, h.action_type, h.field_name, h.old_value, h.new_value,
                                       h.created_at, u.full_name AS changed_by_name
                                FROM task_history h JOIN users u ON u.id = h.changed_by
                                WHERE h.task_id = ? ORDER BY h.created_at DESC, h.id DESC");
    $statement->execute([(int) $taskId]);
    $history = $statement->fetchAll();
} catch (Throwable $exception) {
    app_log('Task history request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load task history right now.', [], 500);
}
json_response(true, 'Task history loaded.', ['history' => $history]);
