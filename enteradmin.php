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

// 從配置文件讀取管理員密碼
$config_content = file_get_contents('config.cfg');
preg_match('/\$AdminPassword\s*=\s*"([^"]*)";/', $config_content, $matches);
$admin_password = $matches[1] ?? '';

// 初始化變數
$error = '';
$authenticated = false;
$users = [];
$resumes = [];
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

// 檢查是否已經驗證過管理員密碼
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
    $authenticated = true;
}

// 處理密碼表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_password'])) {
    $input_password = $_POST['admin_password'];
    
    if ($input_password === $admin_password) {
        $_SESSION['admin_authenticated'] = true;
        $authenticated = true;
    } else {
        $error = "密碼不正確，請重試。";
    }
}


// 取得用戶資料與履歷資料（如果已認證）
if ($authenticated) {
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
        
        // 查詢所有用戶
        $query = "SELECT id, username, email, role, status, created_at FROM users ORDER BY id";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        // 查詢所有履歷資料
        $query = "SELECT r.id, r.user_id, u.username, r.chinese_name, r.english_name, r.gender, r.age, 
                        r.military_status, r.employment_status, r.phone, r.email, r.address, 
                        r.contact_method, r.driving_license, r.transportation, r.education_level, 
                        r.master_school, r.master_department, r.master_start_date, r.master_end_date, 
                        r.bachelor_school, r.bachelor_department, r.bachelor_start_date, r.bachelor_end_date, 
                        r.total_experience, r.job1_title, r.job1_company, r.job1_industry, r.job1_company_size, 
                        r.job1_location, r.job1_start_date, r.job1_end_date, r.job1_duration, 
                        r.job1_description, r.job1_technologies, r.job2_title, r.job2_company, 
                        r.job2_industry, r.job2_company_size, r.job2_location, r.job2_start_date, 
                        r.job2_end_date, r.job2_duration, r.job2_description, r.job2_technologies, 
                        r.english_listening, r.english_speaking, r.english_reading, r.english_writing, 
                        r.toeic_score, r.job_type, r.work_shift, r.available_date, r.expected_salary, 
                        r.preferred_locations, r.preferred_job_title, r.preferred_job_categories, 
                        r.preferred_industries, r.skills, r.github_url, r.autobiography,
                        r.created_at, r.updated_at
                 FROM resume r
                 LEFT JOIN users u ON r.user_id = u.id
                 ORDER BY r.id";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $resumes[] = $row;
            }
        }
        
        // 關閉資料庫連線
        $conn->close();
        
    } catch (Exception $e) {
        $error = "系統錯誤: " . $e->getMessage();
        error_log("讀取資料錯誤: " . $e->getMessage());
    }
}

// 處理匯出資料
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export']) && $authenticated) {
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
        
        // 取得表格類型
        $table_type = $_POST['table_type'] ?? 'users';
        
        // 確認選中了哪個表格與ID
        if ($table_type === 'users' && isset($_POST['user_ids'])) {
            // 匯出 users 表格
            $selected_ids = $_POST['user_ids'];
            
            // 檢查是否有選擇用戶
            if (empty($selected_ids)) {
                throw new Exception("請至少選擇一個用戶進行匯出");
            }
            
            // 準備SQL的IN子句參數
            $id_placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            
            // 查詢所選用戶資料
            $sql = "SELECT id, username, email, role, status, created_at FROM users WHERE id IN ($id_placeholders) ORDER BY id";
            $stmt = $conn->prepare($sql);
            
            // 綁定參數
            $param_types = str_repeat('i', count($selected_ids)); // 所有參數都是整數
            $stmt->bind_param($param_types, ...$selected_ids);
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 檢查是否有結果
            if ($result->num_rows == 0) {
                throw new Exception("找不到所選用戶的資料");
            }
            
            // 將結果轉換為陣列
            $export_data = [];
            $export_data[] = ['ID', '用戶名', '電子郵件', '角色', '狀態', '建立時間']; // 標題行
            
            while ($row = $result->fetch_assoc()) {
                $export_data[] = [
                    $row['id'],
                    $row['username'],
                    $row['email'],
                    $row['role'],
                    $row['status'],
                    $row['created_at']
                ];
            }
            
            $file_prefix = 'users';
            
        } elseif ($table_type === 'resumes' && isset($_POST['resume_ids'])) {
            // 匯出 resume 表格
            $selected_ids = $_POST['resume_ids'];
            
            // 檢查是否有選擇履歷
            if (empty($selected_ids)) {
                throw new Exception("請至少選擇一個履歷進行匯出");
            }
            
            // 準備SQL的IN子句參數
            $id_placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            
            // 查詢所選履歷資料
            $sql = "SELECT r.id, r.user_id, u.username, r.chinese_name, r.english_name, r.gender, r.age, 
                           r.military_status, r.employment_status, r.phone, r.email, r.address, 
                           r.contact_method, r.driving_license, r.transportation, r.education_level, 
                           r.master_school, r.master_department, r.master_start_date, r.master_end_date, 
                           r.bachelor_school, r.bachelor_department, r.bachelor_start_date, r.bachelor_end_date, 
                           r.total_experience, r.job1_title, r.job1_company, r.job1_industry, r.job1_company_size, 
                           r.job1_location, r.job1_start_date, r.job1_end_date, r.job1_duration, 
                           r.job1_description, r.job1_technologies, r.job2_title, r.job2_company, 
                           r.job2_industry, r.job2_company_size, r.job2_location, r.job2_start_date, 
                           r.job2_end_date, r.job2_duration, r.job2_description, r.job2_technologies, 
                           r.english_listening, r.english_speaking, r.english_reading, r.english_writing, 
                           r.toeic_score, r.job_type, r.work_shift, r.available_date, r.expected_salary, 
                           r.preferred_locations, r.preferred_job_title, r.preferred_job_categories, 
                           r.preferred_industries, r.skills, r.github_url, r.autobiography,
                           r.created_at, r.updated_at
                    FROM resume r
                    LEFT JOIN users u ON r.user_id = u.id
                    WHERE r.id IN ($id_placeholders) ORDER BY r.id";
            $stmt = $conn->prepare($sql);
            
            // 綁定參數
            $param_types = str_repeat('i', count($selected_ids)); // 所有參數都是整數
            $stmt->bind_param($param_types, ...$selected_ids);
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            // 檢查是否有結果
            if ($result->num_rows == 0) {
                throw new Exception("找不到所選履歷的資料");
            }
            
            // 將結果轉換為陣列
            $export_data = [];
            $export_data[] = [
                'ID', '用戶ID', '用戶名', '中文姓名', '英文姓名', '性別', '年齡', 
                '兵役狀態', '就業狀態', '電話', '電子郵件', '地址', '聯絡方式', 
                '駕駛執照', '交通工具', '最高學歷', '碩士學校', '碩士系所', 
                '碩士開始日期', '碩士結束日期', '學士學校', '學士系所', 
                '學士開始日期', '學士結束日期', '總年資', '工作1職稱', 
                '工作1公司', '工作1產業', '工作1公司規模', '工作1地點', 
                '工作1開始日期', '工作1結束日期', '工作1時長', '工作1描述', 
                '工作1技術', '工作2職稱', '工作2公司', '工作2產業', 
                '工作2公司規模', '工作2地點', '工作2開始日期', '工作2結束日期', 
                '工作2時長', '工作2描述', '工作2技術', '英語聽力', '英語口說', 
                '英語閱讀', '英語寫作', '多益成績', '希望工作性質', '上班時段', 
                '可上班日', '期望薪資', '希望地點', '希望職稱', '希望職類', 
                '希望產業', '專長技能', 'GitHub連結', '自傳', '建立時間', '更新時間'
            ]; // 標題行
            
            while ($row = $result->fetch_assoc()) {
                $export_data[] = [
                    $row['id'],
                    $row['user_id'],
                    $row['username'],
                    $row['chinese_name'],
                    $row['english_name'],
                    $row['gender'],
                    $row['age'],
                    $row['military_status'],
                    $row['employment_status'],
                    $row['phone'],
                    $row['email'],
                    $row['address'],
                    $row['contact_method'],
                    $row['driving_license'],
                    $row['transportation'],
                    $row['education_level'],
                    $row['master_school'],
                    $row['master_department'],
                    $row['master_start_date'],
                    $row['master_end_date'],
                    $row['bachelor_school'],
                    $row['bachelor_department'],
                    $row['bachelor_start_date'],
                    $row['bachelor_end_date'],
                    $row['total_experience'],
                    $row['job1_title'],
                    $row['job1_company'],
                    $row['job1_industry'],
                    $row['job1_company_size'],
                    $row['job1_location'],
                    $row['job1_start_date'],
                    $row['job1_end_date'],
                    $row['job1_duration'],
                    $row['job1_description'],
                    $row['job1_technologies'],
                    $row['job2_title'],
                    $row['job2_company'],
                    $row['job2_industry'],
                    $row['job2_company_size'],
                    $row['job2_location'],
                    $row['job2_start_date'],
                    $row['job2_end_date'],
                    $row['job2_duration'],
                    $row['job2_description'],
                    $row['job2_technologies'],
                    $row['english_listening'],
                    $row['english_speaking'],
                    $row['english_reading'],
                    $row['english_writing'],
                    $row['toeic_score'],
                    $row['job_type'],
                    $row['work_shift'],
                    $row['available_date'],
                    $row['expected_salary'],
                    $row['preferred_locations'],
                    $row['preferred_job_title'],
                    $row['preferred_job_categories'],
                    $row['preferred_industries'],
                    $row['skills'],
                    $row['github_url'],
                    $row['autobiography'],
                    $row['created_at'],
                    $row['updated_at']
                ];
            }
            
            $file_prefix = 'resumes';
            
        } else {
            throw new Exception("請選擇要匯出的表格和資料");
        }
        
        // 關閉資料庫連線
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
        
        // 創建並直接輸出XLSX檔案
        require_once 'vendor/autoload.php'; // 假設使用Composer安裝了PhpSpreadsheet

        // 如果找不到PhpSpreadsheet，改CSV輸出
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="' . $file_prefix . '_export_' . date('Y-m-d') . '.csv"');
            header('Cache-Control: max-age=0');
            
            $output = fopen('php://output', 'w');
            foreach ($export_data as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
        
        // 使用PhpSpreadsheet創建XLSX
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 設定標題樣式
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ];
        
        // 填充資料
        foreach ($export_data as $rowIndex => $rowData) {
            foreach ($rowData as $colIndex => $cellValue) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
                $sheet->setCellValue($cellCoordinate, $cellValue);
                
                // 設定第一行為標題樣式
                if ($rowIndex === 0) {
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 1);
                    $sheet->getStyle($cellCoordinate)->applyFromArray($styleArray);
                }
            }
        }
        
        // 設定列寬（自動調整）
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // 輸出檔案
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $file_prefix . '_export_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        $error = "匯出錯誤: " . $e->getMessage();
        error_log("資料匯出錯誤: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/enteradmin.css">
    <style>
        /* 添加的標籤樣式 */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            background-color: #f1f1f1;
        }
        
        .tab.active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 暗色模式支援 - 標籤樣式 */
        @media (prefers-color-scheme: dark) {
            .tab {
                background-color: #333;
                border-color: #444;
                color: #ccc;
            }
            
            .tab.active {
                background-color: #2e7d32;
                color: white;
                border-color: #2e7d32;
            }
        }
    </style>
</head>
<body>
    <div class="container admin-container">
        <h1>管理後台</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($upload_success)): ?>
            <div class="success-message">
                <?php echo $upload_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$authenticated): ?>
            <!-- 管理員密碼驗證表單 -->
            <div class="auth-form">
                <p>請輸入管理員密碼以進入後台</p>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="admin_password">管理員密碼</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                    </div>
                    <button type="submit" class="btn">驗證</button>
                </form>
            </div>
        <?php else: ?>
            <!-- 管理後台功能 -->
            <!-- 1. 上傳XLSX/XLS的表單 -->
            <div class="admin-section">
                <h2>Excel 檔案管理</h2>
                <p>您可以上傳 Excel 檔案來批量新增或更新用戶資料與履歷資料。</p>
                
                <div class="excel-options">
                    <a href="uploadxlsx.php" class="btn btn-upload-excel">
                        <i class="fa fa-upload"></i> 上傳 Excel 檔案
                    </a>
                    <div class="excel-info">
                        <p>透過上傳 Excel 檔案，您可以：</p>
                        <ul>
                            <li>批量新增或更新用戶資料</li>
                            <li>批量新增或更新履歷資料</li>
                            <li>系統將自動識別 Excel 表格類型並處理資料</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- 2. 資料表選擇標籤 -->
            <div class="admin-section">
                <h2>資料管理與匯出</h2>
                
                <div class="tabs">
                    <div class="tab <?php echo $activeTab === 'users' ? 'active' : ''; ?>" data-tab="users">用戶資料</div>
                    <div class="tab <?php echo $activeTab === 'resumes' ? 'active' : ''; ?>" data-tab="resumes">履歷資料</div>
                </div>
                
                <!-- 用戶資料標籤內容 -->
                <div id="users-tab" class="tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
                    <div class="select-all-container">
                        <input type="checkbox" id="select_all_users" name="select_all_users">
                        <label for="select_all_users">全選</label>
                    </div>
                    
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-header"></th>
                                    <th>ID</th>
                                    <th>用戶名</th>
                                    <th>電子郵件</th>
                                    <th>角色</th>
                                    <th>狀態</th>
                                    <th>建立時間</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">沒有找到用戶數據</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>">
                                            </td>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td><?php echo htmlspecialchars($user['status']); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button id="exportUsersBtn" class="btn btn-export">匯出所選用戶資料 (XLSX)</button>
                </div>
                
                <!-- 履歷資料標籤內容 -->
                <div id="resumes-tab" class="tab-content <?php echo $activeTab === 'resumes' ? 'active' : ''; ?>">
                    <div class="select-all-container">
                        <input type="checkbox" id="select_all_resumes" name="select_all_resumes">
                        <label for="select_all_resumes">全選</label>
                    </div>
                    
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-header"></th>
                                    <th>ID</th>
                                    <th>用戶ID</th>
                                    <th>用戶名</th>
                                    <th>中文姓名</th>
                                    <th>英文姓名</th>
                                    <th>性別</th>
                                    <th>年齡</th>
                                    <th>兵役狀態</th>
                                    <th>就業狀態</th>
                                    <th>電話</th>
                                    <th>電子郵件</th>
                                    <th>最高學歷</th>
                                    <th>學士學校</th>
                                    <th>碩士學校</th>
                                    <th>總年資</th>
                                    <th>期望薪資</th>
                                    <th>建立時間</th>
                                    <th>更新時間</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($resumes)): ?>
                                    <tr>
                                        <td colspan="19" style="text-align: center;">沒有找到履歷數據</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resumes as $resume): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="resume-checkbox" name="selected_resumes[]" value="<?php echo $resume['id']; ?>">
                                            </td>
                                            <td><?php echo $resume['id']; ?></td>
                                            <td><?php echo $resume['user_id']; ?></td>
                                            <td><?php echo htmlspecialchars($resume['username']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['chinese_name']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['english_name']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['gender']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['age']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['military_status']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['employment_status']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['email']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['education_level']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['bachelor_school']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['master_school']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['total_experience']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['expected_salary']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($resume['updated_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button id="exportResumesBtn" class="btn btn-export">匯出所選履歷資料 (XLSX)</button>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="login_success.php" class="btn btn-back">返回儀表板</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 檔案選擇按鈕功能
            const selectFileBtn = document.getElementById('select_file_btn');
            const fileInput = document.getElementById('excel_file');
            const fileNameDisplay = document.getElementById('selected_file_name');
            
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
            }
            
            // 標籤切換功能
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // 移除所有標籤的活動狀態
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // 設置當前標籤為活動狀態
                    this.classList.add('active');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                    
                    // 更新URL以保存當前標籤
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                });
            });
            
            // 用戶資料全選功能
            const selectAllUsersCheckbox = document.getElementById('select_all_users');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');
            
            if (selectAllUsersCheckbox) {
                selectAllUsersCheckbox.addEventListener('change', function() {
                    userCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllUsersCheckbox.checked;
                    });
                });
            }
            
            // 履歷資料全選功能
            const selectAllResumesCheckbox = document.getElementById('select_all_resumes');
            const resumeCheckboxes = document.querySelectorAll('.resume-checkbox');
            
            if (selectAllResumesCheckbox) {
                selectAllResumesCheckbox.addEventListener('change', function() {
                    resumeCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllResumesCheckbox.checked;
                    });
                });
            }
            
            // 匯出用戶資料按鈕功能
            const exportUsersBtn = document.getElementById('exportUsersBtn');
            
            if (exportUsersBtn) {
                exportUsersBtn.addEventListener('click', function() {
                    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
                    
                    if (selectedCheckboxes.length === 0) {
                        alert('請至少選擇一個用戶進行匯出');
                        return;
                    }
                    
                    // 收集所選用戶ID
                    const selectedUserIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
                    
                    // 創建一個表單直接提交到當前頁面以處理匯出
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>';
                    
                    // 添加表格類型
                    const tableTypeInput = document.createElement('input');
                    tableTypeInput.type = 'hidden';
                    tableTypeInput.name = 'table_type';
                    tableTypeInput.value = 'users';
                    form.appendChild(tableTypeInput);
                    
                    // 添加所選用戶ID
                    selectedUserIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'user_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                    
                    // 添加匯出標誌
                    const exportInput = document.createElement('input');
                    exportInput.type = 'hidden';
                    exportInput.name = 'export';
                    exportInput.value = '1';
                    form.appendChild(exportInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            }
            
            // 匯出履歷資料按鈕功能
            const exportResumesBtn = document.getElementById('exportResumesBtn');
            
            if (exportResumesBtn) {
                exportResumesBtn.addEventListener('click', function() {
                    const selectedCheckboxes = document.querySelectorAll('.resume-checkbox:checked');
                    
                    if (selectedCheckboxes.length === 0) {
                        alert('請至少選擇一個履歷進行匯出');
                        return;
                    }
                    
                    // 收集所選履歷ID
                    const selectedResumeIds = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
                    
                    // 創建一個表單直接提交到當前頁面以處理匯出
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>';
                    
                    // 添加表格類型
                    const tableTypeInput = document.createElement('input');
                    tableTypeInput.type = 'hidden';
                    tableTypeInput.name = 'table_type';
                    tableTypeInput.value = 'resumes';
                    form.appendChild(tableTypeInput);
                    
                    // 添加所選履歷ID
                    selectedResumeIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'resume_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                    
                    // 添加匯出標誌
                    const exportInput = document.createElement('input');
                    exportInput.type = 'hidden';
                    exportInput.name = 'export';
                    exportInput.value = '1';
                    form.appendChild(exportInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        });
    </script>
</body>
</html>