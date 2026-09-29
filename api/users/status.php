<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/bootstrap.php';
require_post_json();
$actor = require_role(['SYSTEM ADMINISTRATOR']);
refresh_session_user();
$actor = current_user();
if ($actor === null || $actor['role'] !== 'SYSTEM ADMINISTRATOR') {
    json_response(false, 'You are not authorized to perform this action.', [], 403);
}
$input = request_data();
require_valid_csrf($input['csrf_token'] ?? null);
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = (string) ($input['status'] ?? '');
if (!$id || !in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
    json_response(false, 'Invalid user status request.', [], 422);
}
if ($id === (int) $actor['id'] && $status === 'INACTIVE') {
    json_response(false, 'You cannot deactivate your own account.', [], 422);
}
try {
    $pdo = db();
    $pdo->beginTransaction();
    $lock = $pdo->prepare('SELECT role, status FROM users WHERE id = ? FOR UPDATE');
    $lock->execute([$id]);
    $target = $lock->fetch();
    if (!$target) {
        $pdo->rollBack();
        json_response(false, 'User not found.', [], 404);
    }
    if ($target['role'] === 'SYSTEM ADMINISTRATOR' && $target['status'] === 'ACTIVE' && $status === 'INACTIVE') {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'SYSTEM ADMINISTRATOR' AND status = 'ACTIVE'")->fetchColumn();
        if ($count <= 1) {
            $pdo->rollBack();
            json_response(false, 'At least one active system administrator must remain.', [], 422);
        }
    }
    $update = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
    $update->execute([$status, $id]);
    $pdo->commit();
    json_response(true, 'User status updated.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    app_log('User status update failed: ' . $exception->getMessage());
    json_response(false, 'Unable to update user status.', [], 500);
}
