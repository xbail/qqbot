<?php
/**
 * 卡密充值 API
 * POST type=redeem&code=XXXXXX
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$user = api_auth();
if (!$user) api_err('未登录', 401);

$type = $_REQUEST['type'] ?? '';

switch ($type) {
    case 'redeem':
        $code = trim($_POST['code'] ?? '');
        if (empty($code)) api_err('请输入卡密');
        $result = cardkey_redeem($user['id'], $code);
        if ($result['ok']) {
            api_ok($result['msg'], $result);
        } else {
            api_err($result['msg']);
        }
        break;

    default:
        api_err('未知操作');
}
