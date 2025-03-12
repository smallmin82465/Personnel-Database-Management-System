<?php
// 啟動 session
session_start();

// 檢查用戶是否已登入
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 引入配置文件
require_once 'config.php';

// 初始化變數
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 獲取表單數據
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 基本驗證
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "所有欄位都必須填寫";
    } elseif (strlen($new_password) < 6) {
        $error = "新密碼必須至少包含6個字符";
    } elseif ($new_password !== $confirm_password) {
        $error = "新密碼與確認密碼不匹配";
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
            
            // 查詢當前用戶密碼
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // 驗證當前密碼
                if (password_verify($current_password, $user['password'])) {
                    // 當前密碼正確，更新為新密碼
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $success = "密碼已成功更新！";
                    } else {
                        $error = "密碼更新失敗: " . $update_stmt->error;
                    }
                    
                    $update_stmt->close();
                } else {
                    $error = "當前密碼不正確";
                }
            } else {
                $error = "找不到用戶資料";
            }
            
            // 關閉資料庫連線
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $error = "系統錯誤: " . $e->getMessage();
            error_log("密碼更新錯誤: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密碼 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/changepassword.css">
</head>
<body>
    <div class="container password-container">
        <h1>修改密碼</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="passwordForm">
            <div class="form-group">
                <label for="current_password">當前密碼</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">新密碼</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-strength-label" id="strengthLabel">密碼強度</div>
                
                <div class="password-requirements">
                    <div class="password-requirement" id="length">✓ 至少6個字符</div>
                    <div class="password-requirement" id="number">✓ 至少包含1個數字 (建議)</div>
                    <div class="password-requirement" id="uppercase">✓ 至少包含1個大寫字母 (選擇性)</div>
                    <div class="password-requirement" id="special">✓ 至少包含1個特殊字符 (選擇性)</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">確認新密碼</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="btn">更新密碼</button>
            
            <div class="action-buttons">
                <a href="login_success.php" class="btn btn-secondary">返回儀表板</a>
            </div>
        </form>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strengthBar');
            const strengthLabel = document.getElementById('strengthLabel');
            const lengthReq = document.getElementById('length');
            const numberReq = document.getElementById('number');
            const uppercaseReq = document.getElementById('uppercase');
            const specialReq = document.getElementById('special');
            
            // 密碼強度檢查函數
            newPasswordInput.addEventListener('input', function() {
                const password = newPasswordInput.value;
                let strength = 0;
                
                // 長度檢查 - 必須滿足
                if (password.length >= 6) {
                    lengthReq.classList.add('valid');
                    strength += 40; // 給予更多的權重
                } else {
                    lengthReq.classList.remove('valid');
                }
                
                // 數字檢查 - 建議性質
                if (/\d/.test(password)) {
                    numberReq.classList.add('valid');
                    strength += 30;
                } else {
                    numberReq.classList.remove('valid');
                }
                
                // 大寫字母檢查 - 選擇性
                if (/[A-Z]/.test(password)) {
                    uppercaseReq.classList.add('valid');
                    strength += 15;
                } else {
                    uppercaseReq.classList.remove('valid');
                }
                
                // 特殊字符檢查 - 選擇性
                if (/[^A-Za-z0-9]/.test(password)) {
                    specialReq.classList.add('valid');
                    strength += 15;
                } else {
                    specialReq.classList.remove('valid');
                }
                
                // 更新強度條和標籤
                strengthBar.style.width = strength + '%';
                
                if (password.length < 6) {
                    strengthBar.style.backgroundColor = '#f44336';
                    strengthLabel.textContent = '太短';
                } else if (strength <= 40) {
                    strengthBar.style.backgroundColor = '#f44336';
                    strengthLabel.textContent = '弱';
                } else if (strength <= 70) {
                    strengthBar.style.backgroundColor = '#ff9800';
                    strengthLabel.textContent = '中等';
                } else if (strength <= 85) {
                    strengthBar.style.backgroundColor = '#8bc34a';
                    strengthLabel.textContent = '強';
                } else {
                    strengthBar.style.backgroundColor = '#4caf50';
                    strengthLabel.textContent = '非常強';
                }
            });
            
            // 密碼確認檢查
            confirmPasswordInput.addEventListener('input', function() {
                if (newPasswordInput.value === confirmPasswordInput.value) {
                    confirmPasswordInput.style.borderColor = '#4CAF50';
                } else {
                    confirmPasswordInput.style.borderColor = '#f44336';
                }
            });
            
            // 表單提交前的驗證
            document.getElementById('passwordForm').addEventListener('submit', function(event) {
                // 檢查密碼長度
                if (newPasswordInput.value.length < 6) {
                    event.preventDefault();
                    alert('新密碼必須至少包含6個字符！');
                    return;
                }
                
                // 檢查是否包含數字
                if (!/\d/.test(newPasswordInput.value)) {
                    const proceed = confirm('您的密碼未包含數字，這可能不夠安全。是否仍要繼續？');
                    if (!proceed) {
                        event.preventDefault();
                        return;
                    }
                }
                
                // 確認密碼匹配
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                    alert('新密碼與確認密碼不匹配！');
                }
            });
            
            // 成功訊息淡出效果
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>