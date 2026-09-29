<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_auth();
$user = active_session_user();
if ($user === null) {
    json_response(false, 'Authentication required.', [], 401);
}
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) {
    json_response(false, 'Invalid project id.', [], 422);
}

try {
    $statement = db()->prepare("SELECT p.*, owner.full_name AS project_owner, owner.username AS project_owner_username,
                                       creator.full_name AS created_by_name
                                FROM projects p
                                JOIN users owner ON owner.id = p.project_owner_id
                                JOIN users creator ON creator.id = p.created_by
                                WHERE p.id = ?");
    $statement->execute([$id]);
    $project = $statement->fetch();
} catch (Throwable $exception) {
    app_log('Project detail request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load project details right now.', [], 500);
}
if (!$project) {
    json_response(false, 'Project not found.', [], 404);
}
try {
    $members = db()->prepare("SELECT u.id, u.username, u.full_name, u.role
                              FROM project_members pm JOIN users u ON u.id = pm.user_id
                              WHERE pm.project_id = ? ORDER BY u.full_name");
    $members->execute([$id]);
    $project['members'] = $members->fetchAll();
} catch (Throwable $exception) {
    app_log('Project member detail request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load project details right now.', [], 500);
}
json_response(true, 'Project loaded.', ['project' => $project]);
