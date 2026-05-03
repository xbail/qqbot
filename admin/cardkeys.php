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
    <title>卡密管理 · QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span style="font-weight:600;">卡密管理</span>
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
                <a href="cardkeys.php" class="nav-item active"><i class="fas fa-key"></i> 卡密管理</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> 价格设置</a>
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
                <div class="page-title">卡密管理</div>
            </div>

            <div class="container">
                <!-- Stats -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">未使用数量</div>
                        <div class="stat-value" id="sUnused" style="color:var(--success);">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">已使用数量</div>
                        <div class="stat-value" id="sUsed" style="color:var(--primary);">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">总充值金额</div>
                        <div class="stat-value" style="color:var(--warning);">¥<span id="sTotal">0</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">今日充值</div>
                        <div class="stat-value" style="color:var(--danger);">¥<span id="sToday">0</span></div>
                    </div>
                </div>

                <!-- Generate Form -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> 生成卡密</div>
                    </div>
                    <div style="padding: 20px;">
                        <form id="genForm" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                            <div class="form-group" style="margin-bottom:0; flex:1; min-width:120px;">
                                <label>金额 (¥)</label>
                                <input type="number" class="form-control" id="genAmount" min="0.01" max="9999" step="0.01" required placeholder="例如 10">
                            </div>
                            <div class="form-group" style="margin-bottom:0; flex:1; min-width:120px;">
                                <label>数量</label>
                                <input type="number" class="form-control" id="genCount" min="1" max="500" required placeholder="最多500张">
                            </div>
                            <div class="form-group" style="margin-bottom:0; flex:1; min-width:120px;">
                                <label>批次名称 (可选)</label>
                                <input type="text" class="form-control" id="genBatch" placeholder="例如 2025-01">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <button type="submit" class="btn btn-primary" id="genBtn"><i class="fas fa-bolt"></i> 生成</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card Keys Table -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">卡密列表</div>
                        <div style="display:flex; gap:8px;">
                            <select id="statusFilter" class="form-control" style="width:auto; padding:4px 10px; font-size:12px;">
                                <option value="">全部状态</option>
                                <option value="unused">未使用</option>
                                <option value="used">已使用</option>
                            </select>
                            <button class="btn btn-secondary btn-sm" id="refreshBtn"><i class="fas fa-sync-alt"></i> 刷新</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>卡密</th><th>金额</th><th>状态</th><th>使用者</th><th>使用时间</th><th>批次</th></tr></thead>
                            <tbody id="cardTable"></tbody>
                        </table>
                    </div>
                    <div class="pagination" id="pagination"></div>
                </div>
            </div>
        </main>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
    let currentPage = 1, totalPages = 1;

    function esc(s) { if (!s) return ''; return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }

    async function loadStats() {
        try {
            const res = await fetch('api/cardkeys.php?type=stats');
            const data = await res.json();
            if (data.code === 200) {
                document.getElementById('sUnused').textContent = data.unused ?? 0;
                document.getElementById('sUsed').textContent = data.used ?? 0;
                document.getElementById('sTotal').textContent = data.total_amount ?? 0;
                document.getElementById('sToday').textContent = data.today_amount ?? 0;
            }
        } catch { toast('加载统计失败', false); }
    }

    async function loadList(page) {
        currentPage = page || 1;
        const status = document.getElementById('statusFilter').value;
        try {
            let url = `api/cardkeys.php?type=list&page=${currentPage}`;
            if (status) url += `&status=${status}`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.code === 200) {
                const list = data.list || [];
                totalPages = data.pages || 1;
                const tbody = document.getElementById('cardTable');
                if (!list.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无卡密数据</td></tr>';
                } else {
                    tbody.innerHTML = list.map(k => `<tr>
                        <td><code style="font-size:12px; background:#f1f5f9; padding:2px 6px; border-radius:4px;">${esc(k.card_code)}</code></td>
                        <td style="font-weight:600; color:var(--warning);">¥${esc(k.amount)}</td>
                        <td><span class="badge ${k.status==='unused'?'badge-unused':'badge-used'}">${k.status==='unused'?'未使用':'已使用'}</span></td>
                        <td>${esc(k.used_by||'-')}</td>
                        <td style="font-size:12px; color:var(--text-muted);">${esc(k.used_at||'-')}</td>
                        <td style="font-size:12px;">${esc(k.batch_name||'-')}</td>
                    </tr>`).join('');
                }
                renderPagination();
            }
        } catch { toast('加载卡密列表失败', false); }
    }

    function renderPagination() {
        const el = document.getElementById('pagination');
        if (totalPages <= 1) { el.innerHTML = ''; return; }
        let html = `<button ${currentPage<=1?'disabled':''} onclick="loadList(${currentPage-1})"><i class="fas fa-chevron-left"></i></button>`;
        html += `<span class="page-info">第 ${currentPage} / ${totalPages} 页</span>`;
        html += `<button ${currentPage>=totalPages?'disabled':''} onclick="loadList(${currentPage+1})"><i class="fas fa-chevron-right"></i></button>`;
        el.innerHTML = html;
    }

    // Generate
    document.getElementById('genForm').onsubmit = async e => {
        e.preventDefault();
        const btn = document.getElementById('genBtn');
        const amount = document.getElementById('genAmount').value;
        const count = document.getElementById('genCount').value;
        const batch = document.getElementById('genBatch').value;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 生成中...';
        try {
            const formData = new FormData();
            formData.append('amount', amount);
            formData.append('count', count);
            formData.append('batch_name', batch);
            const res = await fetch('api/cardkeys.php?type=generate', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.code === 200) {
                toast(data.msg || '生成成功', true);
                document.getElementById('genForm').reset();
                loadStats();
                loadList(1);
            } else {
                toast(data.msg || '生成失败', false);
            }
        } catch { toast('网络错误', false); }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt"></i> 生成';
    };

    document.getElementById('statusFilter').onchange = () => loadList(1);
    document.getElementById('refreshBtn').onclick = () => { loadStats(); loadList(currentPage); };

    // Mobile menu
    loadStats();
    loadList(1);
    </script>
</body>
</html>
