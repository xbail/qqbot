<?php
/**
 * 机器人信息 API
 * 获取当前用户的所有机器人列表
 * 使用数据库缓存 name/avatar，避免每次请求QQ API
 */
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$user = api_auth();
if (!$user) api_err('未登录', 401);

$userId = $user['id'] ?? '';
$role   = $user['role'] ?? 'user';
$type   = $_REQUEST['type'] ?? '';

if (empty($type)) api_err('未传入数据');

switch ($type) {
    case 'list':
        $host = $_SERVER['HTTP_HOST'] ?? 'your-domain';
        $callbackUrl = 'https://' . $host . '/webhook.php';

        // 强制刷新标志
        $forceRefresh = !empty($_GET['refresh']);

        $stmt = db()->prepare("SELECT id, appid, secret, type, name, avatar, user_id FROM bots");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $list = [];
        foreach ($rows as $row) {
            // 超管看所有，普通用户只看自己的
            if ($role !== 'admin' && $row['user_id'] != $userId) {
                continue;
            }

            $fh = [
                'appid'    => $row['appid'],
                'secret'   => $row['secret'],
                'type'     => $row['type'],
                'callback' => $callbackUrl,
                'name'     => $row['name'] ?: '未知',
                'avatar'   => $row['avatar'] ?: '',
                'data'     => ['群聊' => 0, '私聊' => 0, '加群' => 0, '退群' => 0, '添加' => 0, '被删' => 0],
            ];

            // 只在有缓存数据时直接用缓存；无缓存或强制刷新时才请求QQ API
            if (empty($row['name']) || $forceRefresh) {
                @set_error_handler(function($errno, $errstr) { return true; });
                try {
                    $msg = bot_get_info($row['appid'], $row['secret']);
                    if (!empty($msg['username'])) {
                        $fh['name'] = $msg['username'];
                        // 更新缓存
                        $upd = db()->prepare("UPDATE bots SET name=?, avatar=? WHERE id=?");
                        $upd->execute([$msg['username'], $msg['avatar'] ?? '', $row['id']]);
                        if (!empty($msg['avatar'])) $fh['avatar'] = $msg['avatar'];
                    }
                } catch (Throwable $e) {}
                @restore_error_handler();
            }

            $list[] = $fh;
        }

        echo json_encode($list, JSON_UNESCAPED_UNICODE);
        break;

    default:
        api_err('未知操作');
}

function bot_get_info($appid, $secret) {
    if (empty($appid) || empty($secret)) {
        return ['username' => '未知', 'avatar' => ''];
    }

    $url = 'https://bots.qq.com/app/getAppAccessToken';
    $postData = json_encode(['appId' => (string)$appid, 'clientSecret' => $secret]);
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $postData,
            'ignore_errors' => true,
            'timeout' => 3,
        ]
    ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) return ['username' => '未知', 'avatar' => ''];
    $fw = json_decode($resp, true);
    if (!is_array($fw)) return ['username' => '未知', 'avatar' => ''];
    $token = $fw['access_token'] ?? '';
    if (empty($token)) return ['username' => '未知', 'avatar' => ''];

    $apiUrl = 'https://api.sgroup.qq.com/users/@me';
    $opts2 = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: QQBot {$token}\r\nContent-Type: application/json\r\n",
            'ignore_errors' => true,
            'timeout' => 3,
        ]
    ];
    $ctx2 = stream_context_create($opts2);
    $resp2 = @file_get_contents($apiUrl, false, $ctx2);
    if ($resp2 === false) return ['username' => '未知', 'avatar' => ''];
    $info = json_decode($resp2, true);
    if (!is_array($info)) return ['username' => '未知', 'avatar' => ''];

    return [
        'username' => $info['username'] ?? $info['bot_nickname'] ?? '未知',
        'avatar'   => $info['avatar'] ?? '',
    ];
}
