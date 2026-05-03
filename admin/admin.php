<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) { header('Location: index.php'); exit(); }
if (($currentUser['role'] ?? '') !== 'admin') { header('Location: main.php'); exit(); }

// Fetch stats from SQLite
$totalUsers = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBots = db()->query("SELECT COUNT(*) FROM bots")->fetchColumn();
$unusedCards = db()->query("SELECT COUNT(*) FROM card_keys WHERE status='unused'")->fetchColumn();
$totalRecharge = db()->query("SELECT COALESCE(SUM(amount), 0) FROM card_keys WHERE status='used'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 · QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        <span style="font-weight:600;">后台管理</span>
        <div></div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div class="overlay" id="overlay"></div>

    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>QQ官机云平台</h1>
                <p><i class="fas fa-shield-alt"></i> 管理员后台</p>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">管理</div>
                <a href="admin.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="users.php" class="nav-item"><i class="fas fa-users-cog"></i> 用户管理</a>
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

        <!-- Main Content -->
        <main class="main">
            <div class="top-bar">
                <div class="page-title">系统总览</div>
                <div>
                    <button class="btn btn-secondary btn-sm" onclick="loadUsers()"><i class="fas fa-sync-alt"></i> 刷新</button>
                </div>
            </div>

            <div class="container">
                <!-- Stats -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                        <div class="stat-label">用户总数</div>
                        <div class="stat-value" id="statUsers"><?= intval($totalUsers) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-robot"></i></div>
                        <div class="stat-label">机器人总数</div>
                        <div class="stat-value" id="statBots"><?= intval($totalBots) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-ticket-alt"></i></div>
                        <div class="stat-label">卡密余额</div>
                        <div class="stat-value" id="statCards"><?= intval($unusedCards) ?></div>
                        <div class="stat-sub">未使用卡密</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-coins"></i></div>
                        <div class="stat-label">总充值金额</div>
                        <div class="stat-value" style="color:var(--success);">¥<span id="statRevenue"><?= number_format(floatval($totalRecharge), 2) ?></span></div>
                    </div>
                </div>

                <!-- User Table -->
                <div class="section-card">
                    <div class="section-header">
                        <div class="section-title">用户列表</div>
                        <span style="font-size:12px;color:var(--text-muted);" id="userCount"></span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>角色</th>
                                    <th>余额</th>
                                    <th>机器人</th>
                                    <th>到期时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="userTable">
                                <tr><td colspan="6" class="empty-state">加载中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Membership Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="editModalTitle">编辑会员</h3>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <p style="font-size:13px;color:var(--text-sub);margin-bottom:16px;">
                        用户: <strong id="editUserName"></strong>
                    </p>

                    <div class="form-section-title"><i class="fas fa-wallet"></i> 余额调整</div>
                    <div class="form-group">
                        <label>调整金额（正数充值，负数扣除）</label>
                        <input type="number" step="0.01" class="form-control" id="editAmount" placeholder="例: 100 或 -50">
                    </div>
                    <div class="form-group">
                        <label>调整原因</label>
                        <input type="text" class="form-control" id="editReason" placeholder="例: 充值100元">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="adjustBtn" style="width:100%;justify-content:center;">
                        <i class="fas fa-check"></i> 确认调整余额
                    </button>

                    <hr class="form-divider">

                    <div class="form-section-title"><i class="fas fa-calendar-alt"></i> 到期时间设置</div>
                    <div class="form-group">
                        <label>到期日期</label>
                        <input type="date" class="form-control" id="editExpires">
                        <div class="form-hint">设置后用户到期时间将被覆盖，留空则不修改</div>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm" id="setExpiresBtn" style="width:100%;justify-content:center;">
                        <i class="fas fa-save"></i> 保存到期时间
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">关闭</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width:380px;">
            <div class="modal-header">
                <h3>确认删除</h3>
                <button class="close-btn" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align:center;">
                <i class="fas fa-exclamation-triangle" style="font-size:32px;color:var(--danger);margin-bottom:12px;display:block;"></i>
                <p>确定删除用户 <strong id="deleteUserName"></strong> 吗？</p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">此操作不可恢复</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('deleteModal')">取消</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">确认删除</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
    // ==================== Helpers ====================
    function esc(s) {
        if (!s) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
        }[c]));
    }

    // Close modal on overlay click
    document.addEventListener('click', e => {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    });

    // ==================== State ====================
    let usersData = [];
    let deleteTargetId = null;

    // ==================== Load Users ====================
    async function loadUsers() {
        try {
            const res = await fetch('api/membership.php?type=list');
            const data = await res.json();
            if (data.code === 200 && Array.isArray(data.list)) {
                usersData = data.list;
                renderUsers();
            } else {
                toast(data.msg || '加载失败', false);
            }
        } catch (e) {
            toast('网络错误', false);
            document.getElementById('userTable').innerHTML =
                '<tr><td colspan="6" class="empty-state">加载失败，请刷新重试</td></tr>';
        }
    }

    // ==================== Render Users ====================
    function renderUsers() {
        const tbody = document.getElementById('userTable');
        document.getElementById('userCount').textContent = usersData.length ? '共 ' + usersData.length + ' 个用户' : '';

        if (!usersData.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">暂无用户</td></tr>';
            return;
        }

        tbody.innerHTML = usersData.map(u => {
            const roleBadge = u.role === 'admin'
                ? '<span class="role-badge role-admin"><i class="fas fa-crown"></i> 管理员</span>'
                : '<span class="role-badge role-user">用户</span>';

            const balance = typeof u.balance === 'number' ? u.balance : 0;
            const balanceStr = '¥' + balance.toFixed(2);

            const botCount = u.actual_bot_count ?? u.bot_count ?? 0;

            let expiresHtml = '-';
            if (u.expires_at) {
                const expDate = new Date(u.expires_at);
                const now = new Date();
                if (expDate < now) {
                    expiresHtml = '<span style="color:var(--danger);"><i class="fas fa-times-circle"></i> ' + esc(u.expires_at) + '</span>';
                } else {
                    expiresHtml = '<span style="color:var(--success);"><i class="fas fa-check-circle"></i> ' + esc(u.expires_at) + '</span>';
                }
            }

            const canDelete = u.role !== 'admin';
            const actionsHtml = `
                <div class="actions-cell">
                    <button class="btn btn-warning btn-sm edit-btn" data-id="${esc(u.id)}" data-name="${esc(u.username)}" data-expires="${esc(u.expires_at || '')}">
                        <i class="fas fa-edit"></i> 编辑会员
                    </button>
                    ${canDelete ? `<button class="btn btn-danger btn-sm delete-btn" data-id="${esc(u.id)}" data-name="${esc(u.username)}">
                        <i class="fas fa-trash"></i> 删除
                    </button>` : ''}
                </div>`;

            return `<tr>
                <td><strong>${esc(u.username)}</strong></td>
                <td>${roleBadge}</td>
                <td>${balanceStr}</td>
                <td>${botCount}</td>
                <td>${expiresHtml}</td>
                <td>${actionsHtml}</td>
            </tr>`;
        }).join('');

        // Bind edit buttons
        tbody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.onclick = () => {
                document.getElementById('editUserId').value = btn.dataset.id;
                document.getElementById('editUserName').textContent = btn.dataset.name;
                document.getElementById('editAmount').value = '';
                document.getElementById('editReason').value = '';
                document.getElementById('editExpires').value = btn.dataset.expires || '';
                openModal('editModal');
            };
        });

        // Bind delete buttons
        tbody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.onclick = () => {
                deleteTargetId = btn.dataset.id;
                document.getElementById('deleteUserName').textContent = btn.dataset.name;
                openModal('deleteModal');
            };
        });
    }

    // ==================== Adjust Balance ====================
    document.getElementById('adjustBtn').onclick = async function() {
        const userId = document.getElementById('editUserId').value;
        const amountStr = document.getElementById('editAmount').value.trim();
        const reason = document.getElementById('editReason').value.trim();

        if (!amountStr) {
            toast('请输入调整金额', false);
            return;
        }

        const amount = parseFloat(amountStr);
        if (isNaN(amount) || amount === 0) {
            toast('请输入有效的金额', false);
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';

        try {
            const fd = new FormData();
            fd.append('type', 'admin_adjust');
            fd.append('user_id', userId);
            fd.append('amount', amount);
            fd.append('reason', reason);
            const res = await fetch('api/membership.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.code === 200 || data.ok) {
                toast('余额调整成功', true);
                document.getElementById('editAmount').value = '';
                document.getElementById('editReason').value = '';
                loadUsers();
            } else {
                toast(data.msg || '调整失败', false);
            }
        } catch (e) {
            toast('网络错误', false);
        }

        this.disabled = false;

        this.innerHTML = '<i class="fas fa-check"></i> 确认调整余额';
    };

    // ==================== Set Expires ====================
    document.getElementById('setExpiresBtn').onclick = async function() {
        const userId = document.getElementById('editUserId').value;
        const expires = document.getElementById('editExpires').value || null;

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';

        try {
            const fd = new FormData();
            fd.append('type', 'admin_set_expires');
            fd.append('user_id', userId);
            fd.append('expires', expires || '');
            const res = await fetch('api/membership.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.code === 200 || data.ok) {
                toast('到期时间设置成功', true);
                loadUsers();
            } else {
                toast(data.msg || '设置失败', false);
            }
        } catch (e) {
            toast('网络错误', false);
        }

        this.disabled = false;

        this.innerHTML = '<i class="fas fa-check"></i> 确认设置到期时间';
    };

    loadUsers();
</script>
</body>
</html>