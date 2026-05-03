<?php
/**
 * 机器人管理 API
 * 添加 / 删除 / 列表
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$user = api_auth();
if (!$user) api_err('未登录', 401);

$userId = $user['id'];
$role   = $user['role'] ?? 'user';
$type   = $_REQUEST['type'] ?? '';

if (empty($type)) api_err('未传入操作类型');

// 内联函数：获取机器人信息（避免 require_once info.php 导致的exit问题）
function _bot_fetch_info($appid, $secret, $env = 'production') {
    $apiUrl = "https://bots.qq.com/app/getAppInfo";
    $postData = json_encode([
        'appId'     => $appid,
        'clientSecret' => $secret,
        'environment'  => $env,
    ]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $postData,
            'timeout' => 5,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($apiUrl, false, $ctx);
    if ($resp === false) return ['username' => '', 'avatar' => ''];
    $json = json_decode($resp, true);
    if (!is_array($json)) return ['username' => '', 'avatar' => ''];
    return [
        'username' => $json['applicant'] ?? $json['username'] ?? '',
        'avatar'   => $json['icon'] ?? $json['avatar'] ?? '',
    ];
}

switch ($type) {

    case 'add':
        $appid  = trim($_REQUEST['appid'] ?? '');
        $secret = trim($_REQUEST['secret'] ?? '');
        $environment = $_REQUEST['environment'] ?? '正式';

        if (empty($appid) || empty($secret)) api_err('请填写 AppID 和 Secret');
        if (!preg_match('/^\d{10,}$/', $appid)) api_err('AppID 格式不正确');

        // 检查会员是否有效（管理员跳过会员检查）
        if ($role !== 'admin') {
            $membership = users_get_membership($user);
            if (!$membership['is_valid']) {
                api_err('会员已过期，请先续费后再添加机器人');
            }

            // 非管理员才检查机器人数量上限
            $limit = users_check_bot_limit($userId);
            if (!$limit['ok']) {
                api_err($limit['msg']);
            }
        } else {
            $limit = ['ok' => true, 'current' => 0, 'max' => 9999];
        }

        // 检查 AppID 是否已被其他用户绑定
        $owner = users_find_owner($appid);
        if ($owner && ($owner['id'] ?? '') !== $userId) {
            api_err('该 AppID 已被其他用户绑定');
        }

        $result = users_register_appid($userId, $appid, $secret, $environment);
        if (!$result['ok']) {
            api_err($result['msg'] ?? '添加失败');
        }

        // 添加后立即获取并缓存 name/avatar（内联调用，不require info.php）
        @set_error_handler(function($errno, $errstr) { return true; });
        try {
            $envKey = (strtolower($environment) === '沙箱') ? 'sandbox' : 'production';
            $cached = _bot_fetch_info($appid, $secret, $envKey);
            if (!empty($cached['username'])) {
                db()->prepare("UPDATE bots SET name=?, avatar=? WHERE appid=?")
                   ->execute([$cached['username'], $cached['avatar'] ?? '', $appid]);
            }
        } catch (Throwable $e) {}
        @restore_error_handler();

        api_ok('添加成功', [
            'bot_usage' => [
                'current' => $limit['current'] + 1,
                'max'     => $limit['max'],
            ],
        ]);
        break;

    case 'del':
        $appid = trim($_REQUEST['appid'] ?? '');
        if (empty($appid)) api_err('缺少 AppID');

        $owner = users_find_owner($appid);
        if (!$owner) api_err('机器人不存在或未注册');
        if ($role !== 'admin' && ($owner['id'] ?? '') !== $userId) {
            api_err('无权删除此机器人');
        }

        $targetUserId = ($role === 'admin') ? ($owner['id'] ?? $userId) : $userId;
        $result = users_remove_appid($targetUserId, $appid);
        if (!$result['ok']) {
            api_err($result['msg'] ?? '删除失败');
        }

        $limitAfter = users_check_bot_limit($targetUserId);
        api_ok('删除成功', [
            'bot_usage' => [
                'current' => $limitAfter['current'],
                'max'     => $limitAfter['max'],
            ],
        ]);
        break;

    case 'list':
        $userMain = users_main_read($userId);
        $bots = [];
        foreach ($userMain as $appid => $info) {
            $bots[] = [
                'appid'  => $appid,
                'type'   => $info['type'] ?? '正式',
                'plugin' => $info['plugin'] ?? [],
            ];
        }

        $limit = users_check_bot_limit($userId);
        $membership = users_get_membership($user);
        api_ok('ok', [
            'bots'      => $bots,
            'bot_usage' => [
                'current' => $limit['current'],
                'max'     => $limit['max'],
            ],
            'membership' => $membership,
        ]);
        break;

    default:
        api_err('未知操作');
}
