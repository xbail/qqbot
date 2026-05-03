<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$user = users_verify_token($token);
if ($user) {
    $isAdmin = ($user['role'] ?? '') === 'admin';
    header("Location: " . ($isAdmin ? 'admin.php' : 'main.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>QQ官机云平台 · 登录</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #1a2c34;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
            border: 1px solid #e9ecef;
            overflow: hidden;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .login-header {
            padding: 28px 28px 0;
            text-align: center;
        }

        .login-logo {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2c6b9e, #1a4d7a);
            color: white;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }

        .login-header h1 {
            font-size: 22px;
            font-weight: 600;
            color: #1a2c34;
            letter-spacing: -0.3px;
        }

        .login-header p {
            font-size: 13px;
            color: #6c7a8a;
            margin-top: 4px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            margin: 24px 28px 0;
            border-bottom: 2px solid #eef2f5;
            position: relative;
        }

        .tab-btn {
            flex: 1;
            padding: 10px 0;
            font-size: 14px;
            font-weight: 500;
            color: #6c7a8a;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.2s;
            font-family: inherit;
            position: relative;
        }

        .tab-btn:hover {
            color: #2c6b9e;
        }

        .tab-btn.active {
            color: #2c6b9e;
            font-weight: 600;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 20%;
            right: 20%;
            height: 2px;
            background: #2c6b9e;
            border-radius: 1px;
        }

        /* Form */
        .login-body {
            padding: 24px 28px 32px;
        }

        .form-panel {
            display: none;
        }

        .form-panel.active {
            display: block;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #2c3e44;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dce3e9;
            border-radius: 8px;
            background: #ffffff;
            transition: all 0.15s ease;
            font-family: inherit;
            color: #1a2c34;
        }

        .form-control:focus {
            outline: none;
            border-color: #2c6b9e;
            box-shadow: 0 0 0 3px rgba(44, 107, 158, 0.1);
        }

        .form-control::placeholder {
            color: #9aaebf;
        }

        .btn {
            width: 100%;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            font-family: inherit;
            border: none;
        }

        .btn-primary {
            background: #2c6b9e;
            color: white;
        }

        .btn-primary:hover {
            background: #235b87;
        }

        .btn-primary:active {
            background: #1d4e75;
        }

        .btn-primary:disabled {
            background: #a0b8cc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #f0f2f5;
            color: #4a5b6e;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e6e9ef;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-group .btn {
            width: auto;
            flex: 1;
        }

        .message {
            margin-top: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            display: none;
            line-height: 1.5;
        }

        .message.error {
            display: block;
            background: #fef2f0;
            color: #c23d2e;
            border: 1px solid #ffe0db;
        }

        .message.success {
            display: block;
            background: #eef6ec;
            color: #2c6e2c;
            border: 1px solid #d4e6d1;
        }

        .login-footer {
            padding: 14px 28px;
            border-top: 1px solid #eef2f5;
            font-size: 12px;
            color: #8a99a8;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
                align-items: flex-start;
                padding-top: 48px;
            }

            .login-card {
                border-radius: 12px;
            }

            .login-header {
                padding: 24px 20px 0;
            }

            .tabs {
                margin: 20px 20px 0;
            }

            .login-body {
                padding: 20px 20px 28px;
            }

            .login-footer {
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo"><i class="fas fa-robot"></i></div>
            <h1>QQ官机云平台</h1>
            <p>多用户后台管理系统</p>
        </div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="login">登录</button>
            <button class="tab-btn" data-tab="register">注册</button>
        </div>

        <div class="login-body">
            <!-- 登录面板 -->
            <div class="form-panel active" id="panel-login">
                <form id="loginForm">
                    <div class="form-group">
                        <label for="login-username">账号</label>
                        <input type="text" class="form-control" id="login-username" name="username" placeholder="请输入账号" autocomplete="username" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">密码</label>
                        <input type="password" class="form-control" id="login-password" name="password" placeholder="请输入密码" autocomplete="current-password" required>
                    </div>
                    <div id="login-msg" class="message"></div>
                    <div class="btn-group">
                        <button type="reset" class="btn btn-secondary">清空</button>
                        <button type="submit" class="btn btn-primary" id="loginBtn">登录</button>
                    </div>
                </form>
            </div>

            <!-- 注册面板 -->
            <div class="form-panel" id="panel-register">
                <form id="registerForm">
                    <div class="form-group">
                        <label for="reg-username">账号</label>
                        <input type="text" class="form-control" id="reg-username" name="username" placeholder="字母、数字、下划线或中文，2-20个字符" autocomplete="username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password">密码</label>
                        <input type="password" class="form-control" id="reg-password" name="password" placeholder="至少6个字符" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password2">确认密码</label>
                        <input type="password" class="form-control" id="reg-password2" name="password2" placeholder="再次输入密码" autocomplete="new-password" required>
                    </div>
                    <div id="reg-msg" class="message"></div>
                    <div class="btn-group">
                        <button type="reset" class="btn btn-secondary">清空</button>
                        <button type="submit" class="btn btn-primary" id="regBtn">注册</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer">
            <a href="../index.php" style="color:#2c6b9e;text-decoration:none;font-weight:500;"><i class="fas fa-arrow-left"></i> 返回首页</a>
            <span style="margin:0 8px;color:#dce3e9;">|</span>
            QQ官机云平台 · 支持多用户
        </div>
    </div>

    <script>
        // ============ Tab 切换 ============
        const tabBtns = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.form-panel');

        tabBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                tabBtns.forEach(function(b) { b.classList.remove('active'); });
                panels.forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById('panel-' + target).classList.add('active');
                clearAllMessages();
            });
        });

        // ============ 工具函数 ============
        function showMessage(id, text, type) {
            var el = document.getElementById(id);
            el.className = 'message ' + type;
            el.textContent = text;
        }

        function clearMessage(id) {
            var el = document.getElementById(id);
            el.className = 'message';
            el.textContent = '';
        }

        function clearAllMessages() {
            clearMessage('login-msg');
            clearMessage('reg-msg');
        }

        function setCookie(name, value, days) {
            var expires = new Date();
            expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
        }

        // ============ 登录 ============
        var loginForm = document.getElementById('loginForm');
        var loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessage('login-msg');

            var username = document.getElementById('login-username').value.trim();
            var password = document.getElementById('login-password').value.trim();

            if (!username || !password) {
                showMessage('login-msg', '请输入账号和密码', 'error');
                return;
            }

            loginBtn.disabled = true;
            loginBtn.textContent = '登录中...';

            try {
                var formData = new FormData();
                formData.append('type', 'login');
                formData.append('username', username);
                formData.append('password', password);

                var response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                var data = await response.json();

                if (data.code === 200) {
                    if (data.token) {
                        setCookie('admin_token', data.token, 30);
                    }
                    showMessage('login-msg', data.msg || '登录成功，正在跳转...', 'success');
                    var redirectUrl = (data.role === 'admin') ? 'admin.php' : 'main.php';
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 600);
                } else {
                    showMessage('login-msg', data.msg || '账号或密码错误', 'error');
                    loginBtn.disabled = false;
                    loginBtn.textContent = '登录';
                }
            } catch (error) {
                showMessage('login-msg', '网络请求失败，请稍后重试', 'error');
                loginBtn.disabled = false;
                loginBtn.textContent = '登录';
            }
        });

        // ============ 注册 ============
        var registerForm = document.getElementById('registerForm');
        var regBtn = document.getElementById('regBtn');

        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            clearMessage('reg-msg');

            var username = document.getElementById('reg-username').value.trim();
            var password = document.getElementById('reg-password').value.trim();
            var password2 = document.getElementById('reg-password2').value.trim();

            if (!username || !password) {
                showMessage('reg-msg', '请输入账号和密码', 'error');
                return;
            }

            if (password !== password2) {
                showMessage('reg-msg', '两次输入的密码不一致', 'error');
                return;
            }

            if (password.length < 6) {
                showMessage('reg-msg', '密码至少需要6个字符', 'error');
                return;
            }

            regBtn.disabled = true;
            regBtn.textContent = '注册中...';

            try {
                var formData = new FormData();
                formData.append('type', 'register');
                formData.append('username', username);
                formData.append('password', password);

                var response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                var data = await response.json();

                if (data.code === 200) {
                    if (data.token) {
                        setCookie('admin_token', data.token, 30);
                    }
                    showMessage('reg-msg', data.msg || '注册成功，正在跳转...', 'success');
                    setTimeout(function() {
                        window.location.href = 'main.php';
                    }, 600);
                } else {
                    showMessage('reg-msg', data.msg || '注册失败', 'error');
                    regBtn.disabled = false;
                    regBtn.textContent = '注册';
                }
            } catch (error) {
                showMessage('reg-msg', '网络请求失败，请稍后重试', 'error');
                regBtn.disabled = false;
                regBtn.textContent = '注册';
            }
        });

                // 页面加载时聚焦
        document.getElementById('login-username').focus();
    </script>
</body>
</html>
