<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_active_task_user();

$projectId = null;
if (isset($_GET['project_id']) && $_GET['project_id'] !== '') {
    $projectId = filter_var($_GET['project_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$projectId) {
        json_response(false, 'Invalid project id.', [], 422);
    }
}
$assignedTo = null;
if (isset($_GET['assigned_to']) && $_GET['assigned_to'] !== '') {
    $assignedTo = filter_var($_GET['assigned_to'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$assignedTo) {
        json_response(false, 'Invalid assigned user filter.', [], 422);
    }
}
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$priority = is_string($_GET['priority'] ?? null) ? $_GET['priority'] : '';
if ($status !== '' && !in_array($status, TASK_STATUSES, true)) {
    json_response(false, 'Invalid task status filter.', [], 422);
}
if ($priority !== '' && !in_array($priority, TASK_PRIORITIES, true)) {
    json_response(false, 'Invalid task priority filter.', [], 422);
}

$where = ' WHERE 1 = 1';
$parameters = [];
list($scopeSql, $scopeParameters) = task_view_scope($user, 't');
$where .= $scopeSql;
$parameters = array_merge($parameters, $scopeParameters);
if ($projectId !== null) {
    $where .= ' AND t.project_id = ?';
    $parameters[] = (int) $projectId;
}
if ($assignedTo !== null) {
    $where .= ' AND t.assigned_to = ?';
    $parameters[] = (int) $assignedTo;
}
if ($status !== '') {
    $where .= ' AND t.status = ?';
    $parameters[] = $status;
}
if ($priority !== '') {
    $where .= ' AND t.priority = ?';
    $parameters[] = $priority;
}

try {
    $statement = db()->prepare("SELECT t.id, t.project_id, t.task_code, t.task_title, t.assigned_to,
                                       t.priority, t.status, t.start_date, t.target_date,
                                       t.estimated_hours, t.actual_hours, t.is_blocked,
                                       p.project_code, p.project_name,
                                       assignee.full_name AS assigned_developer
                                FROM tasks t
                                JOIN projects p ON p.id = t.project_id
                                LEFT JOIN users assignee ON assignee.id = t.assigned_to
                                {$where}
                                ORDER BY t.updated_at DESC, t.id DESC");
    $statement->execute($parameters);
    $tasks = $statement->fetchAll();

    $summary = null;
    if ($projectId !== null) {
        $summarySql = "SELECT COUNT(*) AS total,
                              COALESCE(SUM(t.status = 'BACKLOG'), 0) AS backlog,
                              COALESCE(SUM(t.status = 'TO DO'), 0) AS to_do,
                              COALESCE(SUM(t.status = 'IN PROGRESS'), 0) AS in_progress,
                              COALESCE(SUM(t.status = 'FOR TESTING'), 0) AS for_testing,
                              COALESCE(SUM(t.status = 'FOR REVISION'), 0) AS for_revision,
                              COALESCE(SUM(t.status = 'FOR DEPLOYMENT'), 0) AS for_deployment,
                              COALESCE(SUM(t.status = 'COMPLETED'), 0) AS completed,
                              COALESCE(SUM(t.status = 'CANCELLED'), 0) AS cancelled
                       FROM tasks t WHERE t.project_id = ?" . $scopeSql;
        $summaryStatement = db()->prepare($summarySql);
        $summaryStatement->execute(array_merge([(int) $projectId], $scopeParameters));
        $summary = $summaryStatement->fetch();
        $summary['progress_percent'] = (int) $summary['total'] > 0
            ? round(((int) $summary['completed'] / (int) $summary['total']) * 100, 2)
            : 0;
    }
} catch (Throwable $exception) {
    app_log('Task list request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load tasks right now.', [], 500);
}

json_response(true, 'Tasks loaded.', ['tasks' => $tasks, 'summary' => $summary]);
