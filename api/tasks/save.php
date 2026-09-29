<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_post_json();
$actor = require_task_manager();
$input = request_data();
require_valid_csrf($input['csrf_token'] ?? null);

$rawId = $input['id'] ?? '';
$taskId = null;
if ($rawId !== '' && $rawId !== null) {
    $taskId = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$taskId) {
        json_response(false, 'Invalid task id.', [], 422);
    }
}
$projectId = null;
if ($taskId === null) {
    $projectId = filter_var($input['project_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$projectId) {
        json_response(false, 'A valid project is required to create a task.', [], 422);
    }
}
$title = is_string($input['task_title'] ?? null) ? trim($input['task_title']) : '';
$description = is_string($input['description'] ?? null) ? trim($input['description']) : '';
$priority = is_string($input['priority'] ?? null) ? trim($input['priority']) : '';
$status = is_string($input['status'] ?? null) ? trim($input['status']) : '';
$assignedRaw = $input['assigned_to'] ?? '';
$assignedTo = null;
$errors = [];
if ($assignedRaw !== '' && $assignedRaw !== null) {
    $assignedTo = (is_string($assignedRaw) || is_int($assignedRaw))
        ? filter_var($assignedRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($assignedTo === false) {
        $errors[] = 'Select a valid project member or leave the task unassigned.';
        $assignedTo = null;
    } else {
        $assignedTo = (int) $assignedTo;
    }
}
if ($title === '' || mb_strlen($title) > 200) {
    $errors[] = 'Task Title is required and must be 200 characters or fewer.';
}
if ($description !== '' && mb_strlen($description) > 12000) {
    $errors[] = 'Description must be 12,000 characters or fewer.';
}
if (!in_array($priority, TASK_PRIORITIES, true)) {
    $errors[] = 'Select a valid priority.';
}
if (!in_array($status, TASK_STATUSES, true)) {
    $errors[] = 'Select a valid task status.';
}
$startDate = validate_project_date($input['start_date'] ?? null, 'Start Date', $errors);
$targetDate = validate_project_date($input['target_date'] ?? null, 'Target Date', $errors);
if ($startDate !== null && $targetDate !== null && $targetDate < $startDate) {
    $errors[] = 'Target Date cannot be earlier than Start Date.';
}
$estimatedHours = validate_task_hours($input['estimated_hours'] ?? null, 'Estimated Hours', $errors);
$actualHours = validate_task_hours($input['actual_hours'] ?? null, 'Actual Hours', $errors);
$blockedValue = $input['is_blocked'] ?? '0';
if ($blockedValue === true || $blockedValue === 1 || $blockedValue === '1') {
    $isBlocked = 1;
} elseif ($blockedValue === false || $blockedValue === 0 || $blockedValue === '0' || $blockedValue === '' || $blockedValue === null) {
    $isBlocked = 0;
} else {
    $isBlocked = 0;
    $errors[] = 'Blocked status is invalid.';
}
$blockedReason = is_string($input['blocked_reason'] ?? null) ? trim($input['blocked_reason']) : '';
if (preg_match('/(?:password|passwd|token|secret|api[_ -]?key)\\s*[:=]/i', $blockedReason)) {
    $errors[] = 'Blocked Reason must not contain passwords, tokens, secrets, or API keys.';
}
if ($isBlocked && $blockedReason === '') {
    $errors[] = 'Blocked Reason is required when the task is blocked.';
}
if (mb_strlen($blockedReason) > 5000) {
    $errors[] = 'Blocked Reason must be 5,000 characters or fewer.';
}
if (!$isBlocked) {
    $blockedReason = '';
}
if ($errors) {
    json_response(false, implode(' ', $errors), [], 422);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $existing = null;
    if ($taskId !== null) {
        $taskStatement = $pdo->prepare('SELECT * FROM tasks WHERE id = ? FOR UPDATE');
        $taskStatement->execute([(int) $taskId]);
        $existing = $taskStatement->fetch();
        if (!$existing) {
            $pdo->rollBack();
            json_response(false, 'Task not found.', [], 404);
        }
        $projectId = (int) $existing['project_id'];
    }

    $projectStatement = $pdo->prepare('SELECT id, project_code FROM projects WHERE id = ? FOR UPDATE');
    $projectStatement->execute([(int) $projectId]);
    $project = $projectStatement->fetch();
    if (!$project) {
        $pdo->rollBack();
        json_response(false, 'Project not found.', [], 404);
    }

    if ($assignedTo !== null) {
        $membership = $pdo->prepare('SELECT user_id FROM project_members WHERE project_id = ? AND user_id = ? FOR UPDATE');
        $membership->execute([(int) $projectId, $assignedTo]);
        if (!$membership->fetchColumn()) {
            $pdo->rollBack();
            json_response(false, 'Assigned Developer must be a member of this project.', [], 422);
        }
    }

    if ($existing === null) {
        $taskCode = generate_task_code($pdo, $project['project_code']);
        $statement = $pdo->prepare("INSERT INTO tasks (project_id, task_code, task_title, description, assigned_to, priority, status, start_date, target_date, completed_at, estimated_hours, actual_hours, is_blocked, blocked_reason, created_by)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'COMPLETED' THEN CURRENT_TIMESTAMP ELSE NULL END, ?, ?, ?, ?, ?)");
        $statement->execute([(int) $projectId, $taskCode, $title, $description !== '' ? $description : null, $assignedTo, $priority, $status, $startDate, $targetDate, $status, $estimatedHours, $actualHours, $isBlocked, $isBlocked ? $blockedReason : null, (int) $actor['id']]);
        $newTaskId = (int) $pdo->lastInsertId();
        insert_task_history($pdo, $newTaskId, (int) $actor['id'], 'TASK_CREATED', 'task_title', null, $title);
        if ($isBlocked) {
            insert_task_history($pdo, $newTaskId, (int) $actor['id'], 'BLOCKED', 'blocked_reason', null, $blockedReason);
        }
        if ($status === 'COMPLETED') {
            insert_task_history($pdo, $newTaskId, (int) $actor['id'], 'TASK_COMPLETED', 'status', null, 'COMPLETED');
        }
        $pdo->commit();
        json_response(true, 'Task created successfully.', ['id' => $newTaskId, 'task_code' => $taskCode]);
    }

    $oldStatus = $existing['status'];
    $oldAssignedTo = $existing['assigned_to'] === null ? null : (int) $existing['assigned_to'];
    $oldPriority = $existing['priority'];
    $oldTargetDate = $existing['target_date'];
    $oldBlocked = (int) $existing['is_blocked'];
    $oldBlockedReason = $existing['blocked_reason'];

    $update = $pdo->prepare("UPDATE tasks
                             SET task_title = ?, description = ?, assigned_to = ?, priority = ?, status = ?,
                                 start_date = ?, target_date = ?,
                                 completed_at = CASE WHEN ? = 'COMPLETED' THEN COALESCE(completed_at, CURRENT_TIMESTAMP) WHEN ? <> 'COMPLETED' THEN NULL ELSE completed_at END,
                                 estimated_hours = ?, actual_hours = ?, is_blocked = ?, blocked_reason = ?
                             WHERE id = ?");
    $update->execute([$title, $description !== '' ? $description : null, $assignedTo, $priority, $status, $startDate, $targetDate, $status, $status, $estimatedHours, $actualHours, $isBlocked, $isBlocked ? $blockedReason : null, (int) $taskId]);

    if ($oldStatus !== $status) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'STATUS_CHANGED', 'status', $oldStatus, $status);
        if ($status === 'COMPLETED') {
            insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'TASK_COMPLETED', 'status', $oldStatus, 'COMPLETED');
        } elseif ($oldStatus === 'COMPLETED') {
            insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'TASK_REOPENED', 'status', 'COMPLETED', $status);
        }
    }
    if ($oldAssignedTo !== $assignedTo) {
        $oldAssigneeName = null;
        $newAssigneeName = null;
        if ($oldAssignedTo !== null) {
            $person = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
            $person->execute([$oldAssignedTo]);
            $oldAssigneeName = $person->fetchColumn() ?: null;
        }
        if ($assignedTo !== null) {
            $person = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
            $person->execute([$assignedTo]);
            $newAssigneeName = $person->fetchColumn() ?: null;
        }
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'ASSIGNEE_CHANGED', 'assigned_to', $oldAssigneeName, $newAssigneeName);
    }
    if ($oldPriority !== $priority) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'PRIORITY_CHANGED', 'priority', $oldPriority, $priority);
    }
    if ($oldTargetDate !== $targetDate) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'TARGET_DATE_CHANGED', 'target_date', $oldTargetDate, $targetDate);
    }
    if ($oldBlocked === 0 && $isBlocked === 1) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'BLOCKED', 'blocked_reason', null, $blockedReason);
    } elseif ($oldBlocked === 1 && $isBlocked === 0) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'UNBLOCKED', 'blocked_reason', $oldBlockedReason, null);
    } elseif ($oldBlocked === 1 && $isBlocked === 1 && $oldBlockedReason !== $blockedReason) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'BLOCK_REASON_CHANGED', 'blocked_reason', $oldBlockedReason, $blockedReason);
    }
    if ($existing['task_title'] !== $title) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'TASK_TITLE_CHANGED', 'task_title', $existing['task_title'], $title);
    }
    if ($existing['start_date'] !== $startDate) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'FIELD_CHANGED', 'start_date', $existing['start_date'], $startDate);
    }
    if (($existing['estimated_hours'] === null ? null : number_format((float) $existing['estimated_hours'], 2, '.', '')) !== $estimatedHours) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'FIELD_CHANGED', 'estimated_hours', $existing['estimated_hours'], $estimatedHours);
    }
    if (($existing['actual_hours'] === null ? null : number_format((float) $existing['actual_hours'], 2, '.', '')) !== $actualHours) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'FIELD_CHANGED', 'actual_hours', $existing['actual_hours'], $actualHours);
    }
    if ($existing['description'] !== ($description !== '' ? $description : null)) {
        insert_task_history($pdo, (int) $taskId, (int) $actor['id'], 'DESCRIPTION_UPDATED', 'description');
    }

    $pdo->commit();
    json_response(true, 'Task updated successfully.', ['id' => (int) $taskId]);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('Task save failed: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        json_response(false, 'Task Code must be unique and the assignee must be a project member.', [], 409);
    }
    json_response(false, 'Unable to save the task right now.', [], 500);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('Task save failed: ' . $exception->getMessage());
    json_response(false, 'Unable to save the task right now.', [], 500);
}
