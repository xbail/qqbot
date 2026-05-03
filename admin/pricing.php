<?php
/**
 * 会员充值页
 * 
 * 模式：¥5/机器人/月，余额充值制
 * 用户通过卡密充值余额，然后用余额开通/续费会员
 * 开通会员后才能添加机器人
 */
require_once __DIR__ . '/inc/users.php';
$token = $_COOKIE['admin_token'] ?? '';
$currentUser = users_verify_token($token);
if (!$currentUser) { header('Location: index.php'); exit(); }
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$membership = users_get_membership($currentUser);
$price = users_get_price_per_bot();
$botCount = intval($membership['bot_count'] ?? 0);
$isMember = $membership['is_valid'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会员充值 · QQ官机云平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="main.php" class="logo">QQ官机云平台</a>
        <a href="main.php" class="back"><i class="fas fa-arrow-left"></i> 返回用户中心</a>
    </div>

    <div class="container">
        <?php if ($isMember): ?>
        <div class="alert alert-info">
            <i class="fas fa-crown" style="color:#f59e0b;"></i>
            <span>会员有效中，到期时间: <?= htmlspecialchars($membership['expires_at'] ?? '未知') ?></span>
        </div>
        <?php elseif ($botCount > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>会员已过期，机器人已暂停运行。请续费后恢复服务。</span>
        </div>
        <?php endif; ?>

        <!-- 流程提示 -->
        <div class="flow-steps">
            <?php
            $hasBalance = floatval($membership['balance'] ?? 0) > 0;
            $step1 = $hasBalance ? 'done' : 'active';
            $step2 = $isMember ? 'done' : ($hasBalance ? 'active' : 'pending');
            $step3 = $botCount > 0 ? 'done' : ($isMember ? 'active' : 'pending');
            ?>
            <div class="flow-step <?= $step1 ?>">
                <span class="num">1</span>
                <span>充值余额</span>
            </div>
            <i class="fas fa-chevron-right flow-arrow"></i>
            <div class="flow-step <?= $step2 ?>">
                <span class="num">2</span>
                <span>开通会员</span>
            </div>
            <i class="fas fa-chevron-right flow-arrow"></i>
            <div class="flow-step <?= $step3 ?>">
                <span class="num">3</span>
                <span>添加机器人</span>
            </div>
        </div>

        <!-- 余额卡片 -->
        <div class="balance-card">
            <div class="label">账户余额</div>
            <div class="amount"><span>¥</span><?= number_format($membership['balance'] ?? 0, 2) ?></div>
            <div class="meta">
                <span><i class="fas fa-robot"></i> 已开通 <?= $botCount ?> 个机器人</span>
                <span><i class="fas fa-clock"></i> 到期 <?= $membership['expires_at'] ?? '未开通' ?></span>
            </div>
        </div>

        <!-- 卡密充值 -->
        <div class="section-title"><i class="fas fa-gift"></i> 卡密充值</div>
        <div class="recharge-card">
            <h3>输入卡密为账户充值</h3>
            <div class="input-group">
                <input type="text" id="cardCode" placeholder="请输入卡密代码" maxlength="32" autocomplete="off">
                <button class="btn btn-primary" id="redeemBtn"><i class="fas fa-check"></i> 兑换</button>
            </div>
            <div class="hint">卡密由管理员生成，请联系管理员购买。充值后金额将直接进入您的账户余额。</div>
        </div>

        <!-- 会员充值/续费 -->
        <div class="section-title">
            <i class="fas fa-crown" style="color:#f59e0b;"></i>
            <?= $botCount > 0 ? '续费会员' : '开通会员' ?>
        </div>
        <div class="recharge-card">
            <h3><?= $botCount > 0 ? '选择续费时长' : '开通会员后即可添加机器人' ?></h3>
            <div class="hint" style="margin-bottom:12px;">
                单价: <strong>¥<?= number_format($price, 2) ?>/机器人/月</strong>
                · 已开通 <strong><?= $botCount ?></strong> 个机器人
                <?php if ($botCount == 0): ?>
                · 新用户默认按1个机器人计算
                <?php endif; ?>
            </div>
            <div class="renew-options" id="renewOptions">
                <div class="renew-option" data-months="1">
                    <div class="months">1</div>
                    <div class="months-label">个月</div>
                    <div class="price">¥<?= number_format($price * max($botCount, 1), 2) ?></div>
                </div>
                <div class="renew-option" data-months="3">
                    <div class="months">3</div>
                    <div class="months-label">个月</div>
                    <div class="price">¥<?= number_format($price * 3 * max($botCount, 1), 2) ?></div>
                    <div class="save">省 ¥<?= number_format($price * max($botCount, 1), 2) ?></div>
                </div>
                <div class="renew-option" data-months="6">
                    <div class="months">6</div>
                    <div class="months-label">个月</div>
                    <div class="price">¥<?= number_format($price * 6 * max($botCount, 1), 2) ?></div>
                    <div class="save">省 ¥<?= number_format($price * 2 * max($botCount, 1), 2) ?></div>
                </div>
                <div class="renew-option" data-months="12">
                    <div class="months">12</div>
                    <div class="months-label">个月</div>
                    <div class="price">¥<?= number_format($price * 12 * max($botCount, 1), 2) ?></div>
                    <div class="save">省 ¥<?= number_format($price * 4 * max($botCount, 1), 2) ?></div>
                </div>
            </div>
            <button class="btn btn-success btn-block" id="renewBtn" style="margin-top:16px;" disabled>
                <i class="fas fa-check"></i> <?= $botCount > 0 ? '确认续费' : '确认开通' ?>
            </button>
        </div>

        <!-- 消费记录 -->
        <div class="section-title"><i class="fas fa-receipt"></i> 消费记录</div>
        <div class="recharge-card">
            <div class="orders-list" id="ordersList">
                <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">加载中...</div>
            </div>
        </div>
    </div>

    <div id="overlay" class="overlay"></div>
    <div id="toast" class="toast"></div>

    <script src="assets/common.js"></script>
    <script>
    const pricePerBot = <?= floatval($price) ?>;
    const botCount = <?= $botCount ?>;
    let selectedMonths = 0;

    // 卡密兑换
    document.getElementById('redeemBtn').onclick = async () => {
        const code = document.getElementById('cardCode').value.trim();
        if (!code) { toast('请输入卡密', false); return; }
        const btn = document.getElementById('redeemBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 兑换中...';
        try {
            const formData = new FormData();
            formData.append('type', 'redeem');
            formData.append('code', code);
            const res = await fetch('api/cardkey.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.code === 200) {
                toast('充值成功: ' + (data.msg || ''), true);
                document.getElementById('cardCode').value = '';
                setTimeout(() => location.reload(), 1000);
            } else {
                toast(data.msg || '兑换失败', false);
            }
        } catch { toast('网络错误', false); }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> 兑换';
    };

    // 续费选项
    document.querySelectorAll('.renew-option').forEach(el => {
        el.onclick = () => {
            document.querySelectorAll('.renew-option').forEach(e => e.classList.remove('selected'));
            el.classList.add('selected');
            selectedMonths = parseInt(el.dataset.months);
            document.getElementById('renewBtn').disabled = false;
        };
    });

    // 确认续费/开通
    const renewBtn = document.getElementById('renewBtn');
    renewBtn.onclick = async () => {
        if (!selectedMonths) { toast('请选择时长', false); return; }
        renewBtn.disabled = true;
        renewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中...';
        try {
            const formData = new FormData();
            formData.append('type', 'purchase');
            formData.append('months', selectedMonths);
            const res = await fetch('api/membership.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.code === 200) {
                toast('开通成功！现在可以去添加机器人了', true);
                setTimeout(() => location.reload(), 1500);
            } else {
                toast(data.msg || '操作失败', false);
            }
        } catch { toast('网络错误', false); }
        renewBtn.disabled = false;
        renewBtn.innerHTML = '<i class="fas fa-check"></i> <?= $botCount > 0 ? "确认续费" : "确认开通" ?>';
    };

    // 加载消费记录
    async function loadOrders() {
        try {
            const res = await fetch('api/membership.php?type=orders');
            const data = await res.json();
            const list = data.orders || data.list || [];
            const box = document.getElementById('ordersList');
            if (!list.length) { box.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px;">暂无消费记录</div>'; return; }
            box.innerHTML = list.map(o => {
                const isPos = o.amount > 0;
                return '<div class="order-item">' +
                    '<div>' +
                        '<div class="type">' + esc(o.description || o.type) + '</div>' +
                        '<div class="time">' + esc(o.created_at || '') + '</div>' +
                    '</div>' +
                    '<div class="amount ' + (isPos ? 'amount-pos' : 'amount-neg') + '">' + (isPos ? '+' : '') + '¥' + Number(o.amount).toFixed(2) + '</div>' +
                '</div>';
            }).join('');
        } catch { }
    }

    function esc(s) { if (!s) return ''; return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }

    loadOrders();
    </script>
</body>
</html>
