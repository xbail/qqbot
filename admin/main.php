<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) {
    header('Location: index.php');
    exit();
}
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$membership = users_get_membership($currentUser);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 · QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span style="font-weight:600;">用户中心</span>
        <div></div>
    </div>

    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>QQ官机云平台</h1>
                <p>用户中心</p>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">机器人</div>
                <a href="#" class="nav-item active" id="navDashboard">
                    <i class="fas fa-tachometer-alt"></i> 总览
                </a>
                <a href="#" class="nav-item" id="navAddBot">
                    <i class="fas fa-plus-circle"></i> 添加机器人
                </a>
                <a href="#" class="nav-item" id="navChatLogs">
                    <i class="fas fa-comments"></i> 聊天记录
                </a>
                <div class="nav-section">管理</div>
                <a href="pricing.php" class="nav-item">
                    <i class="fas fa-crown" style="color:#f59e0b;"></i> 会员充值
                </a>
                <a href="#" class="nav-item" id="navCardKey">
                    <i class="fas fa-key" style="color:#16a34a;"></i> 卡密充值
                </a>
                <a href="plugin.php" class="nav-item">
                    <i class="fas fa-puzzle-piece"></i> 插件管理
                </a>
                <a href="set.php" class="nav-item">
                    <i class="fas fa-user-cog"></i> 账号设置
                </a>
                <?php if ($isAdmin): ?>
                <a href="admin.php" class="nav-item">
                    <i class="fas fa-shield-alt"></i> 后台管理
                </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info"><i class="fas fa-user"></i> <?= htmlspecialchars($currentUser['username'] ?? '未知') ?></div>
                <div class="balance-info" id="sidebarBalance">
                    <i class="fas fa-wallet"></i>
                    <span>¥<?= number_format($membership['balance'] ?? 0, 2) ?></span>
                </div>
                <div class="expire-info" id="sidebarExpire">
                    <i class="fas fa-clock"></i>
                    <?php if ($membership && isset($membership['expires_at']) && $membership['expires_at']): ?>
                        到期: <?= htmlspecialchars($membership['expires_at']) ?>
                    <?php else: ?>
                        暂无会员
                    <?php endif; ?>
                </div>
                <a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>

        <main class="main">
            <?php if ($membership && isset($membership['is_valid']) && !$membership['is_valid']): ?>
            <div class="expired-banner">
                <i class="fas fa-exclamation-triangle"></i>
                会员已过期，请充值续费
            </div>
            <?php endif; ?>

            <div class="top-bar">
                <div class="page-title">机器人总览</div>
                <div class="top-actions">
                    <button class="btn btn-secondary btn-sm" id="refreshBtn"><i class="fas fa-sync-alt"></i> 刷新</button>
                    <button class="btn btn-primary btn-sm" id="addBtn"><i class="fas fa-plus"></i> 添加</button>
                </div>
            </div>

            <div class="container">
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">🤖 机器人总数</div>
                        <div class="stat-value" id="sTotal">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">💬 今日群聊</div>
                        <div class="stat-value" id="sGroup">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">✉️ 今日私聊</div>
                        <div class="stat-value" id="sPrivate">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">➕ 今日加群</div>
                        <div class="stat-value" id="sJoin">0</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">💰 余额</div>
                        <div class="stat-value balance" id="sBalance">¥0.00</div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title"><i class="fas fa-robot"></i> 我的机器人</div>
                        <div class="section-subtitle" id="botCountInfo">共 0 个</div>
                    </div>
                    <div class="bots-grid" id="botsGrid"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i> 加载中...</div></div>
                </div>
            </div>
        </main>
    </div>

    <!-- 添加机器人模态框 -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>添加机器人</h3>
                <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form id="addForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>AppID</label>
                        <input type="text" class="form-control" id="fAppid" required placeholder="机器人 AppID">
                    </div>
                    <div class="form-group">
                        <label>Secret</label>
                        <input type="text" class="form-control" id="fSecret" required placeholder="机器人 Secret">
                    </div>
                    <div class="form-group">
                        <label>环境</label>
                        <select class="form-select" id="fEnv">
                            <option value="正式">正式环境</option>
                            <option value="沙箱">沙箱环境</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">取消</button>
                    <button type="submit" class="btn btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal" id="delModal">
        <div class="modal-content" style="max-width:360px;">
            <div class="modal-header">
                <h3>确认删除</h3>
                <button class="close-btn" onclick="closeModal('delModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align:center;padding:24px;">
                <i class="fas fa-exclamation-triangle" style="font-size:28px;color:var(--danger);margin-bottom:10px;display:block;"></i>
                <p>确定删除该机器人？</p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">此操作不可恢复</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('delModal')">取消</button>
                <button class="btn btn-danger" id="confirmDelBtn">确认删除</button>
            </div>
        </div>
    </div>

    <!-- 聊天记录选择模态框 -->
    <div class="modal" id="chatModal">
        <div class="modal-content" style="max-width:420px;">
            <div class="modal-header">
                <h3>选择机器人查看聊天</h3>
                <button class="close-btn" onclick="closeModal('chatModal')">&times;</button>
            </div>
            <div class="modal-body" id="chatBotList" style="max-height:50vh;overflow-y:auto;"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('chatModal')">取消</button>
            </div>
        </div>
    </div>

    <!-- 卡密充值模态框 -->
    <div class="modal" id="cardKeyModal">
        <div class="modal-content" style="max-width:400px;">
            <div class="modal-header">
                <h3><i class="fas fa-key" style="color:#16a34a;margin-right:6px;"></i>卡密充值</h3>
                <button class="close-btn" onclick="closeModal('cardKeyModal')">&times;</button>
            </div>
            <form id="cardKeyForm">
                <div class="modal-body">
                    <div class="form-group" style="text-align:center;margin-bottom:8px;">
                        <i class="fas fa-gift" style="font-size:36px;color:var(--primary);margin-bottom:12px;display:block;"></i>
                        <p style="font-size:13px;color:var(--text-sub);">请输入您的充值卡密</p>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control cardkey-input" id="cardKeyInput" placeholder="请输入卡密" required autocomplete="off" maxlength="32">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('cardKeyModal')">取消</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> 兑换</button>
                </div>
            </form>
        </div>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
    let bots = [], delTarget = null;

    function esc(s) { if (!s) return ''; return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }

    // 加载机器人列表
    async function loadBots() {
        const grid = document.getElementById('botsGrid');
        if (!bots.length) grid.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>';
        try {
            const res = await fetch('api/info.php?type=list');
            const data = await res.json();
            bots = Array.isArray(data) ? data : [];
            renderStats();
            renderBots();
        } catch(e) {
            // 加载失败时显示已有缓存数据
            if (!bots.length) grid.innerHTML = '<div class="empty-state">加载失败，请刷新重试</div>';
            toast('加载失败', false);
        }
    }

    // 加载会员信息
    async function loadMembership() {
        try {
            const res = await fetch('api/membership.php?type=info');
            const data = await res.json();
            if (data && data.balance !== undefined) {
                document.getElementById('sBalance').textContent = '¥' + Number(data.balance).toFixed(2);
                document.getElementById('sidebarBalance').querySelector('span').textContent = '¥' + Number(data.balance).toFixed(2);
                if (data.expires_at) {
                    document.getElementById('sidebarExpire').innerHTML = '<i class="fas fa-clock"></i> 到期: ' + esc(data.expires_at);
                }
            }
        } catch(e) { /* ignore */ }
    }

    function renderStats() {
        let g=0, p=0, j=0;
        bots.forEach(b => {
            g += Number(b.data?.群聊 || 0);
            p += Number(b.data?.私聊 || 0);
            j += Number(b.data?.加群 || 0);
        });
        document.getElementById('sTotal').textContent = bots.length;
        document.getElementById('sGroup').textContent = g;
        document.getElementById('sPrivate').textContent = p;
        document.getElementById('sJoin').textContent = j;
        const countEl = document.getElementById('botCountInfo');
        if (countEl) countEl.textContent = '共 ' + bots.length + ' 个';
    }

    function renderBots() {
        const grid = document.getElementById('botsGrid');
        if (!bots.length) {
            grid.innerHTML = '<div class="empty-state">暂无机器人，点击"添加机器人"开始</div>';
            return;
        }
        grid.innerHTML = bots.map(b => {
            const exp = b.expires ? new Date(b.expires) : null;
            const now = new Date();
            const expired = exp && exp < now;
            const daysLeft = exp ? Math.max(0, Math.ceil((exp - now) / 86400000)) : null;
            return `
            <div class="bot-card${expired?' bot-expired':''}">
                <div class="bot-card-top">
                    <div class="bot-header">
                        ${b.avatar ? `<img src="${esc(b.avatar)}" class="bot-avatar" onerror="this.parentElement.querySelector('.avatar-fallback').style.display='flex';this.style.display='none'">` : ''}
                        <div class="avatar-fallback" ${b.avatar ? 'style="display:none"' : ''}>${esc((b.name||'?')[0])}</div>
                        <div>
                            <div class="bot-name">${esc(b.name||'未命名')}</div>
                            <div class="bot-id">ID: ${esc(b.appid)}</div>
                        </div>
                    </div>
                    <div class="bot-status-badge ${expired?'expired':'active'}">${expired?'已到期':'运行中'}</div>
                </div>
                ${expired?`<div class="expired-banner" style="margin:8px 0;padding:8px 12px;font-size:12px;"><i class="fas fa-exclamation-triangle"></i> 会员已到期，请续费后继续使用</div>`:''}
                ${daysLeft!==null?`<div class="bot-meta"><i class="fas fa-clock"></i> 剩余 <b>${daysLeft}</b> 天 · ${esc(b.type||'正式环境')}</div>`:`<div class="bot-meta"><i class="fas fa-cube"></i> ${esc(b.type||'正式环境')}</div>`}
                <div class="bot-stats">
                    <div class="bot-stat"><div class="num">${b.data?.群聊||0}</div><div class="lbl">群聊</div></div>
                    <div class="bot-stat"><div class="num">${b.data?.私聊||0}</div><div class="lbl">私聊</div></div>
                    <div class="bot-stat"><div class="num">${b.data?.加群||0}</div><div class="lbl">加群</div></div>
                </div>
                ${b.callback?`<div class="bot-callback">
                    <div class="url-row"><span class="url-text">${esc(b.callback)}</span><button class="url-copy" onclick="navigator.clipboard.writeText('${esc(b.callback||'')}');toast('已复制',true)"><i class="fas fa-copy"></i></button></div>
                </div>`:''}
                <div class="bot-actions">
                    <a href="plugin.php?appid=${encodeURIComponent(b.appid)}" class="btn btn-secondary btn-sm"><i class="fas fa-puzzle-piece"></i> 插件</a>
                    <a href="chat.php?appid=${encodeURIComponent(b.appid)}" class="btn btn-secondary btn-sm"><i class="fas fa-comments"></i> 日志</a>
                    <button class="btn btn-danger btn-sm del-btn" data-id="${esc(b.appid)}"><i class="fas fa-trash"></i></button>
                </div>
            </div>`;
        }).join('');
        grid.querySelectorAll('.del-btn').forEach(btn => {
            btn.onclick = () => { delTarget = btn.dataset.id; openModal('delModal'); };
        });
    }

    // 添加机器人
    document.getElementById('addForm').onsubmit = async e => {
        e.preventDefault();
        const appid = document.getElementById('fAppid').value.trim();
        const secret = document.getElementById('fSecret').value.trim();
        const env = document.getElementById('fEnv').value;
        if (!appid || !secret) { toast('请填写完整', false); return; }
        try {
            const res = await fetch(`api/bot.php?type=add&appid=${encodeURIComponent(appid)}&secret=${encodeURIComponent(secret)}&environment=${encodeURIComponent(env)}`);
            const data = await res.json();
            if (data.code === 200) { toast('添加成功', true); closeModal('addModal'); document.getElementById('addForm').reset(); loadBots(); }
            else toast(data.msg || '添加失败', false);
        } catch(e) { toast('网络错误', false); }
    };

    // 删除机器人
    document.getElementById('confirmDelBtn').onclick = async () => {
        if (!delTarget) return;
        try {
            const res = await fetch(`api/bot.php?type=del&appid=${encodeURIComponent(delTarget)}`);
            const data = await res.json();
            if (data.code === 200) { toast('删除成功', true); closeModal('delModal'); loadBots(); }
            else toast(data.msg || '删除失败', false);
        } catch(e) { toast('网络错误', false); }
        delTarget = null;
    };

    // 卡密充值
    document.getElementById('cardKeyForm').onsubmit = async e => {
        e.preventDefault();
        const code = document.getElementById('cardKeyInput').value.trim();
        if (!code) { toast('请输入卡密', false); return; }
        try {
            const fd = new FormData();
            fd.append('code', code);
            const res = await fetch('api/cardkey.php?type=redeem', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.code === 200) {
                toast('充值成功！', true);
                closeModal('cardKeyModal');
                document.getElementById('cardKeyInput').value = '';
                loadMembership();
                loadBots();
            } else {
                toast(data.msg || '充值失败', false);
            }
        } catch(e) { toast('网络错误', false); }
    };

    // 导航
    document.getElementById('addBtn').onclick = () => openModal('addModal');
    document.getElementById('navAddBot').onclick = e => { e.preventDefault(); openModal('addModal'); };
    document.getElementById('navCardKey').onclick = e => { e.preventDefault(); openModal('cardKeyModal'); };
    document.getElementById('navChatLogs').onclick = e => {
        e.preventDefault();
        const box = document.getElementById('chatBotList');
        if (!bots.length) { box.innerHTML = '<div class="empty-state">暂无机器人</div>'; }
        else {
            box.innerHTML = bots.map(b => `<a href="chat.php?appid=${encodeURIComponent(b.appid)}" style="display:flex;align-items:center;gap:10px;padding:10px;border:1px solid var(--border);border-radius:8px;text-decoration:none;color:inherit;margin-bottom:8px;"><div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0;">${esc((b.name||'?')[0])}</div><div style="min-width:0;"><div style="font-weight:600;">${esc(b.name||'未命名')}</div><div style="font-size:12px;color:var(--text-muted);">${esc(b.appid)}</div></div></a>`).join('');
        }
        openModal('chatModal');
    };

    // Init
    loadBots();
    loadMembership();
</script>
</body>
</html>
