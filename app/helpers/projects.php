<?php
declare(strict_types=1);

const PROJECT_STATUSES = ['PLANNING', 'ACTIVE', 'ON HOLD', 'FOR TESTING', 'FOR DEPLOYMENT', 'COMPLETED', 'CANCELLED'];
const PROJECT_PRIORITIES = ['LOW', 'NORMAL', 'HIGH', 'URGENT'];
const PROJECT_MANAGER_ROLES = ['SYSTEM ADMINISTRATOR', 'DEVELOPMENT OFFICER'];

function active_session_user(): ?array
{
    refresh_session_user();
    return current_user();
}

function require_project_manager(): array
{
    $user = require_role(PROJECT_MANAGER_ROLES);
    $user = active_session_user();
    if ($user === null || !in_array($user['role'], PROJECT_MANAGER_ROLES, true)) {
        json_response(false, 'You are not authorized to manage projects.', [], 403);
    }
    return $user;
}

function validate_project_date($value, string $label, array &$errors): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_string($value)) {
        $errors[] = $label . ' is invalid.';
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    $dateErrors = DateTime::getLastErrors();
    if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $date->format('Y-m-d') !== $value) {
        $errors[] = $label . ' must be a valid date.';
        return null;
    }
    return $value;
}

function validate_project_member_ids($value, array &$errors): array
{
    if ($value === null || $value === '') {
        return [];
    }
    if (!is_array($value)) {
        $errors[] = 'Assigned developers are invalid.';
        return [];
    }
    $ids = [];
    foreach ($value as $candidate) {
        $id = filter_var($candidate, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            $errors[] = 'Assigned developers must be valid active users.';
            return [];
        }
        $ids[] = (int) $id;
    }
    return array_values(array_unique($ids));
}

function validate_active_project_users(PDO $pdo, int $ownerId, array $memberIds): bool
{
    $ids = array_values(array_unique(array_merge([$ownerId], $memberIds)));
    if (!$ids) {
        return false;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("SELECT id FROM users WHERE status = 'ACTIVE' AND id IN ({$placeholders}) ORDER BY id FOR UPDATE");
    $statement->execute($ids);
    return count($statement->fetchAll(PDO::FETCH_COLUMN)) === count($ids);
}

function sync_project_members(PDO $pdo, int $projectId, array $memberIds): void
{
    $statement = $pdo->prepare('SELECT user_id FROM project_members WHERE project_id = ?');
    $statement->execute([$projectId]);
    $existingIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    $removeIds = array_values(array_diff($existingIds, $memberIds));
    $addIds = array_values(array_diff($memberIds, $existingIds));

    if ($removeIds) {
        $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
        $delete = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id IN ({$placeholders})");
        $delete->execute(array_merge([$projectId], $removeIds));
    }

    if ($addIds) {
        $insert = $pdo->prepare('INSERT INTO project_members (project_id, user_id) VALUES (?, ?)');
        foreach ($addIds as $userId) {
            $insert->execute([$projectId, $userId]);
        }
    }
}
