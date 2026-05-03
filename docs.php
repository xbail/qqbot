<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>插件开发文档 - QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #2563eb; --primary-light: #3b82f6;
            --accent: #06b6d4;
            --bg: #f8fafc; --bg-card: #ffffff; --bg-code: #f1f5f9;
            --text: #1e293b; --text-muted: #475569; --text-dim: #94a3b8;
            --border: #e2e8f0; --border-light: #cbd5e1;
            --gradient: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            --green: #16a34a; --yellow: #f59e0b; --red: #dc2626;
        }
        html { scroll-behavior: smooth; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'PingFang SC', 'Microsoft YaHei', sans-serif; background: var(--bg); color: var(--text); line-height: 1.7; }

        /* NAV */
        .nav { position: fixed; top: 0; width: 100%; z-index: 100; background: rgba(248,250,252,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 0 40px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; color: var(--text); text-decoration: none; }
        .nav-brand .logo { color: white; }
        .nav-brand .logo { width: 36px; height: 36px; background: var(--gradient); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active-link { color: var(--text); }
        .nav-search { position: relative; }
        .nav-search input { background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 7px 14px 7px 36px; color: var(--text); font-size: 13px; width: 200px; outline: none; transition: border-color 0.2s; }
        .nav-search input:focus { border-color: var(--primary); }
        .nav-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-dim); font-size: 13px; }
        .hamburger { display: none; background: none; border: none; color: var(--text); font-size: 22px; cursor: pointer; padding: 8px; }

        /* LAYOUT */
        .layout { display: flex; padding-top: 64px; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { width: 260px; position: fixed; top: 64px; bottom: 0; left: 0; background: var(--bg); border-right: 1px solid var(--border); padding: 20px 0; overflow-y: auto; z-index: 50; transition: transform 0.3s; }
        .sidebar-section { margin-bottom: 8px; }
        .sidebar-section-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; cursor: pointer; user-select: none; transition: background 0.15s; }
        .sidebar-section-header:hover { background: rgba(0,0,0,0.03); }
        .sidebar-section-header h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-dim); margin: 0; }
        .sidebar-section-header .chevron { font-size: 10px; color: var(--text-dim); transition: transform 0.2s; }
        .sidebar-section.collapsed .chevron { transform: rotate(-90deg); }
        .sidebar-section.collapsed .sidebar-links { display: none; }
        .sidebar-links a { display: block; padding: 7px 20px 7px 24px; color: var(--text-muted); text-decoration: none; font-size: 13px; border-left: 3px solid transparent; transition: all 0.15s; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-links a:hover { color: var(--text); background: rgba(37,99,235,0.04); }
        .sidebar-links a.active { color: var(--primary); border-left-color: var(--primary); background: rgba(37,99,235,0.06); }
        .sidebar-links a.sub { padding-left: 36px; font-size: 12px; }

        /* CONTENT */
        .content { flex: 1; margin-left: 260px; padding: 40px 60px 80px; max-width: 960px; }
        .content h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .content .subtitle { color: var(--text-muted); font-size: 15px; margin-bottom: 32px; }
        .content h2 { font-size: 22px; font-weight: 600; margin-top: 52px; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
        .content h3 { font-size: 17px; font-weight: 600; margin-top: 28px; margin-bottom: 10px; }
        .content h4 { font-size: 15px; font-weight: 600; margin-top: 20px; margin-bottom: 8px; }
        .content p { margin-bottom: 14px; font-size: 14.5px; color: var(--text-muted); }
        .content ul, .content ol { margin-bottom: 14px; padding-left: 24px; }
        .content li { margin-bottom: 6px; font-size: 14.5px; color: var(--text-muted); }
        .content li strong { color: var(--text); }
        .content a { color: var(--primary-light); text-decoration: none; }
        .content a:hover { text-decoration: underline; }
        .content hr { border: none; border-top: 1px solid var(--border); margin: 32px 0; }
        .content blockquote { border-left: 3px solid var(--primary); padding: 12px 16px; margin: 16px 0; background: rgba(37,99,235,0.06); border-radius: 0 8px 8px 0; font-size: 14px; color: var(--text-muted); }

        /* CODE */
        pre { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 18px 22px; margin-bottom: 18px; overflow-x: auto; font-size: 13px; line-height: 1.75; position: relative; color: #e2e8f0; }
        pre code { font-family: 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace; }
        .copy-btn { position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); color: #94a3b8; border-radius: 6px; padding: 4px 10px; font-size: 11px; cursor: pointer; transition: all 0.2s; z-index: 5; }
        .copy-btn:hover { background: rgba(255,255,255,0.15); color: var(--text); }
        .copy-btn.copied { background: rgba(34,197,94,0.2); color: var(--green); border-color: var(--green); }
        .inline-code { background: #eff6ff; color: var(--primary); padding: 2px 7px; border-radius: 4px; font-size: 13px; font-family: 'Fira Code', 'Consolas', monospace; }

        /* Syntax highlight classes */
        .kw { color: #ff7b72; } .fn { color: #d2a8ff; } .str { color: #a5d6ff; }
        .cmt { color: #8b949e; } .var { color: #ffa657; } .num { color: #79c0ff; }
        .type { color: #7ee787; } .op { color: #ff7b72; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13.5px; }
        th, td { padding: 10px 14px; border: 1px solid var(--border); text-align: left; }
        th { background: #f8fafc; color: var(--text); font-weight: 600; font-size: 13px; }
        td { color: var(--text-sub, #475569); }
        td code, th code { background: rgba(37,99,235,0.1); color: var(--primary-light); padding: 2px 6px; border-radius: 3px; font-size: 12px; font-family: 'Fira Code', 'Consolas', monospace; }

        /* CALLOUT */
        .callout { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: 13.5px; border-left: 4px solid; }
        .callout.info { background: #eff6ff; border-color: var(--primary); }
        .callout.warn { background: #fffbeb; border-color: var(--yellow); }
        .callout.danger { background: #fef2f2; border-color: var(--red); }
        .callout.success { background: #f0fdf4; border-color: var(--green); }
        .callout strong { color: var(--text); }

        /* Search highlight */
        .search-highlight { background: #fef3c7; padding: 1px 3px; border-radius: 2px; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Mobile */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .content { margin-left: 0; padding: 24px 16px 60px; max-width: 100%; }
            .nav { padding: 0 12px; }
            .hamburger { display: block; }
            .nav-links { display: none; }
            .nav-search { display: none; }
            .content h1 { font-size: 24px; }
            .content h2 { font-size: 19px; margin-top: 36px; }
            .content h3 { font-size: 16px; }
            .content p, .content li { font-size: 14px; }
            table { font-size: 12px; display: block; overflow-x: auto; }
            pre { font-size: 12px; padding: 14px 16px; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.3); z-index: 40; }
            .sidebar.open ~ .sidebar-overlay { display: block; }
        }
    </style>
</head>
<body>

<nav class="nav">
    <div style="display:flex;align-items:center;gap:16px;">
        <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <a href="index.php" class="nav-brand">
            <div class="logo"><i class="fas fa-robot"></i></div>
            QQ官机云平台
        </a>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-home"></i> 首页</a>
        <a href="docs.php" class="active-link"><i class="fas fa-book"></i> 插件文档</a>
        <a href="admin/index.php"><i class="fas fa-sign-in-alt"></i> 登录</a>
        <div class="nav-search">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="搜索文档..." onkeyup="filterContent(this.value)">
        </div>
    </div>
</nav>

<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>基础入门</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-1" class="active">1. 插件运行机制</a>
                <a href="#section-2">2. 插件基础模板</a>
                <a href="#section-3">3. 全局常量</a>
                <a href="#section-4">4. 命令判断</a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>消息 API</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-5">5. 发送消息 API</a>
                <a href="#s5-1" class="sub">文字</a>
                <a href="#s5-2" class="sub">图片</a>
                <a href="#s5-3" class="sub">语音</a>
                <a href="#s5-4" class="sub">文件</a>
                <a href="#s5-5" class="sub">视频</a>
                <a href="#s5-6" class="sub">按钮</a>
                <a href="#s5-7" class="sub">原生 Markdown</a>
                <a href="#s5-8" class="sub">模板 Markdown</a>
                <a href="#s5-9" class="sub">原生按钮</a>
                <a href="#s5-10" class="sub">Ark 卡片</a>
                <a href="#s5-11" class="sub">流式回复</a>
                <a href="#s5-12" class="sub">撤回消息</a>
                <a href="#s5-13" class="sub">底层 API</a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>Markdown & 数据</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-6">6. MD 短代码</a>
                <a href="#section-7">7. 数据存储</a>
                <a href="#section-8">8. 日志</a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>辅助工具</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-9">9. 辅助函数</a>
                <a href="#s9-1" class="sub">HTTP 请求</a>
                <a href="#s9-2" class="sub">前缀判断</a>
                <a href="#s9-3" class="sub">头像</a>
                <a href="#s9-4" class="sub">BOT 信息</a>
                <a href="#s9-5" class="sub">二维码</a>
                <a href="#s9-6" class="sub">MD 转 HTML</a>
                <a href="#s9-7" class="sub">HTML 转图</a>
                <a href="#s9-8" class="sub">邮箱</a>
                <a href="#s9-9" class="sub">域名大写</a>
                <a href="#section-10">10. 画布/GD 工具</a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>事件 & 管理</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-11">11. 加群/退群/互动</a>
                <a href="#section-12">12. 后台插件管理</a>
            </div>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header" onclick="toggleSection(this)">
                <h4>示例 & FAQ</h4>
                <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a href="#section-13">13. 完整示例</a>
                <a href="#s13-1" class="sub">菜单插件</a>
                <a href="#s13-2" class="sub">签到积分</a>
                <a href="#s13-3" class="sub">API 插件</a>
                <a href="#s13-4" class="sub">图片生成</a>
                <a href="#section-14">14. 常见问题</a>
                <a href="#section-15">15. 开发注意事项</a>
            </div>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- CONTENT -->
    <main class="content" id="docContent">

        <h1><i class="fas fa-book" style="color: var(--primary-light);"></i> 插件开发文档</h1>
        <p class="subtitle">QQ官机云平台 v0.1 插件开发完全指南。基于 QQ 官方机器人 API，只需一个 PHP 文件即可扩展机器人功能。</p>
        <blockquote>本文档按当前框架源码整理，插件目录为 <code class="inline-code">plugin/</code>，插件管理页为 <code class="inline-code">admin/plugin.php?appid=你的appid</code>。</blockquote>

        <hr>

        <!-- ========== 1. 插件运行机制 ========== -->
        <h2 id="section-1">1. 插件运行机制</h2>
        <p>框架入口：<code class="inline-code">index.php</code></p>
        <p>消息流程：</p>
        <pre><code>QQ 官方事件 / 代发入口
    ↓
index.php 解析事件、校验 appid、初始化上下文
    ↓
载入 function.php
    ↓
载入 bot.php
    ↓
遍历 plugin/*.php
    ↓
只执行 main.json 中已启用的插件</code></pre>

        <p>插件文件必须放在：</p>
        <pre><code>plugin/插件名.php</code></pre>

        <p>启用配置在：</p>
        <pre><code>main.json</code></pre>

        <p>示例：</p>
        <pre><code>{
  "102753159": {
    "secret": "***",
    "type": "正式",
    "plugin": {
      "菜单": true,
      "音乐系统": true
    }
  }
}</code></pre>

        <div class="callout info">
            <strong>📌 注意：</strong>
            <ul style="margin-top:6px;">
                <li>插件名就是文件名，不含 <code class="inline-code">.php</code> 后缀。</li>
                <li>只有 <code class="inline-code">main.json</code> 中 <code class="inline-code">plugin.插件名 = true</code> 的插件会执行。</li>
                <li>框架使用 <code class="inline-code">require_once</code> 载入插件，同一个请求中每个插件只载入一次。</li>
                <li>插件报错会被框架捕获并写入日志，不会中断后续插件。</li>
                <li>插件里不要 <code class="inline-code">echo</code> / <code class="inline-code">print</code> 输出内容，回复消息请使用框架函数。</li>
            </ul>
        </div>

        <hr>

        <!-- ========== 2. 插件基础模板 ========== -->
        <h2 id="section-2">2. 插件基础模板</h2>
        <pre><code><span class="kw">&lt;?php</span>
<span class="cmt">// plugin/示例插件.php</span>

<span class="kw">if</span> (消息来源 != <span class="str">"群聊"</span> && 消息来源 != <span class="str">"私聊"</span>) {
    <span class="kw">return</span>;
}

<span class="kw">if</span> (消息 == <span class="str">"ping"</span>) {
    文字(<span class="str">"pong"</span>);
    <span class="kw">return</span>;
}</code></pre>

        <div class="callout warn">
            <strong>⚠️ 推荐规则：</strong>
            <ul style="margin-top:6px;">
                <li>每个命令处理完后 <code class="inline-code">return;</code>，避免继续执行后面的逻辑。</li>
                <li>使用 <code class="inline-code">消息</code>、<code class="inline-code">消息来源</code> 等常量，不要写成 <code class="inline-code">$消息</code>。</li>
                <li>判断相等用 <code class="inline-code">==</code> 或 <code class="inline-code">===</code>，不要写成赋值 <code class="inline-code">=</code>。</li>
            </ul>
        </div>

        <hr>

        <!-- ========== 3. 全局常量 ========== -->
        <h2 id="section-3">3. 全局常量</h2>
        <p>框架在执行插件前会定义以下常量。</p>
        <table>
            <tr><th>常量</th><th>说明</th></tr>
            <tr><td><code>消息来源</code></td><td>当前事件来源：<code>群聊</code>、<code>私聊</code>、<code>加群</code>、<code>退群</code>、<code>互动</code></td></tr>
            <tr><td><code>消息ID</code></td><td>当前消息 ID，普通群聊/私聊消息可用</td></tr>
            <tr><td><code>事件ID</code></td><td>事件 ID，加群/退群/互动等事件可用</td></tr>
            <tr><td><code>消息</code></td><td>用户消息文本，框架会 <code>trim(content, "/ ")</code>，会去掉开头/结尾的 <code>/</code> 和空格</td></tr>
            <tr><td><code>来源</code></td><td>群聊时为群 openid，私聊时为用户 openid，事件时为对应来源 ID</td></tr>
            <tr><td><code>用户</code></td><td>触发用户 openid</td></tr>
            <tr><td><code>appid</code></td><td>当前机器人 AppID</td></tr>
            <tr><td><code>secret</code></td><td>当前机器人 Secret</td></tr>
            <tr><td><code>type</code></td><td>环境：<code>正式</code> 或 <code>沙箱</code></td></tr>
            <tr><td><code>plugin</code></td><td>当前 appid 的插件启用数组</td></tr>
            <tr><td><code>raw</code></td><td>原始 QQ 事件数组</td></tr>
        </table>

        <h3>事件映射</h3>
        <table>
            <tr><th>QQ 事件</th><th>消息来源</th><th>说明</th></tr>
            <tr><td><code>GROUP_AT_MESSAGE_CREATE</code></td><td><code>群聊</code></td><td>群 @ 机器人消息</td></tr>
            <tr><td><code>C2C_MESSAGE_CREATE</code></td><td><code>私聊</code></td><td>用户私聊机器人</td></tr>
            <tr><td><code>GROUP_ADD_ROBOT</code></td><td><code>加群</code></td><td>机器人被添加到群</td></tr>
            <tr><td><code>GROUP_DEL_ROBOT</code></td><td><code>退群</code></td><td>机器人被移出群</td></tr>
            <tr><td><code>INTERACTION_CREATE</code></td><td><code>互动</code></td><td>按钮等互动回调</td></tr>
        </table>

        <hr>

        <!-- ========== 4. 命令判断 ========== -->
        <h2 id="section-4">4. 命令判断</h2>
        <p>由于框架会对 <code class="inline-code">消息</code> 执行：</p>
        <pre><code><span class="fn">trim</span>($content, <span class="str">"/ "</span>)</code></pre>

        <p>所以群聊里用户发送：</p>
        <pre><code>/菜单</code></pre>
        <p>插件中收到的 <code class="inline-code">消息</code> 通常是：</p>
        <pre><code>菜单</code></pre>

        <h3>基础判断</h3>
        <pre><code><span class="kw">if</span> (消息 == <span class="str">"菜单"</span>) {
    文字(<span class="str">"这是菜单"</span>);
    <span class="kw">return</span>;
}</code></pre>

        <h3>前缀命令</h3>
        <pre><code><span class="kw">if</span> (前缀(消息, <span class="str">"echo "</span>)) {
    $text = 前缀后(消息, <span class="str">"echo "</span>);
    文字($text);
    <span class="kw">return</span>;
}</code></pre>

        <h3>限制场景</h3>
        <pre><code><span class="kw">if</span> (消息来源 != <span class="str">"群聊"</span>) {
    <span class="kw">return</span>;
}</code></pre>

        <h3>多个场景</h3>
        <pre><code><span class="kw">if</span> (!<span class="fn">in_array</span>(消息来源, [<span class="str">"群聊"</span>, <span class="str">"私聊"</span>])) {
    <span class="kw">return</span>;
}</code></pre>

        <hr>

        <!-- ========== 5. 发送消息 API ========== -->
        <h2 id="section-5">5. 发送消息 API</h2>
        <p>这些函数主要定义在 <code class="inline-code">bot.php</code>。</p>

        <h3 id="s5-1">5.1 文字</h3>
        <pre><code>文字(<span class="str">"要发送的内容"</span>);</code></pre>
        <div class="callout info">
            <strong>说明：</strong>
            <ul style="margin-top:6px;">
                <li>群聊自动带 <code class="inline-code">msg_id</code> 回复。</li>
                <li>私聊优先使用 <code class="inline-code">事件ID</code>，否则使用 <code class="inline-code">消息ID</code>。</li>
                <li>加群、退群、互动事件使用 <code class="inline-code">event_id</code>。</li>
            </ul>
        </div>

        <h3 id="s5-2">5.2 图片</h3>
        <pre><code>图片(<span class="str">"https://example.com/a.jpg"</span>);
图片(<span class="str">"https://example.com/a.jpg"</span>, <span class="str">"图片说明"</span>);

$data = <span class="fn">file_get_contents</span>(<span class="str">"/path/a.jpg"</span>);
图片($data, <span class="str">"本地图片"</span>);</code></pre>
        <div class="callout info">
            <strong>说明：</strong>
            <ul style="margin-top:6px;">
                <li>URL 会走 QQ 文件上传接口。</li>
                <li>非 URL 内容会按二进制 base64 上传。</li>
            </ul>
        </div>

        <h3 id="s5-3">5.3 语音</h3>
        <pre><code>语音(<span class="str">"https://example.com/a.mp3"</span>);</code></pre>
        <div class="callout info">
            <strong>说明：</strong>
            <ul style="margin-top:6px;">
                <li>框架会调用 <code class="inline-code">silk()</code> 将 mp3 链接转 silk。</li>
                <li>当前转换接口：<code class="inline-code">https://oiapi.net/API/Mp32Silk?url=</code>。</li>
            </ul>
        </div>

        <h3 id="s5-4">5.4 文件</h3>
        <pre><code>文件(<span class="str">"https://example.com/a.pdf"</span>, <span class="str">"文件名.pdf"</span>);

$data = <span class="fn">file_get_contents</span>(<span class="str">"/path/a.zip"</span>);
文件($data, <span class="str">"a.zip"</span>);</code></pre>

        <h3 id="s5-5">5.5 视频</h3>
        <pre><code>视频(<span class="str">"https://example.com/a.mp4"</span>);</code></pre>

        <h3 id="s5-6">5.6 官方键盘按钮</h3>
        <pre><code>按钮(<span class="str">"keyboard_id"</span>);</code></pre>
        <div class="callout info">
            <strong>说明：</strong><code class="inline-code">keyboard_id</code> 需要在 QQ 开放平台配置。
        </div>

        <h3 id="s5-7">5.7 原生 Markdown</h3>
        <pre><code>原生MD(<span class="str">"# 标题\n内容"</span>);
原生MD(<span class="str">"# 标题\n内容"</span>, <span class="str">"keyboard_id"</span>);</code></pre>

        <h3 id="s5-8">5.8 自定义模板 Markdown</h3>
        <pre><code>发MD(<span class="str">"template_id"</span>, [
    [<span class="str">"key"</span> => <span class="str">"title"</span>, <span class="str">"values"</span> => [<span class="str">"标题"</span>]],
    [<span class="str">"key"</span> => <span class="str">"content"</span>, <span class="str">"values"</span> => [<span class="str">"内容"</span>]]
]);

<span class="cmt">// 单个参数也支持</span>
发MD(<span class="str">"template_id"</span>, [<span class="str">"key"</span> => <span class="str">"content"</span>, <span class="str">"values"</span> => [<span class="str">"内容"</span>]], <span class="str">"keyboard_id"</span>);</code></pre>

        <h3 id="s5-9">5.9 原生自定义按钮</h3>
        <p><code class="inline-code">原生按钮($md, $rows)</code> 用于发送带 inline keyboard content 的 Markdown。</p>
        <pre><code>$md = <span class="str">"# 按钮示例"</span>;

$rows = [[
    <span class="str">"buttons"</span> => [[
        <span class="str">"id"</span> => <span class="str">"demo_btn"</span>,
        <span class="str">"render_data"</span> => [
            <span class="str">"label"</span> => <span class="str">"点我"</span>,
            <span class="str">"visited_label"</span> => <span class="str">"已点击"</span>,
            <span class="str">"style"</span> => <span class="num">1</span>
        ],
        <span class="str">"action"</span> => [
            <span class="str">"type"</span> => <span class="num">1</span>,
            <span class="str">"permission"</span> => [<span class="str">"type"</span> => <span class="num">2</span>],
            <span class="str">"data"</span> => <span class="str">"demo:click"</span>,
            <span class="str">"at_bot_show_channel_list"</span> => <span class="kw">true</span>,
            <span class="str">"unsupport_tips"</span> => <span class="str">"当前客户端不支持"</span>
        ]
    ]]
]];

原生按钮($md, $rows);</code></pre>

        <p>处理按钮回调：</p>
        <pre><code><span class="kw">if</span> (消息来源 === <span class="str">"互动"</span>) {
    $btnData = raw[<span class="str">"d"</span>][<span class="str">"data"</span>][<span class="str">"resolved"</span>][<span class="str">"button_data"</span>] ?? (raw[<span class="str">"d"</span>][<span class="str">"data"</span>][<span class="str">"data"</span>] ?? <span class="str">""</span>);

    <span class="kw">if</span> ($btnData === <span class="str">"demo:click"</span>) {
        文字(<span class="str">"你点击了按钮"</span>);
        <span class="kw">return</span>;
    }
}</code></pre>

        <h3 id="s5-10">5.10 Ark 卡片</h3>
        <h4>文本列表卡</h4>
        <pre><code>文卡(
    [<span class="str">"text"</span> => <span class="str">"选项1"</span>, <span class="str">"url"</span> => <span class="str">"https://example.com"</span>],
    [<span class="str">"text"</span> => <span class="str">"选项2"</span>]
);</code></pre>

        <h4>大图卡</h4>
        <pre><code>大图(<span class="str">"主标题"</span>, <span class="str">"副标题"</span>, <span class="str">"https://example.com/cover.jpg"</span>);</code></pre>

        <h4>跳转卡</h4>
        <pre><code>        跳转卡("标题", "描述", "https://example.com/img.jpg", "https://example.com");</code></pre>

        </section>
    </main>

    <footer class="footer">
        <p>QQ官机云平台 · 插件开发文档 v0.1</p>
    </footer>

    <script>
        // 移动端导航
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');
        if (navToggle) {
            navToggle.addEventListener('click', () => navLinks.classList.toggle('show'));
        }
    </script>
</body>
</html>
