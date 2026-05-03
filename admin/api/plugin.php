<?php
/**
 * 插件管理 API（SQLite 版）
 * 操作：list, open, close, filelist, add, delete, read, write, upload
 * 插件文件是全局共享的（plugin/目录），启用/禁用按 appid 隔离
 * 添加/删除/上传仅超管可用
 * 数据库：bot_plugins 表存储插件启用状态
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$token = $_COOKIE['admin_token'] ?? '';
$user = users_verify_token($token);
if (!$user) {
    api_err('未登录', 401);
}

$isAdmin = ($user['role'] ?? '') === 'admin';
$pluginDir = dirname(__DIR__, 2) . '/plugin';

// 安全：验证插件名，防止路径穿越
function validatePluginName($name) {
    return preg_match('/^[a-zA-Z0-9_\-\x{4e00}-\x{9fff}]+$/u', $name);
}

$type = $_REQUEST['type'] ?? '';

switch ($type) {
    // 获取指定 appid 的插件启用列表
    case 'list':
        $appid = $_REQUEST['appid'] ?? '';
        if (empty($appid)) api_err('缺少 appid');
        $plugins = bot_plugins_get($appid);
        echo json_encode((object)$plugins);
        exit;

    // 列出所有插件文件
    case 'filelist':
        $files = glob($pluginDir . '/*.php');
        $list = [];
        if ($files) {
            foreach ($files as $f) {
                $list[] = basename($f, '.php');
            }
        }
        api_ok('ok', ['list' => $list]);
        break;

    // 开启插件
    case 'open':
        $appid = $_REQUEST['appid'] ?? '';
        $name = $_REQUEST['name'] ?? '';
        if (empty($appid) || empty($name)) api_err('参数不完整');
        if (bot_plugins_set($appid, $name, true)) {
            api_ok('启用成功');
        } else {
            api_err('操作失败（机器人不存在）');
        }
        break;

    // 关闭插件
    case 'close':
        $appid = $_REQUEST['appid'] ?? '';
        $name = $_REQUEST['name'] ?? '';
        if (empty($appid) || empty($name)) api_err('参数不完整');
        if (bot_plugins_set($appid, $name, false)) {
            api_ok('禁用成功');
        } else {
            api_err('操作失败（机器人不存在）');
        }
        break;

    // 读取插件内容
    case 'read':
        $name = $_REQUEST['name'] ?? '';
        if (empty($name)) api_err('参数不完整');
        if (!validatePluginName($name)) api_err('无效的插件名');
        $path = $pluginDir . '/' . $name . '.php';
        if (!is_file($path)) api_err('插件不存在');
        $content = file_get_contents($path);
        if ($content === false) api_err('读取失败');
        api_ok('ok', ['content' => $content]);
        break;

    // 写入插件内容
    case 'write':
        $name = $_REQUEST['name'] ?? '';
        if (empty($name)) api_err('参数不完整');
        if (!validatePluginName($name)) api_err('无效的插件名');
        $body = json_decode(file_get_contents('php://input'), true);
        $content = $body['content'] ?? '';
        $path = $pluginDir . '/' . $name . '.php';
        if (!is_file($path)) api_err('插件不存在');
        if (file_put_contents($path, $content) !== false) {
            api_ok('保存成功');
        } else {
            api_err('保存失败');
        }
        break;

    // 新建插件（仅超管）
    case 'add':
        if (!$isAdmin) api_err('仅管理员可操作', 403);
        $name = $_REQUEST['name'] ?? '';
        if (empty($name)) api_err('请输入插件名称');
        if (!validatePluginName($name)) api_err('插件名称仅支持字母、数字、下划线和中文');
        $path = $pluginDir . '/' . $name . '.php';
        if (is_file($path)) api_err('插件已存在');
        if (file_put_contents($path, "<?php\n\n?>") !== false) {
            api_ok('创建成功');
        } else {
            api_err('创建失败');
        }
        break;

    // 删除插件（仅超管）
    case 'delete':
        if (!$isAdmin) api_err('仅管理员可操作', 403);
        $name = $_REQUEST['name'] ?? '';
        if (empty($name)) api_err('参数不完整');
        if (!validatePluginName($name)) api_err('无效的插件名');
        $path = $pluginDir . '/' . $name . '.php';
        if (!is_file($path)) api_err('插件不存在');
        // 同时清理所有机器人的启用记录
        db()->prepare("DELETE FROM bot_plugins WHERE plugin_name = ?")->execute([$name]);
        if (unlink($path)) {
            api_ok('删除成功');
        } else {
            api_err('删除失败');
        }
        break;

    // 上传插件（支持 .php 和 .zip，仅超管）
    case 'upload':
        if (!$isAdmin) api_err('仅管理员可操作', 403);
        if (!isset($_FILES['plugin_file'])) api_err('未收到文件');
        $file = $_FILES['plugin_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            api_err('上传失败，错误码：' . $file['error']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            api_err('文件不能超过5MB');
        }

        $filename = basename($file['name']);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($ext === 'php') {
            // 直接保存 .php 插件
            $name = pathinfo($filename, PATHINFO_FILENAME);
            if (!validatePluginName($name)) api_err('插件名不合法');
            $dest = $pluginDir . '/' . $name . '.php';
            if (is_file($dest)) api_err('插件已存在');
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                api_ok('上传成功', ['name' => $name]);
            } else {
                api_err('保存失败');
            }
        } elseif ($ext === 'zip') {
            // 解压 .zip 到临时目录，找到 .php 文件移入 plugin/
            $tmpDir = sys_get_temp_dir() . '/plugin_upload_' . uniqid();
            if (!mkdir($tmpDir, 0755, true)) api_err('创建临时目录失败');

            if (!class_exists('ZipArchive')) {
                rmdir($tmpDir);
                api_err('服务器不支持 zip 解压');
            }

            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) {
                rmdir($tmpDir);
                api_err('ZIP文件无法打开');
            }

            $uploaded = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                // 跳过目录和非php文件
                if (substr($entryName, -1) === '/') continue;
                if (strtolower(pathinfo($entryName, PATHINFO_EXTENSION)) !== 'php') continue;
                // 跳过嵌套目录中的文件（只处理根目录的php）
                if (strpos($entryName, '/') !== false) continue;

                $name = pathinfo($entryName, PATHINFO_FILENAME);
                if (!validatePluginName($name)) continue;
                $dest = $pluginDir . '/' . $name . '.php';
                if (is_file($dest)) continue; // 跳过已存在的

                $content = $zip->getFromIndex($i);
                if ($content !== false && file_put_contents($dest, $content) !== false) {
                    $uploaded[] = $name;
                }
            }
            $zip->close();
            // 清理临时目录
            @unlink($file['tmp_name']);
            @rmdir($tmpDir);

            if (empty($uploaded)) {
                api_err('ZIP中没有找到可安装的插件（.php文件）');
            }
            api_ok('成功安装 ' . count($uploaded) . ' 个插件', ['installed' => $uploaded]);
        } else {
            api_err('仅支持 .php 和 .zip 文件');
        }
        break;

    default:
        api_err('未知操作');
}
