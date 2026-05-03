<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQ官机云平台 · 首页</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #2563eb; --primary-light: #3b82f6;
            --accent: #06b6d4; --bg: #f8fafc;
            --bg-card: #ffffff; --text: #1e293b;
            --text-muted: #64748b; --border: #e2e8f0;
            --gradient: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
        }
        html { scroll-behavior: smooth; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'PingFang SC', 'Microsoft YaHei', sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }
        .nav { position: fixed; top: 0; width: 100%; z-index: 100; background: rgba(248,250,252,0.95); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border); padding: 0 40px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .nav-brand { display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; color: var(--text); text-decoration: none; }
        .nav-brand .logo { width: 36px; height: 36px; background: var(--gradient); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a { color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.2s; }
        .nav-links a:hover, .nav-links a.active-link { color: var(--primary); }
        .hamburger { display: none; background: none; border: none; color: var(--text); font-size: 22px; cursor: pointer; }
        .hero { padding: 140px 40px 80px; text-align: center; max-width: 800px; margin: 0 auto; }
        .hero h1 { font-size: 48px; font-weight: 800; line-height: 1.2; margin-bottom: 20px; color: var(--text); }
        .hero h1 .gradient { background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 18px; color: var(--text-muted); max-width: 560px; margin: 0 auto 36px; }
        .hero-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 28px; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-family: inherit; }
        .btn-primary { background: var(--gradient); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(37,99,235,0.3); }
        .btn-secondary { background: var(--bg-card); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
        .features { padding: 60px 40px; max-width: 1100px; margin: 0 auto; }
        .section-title { text-align: center; margin-bottom: 48px; }
        .section-title h2 { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .section-title p { color: var(--text-muted); font-size: 15px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; }
        .feature-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; padding: 28px; transition: all 0.2s; }
        .feature-card:hover { border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
        .feature-icon { width: 48px; height: 48px; background: rgba(37,99,235,0.08); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--primary); margin-bottom: 16px; }
        .feature-card h3 { font-size: 17px; font-weight: 600; margin-bottom: 8px; }
        .feature-card p { font-size: 14px; color: var(--text-muted); line-height: 1.7; }
        .code-section { padding: 60px 40px; max-width: 800px; margin: 0 auto; }
        .code-block { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-top: 24px; }
        .code-header { display: flex; align-items: center; gap: 8px; padding: 12px 16px; background: rgba(255,255,255,0.03); border-bottom: 1px solid #334155; }
        .code-dot { width: 10px; height: 10px; border-radius: 50%; }
        .code-dot.red { background: #ef4444; } .code-dot.yellow { background: #eab308; } .code-dot.green { background: #22c55e; }
        .code-filename { margin-left: 8px; font-size: 12px; color: #94a3b8; }
        .code-block pre { padding: 20px; overflow-x: auto; font-size: 13.5px; line-height: 1.7; font-family: 'SF Mono', 'Consolas', 'Monaco', monospace; color: #e2e8f0; }
        .code-block .kw { color: #ff7b72; } .code-block .fn { color: #d2a8ff; } .code-block .str { color: #a5d6ff; } .code-block .cmt { color: #8b949e; }
        .stats { padding: 60px 40px; text-align: center; }
        .stats-grid { display: flex; justify-content: center; gap: 48px; flex-wrap: wrap; max-width: 800px; margin: 0 auto; }
        .stat-item .stat-num { font-size: 36px; font-weight: 800; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-item .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
        .footer { padding: 40px; text-align: center; border-top: 1px solid var(--border); color: var(--text-muted); font-size: 13px; }
        .footer a { color: var(--primary); text-decoration: none; }
        @media (max-width: 768px) {
            .nav { padding: 0 16px; } .nav-links { display: none; flex-direction: column; position: absolute; top: 64px; left: 0; right: 0; background: white; border-bottom: 1px solid var(--border); padding: 16px; gap: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); } .nav-links.open { display: flex; } .hamburger { display: block; }
            .hero { padding: 120px 20px 60px; } .hero h1 { font-size: 32px; } .hero p { font-size: 15px; }
            .features, .code-section { padding: 40px 16px; } .stats-grid { gap: 32px; }
        }
    </style>
    <script>
    function toggleMobileMenu() {
        document.getElementById('navLinks').classList.toggle('open');
    }
    </script>
</head>
<body>
    <nav class="nav">
        <a href="index.php" class="nav-brand">
            <div class="logo"><i class="fas fa-robot"></i></div>
            QQ官机云平台 <span style="font-size:12px;font-weight:500;background:var(--primary);color:white;padding:2px 8px;border-radius:20px;">v0.1</span>
        </a>
        <button class="hamburger" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="active-link">首页</a>
            <a href="docs.php">开发文档</a>
            <a href="https://q.qq.com" target="_blank"><i class="fas fa-external-link-alt" style="margin-right:4px;font-size:12px;"></i>注册QQ机器人</a>
            <a href="admin/index.php">登录</a>
        </div>
    </nav>

    <section class="hero">
        <h1>用 PHP 快速构建<br><span class="gradient">QQ 官方机器人</span></h1>
        <p>轻量、易上手的 QQ 官方机器人开发框架。中文变量名，中文函数名，告别繁琐的 API 封装。</p>
        <div class="hero-actions">
            <a href="docs.php" class="btn btn-primary"><i class="fas fa-book"></i> 阅读文档</a>
            <a href="admin/index.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> 登录后台</a>
        </div>
    </section>

    <section class="features">
        <div class="section-title">
            <h2>为什么选择QQ官机云平台</h2>
            <p>专为中文开发者打造的 QQ 机器人框架</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-language"></i></div>
                <h3>全中文语法</h3>
                <p>变量名、函数名全部使用中文。群()、私()、text()、image()，写插件就像写作文一样自然。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>极速上手</h3>
                <p>30 秒创建第一个机器人插件。无需理解复杂的异步回调，框架帮你处理一切。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-puzzle-piece"></i></div>
                <h3>插件生态</h3>
                <p>丰富的内置插件：天气查询、音乐播放、小游戏、AI 对话。社区持续更新中。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>安全可靠</h3>
                <p>基于 QQ 官方机器人 API，稳定不掉线。支持沙盒测试环境，上线前充分验证。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-cogs"></i></div>
                <h3>完善的管理后台</h3>
                <p>可视化管理机器人配置、插件开关、聊天日志。支持多用户隔离，一个后台管多个号。</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-code"></i></div>
                <h3>强大的自定义 API</h3>
                <p>轻松创建自定义 API 接口，对接外部系统。RESTful 风格，返回 JSON / Markdown / 图片。</p>
            </div>
        </div>
    </section>

    <section class="pricing" style="padding: 60px 40px; max-width: 800px; margin: 0 auto;">
        <div class="section-title">
            <h2>透明定价</h2>
            <p>按机器人数量付费，无隐藏费用</p>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 40px; text-align: center; max-width: 400px; margin: 0 auto; transition: all 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 32px rgba(37,99,235,0.12)'" onmouseout="this.style.borderColor='var(--border)';this.style.transform='none';this.style.boxShadow='none'">
            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">每机器人</div>
            <div style="font-size: 48px; font-weight: 800; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 4px;">¥5</div>
            <div style="font-size: 15px; color: var(--text-muted); margin-bottom: 28px;">/月/机器人</div>
            <ul style="list-style: none; padding: 0; margin: 0 0 32px 0; text-align: left;">
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); display: flex; align-items: center; gap: 10px;"><i class="fas fa-check-circle" style="color: #22c55e; font-size: 16px;"></i> 无限消息</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); display: flex; align-items: center; gap: 10px;"><i class="fas fa-check-circle" style="color: #22c55e; font-size: 16px;"></i> 7x24小时在线</li>
                <li style="padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text); display: flex; align-items: center; gap: 10px;"><i class="fas fa-check-circle" style="color: #22c55e; font-size: 16px;"></i> 插件市场</li>
                <li style="padding: 10px 0; font-size: 14px; color: var(--text); display: flex; align-items: center; gap: 10px;"><i class="fas fa-check-circle" style="color: #22c55e; font-size: 16px;"></i> 技术支持</li>
            </ul>
            <a href="admin/index.php" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px 28px; font-size: 15px;"><i class="fas fa-rocket"></i> 立即开始</a>
        </div>
    </section>

    <section class="code-section">
        <div class="section-title">
            <h2>30 秒写一个插件</h2>
            <p>看看有多简单</p>
        </div>
        <div class="code-block">
            <div class="code-header">
                <span class="code-dot red"></span>
                <span class="code-dot yellow"></span>
                <span class="code-dot green"></span>
                <span class="code-filename">plugin/hello.php</span>
            </div>
<pre><span class="cmt">// 收到群消息时回复</span>
<span class="kw">if</span>(消息来源 === <span class="str">"群聊"</span>){
    <span class="fn">群</span>(群号, <span class="fn">text</span>(<span class="str">"你好！我是机器人~"</span>));
}

<span class="cmt">// 收到私聊消息时回复</span>
<span class="kw">if</span>(消息来源 === <span class="str">"私聊"</span>){
    <span class="fn">私</span>(QQ, <span class="fn">text</span>(<span class="str">"有什么可以帮你的？"</span>));
}</pre>
        </div>
    </section>

    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item"><div class="stat-num">100+</div><div class="stat-label">内置 API 函数</div></div>
            <div class="stat-item"><div class="stat-num">50+</div><div class="stat-label">社区插件</div></div>
            <div class="stat-item"><div class="stat-num">1000+</div><div class="stat-label">活跃用户</div></div>
            <div class="stat-item"><div class="stat-num">v0.1</div><div class="stat-label">最新版本</div></div>
        </div>
    </section>

    <footer class="footer">
        <p>QQ官机云平台 · QQ 官方机器人开发框架 · <a href="docs.php">文档</a></p>
    </footer>
</body>
</html>
