<?php
// 啟動 session
session_start();

// 清除所有 session 變數
$_SESSION = array();

// 如果有 session cookie，同時清除 cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 銷毀 session
session_destroy();

// 重定向到登入頁面
header("Location: index.php");
exit;