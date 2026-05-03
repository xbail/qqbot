<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) { header('Location: index.php'); exit(); }
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
if (!$isAdmin) { header('Location: main.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>价格设置 · QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span style="font-weight:600;">价格设置</span>
        <div></div>
    </div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>QQ官机云平台</h1>
                <p><i class="fas fa-shield-alt"></i> 管理员后台</p>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">管理</div>
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="admin.php" class="nav-item"><i class="fas fa-users"></i> 用户管理</a>
                <a href="plugin.php" class="nav-item"><i class="fas fa-puzzle-piece"></i> 插件管理</a>
                <a href="cardkeys.php" class="nav-item"><i class="fas fa-key"></i> 卡密管理</a>
                <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> 价格设置</a>
                <div class="nav-section">切换</div>
                <a href="main.php" class="nav-item"><i class="fas fa-home"></i> 用户中心</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($currentUser['username']) ?></div>
                <a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>

        <main class="main">
            <div class="top-bar">
                <div class="page-title">价格与网站设置</div>
            </div>

            <div class="container">
                <!-- 价格设置 -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><i class="fas fa-tag" style="color:var(--warning);"></i> 机器人月费</div>
                    </div>
                    <div class="section-body">
                        <form id="priceForm">
                            <div class="form-group">
                                <label>月费价格 (元/月)</label>
                                <input type="number" class="form-control" id="priceInput" min="0.01" max="9999" step="0.01" placeholder="输入价格">
                                <div class="hint" id="currentPrice">当前价格: <i class="fas fa-info-circle"></i> 加载中...</div>
                            </div>
                            <button type="submit" class="btn btn-success" id="priceBtn"><i class="fas fa-save"></i> 保存价格</button>
                        </form>
                    </div>
                </div>

                <!-- 网站设置 -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><i class="fas fa-globe" style="color:var(--primary);"></i> 网站名称</div>
                    </div>
                    <div class="section-body">
                        <form id="siteForm">
                            <div class="form-group">
                                <label>网站名称</label>
                                <input type="text" class="form-control" id="siteInput" placeholder="输入网站名称">
                                <div class="hint" id="currentSite">当前名称: <i class="fas fa-info-circle"></i> 加载中...</div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="siteBtn"><i class="fas fa-save"></i> 保存名称</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
    async function loadPrice() {
        try {
            const res = await fetch('api/settings.php?type=get_price');
            const data = await res.json();
            if (data.code === 200) {
                const price = data.price_per_bot ?? 0;
                document.getElementById('priceInput').value = price;
                document.getElementById('currentPrice').innerHTML = `<i class="fas fa-info-circle"></i> 当前价格: ¥${price}/月`;
            }
        } catch { toast('加载价格失败', false); }
    }

    async function loadSite() {
        try {
            const res = await fetch('api/settings.php?type=get_site');
            const data = await res.json();
            if (data.code === 200) {
                const name = data.site_name ?? '';
                document.getElementById('siteInput').value = name;
                document.getElementById('currentSite').innerHTML = `<i class="fas fa-info-circle"></i> 当前名称: ${name}`;
            }
        } catch { toast('加载站点信息失败', false); }
    }

    // Save price
    document.getElementById('priceForm').onsubmit = async e => {
        e.preventDefault();
        const price = document.getElementById('priceInput').value.trim();
        if (!price || isNaN(price) || parseFloat(price) <= 0) { toast('请输入有效价格', false); return; }
        const btn = document.getElementById('priceBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        try {
            const formData = new FormData();
            formData.append('price', price);
            const res = await fetch('api/settings.php?type=set_price', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.code === 200) {
                toast(data.msg || '价格已更新', true);
                loadPrice();
            } else {
                toast(data.msg || '保存失败', false);
            }
        } catch { toast('网络错误', false); }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> 保存价格';
    };

    // Save site
    document.getElementById('siteForm').onsubmit = async e => {
        e.preventDefault();
        const name = document.getElementById('siteInput').value.trim();
        if (!name) { toast('请输入网站名称', false); return; }
        const btn = document.getElementById('siteBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
        try {
            const formData = new FormData();
            formData.append('site_name', name);
            const res = await fetch('api/settings.php?type=set_site', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.code === 200) {
                toast(data.msg || '名称已更新', true);
                loadSite();
            } else {
                toast(data.msg || '保存失败', false);
            }
        } catch { toast('网络错误', false); }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> 保存名称';
    };

    // Mobile menu
    loadPrice();
    loadSite();
    </script>
</body>
</html>
