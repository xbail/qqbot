<?php
/**
 * QQ官机云平台 · 多用户管理模块（SQLite 版）
 * 
 * 存储：SQLite 数据库 (data/guanji.db)
 * 机器人数据：bots 表（替代全局 main.json 和每用户 main.json）
 * 用户数据：users 表
 * 卡密：card_keys 表
 */

require_once __DIR__ . '/db.php';
db_init();

/* ==================== 路径常量（兼容旧代码） ==================== */
define('USERS_DIR', dirname(__DIR__, 2) . '/users');

/* ==================== 用户数据读写 ==================== */

function users_read(): array {
    $rows = db()->query("SELECT * FROM users")->fetchAll();
    $users = [];
    foreach ($rows as $row) {
        $users[$row['id']] = $row;
    }
    return $users;
}

function users_save_user(array $user): void {
    $stmt = db()->prepare("UPDATE users SET username=?, password=?, role=?, balance=?, bot_count=?, expires_at=? WHERE id=?");
    $stmt->execute([
        $user['username'], $user['password'], $user['role'],
        $user['balance'], $user['bot_count'], $user['expires_at'] ?? null,
        $user['id']
    ]);
}

function users_find_by_name(string $username): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

function users_find_by_id($id): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/* ==================== 认证 ==================== */

function users_verify_password(string $password, string $hash): bool {
    if ($password === $hash) return true;
    return password_verify($password, $hash);
}

function users_authenticate(string $username, string $password): array {
    $user = users_find_by_name($username);
    if (!$user) return ['ok' => false, 'msg' => '账号不存在'];
    if (!users_verify_password($password, $user['password'])) {
        return ['ok' => false, 'msg' => '密码错误'];
    }
    return ['ok' => true, 'user_id' => $user['id'], 'username' => $user['username'], 'role' => $user['role'] ?? 'user'];
}

function users_create_token(int $user_id, string $username): string {
    $secret = 'guanji_frame_v2_' . md5($username . $user_id . 'salt_2024');
    $payload = base64_encode(json_encode(['uid' => $user_id, 'name' => $username, 'ts' => time()]));
    $sig = md5($payload . $secret);
    return $payload . '.' . $sig;
}

function users_verify_token(string $token): ?array {
    if (empty($token)) return null;
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) return null;
    $payload = $parts[0];
    $sig = $parts[1];
    $data = json_decode(base64_decode($payload), true);
    if (!$data || !isset($data['uid']) || !isset($data['name'])) return null;
    $user = users_find_by_id($data['uid']);
    if (!$user || $user['username'] !== $data['name']) return null;
    $secret = 'guanji_frame_v2_' . md5($data['name'] . $data['uid'] . 'salt_2024');
    $expected = md5($payload . $secret);
    if (!hash_equals($expected, $sig)) return null;
    if (time() - ($data['ts'] ?? 0) > 30 * 86400) return null;
    return $user;
}

function admin_auth(): ?array {
    $token = $_COOKIE['admin_token'] ?? '';
    $user = users_verify_token($token);
    if (!$user) {
        header('Location: index.php');
        exit();
    }
    return $user;
}

function api_auth(): ?array {
    $token = $_COOKIE['admin_token'] ?? '';
    return users_verify_token($token);
}

/* ==================== 用户 CRUD ==================== */

function users_create(string $username, string $password, string $role = 'user'): array {
    if (empty($username) || empty($password)) {
        return ['ok' => false, 'msg' => '账号和密码不能为空'];
    }
    if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20}$/u', $username)) {
        return ['ok' => false, 'msg' => '账号只能包含字母、数字、下划线或中文，2-20个字符'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'msg' => '密码至少6个字符'];
    }
    if (users_find_by_name($username)) {
        return ['ok' => false, 'msg' => '账号已存在'];
    }

    $stmt = db()->prepare("INSERT INTO users(username, password, role) VALUES(?, ?, ?)");
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
    $user_id = (int) db()->lastInsertId();

    // 创建用户目录
    $user_dir = USERS_DIR . '/' . $user_id;
    @mkdir($user_dir, 0755, true);
    @mkdir($user_dir . '/database', 0755, true);
    return ['ok' => true, 'user_id' => $user_id];
}

function users_delete($user_id): array {
    $user = users_find_by_id($user_id);
    if (!$user) return ['ok' => false, 'msg' => '用户不存在'];

    // 删除该用户的所有机器人
    db()->prepare("DELETE FROM bots WHERE user_id = ?")->execute([$user_id]);

    // 删除用户目录
    $user_dir = USERS_DIR . '/' . $user_id;
    if (is_dir($user_dir)) {
        users_rmdir_recursive($user_dir);
    }

    db()->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    return ['ok' => true];
}

function users_change_password($user_id, string $new_password): array {
    if (strlen($new_password) < 6) {
        return ['ok' => false, 'msg' => '密码至少6个字符'];
    }
    $user = users_find_by_id($user_id);
    if (!$user) return ['ok' => false, 'msg' => '用户不存在'];

    $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
    return ['ok' => true];
}

function users_list(): array {
    $rows = db()->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();
    $list = [];
    foreach ($rows as $row) {
        $bot_count = db()->prepare("SELECT COUNT(*) as cnt FROM bots WHERE user_id = ?");
        $bot_count->execute([$row['id']]);
        $cnt = $bot_count->fetch()['cnt'] ?? 0;

        $list[] = [
            'id'               => $row['id'],
            'username'         => $row['username'],
            'role'             => $row['role'] ?? 'user',
            'balance'          => floatval($row['balance'] ?? 0),
            'bot_count'        => $row['bot_count'] ?? 0,
            'expires_at'       => $row['expires_at'],
            'created_at'       => $row['created_at'] ?? '',
            'actual_bot_count' => $cnt,
        ];
    }
    return $list;
}

/* ==================== 全局路由表（兼容旧接口） ==================== */

/**
 * 读取全局路由表 appid → user_id（从 bots 表）
 */
function users_global_main_read(): array {
    $rows = db()->query("SELECT appid, user_id FROM bots")->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[$row['appid']] = $row['user_id'];
    }
    return $map;
}

/**
 * 保存全局路由表（全量同步）
 */
function users_global_main_save(array $data): void {
    // 获取当前数据库中的所有 appid
    $rows = db()->query("SELECT appid, user_id FROM bots")->fetchAll();
    $current = [];
    foreach ($rows as $row) {
        $current[$row['appid']] = $row['user_id'];
    }

    // 找出要删除的（在数据库中但不在新数据中）
    foreach ($current as $appid => $uid) {
        if (!isset($data[$appid])) {
            db()->prepare("DELETE FROM bots WHERE appid = ?")->execute([$appid]);
        }
    }

    // 找出要新增的（在新数据中但不在数据库中）
    foreach ($data as $appid => $uid) {
        if (!isset($current[$appid])) {
            $stmt = db()->prepare("INSERT OR IGNORE INTO bots(appid, user_id, secret, type) VALUES(?, ?, '', '正式')");
            $stmt->execute([$appid, $uid]);
        }
    }
}

/* ==================== 机器人（Bot）管理 ==================== */

function users_register_appid($user_id, string $appid, string $secret, string $type = '沙箱'): array {
    // 检查是否已存在
    $stmt = db()->prepare("SELECT id FROM bots WHERE appid = ?");
    $stmt->execute([$appid]);
    if ($stmt->fetch()) {
        // 更新
        $stmt = db()->prepare("UPDATE bots SET user_id=?, secret=?, type=? WHERE appid=?");
        $stmt->execute([$user_id, $secret, $type, $appid]);
    } else {
        // 插入
        $stmt = db()->prepare("INSERT INTO bots(user_id, appid, secret, type) VALUES(?, ?, ?, ?)");
        $stmt->execute([$user_id, $appid, $secret, $type]);
    }

    // 更新用户 bot_count
    $cnt = db()->prepare("SELECT COUNT(*) as cnt FROM bots WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $count = $cnt->fetch()['cnt'];
    db()->prepare("UPDATE users SET bot_count = ? WHERE id = ?")->execute([$count, $user_id]);

    return ['ok' => true];
}

function users_remove_appid($user_id, string $appid): array {
    $stmt = db()->prepare("DELETE FROM bots WHERE appid = ? AND user_id = ?");
    $stmt->execute([$appid, $user_id]);

    // 更新用户 bot_count
    $cnt = db()->prepare("SELECT COUNT(*) as cnt FROM bots WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $count = $cnt->fetch()['cnt'];
    db()->prepare("UPDATE users SET bot_count = ? WHERE id = ?")->execute([$count, $user_id]);

    return ['ok' => true];
}

function users_find_owner(string $appid): ?array {
    $stmt = db()->prepare("SELECT u.* FROM bots b JOIN users u ON b.user_id = u.id WHERE b.appid = ?");
    $stmt->execute([$appid]);
    return $stmt->fetch() ?: null;
}

/* ==================== 机器人配置读写（从 SQLite bots 表） ==================== */

/**
 * 读取用户的机器人配置
 * 从 bots 表构建 appid → {secret, type, plugin} 映射
 */
function users_main_read($user_id): array {
    $stmt = db()->prepare("SELECT appid, secret, type FROM bots WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();

    $main = [];
    foreach ($rows as $row) {
        // 从 bot_plugins 表读取插件启用状态
        $plugins = [];
        $bp_stmt = db()->prepare("SELECT plugin_name, enabled FROM bot_plugins WHERE bot_id = (SELECT id FROM bots WHERE appid = ? LIMIT 1)");
        $bp_stmt->execute([$row['appid']]);
        foreach ($bp_stmt->fetchAll() as $bp) {
            $plugins[$bp['plugin_name']] = (bool)$bp['enabled'];
        }

        $main[$row['appid']] = [
            'secret' => $row['secret'],
            'type'   => $row['type'],
            'plugin' => $plugins,
        ];
    }
    return $main;
}

/**
 * 写入用户的机器人配置
 * 实际上更新 bots 表中该用户的记录，同时同步 plugin 启用状态
 */
function users_main_write($user_id, array $data): void {
    // 先删除该用户所有旧记录（级联删除 bot_plugins）
    db()->prepare("DELETE FROM bots WHERE user_id = ?")->execute([$user_id]);

    // 写入新记录
    $stmt = db()->prepare("INSERT INTO bots(user_id, appid, secret, type) VALUES(?, ?, ?, ?)");
    foreach ($data as $appid => $info) {
        $stmt->execute([
            $user_id, $appid,
            $info['secret'] ?? '',
            $info['type'] ?? '正式',
        ]);

        // 同步插件启用状态
        $plugins = $info['plugin'] ?? [];
        $bot_id = db()->lastInsertId();
        if (!empty($plugins)) {
            $bp_stmt = db()->prepare("INSERT INTO bot_plugins(bot_id, plugin_name, enabled) VALUES(?, ?, ?)");
            foreach ($plugins as $pName => $pEnabled) {
                $bp_stmt->execute([$bot_id, $pName, $pEnabled ? 1 : 0]);
            }
        }
    }

    // 更新 bot_count
    $count = count($data);
    db()->prepare("UPDATE users SET bot_count = ? WHERE id = ?")->execute([$count, $user_id]);
}

/* ==================== 工具函数 ==================== */

function users_rmdir_recursive(string $dir): void {
    if (!is_dir($dir)) return;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            users_rmdir_recursive($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function api_json(array $data): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function api_ok(string $msg = 'ok', array $extra = []): void {
    api_json(array_merge(['code' => 200, 'msg' => $msg], $extra));
}

function api_err(string $msg, int $code = 400): void {
    api_json(['code' => $code, 'msg' => $msg]);
}

/* ==================== 会员/计费体系 ==================== */

/**
 * 获取每机器人每月价格
 */
function users_get_price_per_bot(): float {
    return floatval(db_setting('price_per_bot', '5'));
}

/**
 * 获取用户会员信息（简化版：余额+到期时间）
 */
function users_get_membership($user): array {
    $expires = $user['expires_at'] ?? null;
    $is_valid = false;
    if ($expires) {
        $is_valid = strtotime($expires) > time();
    }
    return [
        'balance'   => floatval($user['balance'] ?? 0),
        'bot_count' => intval($user['bot_count'] ?? 0),
        'expires_at' => $expires,
        'is_valid'  => $is_valid,
        'price_per_bot' => users_get_price_per_bot(),
    ];
}

/**
 * 检查用户机器人数量限制
 * 新逻辑：用户需要有效会员（expires_at > now）且 bot_count < 无限制
 * 但实际限制取决于余额是否足够续费
 */
function users_check_bot_limit($user_id): array {
    $user = users_find_by_id($user_id);
    if (!$user) {
        return ['ok' => false, 'current' => 0, 'max' => 0, 'msg' => '用户不存在'];
    }

    $cnt = db()->prepare("SELECT COUNT(*) as cnt FROM bots WHERE user_id = ?");
    $cnt->execute([$user_id]);
    $current = $cnt->fetch()['cnt'];

    // 检查是否为有效会员
    $expires = $user['expires_at'] ?? null;
    $is_valid = $expires && strtotime($expires) > time();

    if (!$is_valid) {
        return [
            'ok' => false,
            'current' => $current,
            'max' => $current,
            'msg' => '会员已过期，请续费后再添加机器人',
        ];
    }

    // 有效会员可以添加任意数量（只要余额够续费）
    return [
        'ok' => true,
        'current' => $current,
        'max' => 9999,
        'msg' => "{$current}个机器人运行中",
    ];
}

/**
 * 检查会员是否有效
 */
function users_is_valid_membership($user): bool {
    $expires = $user['expires_at'] ?? null;
    return $expires && strtotime($expires) > time();
}

/* ==================== 卡密系统 ==================== */

/**
 * 生成卡密
 */
function cardkey_generate(float $amount, int $count, string $batch_name = ''): array {
    $codes = [];
    $stmt = db()->prepare("INSERT INTO card_keys(card_code, amount, batch_name) VALUES(?, ?, ?)");

    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(6))); // 12位大写十六进制
        try {
            $stmt->execute([$code, $amount, $batch_name]);
            $codes[] = $code;
        } catch (PDOException $e) {
            // 唯一约束冲突，重试
            $code = strtoupper(bin2hex(random_bytes(8)));
            $stmt->execute([$code, $amount, $batch_name]);
            $codes[] = $code;
        }
    }

    return $codes;
}

/**
 * 用户使用卡密充值
 */
function cardkey_redeem(int $user_id, string $code): array {
    $code = strtoupper(trim($code));

    $stmt = db()->prepare("SELECT * FROM card_keys WHERE card_code = ? AND status = 'unused'");
    $stmt->execute([$code]);
    $card = $stmt->fetch();

    if (!$card) {
        return ['ok' => false, 'msg' => '卡密无效或已使用'];
    }

    $amount = floatval($card['amount']);

    // 标记已使用
    $stmt = db()->prepare("UPDATE card_keys SET status = 'used', used_by = ?, used_at = datetime('now','localtime') WHERE id = ?");
    $stmt->execute([$user_id, $card['id']]);

    // 增加余额
    db()->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $user_id]);

    // 记录订单
    db()->prepare("INSERT INTO orders(user_id, type, amount, description) VALUES(?, 'card_redeem', ?, ?)")
       ->execute([$user_id, $amount, "卡密充值 ¥{$amount}"]);

    $user = users_find_by_id($user_id);
    return [
        'ok' => true,
        'msg' => "充值成功！已到账 ¥{$amount}",
        'amount' => $amount,
        'balance' => floatval($user['balance']),
    ];
}

/**
 * 用户续费/购买机器人位
 * deduct_price = price_per_bot * bot_count * months
 */
function membership_purchase(int $user_id, int $months): array {
    if ($months <= 0 || $months > 36) {
        return ['ok' => false, 'msg' => '月数无效'];
    }

    $user = users_find_by_id($user_id);
    if (!$user) return ['ok' => false, 'msg' => '用户不存在'];

    $price = users_get_price_per_bot();
    $bot_count = max(intval($user['bot_count']), 1); // 至少按1个机器人算
    $total_cost = $price * $bot_count * $months;
    $balance = floatval($user['balance']);

    if ($balance < $total_cost) {
        return [
            'ok' => false,
            'msg' => "余额不足！需要 ¥{$total_cost}（{$bot_count}个机器人×{$months}个月×¥{$price}），当前余额 ¥{$balance}",
        ];
    }

    // 计算新的到期时间
    $expires = $user['expires_at'] ?? null;
    $now = time();
    if ($expires && strtotime($expires) > $now) {
        // 在当前到期时间基础上延长
        $new_expires = strtotime($expires) + ($months * 30 * 86400);
    } else {
        // 从现在开始
        $new_expires = $now + ($months * 30 * 86400);
    }
    $expires_str = date('Y-m-d H:i:s', $new_expires);

    // 扣余额
    db()->prepare("UPDATE users SET balance = balance - ?, expires_at = ? WHERE id = ?")
       ->execute([$total_cost, $expires_str, $user_id]);

    // 记录订单
    db()->prepare("INSERT INTO orders(user_id, type, amount, description) VALUES(?, 'purchase', ?, ?)")
       ->execute([$user_id, -$total_cost, "续费{$bot_count}个机器人{$months}个月"]);

    $user2 = users_find_by_id($user_id);
    return [
        'ok' => true,
        'msg' => "续费成功！扣费 ¥{$total_cost}，到期时间 {$expires_str}",
        'expires_at' => $expires_str,
        'balance' => floatval($user2['balance']),
        'deducted' => $total_cost,
    ];
}

/**
 * 获取用户订单记录
 */
function orders_list(int $user_id, int $limit = 20): array {
    $stmt = db()->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * 管理员手动调整用户余额
 */
function admin_adjust_balance(int $user_id, float $amount, string $reason = ''): array {
    $user = users_find_by_id($user_id);
    if (!$user) return ['ok' => false, 'msg' => '用户不存在'];

    db()->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $user_id]);

    db()->prepare("INSERT INTO orders(user_id, type, amount, description) VALUES(?, 'admin_adjust', ?, ?)")
       ->execute([$user_id, $amount, $reason ?: "管理员调整"]);

    return ['ok' => true, 'msg' => '调整成功'];
}

/**
 * 管理员手动设置用户到期时间
 */
function admin_set_expires(int $user_id, ?string $expires_at): array {
    $user = users_find_by_id($user_id);
    if (!$user) return ['ok' => false, 'msg' => '用户不存在'];

    db()->prepare("UPDATE users SET expires_at = ? WHERE id = ?")->execute([$expires_at, $user_id]);
    return ['ok' => true, 'msg' => '设置成功'];
}

/* ==================== 插件管理（SQLite） ==================== */

/**
 * 获取指定机器人已启用的插件列表
 */
function bot_plugins_get(string $appid): array {
    $bot = db()->prepare("SELECT id FROM bots WHERE appid = ? LIMIT 1");
    $bot->execute([$appid]);
    $row = $bot->fetch();
    if (!$row) return [];

    $stmt = db()->prepare("SELECT plugin_name, enabled FROM bot_plugins WHERE bot_id = ?");
    $stmt->execute([$row['id']]);
    $plugins = [];
    foreach ($stmt->fetchAll() as $r) {
        $plugins[$r['plugin_name']] = (bool)$r['enabled'];
    }
    return $plugins;
}

/**
 * 启用/禁用指定机器人的插件
 */
function bot_plugins_set(string $appid, string $plugin_name, bool $enabled): bool {
    $bot = db()->prepare("SELECT id FROM bots WHERE appid = ? LIMIT 1");
    $bot->execute([$appid]);
    $row = $bot->fetch();
    if (!$row) return false;

    $bot_id = $row['id'];
    $stmt = db()->prepare("INSERT INTO bot_plugins(bot_id, plugin_name, enabled) VALUES(?, ?, ?)
                           ON CONFLICT(bot_id, plugin_name) DO UPDATE SET enabled = excluded.enabled");
    $stmt->execute([$bot_id, $plugin_name, $enabled ? 1 : 0]);
    return true;
}

/**
 * 删除机器人上的某插件记录（彻底移除）
 */
function bot_plugins_remove(string $appid, string $plugin_name): bool {
    $bot = db()->prepare("SELECT id FROM bots WHERE appid = ? LIMIT 1");
    $bot->execute([$appid]);
    $row = $bot->fetch();
    if (!$row) return false;

    db()->prepare("DELETE FROM bot_plugins WHERE bot_id = ? AND plugin_name = ?")
       ->execute([$row['id'], $plugin_name]);
    return true;
}

/* ==================== Webhook 运行时配置（替代 main.json） ==================== */

/**
 * 获取所有机器人的完整运行时配置（供 webhook.php 使用）
 * 返回格式: appid => ['secret' => ..., 'type' => ..., 'user_id' => ..., 'plugin' => [...]]
 */
function webhook_all_configs(): array {
    $bots = db()->query("SELECT id, appid, secret, type, user_id FROM bots")->fetchAll();
    $configs = [];

    foreach ($bots as $bot) {
        // 读取该机器人的插件列表
        $plugins = [];
        $bp_stmt = db()->prepare("SELECT plugin_name, enabled FROM bot_plugins WHERE bot_id = ?");
        $bp_stmt->execute([$bot['id']]);
        foreach ($bp_stmt->fetchAll() as $bp) {
            $plugins[$bp['plugin_name']] = (bool)$bp['enabled'];
        }

        $configs[$bot['appid']] = [
            'secret'   => $bot['secret'],
            'type'     => $bot['type'],
            'user_id'  => $bot['user_id'],
            'plugin'   => $plugins,
        ];
    }

    return $configs;
}

/**
 * 获取单个机器人的运行时配置
 */
function webhook_bot_config(string $appid): ?array {
    $stmt = db()->prepare("SELECT id, appid, secret, type, user_id FROM bots WHERE appid = ? LIMIT 1");
    $stmt->execute([$appid]);
    $bot = $stmt->fetch();
    if (!$bot) return null;

    $plugins = [];
    $bp_stmt = db()->prepare("SELECT plugin_name, enabled FROM bot_plugins WHERE bot_id = ?");
    $bp_stmt->execute([$bot['id']]);
    foreach ($bp_stmt->fetchAll() as $bp) {
        $plugins[$bp['plugin_name']] = (bool)$bp['enabled'];
    }

    return [
        'secret'   => $bot['secret'],
        'type'     => $bot['type'],
        'user_id'  => $bot['user_id'],
        'plugin'   => $plugins,
    ];
}
