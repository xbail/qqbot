<?php
/**
 * 管理员用户管理 API（SQLite 版）
 * 
 * GET        — 用户列表
 * POST type  — create / reset_password / delete
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$user = api_auth();
if (!$user) api_err('未登录', 401);
if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);

// GET: 用户列表
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $list = users_list();
    api_ok('ok', ['list' => $list]);
    exit;
}

$type = $_POST['type'] ?? '';

switch ($type) {
    case 'create':
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';

        if (empty($username) || empty($password)) api_err('请填写完整信息');
        if (!in_array($role, ['admin', 'user'])) $role = 'user';

        $result = users_create($username, $password, $role);
        if ($result['ok']) {
            api_ok('创建成功');
        } else {
            api_err($result['msg']);
        }
        break;

    case 'reset_password':
        $targetUserId = intval($_POST['user_id'] ?? 0);
        $newPassword = trim($_POST['new_password'] ?? '');

        if (!$targetUserId || empty($newPassword)) api_err('参数不完整');
        if (strlen($newPassword) < 6) api_err('密码至少需要6个字符');

        $result = users_change_password($targetUserId, $newPassword);
        if ($result['ok']) {
            api_ok('密码已重置');
        } else {
            api_err($result['msg']);
        }
        break;

    case 'delete':
        $targetUserId = intval($_POST['user_id'] ?? 0);
        if (!$targetUserId) api_err('参数不完整');
        if ($targetUserId == ($user['id'] ?? 0)) api_err('不能删除自己的账号');

        $result = users_delete($targetUserId);
        if ($result['ok']) {
            api_ok('删除成功');
        } else {
            api_err($result['msg']);
        }
        break;

    default:
        api_err('未知操作');
}
