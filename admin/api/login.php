<?php
/**
 * 登录/注册 API
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$type = $_POST["type"] ?? "";
$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

switch ($type) {
    case "login":
        if (empty($username) || empty($password)) {
            api_err("请输入账号和密码");
        }
        $result = users_authenticate($username, $password);
        if ($result['ok']) {
            $token = users_create_token($result['user_id'], $result['username']);
            setcookie('admin_token', $token, time() + 30 * 86400, '/');
            api_ok("登录成功", [
                'token'    => $token,
                'username' => $result['username'],
                'role'     => $result['role'],
            ]);
        }
        api_err($result['msg'] ?? '账号或密码错误');
        break;

    case "register":
        if (empty($username) || empty($password)) {
            api_err("请输入账号和密码");
        }
        $result = users_create($username, $password, 'user');
        if ($result['ok']) {
            $token = users_create_token($result['user_id'], $username);
            setcookie('admin_token', $token, time() + 30 * 86400, '/');
            api_ok("注册成功", [
                'token'    => $token,
                'username' => $username,
                'role'     => 'user',
            ]);
        } else {
            api_err($result['msg']);
        }
        break;

    default:
        api_err("未知操作类型", 400);
}
