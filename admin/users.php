<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) {
    header('Location: index.php');
    exit();
}
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
if (!$isAdmin) {
    header('Location: main.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQ官机云平台 · 用户管理</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span style="font-weight:500;">QQ官机云平台</span>
        <div></div>
    </div>

    <div class="desktop-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>QQ官机云平台</h1>
                <p><i class="fas fa-shield-alt"></i> 管理员后台</p>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">管理</div>
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="users.php" class="nav-item active"><i class="fas fa-users-cog"></i> 用户管理</a>
                <a href="cardkeys.php" class="nav-item"><i class="fas fa-key"></i> 卡密管理</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> 价格设置</a>
                <a href="plugin.php" class="nav-item"><i class="fas fa-puzzle-piece"></i> 插件管理</a>
                <div class="nav-section">切换</div>
                <a href="main.php" class="nav-item"><i class="fas fa-home"></i> 用户中心</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($currentUser['username'] ?? 'admin') ?></div>
                <a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">用户管理</div>
                <div class="top-actions">
                    <button class="btn btn-primary btn-sm" onclick="openModal('addModal')"><i class="fas fa-plus"></i> 新增用户</button>
                </div>
            </div>

            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-label">总用户数</div><div class="stat-value" id="sTotal">-</div></div>
                    <div class="stat-card"><div class="stat-label">管理员</div><div class="stat-value" id="sAdmin">-</div></div>
                    <div class="stat-card"><div class="stat-label">普通用户</div><div class="stat-value" id="sUser">-</div></div>
                    <div class="stat-card"><div class="stat-label">总余额</div><div class="stat-value" id="sBalance">-</div></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>用户列表</h2>
                        <button class="btn btn-secondary btn-sm" onclick="loadUsers()"><i class="fas fa-sync"></i> 刷新</button>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户名</th>
                                    <th>角色</th>
                                    <th>余额</th>
                                    <th>会员到期</th>
                                    <th>机器人</th>
                                    <th>注册时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="userTable">
                                <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 新增用户模态框 -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header"><h3>新增用户</h3><button class="close-btn" onclick="closeModal('addModal')">&times;</button></div>
            <div class="modal-body">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" class="form-control" id="addUsername" placeholder="字母、数字、下划线，2-20个字符">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" class="form-control" id="addPassword" placeholder="至少6个字符">
                </div>
                <div class="form-group">
                    <label>角色</label>
                    <select class="form-control" id="addRole">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('addModal')">取消</button>
                <button class="btn btn-primary" id="addBtn" onclick="doAddUser()">创建</button>
            </div>
        </div>
    </div>

    <!-- 调整余额模态框 -->
    <div class="modal" id="balanceModal">
        <div class="modal-content">
            <div class="modal-header"><h3>调整余额</h3><button class="close-btn" onclick="closeModal('balanceModal')">&times;</button></div>
            <div class="modal-body">
                <div style="margin-bottom:14px;font-size:13px;color:var(--text-sub);">
                    用户: <strong id="balTargetName"></strong>
                    <span style="margin-left:12px;">当前余额: <strong id="balCurrent">¥0.00</strong></span>
                </div>
                <div class="form-group">
                    <label>调整金额（正数增加，负数减少）</label>
                    <input type="number" class="form-control" id="balAmount" step="0.01" placeholder="例: 100 或 -50">
                </div>
                <div class="form-group">
                    <label>备注</label>
                    <input type="text" class="form-control" id="balReason" placeholder="例: 手动充值">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('balanceModal')">取消</button>
                <button class="btn btn-primary" onclick="doAdjustBalance()">确认</button>
            </div>
        </div>
    </div>

    <!-- 重置密码模态框 -->
    <div class="modal" id="resetPwdModal">
        <div class="modal-content">
            <div class="modal-header"><h3>重置密码</h3><button class="close-btn" onclick="closeModal('resetPwdModal')">&times;</button></div>
            <div class="modal-body">
                <div style="margin-bottom:14px;font-size:13px;color:var(--text-sub);">
                    用户: <strong id="resetTargetName"></strong>
                </div>
                <div class="form-group">
                    <label>新密码</label>
                    <input type="password" class="form-control" id="newPassword" placeholder="至少6个字符">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('resetPwdModal')">取消</button>
                <button class="btn btn-primary" onclick="doResetPassword()">确认重置</button>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width:360px;">
            <div class="modal-header"><h3>确认删除</h3><button class="close-btn" onclick="closeModal('deleteModal')">&times;</button></div>
            <div class="modal-body" style="text-align:center;">
                <i class="fas fa-exclamation-triangle" style="font-size:32px;color:var(--danger);margin-bottom:12px;"></i>
                <p>确定要删除用户 <strong id="delTargetName"></strong> 吗？</p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">此操作不可恢复</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">取消</button>
                <button class="btn btn-danger" onclick="doDeleteUser()">确认删除</button>
            </div>
        </div>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
        let users = [];
        let currentUserId = null;

        function toast(msg, ok) {
            const el = document.getElementById('toast');
            el.textContent = msg;
            el.className = 'toast ' + (ok ? 'ok' : 'err') + ' show';
            setTimeout(() => el.classList.remove('show'), 2500);
        }

        function openModal(id) { document.getElementById(id).classList.add('show'); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }

        async function loadUsers() {
            try {
                const res = await fetch('api/users.php');
                const data = await res.json();
                if (data.code === 200) {
                    users = data.list || [];
                    renderTable();
                    renderStats();
                } else {
                    toast(data.msg || '加载失败', false);
                }
            } catch (e) { toast('网络错误', false); }
        }

        function renderStats() {
            document.getElementById('sTotal').textContent = users.length;
            document.getElementById('sAdmin').textContent = users.filter(u => u.role === 'admin').length;
            document.getElementById('sUser').textContent = users.filter(u => u.role === 'user').length;
            const totalBal = users.reduce((s, u) => s + (u.balance || 0), 0);
            document.getElementById('sBalance').textContent = '¥' + totalBal.toFixed(2);
        }

        function renderTable() {
            const tbody = document.getElementById('userTable');
            if (!users.length) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">暂无用户</td></tr>';
                return;
            }
            tbody.innerHTML = users.map(u => {
                const isExpired = u.expires_at && new Date(u.expires_at) < new Date();
                const isActive = u.expires_at && !isExpired;
                const statusBadge = isActive ? '<span class="badge badge-active">会员有效</span>' :
                                    (u.expires_at && isExpired ? '<span class="badge badge-expired">已过期</span>' : '<span class="badge badge-none">未开通</span>');
                const roleBadge = u.role === 'admin' ? '<span class="badge badge-admin">管理员</span>' : '<span class="badge badge-user">用户</span>';
                return `<tr>
                    <td>${u.id}</td>
                    <td><strong>${esc(u.username)}</strong></td>
                    <td>${roleBadge}</td>
                    <td>¥${(u.balance || 0).toFixed(2)}</td>
                    <td>${statusBadge}<br><span style="font-size:11px;color:var(--text-muted);">${u.expires_at || '-'}</span></td>
                    <td>${u.actual_bot_count || 0}</td>
                    <td style="font-size:12px;color:var(--text-muted);">${u.created_at || '-'}</td>
                    <td>
                        <div class="action-btns">
                            <button class="btn btn-primary btn-sm" onclick="openBalance(${u.id},'${esc(u.username)}',${u.balance||0})"><i class="fas fa-coins"></i> 余额</button>
                            <button class="btn btn-secondary btn-sm" onclick="openResetPwd(${u.id},'${esc(u.username)}')"><i class="fas fa-key"></i></button>
                            <button class="btn btn-danger btn-sm" onclick="openDelete(${u.id},'${esc(u.username)}')"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        function esc(s) { return s ? String(s).replace(/'/g, "\\'").replace(/</g, '&lt;') : ''; }

        // 新增用户
        async function doAddUser() {
            const username = document.getElementById('addUsername').value.trim();
            const password = document.getElementById('addPassword').value.trim();
            const role = document.getElementById('addRole').value;
            if (!username || !password) { toast('请填写完整信息', false); return; }
            if (password.length < 6) { toast('密码至少6个字符', false); return; }
            try {
                const fd = new FormData();
                fd.append('type', 'create');
                fd.append('username', username);
                fd.append('password', password);
                fd.append('role', role);
                const res = await fetch('api/users.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.code === 200) {
                    toast('创建成功', true);
                    closeModal('addModal');
                    document.getElementById('addUsername').value = '';
                    document.getElementById('addPassword').value = '';
                    loadUsers();
                } else { toast(data.msg || '创建失败', false); }
            } catch (e) { toast('网络错误', false); }
        }

        // 调整余额
        function openBalance(id, name, balance) {
            currentUserId = id;
            document.getElementById('balTargetName').textContent = name;
            document.getElementById('balCurrent').textContent = '¥' + balance.toFixed(2);
            document.getElementById('balAmount').value = '';
            document.getElementById('balReason').value = '';
            openModal('balanceModal');
        }

        async function doAdjustBalance() {
            const amount = parseFloat(document.getElementById('balAmount').value);
            const reason = document.getElementById('balReason').value.trim();
            if (isNaN(amount) || amount === 0) { toast('请输入有效金额', false); return; }
            try {
                const fd = new FormData();
                fd.append('user_id', currentUserId);
                fd.append('amount', amount);
                fd.append('reason', reason);
                const res = await fetch('api/membership.php?type=admin_adjust', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.code === 200) {
                    toast('余额已调整', true);
                    closeModal('balanceModal');
                    loadUsers();
                } else { toast(data.msg || '调整失败', false); }
            } catch (e) { toast('网络错误', false); }
        }

        // 重置密码
        function openResetPwd(id, name) {
            currentUserId = id;
            document.getElementById('resetTargetName').textContent = name;
            document.getElementById('newPassword').value = '';
            openModal('resetPwdModal');
        }

        async function doResetPassword() {
            const pwd = document.getElementById('newPassword').value.trim();
            if (!pwd || pwd.length < 6) { toast('密码至少6个字符', false); return; }
            try {
                const fd = new FormData();
                fd.append('type', 'reset_password');
                fd.append('user_id', currentUserId);
                fd.append('new_password', pwd);
                const res = await fetch('api/users.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.code === 200) {
                    toast('密码已重置', true);
                    closeModal('resetPwdModal');
                } else { toast(data.msg || '操作失败', false); }
            } catch (e) { toast('网络错误', false); }
        }

        // 删除用户
        function openDelete(id, name) {
            currentUserId = id;
            document.getElementById('delTargetName').textContent = name;
            openModal('deleteModal');
        }

        async function doDeleteUser() {
            try {
                const fd = new FormData();
                fd.append('type', 'delete');
                fd.append('user_id', currentUserId);
                const res = await fetch('api/users.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.code === 200) {
                    toast('删除成功', true);
                    closeModal('deleteModal');
                    loadUsers();
                } else { toast(data.msg || '删除失败', false); }
            } catch (e) { toast('网络错误', false); }
        }

        // 移动端侧边栏
        loadUsers();
    </script>
</body>
</html>
