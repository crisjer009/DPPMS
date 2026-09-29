<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
$user = require_auth();
$user = active_session_user();
if ($user === null) {
    json_response(false, 'Authentication required.', [], 401);
}

$sql = "SELECT p.id, p.project_code, p.project_name, p.department_requestor, p.priority,
               p.start_date, p.target_date, p.status, p.project_owner_id,
               owner.full_name AS project_owner,
               (SELECT GROUP_CONCAT(member.full_name ORDER BY member.full_name SEPARATOR ', ')
                  FROM project_members pm JOIN users member ON member.id = pm.user_id
                 WHERE pm.project_id = p.id) AS assigned_developers
        FROM projects p
        JOIN users owner ON owner.id = p.project_owner_id
        ORDER BY p.updated_at DESC, p.project_name ASC";
try {
    $rows = db()->query($sql)->fetchAll();
} catch (Throwable $exception) {
    app_log('Project list request failed: ' . $exception->getMessage());
    json_response(false, 'Unable to load projects right now.', [], 500);
}
json_response(true, 'Projects loaded.', ['projects' => $rows]);
