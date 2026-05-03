<?php
/**
 * 管理员卡密管理 API
 * 
 * generate — 批量生成卡密
 * list     — 查看卡密列表
 * stats    — 统计数据
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/users.php';

$user = api_auth();
if (!$user) api_err('未登录', 401);
if (($user['role'] ?? '') !== 'admin') api_err('无权限', 403);

$type = $_REQUEST['type'] ?? '';

switch ($type) {

    case 'generate':
        $amount = floatval($_POST['amount'] ?? 0);
        $count  = intval($_POST['count'] ?? 0);
        $batch  = trim($_POST['batch_name'] ?? '');

        if ($amount <= 0 || $amount > 9999) api_err('金额无效');
        if ($count <= 0 || $count > 500) api_err('数量无效（1-500）');

        $codes = cardkey_generate($amount, $count, $batch);
        api_ok("成功生成 {$count} 张卡密", [
            'codes'  => $codes,
            'amount' => $amount,
            'count'  => count($codes),
        ]);
        break;

    case 'list':
        $page   = max(1, intval($_GET['page'] ?? 1));
        $limit  = 50;
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';

        $where = '';
        $params = [];
        if ($status === 'unused' || $status === 'used') {
            $where = 'WHERE status = ?';
            $params[] = $status;
        }

        if ($params) {
            $totalStmt = db()->prepare("SELECT COUNT(*) as cnt FROM card_keys " . $where);
            $totalStmt->execute($params);
        } else {
            $totalStmt = db()->query("SELECT COUNT(*) as cnt FROM card_keys");
        }
        $total = $totalStmt->fetch()['cnt'];

        if ($params) {
            $stmt = db()->prepare("SELECT * FROM card_keys " . $where . " ORDER BY id DESC LIMIT ? OFFSET ?");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
        } else {
            $stmt = db()->prepare("SELECT * FROM card_keys ORDER BY id DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        }
        $rows = $stmt->fetchAll();

        api_ok('ok', [
            'list'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $limit),
        ]);
        break;

    case 'stats':
        $unused = db()->query("SELECT COUNT(*) as cnt FROM card_keys WHERE status='unused'")->fetch()['cnt'];
        $used   = db()->query("SELECT COUNT(*) as cnt FROM card_keys WHERE status='used'")->fetch()['cnt'];
        $totalAmount = db()->query("SELECT COALESCE(SUM(amount),0) as total FROM card_keys WHERE status='used'")->fetch()['total'];

        // 今日充值
        $today = db()->query("SELECT COALESCE(SUM(amount),0) as total FROM card_keys WHERE status='used' AND date(used_at)=date('now','localtime')")->fetch()['total'];

        api_ok('ok', [
            'unused'       => $unused,
            'used'         => $used,
            'total_amount' => floatval($totalAmount),
            'today_amount' => floatval($today),
        ]);
        break;

    default:
        api_err('未知操作');
}
