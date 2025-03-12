<?php
// 啟動 session
session_start();

// 如果用戶已登入，跳轉到儀表板
if (isset($_SESSION['user_id'])) {
    header("Location: login_success.php");
    exit;
}

// 引入配置文件
require_once 'config.php';

// 初始化變數
$error = '';
$username = '';

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取表單數據
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // 基本驗證
    if (empty($username) || empty($password)) {
        $error = "請輸入用戶名和密碼";
    } else {
        try {
            // 連接資料庫
            $conn = new mysqli(
                $DatabaseInfo["host"],
                $DatabaseInfo["username"],
                $DatabaseInfo["password"],
                $DatabaseInfo["database"],
                $DatabaseInfo["port"]
            );
            
            // 檢查連線
            if ($conn->connect_error) {
                throw new Exception("資料庫連線失敗: " . $conn->connect_error);
            }
            
            // 設定字符集
            $conn->set_charset("utf8mb4");
            
            // 準備 SQL 查詢 - 允許使用用戶名或電子郵件登入
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // 驗證密碼
                if (password_verify($password, $user['password'])) {
                    // 密碼正確，創建 session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // 跳轉到登入成功頁面
                    header("Location: login_success.php");
                    exit;
                } else {
                    $error = "密碼不正確";
                }
            } else {
                $error = "找不到該用戶";
            }
            
            // 關閉資料庫連線
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error = "系統錯誤: " . $e->getMessage();
            // 實際開發時可以記錄日誌
            error_log("登入錯誤: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入系統 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/login.css">
</head>
<body>
    <div class="container">
        <h1>登入系統</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">用戶名或電子郵件</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">記住我</label>
            </div>
            
            <button type="submit" class="btn">登入</button>
        </form>
        
        <div class="register-link">
            還沒有帳號？<a href="register.php">立即註冊</a>
        </div>
    </div>
</body>
</html>