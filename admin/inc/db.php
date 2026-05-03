<?php
/**
 * QQ官机云平台 · SQLite 数据库层
 * 
 * 统一管理 SQLite 连接、初始化、通用查询
 */

define('DB_PATH', dirname(__DIR__, 2) . '/data/guanji.db');

/**
 * 获取 SQLite PDO 连接（单例）
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 性能优化
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("PRAGMA synchronous=NORMAL");
    $pdo->exec("PRAGMA foreign_keys=ON");

    return $pdo;
}

/**
 * 初始化数据库表结构
 */
function db_init(): void {
    $db = db();

    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            username    TEXT UNIQUE NOT NULL,
            password    TEXT NOT NULL,
            role        TEXT DEFAULT 'user',
            balance     REAL DEFAULT 0,
            bot_count   INTEGER DEFAULT 0,
            expires_at  TEXT,
            created_at  TEXT DEFAULT (datetime('now','localtime'))
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS bots (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER NOT NULL,
            appid       TEXT UNIQUE NOT NULL,
            secret      TEXT NOT NULL,
            type        TEXT DEFAULT '正式',
            name        TEXT DEFAULT '',
            avatar      TEXT DEFAULT '',
            created_at  TEXT DEFAULT (datetime('now','localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // 兼容旧表：添加 name/avatar 列（如果不存在）
    foreach (['name', 'avatar'] as $col) {
        try { $db->exec("ALTER TABLE bots ADD COLUMN {$col} TEXT DEFAULT ''"); }
        catch (Throwable $e) { /* 列已存在 */ }
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS card_keys (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            card_code   TEXT UNIQUE NOT NULL,
            amount      REAL NOT NULL,
            status      TEXT DEFAULT 'unused',
            used_by     INTEGER,
            used_at     TEXT,
            batch_name  TEXT DEFAULT '',
            created_at  TEXT DEFAULT (datetime('now','localtime'))
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id     INTEGER NOT NULL,
            type        TEXT NOT NULL,
            amount      REAL DEFAULT 0,
            description TEXT DEFAULT '',
            created_at  TEXT DEFAULT (datetime('now','localtime'))
        )
    ");

    // 索引
    $db->exec("CREATE INDEX IF NOT EXISTS idx_bots_user_id ON bots(user_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_bots_appid ON bots(appid)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_card_keys_status ON card_keys(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS bot_plugins (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            bot_id      INTEGER NOT NULL,
            plugin_name TEXT NOT NULL,
            enabled     INTEGER DEFAULT 1,
            FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE
        )
    ");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_bot_plugins_unique ON bot_plugins(bot_id, plugin_name)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_bot_plugins_bot_id ON bot_plugins(bot_id)");

    // 默认设置
    $stmt = $db->prepare("INSERT OR IGNORE INTO settings(key, value) VALUES(?, ?)");
    $stmt->execute(['price_per_bot', '5']);
    $stmt->execute(['site_name', 'QQ官机云平台']);

    // 默认管理员账号（仅首次初始化时创建）
    $admin = $db->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
    if (!$admin) {
        $db->prepare("INSERT INTO users(username, password, role, balance) VALUES(?, ?, 'admin', 0)")
           ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }
}

/**
 * 获取设置值
 */
function db_setting(string $key, string $default = ''): string {
    $stmt = db()->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

/**
 * 设置值
 */
function db_set_setting(string $key, string $value): void {
    $stmt = db()->prepare("INSERT OR REPLACE INTO settings(key, value) VALUES(?, ?)");
    $stmt->execute([$key, $value]);
}
