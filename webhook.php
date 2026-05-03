<?php
ob_start();

require __DIR__ . "/function.php";
require __DIR__ . "/admin/inc/db.php";
require __DIR__ . "/admin/inc/users.php";

$rawText = file_get_contents("php://input");
if (empty($rawText)) {
    wlog('{"plat_error":"收到未知请求,元数据为空已阻拦"}');
    ob_end_clean();
    die("Request error");
}

// 从 SQLite 读取所有机器人配置（替代 main.json）
$main_json = webhook_all_configs();
if (!is_array($main_json) || empty($main_json)) {
    ob_end_clean();
    die("Main config error");
}

$raw = json_decode($rawText, true);
if (!is_array($raw)) {
    ob_end_clean();
    die("JSON error");
}

// 兼容旧版"代发"入口
if (($raw['type'] ?? '') === '代发') {
    handleRelay($raw, $main_json);
    exit;
}

// 腾讯官方事件入口
$appid = $_SERVER["HTTP_X_BOT_APPID"] ?? "";
if (!isset($main_json[$appid])) {
    wlog('{"plat_error":"收到非官方请求,已阻拦"}');
    ob_end_clean();
    die("Appid error");
}

// 多用户路由: 提取 user_id 并切换到用户目录
$cfg = $main_json[$appid];
$userId = $cfg['user_id'] ?? '';
if ($userId === '') {
    ob_end_clean();
    die("user_id error");
}

// 创建用户目录（如果不存在）并切换工作目录
$userDir = __DIR__ . "/users/" . $userId;
if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}
chdir($userDir);

initAppContext($appid, $cfg);

if (!function_exists('sodium_crypto_sign_seed_keypair') || !extension_loaded('sodium')) {
    wlog('{"plat_error":"未安装或未加载sodium拓展"}');
    ob_end_clean();
    die("sodium error");
}

define("raw", $raw);
$op = $raw["op"] ?? null;

if ($op == 13) {
    sign($raw, secret);
    exit;
}

if ($op == 0) {
    $event_id = $raw["id"] ?? '';
    if ($event_id === '') {
        die("event error");
    }

    $event = 读("事件判断/" . appid . "/" . date("Y-m-d"), $event_id, false);
    if ($event) {
        wlog('{"plat_error":"元数据重复上传"}');
        die("error");
    }

    写("事件判断/" . appid . "/" . date("Y-m-d"), $event_id, true);
    wlog(json_encode($raw, JSON_UNESCAPED_UNICODE));
    Main($raw);
}

/**
 * 处理代发请求
 */
function handleRelay(array $raw, array $main_json)
{
    $relayOp = $raw['op'] ?? '';
    $data = $raw['data'] ?? [];

    switch ($relayOp) {
        case 'get':
            $group = $data['group'] ?? '';
            if ($group === '') {
                echo json_encode(['code' => -2], JSON_UNESCAPED_UNICODE);
                return;
            }

            $resolved = resolveRelayTarget($group, $main_json);
            if (!$resolved) {
                echo json_encode(['code' => -2], JSON_UNESCAPED_UNICODE);
                return;
            }

            $eventJsonFile = __DIR__ . "/官机事件ID.json";
            $eventMap = file_exists($eventJsonFile) ? json_decode(file_get_contents($eventJsonFile), true) : [];
            if (!is_array($eventMap)) {
                $eventMap = [];
            }

            $boundGroup = $resolved['boundGroup'];
            if (!isset($eventMap[$boundGroup]['time'], $eventMap[$boundGroup]['msgid'])) {
                echo json_encode(['code' => -1], JSON_UNESCAPED_UNICODE);
                return;
            }

            if (time() - (int)$eventMap[$boundGroup]['time'] > 290) {
                echo json_encode(['code' => -1], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'code' => 1,
                'msgid' => $eventMap[$boundGroup]['msgid'],
                'bind' => $boundGroup
            ], JSON_UNESCAPED_UNICODE);
            return;

        case 'send':
            $targetAppid = (string)($data['appid'] ?? '');
            if ($targetAppid === '' || !isset($main_json[$targetAppid])) {
                $targetAppid = array_key_first($main_json);
            }
            if ($targetAppid === null || !isset($main_json[$targetAppid])) {
                echo json_encode(['code' => -3, 'msg' => 'appid not found'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $targetCfg = $main_json[$targetAppid];
            $targetUserId = $targetCfg['user_id'] ?? '';
            if ($targetUserId !== '') {
                $targetUserDir = __DIR__ . "/users/" . $targetUserId;
                if (!is_dir($targetUserDir)) {
                    mkdir($targetUserDir, 0755, true);
                }
                chdir($targetUserDir);
            }

            initAppContext($targetAppid, $targetCfg);
            require_once __DIR__ . "/bot.php";

            $address = $data['address'] ?? '';
            $method = $data['method'] ?? 'POST';
            $body = $data['body'] ?? [];

            if ($address === '') {
                echo json_encode(['code' => -4, 'msg' => 'address empty'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo BOTAPI($address, $method, json_encode($body, JSON_UNESCAPED_UNICODE));
            return;

        default:
            echo json_encode(['code' => -9, 'msg' => 'unknown relay op'], JSON_UNESCAPED_UNICODE);
            return;
    }
}

/**
 * 解析代发目标：遍历所有 appid，切换到对应用户目录读取 bind 数据
 */
function resolveRelayTarget(string $group, array $main_json)
{
    foreach ($main_json as $appidKey => $cfg) {
        $userId = $cfg['user_id'] ?? '';
        if ($userId === '') {
            continue;
        }

        // 临时切换到该用户目录以读取 bind 数据
        $savedDir = getcwd();
        $userDir = __DIR__ . "/users/" . $userId;
        if (is_dir($userDir)) {
            chdir($userDir);
        }

        $boundGroup = 读($appidKey . "2bind.json", $group, '');

        // 恢复原目录
        if ($savedDir) {
            chdir($savedDir);
        }

        if ($boundGroup !== '') {
            return [
                'appid' => (string)$appidKey,
                'boundGroup' => $boundGroup
            ];
        }
    }
    return null;
}

/**
 * 初始化应用上下文常量
 */
function initAppContext(string $appidVal, array $cfg)
{
    if (!defined('appid')) {
        define("appid", $appidVal);
    }
    if (!defined('secret')) {
        define("secret", $cfg["secret"] ?? '');
    }
    if (!defined('type')) {
        define("type", $cfg["type"] ?? '正式');
    }
    if (!defined('plugin')) {
        define("plugin", $cfg["plugin"] ?? []);
    }
}

/**
 * 事件分发主入口
 */
function Main($raw)
{
    $event = $raw["t"] ?? '';
    switch ($event) {
        case "GROUP_AT_MESSAGE_CREATE":
            define("消息来源", "群聊");
            define("消息ID", $raw["d"]["id"] ?? '');
            define("消息", trim($raw["d"]["content"] ?? '', "/ "));
            define("来源", $raw["d"]["group_id"] ?? '');
            define("用户", $raw["d"]["author"]["id"] ?? '');
            break;

        case "C2C_MESSAGE_CREATE":
            define("消息来源", "私聊");
            define("消息ID", $raw["d"]["id"] ?? '');
            define("消息", trim($raw["d"]["content"] ?? '', "/ "));
            define("来源", $raw["d"]["author"]["id"] ?? '');
            define("用户", $raw["d"]["author"]["id"] ?? '');
            break;

        case "GROUP_ADD_ROBOT":
            define("消息来源", "加群");
            define("事件ID", $raw["id"] ?? '');
            define("消息", "[加群]");
            define("来源", $raw["d"]["group_openid"] ?? '');
            define("用户", $raw["d"]["op_member_openid"] ?? '');
            break;

        case "GROUP_DEL_ROBOT":
            define("消息来源", "退群");
            define("事件ID", $raw["id"] ?? '');
            define("消息", "[退群]");
            define("来源", $raw["d"]["group_openid"] ?? '');
            define("用户", $raw["d"]["op_member_openid"] ?? '');
            break;

        case "INTERACTION_CREATE":
            define("消息来源", "互动");
            define("事件ID", $raw["id"] ?? '');
            define("来源", $raw["d"]["group_openid"] ?? ($raw["d"]["user_openid"] ?? ''));
            define("用户", $raw["d"]["user_openid"] ?? ($raw["d"]["group_member_openid"] ?? ""));
            define("消息", "[互动]");
            break;

        default:
            return;
    }

    require __DIR__ . "/bot.php";
    load_plugin();
    exit;
}

/**
 * 加载全局 plugin/ 目录中的插件（不受用户目录影响）
 */
function load_plugin()
{
    $All = glob(__DIR__ . "/plugin/*.php");
    foreach ($All as $name) {
        $plugin_name = basename($name, ".php");
        if (isset(plugin[$plugin_name]) && plugin[$plugin_name]) {
            try {
                require_once($name);
            } catch (Throwable $e) {
                $error = json_encode([
                    "plat_error" => "[{$name}]运行出错: " . $e->getMessage() . " 行数:" . $e->getLine()
                ], JSON_UNESCAPED_UNICODE);
                wlog($error);
                continue;
            }
        }
    }
}
