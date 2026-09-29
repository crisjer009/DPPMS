<?php
declare(strict_types=1);

const TASK_STATUSES = ['BACKLOG', 'TO DO', 'IN PROGRESS', 'FOR TESTING', 'FOR REVISION', 'FOR DEPLOYMENT', 'COMPLETED'];
const TASK_PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT'];
const TASK_MANAGER_ROLES = ['SYSTEM ADMINISTRATOR', 'DEVELOPMENT OFFICER'];

function require_task_manager(): array
{
    require_role(TASK_MANAGER_ROLES);
    try {
        $user = active_session_user();
    } catch (Throwable $exception) {
        app_log('Task manager authorization check failed: ' . $exception->getMessage());
        json_response(false, 'Unable to verify your access right now.', [], 503);
    }
    if ($user === null || !in_array($user['role'], TASK_MANAGER_ROLES, true)) {
        json_response(false, 'You are not authorized to manage tasks.', [], 403);
    }
    return $user;
}

function require_active_task_user(): array
{
    require_auth();
    try {
        $user = active_session_user();
    } catch (Throwable $exception) {
        app_log('Task user authentication check failed: ' . $exception->getMessage());
        json_response(false, 'Unable to verify your session right now.', [], 503);
    }
    if ($user === null) {
        json_response(false, 'Authentication required.', [], 401);
    }
    return $user;
}

function task_is_visible_to(PDO $pdo, int $taskId, array $user): bool
{
    if (in_array($user['role'], ['SYSTEM ADMINISTRATOR', 'DEVELOPMENT OFFICER', 'VIEWER'], true)) {
        $statement = $pdo->prepare('SELECT id FROM tasks WHERE id = ?');
        $statement->execute([$taskId]);
        return (bool) $statement->fetchColumn();
    }

    $statement = $pdo->prepare('SELECT t.id FROM tasks t WHERE t.id = ? AND (t.assigned_to = ? OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = t.project_id AND pm.user_id = ?))');
    $statement->execute([$taskId, $user['id'], $user['id']]);
    return (bool) $statement->fetchColumn();
}

function task_view_scope(array $user, string $taskAlias = 't'): array
{
    if ($user['role'] !== 'DEVELOPER') {
        return ['', []];
    }
    return [
        " AND ({$taskAlias}.assigned_to = ? OR EXISTS (SELECT 1 FROM project_members task_view_pm WHERE task_view_pm.project_id = {$taskAlias}.project_id AND task_view_pm.user_id = ?))",
        [(int) $user['id'], (int) $user['id']],
    ];
}

function generate_task_code(PDO $pdo, string $projectCode): string
{
    // The caller locks the project row in its create transaction. That serializes
    // task-code allocation for concurrent task creates in the same project.
    $prefix = $projectCode . '-';
    $statement = $pdo->prepare('SELECT task_code FROM tasks WHERE LEFT(task_code, CHAR_LENGTH(?)) = ?');
    $statement->execute([$prefix, $prefix]);
    $maxSequence = 0;
    $pattern = '/^' . preg_quote($projectCode, '/') . '-([0-9]+)$/i';
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $existingCode) {
        if (preg_match($pattern, (string) $existingCode, $matches)) {
            $maxSequence = max($maxSequence, (int) $matches[1]);
        }
    }
    return $prefix . str_pad((string) ($maxSequence + 1), 3, '0', STR_PAD_LEFT);
}

function insert_task_history(PDO $pdo, int $taskId, int $changedBy, string $actionType, ?string $fieldName = null, ?string $oldValue = null, ?string $newValue = null): void
{
    $statement = $pdo->prepare('INSERT INTO task_history (task_id, action_type, field_name, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$taskId, $actionType, $fieldName, $oldValue, $newValue, $changedBy]);
}

function validate_task_hours($value, string $label, array &$errors): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        $errors[] = $label . ' must be a non-negative number with at most two decimal places.';
        return null;
    }
    $value = (string) $value;
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/', $value)) {
        $errors[] = $label . ' must be a non-negative number with at most two decimal places.';
        return null;
    }
    return number_format((float) $value, 2, '.', '');
}
