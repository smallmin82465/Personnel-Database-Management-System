<?php
// 引入配置文件
require_once 'config.php';

// 初始化變數
$error = '';
$success = '';
$username = '';
$email = '';

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取表單數據
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 基本驗證
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "所有欄位都必須填寫";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "請提供有效的電子郵件";
    } elseif (strlen($password) < 6) {
        $error = "密碼必須至少包含6個字符";
    } elseif ($password !== $confirm_password) {
        $error = "兩次輸入的密碼不匹配";
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
            
            // 檢查用戶名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "該用戶名已被使用";
            } else {
                // 檢查電子郵件是否已存在
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "該電子郵件已被註冊";
                } else {
                    // 密碼加密
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // 插入新用戶
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("sss", $username, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        // 設置註冊成功標誌，用於前端顯示彈出視窗
                        $success = "註冊成功";
                        // 清空表單
                        $username = '';
                        $email = '';
                    } else {
                        $error = "註冊失敗：" . $stmt->error;
                    }
                }
            }
            
            // 關閉資料庫連線
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error = "系統錯誤：" . $e->getMessage();
            // 實際開發時可以記錄日誌
            error_log("註冊錯誤: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新用戶註冊 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/register.css">
</head>
<body>
    <div class="container">
        <h1>新用戶註冊</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- 成功訊息區塊 - 普通顯示 -->
        <?php if (!empty($success)): ?>
            <div class="success-message" style="display: none;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">用戶名</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">電子郵件</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">密碼</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">確認密碼</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="btn">註冊</button>
        </form>
        
        <div class="login-link">
            已有帳號？<a href="login.php">登入</a>
        </div>
    </div>
    
    <!-- 註冊成功彈出視窗 -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">✓</div>
            <h2 class="modal-title">註冊成功</h2>
            <p class="modal-message">您的帳號已成功註冊</p>
            <p class="modal-timer"><span id="countdown">2</span> 秒後自動前往登入頁面</p>
        </div>
    </div>
    
    <?php if (!empty($success)): ?>
    <script>
        // 顯示彈出視窗
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('successModal');
            modal.style.display = 'flex';
            
            // 倒數計時
            var seconds = 2;
            var countdownElement = document.getElementById('countdown');
            var timer = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = 'login.php';
                }
            }, 1000);
        });
    </script>
    <?php endif; ?>
</body>
</html>