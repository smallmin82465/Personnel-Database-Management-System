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
$maintenance = true; // 維護模式開關

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['resume_file'])) {
    $file = $_FILES['resume_file'];
    
    if ($maintenance) {
        $success = "您的檔案已成功上傳，但系統目前正在維護中，暫時無法進行解析。";
    } else {
        // 檢查是否有錯誤
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "檔案過大，請上傳小於10MB的檔案。";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "檔案上傳不完整，請重試。";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "請選擇檔案上傳。";
                    break;
                default:
                    $error = "檔案上傳出錯，請重試。";
            }
        } 
        // 檢查檔案類型
        elseif ($file['type'] !== 'application/pdf') {
            $error = "只支援PDF檔案上傳。";
        } 
        else {
            // 這裡會是未來的檔案處理邏輯
            $success = "檔案上傳成功！系統將自動解析內容。";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳104履歷 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/uploadresume.css">

</head>
<body>
    <div class="container resume-container">
        <h1>上傳104履歷</h1>
        
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
        
        <?php if ($maintenance): ?>
            <div class="maintenance-notice">
                <strong>系統通知：</strong> 履歷解析功能目前正在維護中。您仍然可以上傳檔案，但將暫時無法自動解析內容。維護完成後將自動處理所有已上傳的履歷。
            </div>
        <?php endif; ?>
        
        <div class="upload-section">
            <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="upload-area" id="upload_area">
                    <div class="upload-icon">
                        <i class="fa fa-file-pdf-o">📄</i>
                    </div>
                    <p class="upload-instruction">上傳您的104人力銀行履歷（PDF格式）</p>
                    <p class="upload-note">僅支援PDF檔案，檔案大小不得超過10MB</p>
                    <input type="file" id="resume_file" name="resume_file" accept=".pdf" hidden>
                    <button type="button" id="select_file" class="btn btn-select">選擇檔案</button>
                    <p id="file_name" class="file-name"></p>
                </div>
                
                <button type="submit" class="btn btn-upload">上傳履歷</button>
            </form>
        </div>
        
        <div class="action-buttons">
            <a href="login_success.php" class="btn btn-secondary">返回儀表板</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 檔案選擇按鈕功能
            const selectFileBtn = document.getElementById('select_file');
            const fileInput = document.getElementById('resume_file');
            const fileNameDisplay = document.getElementById('file_name');
            const uploadArea = document.getElementById('upload_area');
            
            if (selectFileBtn && fileInput) {
                selectFileBtn.addEventListener('click', function() {
                    fileInput.click();
                });
                
                fileInput.addEventListener('change', function() {
                    if (fileInput.files.length > 0) {
                        fileNameDisplay.textContent = fileInput.files[0].name;
                    } else {
                        fileNameDisplay.textContent = '';
                    }
                });
                
                // 拖放功能
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    uploadArea.style.borderColor = '#4CAF50';
                    uploadArea.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                });
                
                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    uploadArea.style.borderColor = '';
                    uploadArea.style.backgroundColor = '';
                });
                
                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    uploadArea.style.borderColor = '';
                    uploadArea.style.backgroundColor = '';
                    
                    if (e.dataTransfer.files.length > 0) {
                        const file = e.dataTransfer.files[0];
                        const fileExt = file.name.split('.').pop().toLowerCase();
                        
                        if (fileExt === 'pdf') {
                            fileInput.files = e.dataTransfer.files;
                            fileNameDisplay.textContent = file.name;
                        } else {
                            alert('僅支援PDF檔案格式');
                        }
                    }
                });
            }
            
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