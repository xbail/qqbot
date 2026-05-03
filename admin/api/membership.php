<?php
/**
 * 会员/计费 API
 * 
 * info   — 获取当前用户余额和会员状态
 * purchase — 用余额续费（月数）
 * admin_adjust — 管理员调整余额
 * admin_set_expires — 管理员设置到期时间
 * list   — 管理员查看所有用户
 * orders — 用户订单记录
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$type = $_REQUEST['type'] ?? '';

switch ($type) {

    // ======================== 公开接口 ========================

    // 获取价格信息（无需登录）
    case 'price':
        $price = users_get_price_per_bot();
        api_ok('ok', ['price_per_bot' => $price]);
        exit;

    // 获取当前用户会员信息
    case 'info':
        $auth = api_auth();
        if (!$auth) api_err('未登录', 401);
        $info = users_get_membership($auth);
        api_ok('ok', ['membership' => $info]);
        exit;

    // 用余额续费
    case 'purchase':
        $auth = api_auth();
        if (!$auth) api_err('未登录', 401);
        $months = intval($_REQUEST['months'] ?? 0);
        if ($months <= 0) api_err('请选择续费月数');
        $result = membership_purchase($auth['id'], $months);
        if ($result['ok']) {
            api_ok($result['msg'], $result);
        } else {
            api_err($result['msg']);
        }
        exit;

    // 用户订单记录
    case 'orders':
        $auth = api_auth();
        if (!$auth) api_err('未登录', 401);
        $orders = orders_list($auth['id']);
        api_ok('ok', ['orders' => $orders]);
        exit;

    // ======================== 管理员接口 ========================

    // 管理员调整余额
    case 'admin_adjust':
        $auth = api_auth();
        if (!$auth || ($auth['role'] ?? '') !== 'admin') api_err('无权限', 403);
        $targetUserId = intval($_REQUEST['user_id'] ?? 0);
        $amount = floatval($_REQUEST['amount'] ?? 0);
        $reason = trim($_REQUEST['reason'] ?? '');
        if (!$targetUserId) api_err('请指定用户');
        $result = admin_adjust_balance($targetUserId, $amount, $reason);
        if ($result['ok']) api_ok($result['msg']); else api_err($result['msg']);
        exit;

    // 管理员设置到期时间
    case 'admin_set_expires':
        $auth = api_auth();
        if (!$auth || ($auth['role'] ?? '') !== 'admin') api_err('无权限', 403);
        $targetUserId = intval($_REQUEST['user_id'] ?? 0);
        $expires = $_REQUEST['expires'] ?? null;
        if (!$targetUserId) api_err('请指定用户');
        $result = admin_set_expires($targetUserId, $expires);
        if ($result['ok']) api_ok($result['msg']); else api_err($result['msg']);
        exit;

    // 管理员列表
    case 'list':
        $auth = api_auth();
        if (!$auth || ($auth['role'] ?? '') !== 'admin') api_err('无权限', 403);
        $list = users_list();
        api_ok('ok', ['list' => $list]);
        exit;

    default:
        api_err('未知操作');
}
