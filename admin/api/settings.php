<?php
/**
 * 设置 API
 * change_password / price_settings
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$token = $_COOKIE['admin_token'] ?? '';
$user = users_verify_token($token);
if (!$user) api_err('未登录', 401);

$type = $_POST['type'] ?? $_REQUEST['type'] ?? '';

switch ($type) {
    case 'change_password':
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        if (empty($oldPassword) || empty($newPassword)) api_err('请填写完整信息');
        if (strlen($newPassword) < 6) api_err('新密码至少需要6个字符');

        $storedHash = db()->prepare("SELECT password FROM users WHERE id = ?");
        $storedHash->execute([$user['id']]);
        $hash = $storedHash->fetch()['password'] ?? '';

        if (!password_verify($oldPassword, $hash)) api_err('当前密码不正确');

        db()->prepare("UPDATE users SET password = ? WHERE id = ?")
           ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        api_ok('密码修改成功');
        break;

    // 获取价格设置（管理员）
    case 'get_price':
        if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);
        $price = db_setting('price_per_bot', '5');
        api_ok('ok', ['price_per_bot' => floatval($price)]);
        break;

    // 设置价格（管理员）
    case 'set_price':
        if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);
        $price = floatval($_POST['price'] ?? 0);
        if ($price <= 0 || $price > 9999) api_err('价格无效');
        db_set_setting('price_per_bot', (string) $price);
        api_ok('价格已更新', ['price_per_bot' => $price]);
        break;

    // 获取网站设置（管理员）
    case 'get_site':
        if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);
        api_ok('ok', [
            'site_name' => db_setting('site_name', 'QQ官机云平台'),
        ]);
        break;

    // 更新网站设置（管理员）
    case 'set_site':
        if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);
        if (isset($_POST['site_name'])) {
            db_set_setting('site_name', trim($_POST['site_name']));
        }
        api_ok('设置已更新');
        break;

    default:
        api_err('未知操作');
}
