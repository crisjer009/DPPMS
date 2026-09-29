<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_post_json();
$actor = require_project_manager();
$input = request_data();
require_valid_csrf($input['csrf_token'] ?? null);

$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$code = is_string($input['project_code'] ?? null) ? trim($input['project_code']) : '';
$name = is_string($input['project_name'] ?? null) ? trim($input['project_name']) : '';
$description = is_string($input['description'] ?? null) ? trim($input['description']) : '';
$department = is_string($input['department_requestor'] ?? null) ? trim($input['department_requestor']) : '';
$ownerValue = $input['project_owner_id'] ?? null;
$ownerId = is_string($ownerValue) || is_int($ownerValue) ? filter_var($ownerValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$priority = is_string($input['priority'] ?? null) ? trim($input['priority']) : '';
$status = is_string($input['status'] ?? null) ? trim($input['status']) : '';
$errors = [];

if ($code === '' || mb_strlen($code) > 40) {
    $errors[] = 'Project Code is required and must be 40 characters or fewer.';
}
if ($name === '' || mb_strlen($name) > 200) {
    $errors[] = 'Project Name is required and must be 200 characters or fewer.';
}
if ($description !== '' && mb_strlen($description) > 12000) {
    $errors[] = 'Description must be 12,000 characters or fewer.';
}
if ($department !== '' && mb_strlen($department) > 190) {
    $errors[] = 'Department / Requestor must be 190 characters or fewer.';
}
if ($ownerId === false) {
    $errors[] = 'Select an active project owner.';
    $ownerId = 0;
}
if (!in_array($priority, PROJECT_PRIORITIES, true)) {
    $errors[] = 'Select a valid priority.';
}
if (!in_array($status, PROJECT_STATUSES, true)) {
    $errors[] = 'Select a valid project status.';
}
$startDate = validate_project_date($input['start_date'] ?? null, 'Start Date', $errors);
$targetDate = validate_project_date($input['target_date'] ?? null, 'Target Date', $errors);
if ($startDate !== null && $targetDate !== null && $targetDate < $startDate) {
    $errors[] = 'Target Date cannot be earlier than Start Date.';
}
$memberIds = validate_project_member_ids($input['member_ids'] ?? [], $errors);
if ($errors) {
    json_response(false, implode(' ', $errors), [], 422);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    if (!validate_active_project_users($pdo, (int) $ownerId, $memberIds)) {
        $pdo->rollBack();
        json_response(false, 'Project owner and assigned developers must be active users.', [], 422);
    }

    if ($id === null) {
        $statement = $pdo->prepare('INSERT INTO projects (project_code, project_name, description, project_owner_id, department_requestor, priority, start_date, target_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$code, $name, $description !== '' ? $description : null, (int) $ownerId, $department !== '' ? $department : null, $priority, $startDate, $targetDate, $status, (int) $actor['id']]);
        $projectId = (int) $pdo->lastInsertId();
        sync_project_members($pdo, $projectId, $memberIds);
        $pdo->commit();
        json_response(true, 'Project created successfully.', ['id' => $projectId]);
    }

    $exists = $pdo->prepare('SELECT id FROM projects WHERE id = ? FOR UPDATE');
    $exists->execute([$id]);
    if (!$exists->fetch()) {
        $pdo->rollBack();
        json_response(false, 'Project not found.', [], 404);
    }
    $statement = $pdo->prepare('UPDATE projects SET project_code = ?, project_name = ?, description = ?, project_owner_id = ?, department_requestor = ?, priority = ?, start_date = ?, target_date = ?, status = ? WHERE id = ?');
    $statement->execute([$code, $name, $description !== '' ? $description : null, (int) $ownerId, $department !== '' ? $department : null, $priority, $startDate, $targetDate, $status, $id]);
    sync_project_members($pdo, (int) $id, $memberIds);
    $pdo->commit();
    json_response(true, 'Project updated successfully.', ['id' => (int) $id]);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('Project save failed: ' . $exception->getMessage());
    if ($exception->getCode() === '23000') {
        json_response(false, 'Project Code must be unique, and all assigned users must exist.', [], 409);
    }
    json_response(false, 'Unable to save the project right now.', [], 500);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('Project save failed: ' . $exception->getMessage());
    json_response(false, 'Unable to save the project right now.', [], 500);
}
