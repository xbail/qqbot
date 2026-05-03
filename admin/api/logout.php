<?php
require_once __DIR__ . '/../inc/users.php';

// 清除 cookie
setcookie('admin_token', '', time() - 3600, '/');
header('Location: ../index.php');
exit();
