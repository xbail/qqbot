<?php
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) {
    header('Location: index.php');
    exit();
}
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$appid = $_GET['appid'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QQ官机云平台 · 插件管理</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CodeMirror loaded on demand -->
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
<?php if ($isAdmin): ?>
                <p><i class="fas fa-shield-alt"></i> 管理员后台</p>
<?php else: ?>
                <p>机器人管理后台</p>
<?php endif; ?>
            </div>
            <nav class="sidebar-nav">
<?php if ($isAdmin): ?>
                <div class="nav-section">管理</div>
                <a href="admin.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="users.php" class="nav-item"><i class="fas fa-users-cog"></i> 用户管理</a>
                <a href="cardkeys.php" class="nav-item"><i class="fas fa-key"></i> 卡密管理</a>
                <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> 价格设置</a>
                <a href="plugin.php" class="nav-item active"><i class="fas fa-puzzle-piece"></i> 插件管理</a>
                <div class="nav-section">切换</div>
                <a href="main.php" class="nav-item"><i class="fas fa-home"></i> 用户中心</a>
<?php else: ?>
                <a href="main.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> 总览</a>
                <a href="set.php" class="nav-item"><i class="fas fa-user-cog"></i> 账号设置</a>
                <a href="plugin.php" class="nav-item active"><i class="fas fa-puzzle-piece"></i> 插件管理</a>
                <a href="doc.php" class="nav-item"><i class="fas fa-file-alt"></i> 开发文档</a>
<?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($currentUser['username'] ?? 'admin') ?></div>
                <a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">插件管理</div>
                <div class="top-actions">
                    <?php if ($isAdmin): ?>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('uploadModal').style.display='flex'"><i class="fas fa-upload"></i> 上传插件</button>
                    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addModal').style.display='flex'"><i class="fas fa-plus"></i> 新建插件</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-label">已启用</div><div class="stat-value" id="enabledCount">0</div></div>
                    <div class="stat-card"><div class="stat-label">未启用</div><div class="stat-value" id="disabledCount">0</div></div>
                    <div class="stat-card"><div class="stat-label">全部插件</div><div class="stat-value" id="allCount">0</div></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>插件列表</h2>
                        <div class="tabs">
                            <div class="tab active" data-tab="enabled">已启用</div>
                            <div class="tab" data-tab="disabled">未启用</div>
                            <div class="tab" data-tab="all">全部</div>
                        </div>
                    </div>
                    <div id="enabledPlugins" class="tab-content active"><div class="plugin-grid" id="enabledList"><div class="empty-state">加载中...</div></div></div>
                    <div id="disabledPlugins" class="tab-content"><div class="plugin-grid" id="disabledList"><div class="empty-state">加载中...</div></div></div>
                    <div id="allPlugins" class="tab-content"><div class="plugin-grid" id="allList"><div class="empty-state">加载中...</div></div></div>
                </div>
            </div>
        </main>
    </div>

    <!-- 上传插件模态框 -->
    <div class="modal" id="uploadModal">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header"><h3>上传插件</h3><button class="close-btn" onclick="this.closest('.modal').style.display='none'">&times;</button></div>
            <div class="modal-body">
                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>点击选择文件或拖拽到这里</p>
                    <small>支持 .php 单文件和 .zip 压缩包（最大5MB）</small>
                </div>
                <input type="file" id="uploadInput" accept=".php,.zip" style="display:none;">
                <div id="uploadFileName" style="margin-top:10px;font-size:13px;color:var(--text-sub);display:none;"></div>
                <div class="upload-result" id="uploadResult"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="document.getElementById('uploadModal').style.display='none'">取消</button>
                <button class="btn btn-primary" id="doUploadBtn" disabled><i class="fas fa-upload"></i> 上传</button>
            </div>
        </div>
    </div>

    <!-- 新建插件模态框 -->
    <div class="modal" id="addModal">
        <div class="modal-content" style="max-width:480px;">
            <div class="modal-header"><h3>新建插件</h3><button class="close-btn" onclick="this.closest('.modal').style.display='none'">&times;</button></div>
            <div class="modal-body">
                <div class="form-group">
                    <label>插件名称</label>
                    <input type="text" class="form-control" id="pluginName" placeholder="输入插件名称（不含 .php）">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="document.getElementById('addModal').style.display='none'">取消</button>
                <button class="btn btn-primary" id="confirmAddBtn">确认创建</button>
            </div>
        </div>
    </div>

    <!-- 编辑插件模态框 -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header"><h3>编辑插件</h3><button class="close-btn" onclick="this.closest('.modal').style.display='none'">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>插件名称</label><input type="text" class="form-control" id="editPluginName" readonly></div>
                <div class="form-group"><label>代码内容</label><div class="code-editor-wrap"><textarea id="pluginContent"></textarea></div></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="document.getElementById('editModal').style.display='none'">取消</button>
                <button class="btn btn-primary" id="savePluginBtn"><i class="fas fa-save"></i> 保存</button>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal" id="confirmModal">
        <div class="modal-content" style="max-width:400px;">
            <div class="modal-header"><h3>确认删除</h3><button class="close-btn" onclick="this.closest('.modal').style.display='none'">&times;</button></div>
            <div class="modal-body" style="text-align:center;">
                <i class="fas fa-exclamation-triangle" style="font-size:32px;color:var(--danger);margin-bottom:12px;display:block;"></i>
                <p>确定要删除插件 <strong id="deletePluginName"></strong> 吗？</p>
                <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">此操作不可恢复</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="document.getElementById('confirmModal').style.display='none'">取消</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">确认删除</button>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

<!-- CodeMirror loaded on demand -->
    <script src="assets/common.js"></script>
    <script>
        const appid = '<?= addslashes($appid) ?>';
        const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
        let currentEditingPlugin = null;
        let deleteTargetPlugin = null;
        let pluginEditor = null;

        let cmLoading = false, cmLoaded = false;
        function loadCodeMirror(callback) {
            if (cmLoaded) { callback(); return; }
            if (cmLoading) return;
            cmLoading = true;
            // CSS
            ['https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.16/codemirror.min.css',
             'https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.16/theme/material-darker.min.css'].forEach(href => {
                const l = document.createElement('link'); l.rel = 'stylesheet'; l.href = href; document.head.appendChild(l);
            });
            // JS
            function loadScript(src, cb) {
                const s = document.createElement('script'); s.src = src; s.onload = cb; document.head.appendChild(s);
            }
            loadScript('https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.16/codemirror.min.js', () => {
                loadScript('https://cdn.bootcdn.net/ajax/libs/codemirror/5.65.16/mode/php/php.min.js', () => {
                    cmLoaded = true; callback();
                });
            });
        }
        function initPluginEditor(callback) {
            if (pluginEditor) { if (callback) callback(); return; }
            loadCodeMirror(() => {
                pluginEditor = CodeMirror.fromTextArea(document.getElementById('pluginContent'), {
                    mode: 'application/x-httpd-php', theme: 'material-darker',
                    lineNumbers: true, lineWrapping: true, indentUnit: 4, tabSize: 4,
                    matchBrackets: true, autoCloseBrackets: true, viewportMargin: Infinity
                });
                if (callback) callback();
            });
        }
        function setPluginContent(v) {
            initPluginEditor(() => {
                if (pluginEditor) pluginEditor.setValue(v || '');
                else document.getElementById('pluginContent').value = v || '';
            });
        }
        function getPluginContent() { return pluginEditor ? pluginEditor.getValue() : document.getElementById('pluginContent').value; }

        function showMsg(text, ok) {
            const el = document.getElementById('notification');
            el.textContent = text; el.className = 'notification ' + (ok ? 'success' : 'error') + ' show';
            setTimeout(() => el.classList.remove('show'), 2500);
        }

        // 文件上传
        const uploadZone = document.getElementById('uploadZone');
        const uploadInput = document.getElementById('uploadInput');
        const uploadFileName = document.getElementById('uploadFileName');
        const doUploadBtn = document.getElementById('doUploadBtn');
        let uploadFile = null;

        uploadZone.addEventListener('click', () => uploadInput.click());
        uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
        uploadZone.addEventListener('drop', e => {
            e.preventDefault(); uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length) { handleFileSelect(e.dataTransfer.files[0]); }
        });
        uploadInput.addEventListener('change', () => { if (uploadInput.files.length) handleFileSelect(uploadInput.files[0]); });

        function handleFileSelect(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'php' && ext !== 'zip') { showMsg('仅支持 .php 和 .zip 文件', false); return; }
            if (file.size > 5 * 1024 * 1024) { showMsg('文件不能超过5MB', false); return; }
            uploadFile = file;
            uploadFileName.textContent = '📎 ' + file.name + ' (' + (file.size / 1024).toFixed(1) + 'KB)';
            uploadFileName.style.display = 'block';
            doUploadBtn.disabled = false;
        }

        doUploadBtn.addEventListener('click', async () => {
            if (!uploadFile) return;
            doUploadBtn.disabled = true; doUploadBtn.textContent = '上传中...';
            const fd = new FormData(); fd.append('plugin_file', uploadFile);
            try {
                const res = await fetch('api/plugin.php?type=upload', { method: 'POST', body: fd });
                const data = await res.json();
                const result = document.getElementById('uploadResult');
                if (data.code === 200) {
                    result.className = 'upload-result success'; result.textContent = data.msg;
                    showMsg('上传成功', true);
                    setTimeout(() => { document.getElementById('uploadModal').style.display = 'none'; result.style.display = 'none'; loadPlugins(); }, 1500);
                } else {
                    result.className = 'upload-result error'; result.textContent = data.msg || '上传失败';
                }
            } catch (e) { showMsg('上传失败', false); }
            doUploadBtn.disabled = false; doUploadBtn.innerHTML = '<i class="fas fa-upload"></i> 上传';
            uploadFile = null;
        });

        // 加载插件
        async function loadPlugins() {
            try {
                const [enabledRes, allRes] = await Promise.all([
                    appid ? fetch('api/plugin.php?type=list&appid=' + encodeURIComponent(appid)).then(r => r.json()) : Promise.resolve({}),
                    fetch('api/plugin.php?type=filelist').then(r => r.json())
                ]);
                if (allRes.code !== 200) throw new Error('加载失败');
                const enabledPlugins = Object.keys(enabledRes || {}).filter(k => enabledRes[k] === true);
                const allPlugins = allRes.data?.list || allRes.list || [];
                const enabled = [], disabled = [];
                allPlugins.forEach(p => enabledPlugins.includes(p) ? enabled.push(p) : disabled.push(p));
                document.getElementById('enabledCount').textContent = enabled.length;
                document.getElementById('disabledCount').textContent = disabled.length;
                document.getElementById('allCount').textContent = allPlugins.length;
                renderPluginList(enabled, 'enabledList', true);
                renderPluginList(disabled, 'disabledList', false);
                renderPluginList(allPlugins, 'allList', null);
            } catch (e) { console.error(e); }
        }

        function renderPluginList(plugins, containerId, isEnabled) {
            const container = document.getElementById(containerId);
            if (!plugins.length) { container.innerHTML = '<div class="empty-state">暂无插件</div>'; return; }
            container.innerHTML = plugins.map(p => `
                <div class="plugin-card">
                    <div class="plugin-header">
                        <div><div class="plugin-name">${esc(p)}</div><div class="plugin-file">${esc(p)}.php</div></div>
                        <span class="badge ${isEnabled===true?'enabled':isEnabled===false?'disabled':''}">${isEnabled===true?'已启用':isEnabled===false?'未启用':'插件'}</span>
                    </div>
                    <div class="plugin-actions">
                        ${isEnabled!==null?(isEnabled?
                            `<button class="btn btn-warning" onclick="togglePlugin('${esc(p)}','close')"><i class="fas fa-toggle-off"></i> 禁用</button>`:
                            `<button class="btn btn-success" onclick="togglePlugin('${esc(p)}','open')"><i class="fas fa-toggle-on"></i> 启用</button>`):''}
                        <button class="btn btn-secondary" onclick="openEditModal('${esc(p)}')"><i class="fas fa-edit"></i> 编辑</button>
                        ${isAdmin?`<button class="btn btn-danger" onclick="openDeleteModal('${esc(p)}')"><i class="fas fa-trash"></i></button>`:''}
                    </div>
                </div>
            `).join('');
        }

        async function togglePlugin(plugin, action) {
            if (!appid) { showMsg('请先从总览选择一个机器人', false); return; }
            try {
                const res = await fetch(`api/plugin.php?type=${action}&appid=${encodeURIComponent(appid)}&name=${encodeURIComponent(plugin)}`);
                const data = await res.json();
                if (data.code === 200) { showMsg(action === 'open' ? '启用成功' : '禁用成功', true); loadPlugins(); }
                else showMsg(data.msg || '操作失败', false);
            } catch (e) { showMsg('操作失败', false); }
        }

        async function openEditModal(plugin) {
            currentEditingPlugin = plugin;
            document.getElementById('editPluginName').value = plugin;
            document.getElementById('pluginContent').value = '加载中...';
            document.getElementById('editModal').style.display = 'flex';
            try {
                const res = await fetch(`api/plugin.php?type=read&name=${encodeURIComponent(plugin)}`);
                const data = await res.json();
                const content = (data.code === 200) ? (data.data?.content || data.msg) : (data.msg || '读取失败');
                setPluginContent(content);
                setTimeout(() => pluginEditor && pluginEditor.refresh(), 150);
            } catch (e) { setPluginContent('读取失败'); }
        }

        document.getElementById('savePluginBtn').addEventListener('click', async () => {
            if (!currentEditingPlugin) return;
            try {
                const res = await fetch(`api/plugin.php?type=write&name=${encodeURIComponent(currentEditingPlugin)}`, {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({content: getPluginContent()})
                });
                const data = await res.json();
                if (data.code === 200) { showMsg('保存成功', true); document.getElementById('editModal').style.display = 'none'; loadPlugins(); }
                else showMsg(data.msg || '保存失败', false);
            } catch (e) { showMsg('保存失败', false); }
        });

        function openDeleteModal(plugin) { deleteTargetPlugin = plugin; document.getElementById('deletePluginName').textContent = plugin; document.getElementById('confirmModal').style.display = 'flex'; }
        document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
            if (!deleteTargetPlugin) return;
            try {
                const res = await fetch(`api/plugin.php?type=delete&name=${encodeURIComponent(deleteTargetPlugin)}`);
                const data = await res.json();
                if (data.code === 200) { showMsg('删除成功', true); document.getElementById('confirmModal').style.display = 'none'; loadPlugins(); }
                else showMsg(data.msg || '删除失败', false);
            } catch (e) { showMsg('删除失败', false); }
            deleteTargetPlugin = null;
        });

        // 新建插件
        document.getElementById('confirmAddBtn').addEventListener('click', async () => {
            const name = document.getElementById('pluginName').value.trim();
            if (!name) { showMsg('请输入插件名称', false); return; }
            try {
                const res = await fetch(`api/plugin.php?type=add&name=${encodeURIComponent(name)}`);
                const data = await res.json();
                if (data.code === 200) { showMsg('创建成功', true); document.getElementById('addModal').style.display = 'none'; document.getElementById('pluginName').value = ''; loadPlugins(); }
                else showMsg(data.msg || '创建失败', false);
            } catch (e) { showMsg('创建失败', false); }
        });

        // Tab 切换
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                document.getElementById(tab.dataset.tab + 'Plugins').classList.add('active');
            });
        });

        loadPlugins();
    </script>
    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>
</body>
</html>
