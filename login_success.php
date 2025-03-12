<?php
// 啟動 session
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 獲取用戶信息
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入成功 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/login_success.css">
</head>
<body>
    <div class="container">
        <h1>登入成功</h1>
        
        <p>歡迎回來，<?php echo htmlspecialchars($username); ?>！您已成功登入系統。</p>
        
        <div class="user-info">
            <p><strong>用戶名：</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>角色：</strong> <?php echo $role === 'admin' ? '管理員' : '一般用戶'; ?></p>
            <p><strong>登入時間：</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></p>
        </div>
        
        <p>接下來您可以進行修改個人資料等操作，或者登出系統。</p>
        
        <div class="btn-container">
            <a href="myprofile.php" class="btn btn-myprofile">修改個人資料</a>
            <a href="uploadresume.php" class="btn btn-uploadresume">上傳104履歷</a>
            <a href="changepassword.php" class="btn btn-changepassword">修改密碼</a>
            <a href="enteradmin.php" class="btn btn-enteradmin">進入後台</a>
            <a href="logout.php" class="btn btn-logout">登出</a>
        </div>
    </div>
</body>
</html>