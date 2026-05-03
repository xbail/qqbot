<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) {
    header('Location: index.php');
    exit();
}
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQ官机云平台 · 账号设置</title>
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
                <p>机器人管理后台</p>
            </div>
            <nav class="sidebar-nav">
                <a href="main.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="set.php" class="nav-item active"><i class="fas fa-user-cog"></i> 账号设置</a>
                <a href="doc.php" class="nav-item"><i class="fas fa-file-alt"></i> 开发文档</a>
                <?php if ($isAdmin): ?>
                <a href="users.php" class="nav-item"><i class="fas fa-users-cog"></i> 用户管理</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div style="margin-bottom:6px;"><i class="fas fa-user"></i> <?= htmlspecialchars($currentUser['username'] ?? '未知') ?>（<?= $isAdmin ? '超管' : '用户' ?>）</div>
                <a href="api/logout.php" style="color:var(--danger);text-decoration:none;font-size:11px;"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">账号设置</div>
                <a href="main.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> 返回后台</a>
            </div>

            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h2>修改密码</h2>
                        <p>修改当前账号（<?= htmlspecialchars($currentUser['username'] ?? '') ?>）的登录密码</p>
                    </div>
                    <div class="card-body">
                        <div id="message" class="message"></div>
                        <form id="settingsForm">
                            <div class="form-group">
                                <label>当前密码</label>
                                <input type="password" class="form-control" id="oldPassword" placeholder="请输入当前密码" required>
                            </div>
                            <div class="form-group">
                                <label>新密码</label>
                                <input type="password" class="form-control" id="newPassword" placeholder="至少6个字符" required>
                            </div>
                            <div class="form-group">
                                <label>确认新密码</label>
                                <input type="password" class="form-control" id="newPassword2" placeholder="再次输入新密码" required>
                            </div>
                            <div class="actions">
                                <button type="button" id="resetBtn" class="btn btn-secondary">清空</button>
                                <button type="submit" id="submitBtn" class="btn btn-primary"><i class="fas fa-save"></i> 保存修改</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>说明</h2>
                    </div>
                    <div class="card-body">
                        <div class="tips">
                            <div class="tip"><strong>保存后生效</strong><p>提交成功后，后续登录会使用新密码。</p></div>
                            <div class="tip"><strong>建议先记下来</strong><p>改密码前先把新密码记好，免得改完自己忘了。</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="notification" class="notification"></div>

    <script src="assets/common.js"></script>
    <script>
        const form = document.getElementById('settingsForm');
        const messageBox = document.getElementById('message');
        const submitBtn = document.getElementById('submitBtn');
        const resetBtn = document.getElementById('resetBtn');

        function showMsg(text, type) {
            messageBox.className = 'message ' + type;
            messageBox.textContent = text;
        }

        resetBtn.addEventListener('click', () => { form.reset(); messageBox.className = 'message'; });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            messageBox.className = 'message';
            const oldPwd = document.getElementById('oldPassword').value.trim();
            const newPwd = document.getElementById('newPassword').value.trim();
            const newPwd2 = document.getElementById('newPassword2').value.trim();

            if (!oldPwd || !newPwd) {
                showMsg('请填写完整信息', 'error');
                return;
            }
            if (newPwd.length < 6) {
                showMsg('新密码至少需要6个字符', 'error');
                return;
            }
            if (newPwd !== newPwd2) {
                showMsg('两次输入的新密码不一致', 'error');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 保存中...';
            try {
                const formData = new FormData();
                formData.append('type', 'change_password');
                formData.append('old_password', oldPwd);
                formData.append('new_password', newPwd);
                const res = await fetch('api/settings.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.code === 200) {
                    showMsg(data.msg || '密码修改成功', 'success');
                    form.reset();
                } else {
                    showMsg(data.msg || '修改失败', 'error');
                }
            } catch (err) {
                showMsg('请求失败：' + err.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> 保存修改';
            }
        });
    </script>
    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>
</body>
</html>
