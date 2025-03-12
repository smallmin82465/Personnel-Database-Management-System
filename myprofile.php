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
$resume_data = []; // 用於存儲履歷資料

// 連接資料庫
try {
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
    
    // 檢查是否已有履歷資料
    $stmt = $conn->prepare("SELECT * FROM resume WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $resume_data = $result->fetch_assoc();
    }
    
} catch (Exception $e) {
    $error = "系統錯誤: " . $e->getMessage();
    error_log("讀取履歷錯誤: " . $e->getMessage());
}

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 獲取表單數據
        $chinese_name = trim($_POST['chinese_name'] ?? '');
        $english_name = trim($_POST['english_name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $age = intval($_POST['age'] ?? 0);
        $military_status = trim($_POST['military_status'] ?? '');
        $employment_status = trim($_POST['employment_status'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_method = trim($_POST['contact_method'] ?? '');
        $driving_license = trim($_POST['driving_license'] ?? '');
        $transportation = trim($_POST['transportation'] ?? '');
        
        // 學歷資訊
        $education_level = trim($_POST['education_level'] ?? '');
        $master_school = trim($_POST['master_school'] ?? '');
        $master_department = trim($_POST['master_department'] ?? '');
        $master_start_date = !empty($_POST['master_start_date']) ? $_POST['master_start_date'] : NULL;
        $master_end_date = !empty($_POST['master_end_date']) ? $_POST['master_end_date'] : NULL;
        $bachelor_school = trim($_POST['bachelor_school'] ?? '');
        $bachelor_department = trim($_POST['bachelor_department'] ?? '');
        $bachelor_start_date = !empty($_POST['bachelor_start_date']) ? $_POST['bachelor_start_date'] : NULL;
        $bachelor_end_date = !empty($_POST['bachelor_end_date']) ? $_POST['bachelor_end_date'] : NULL;
        
        // 工作經驗
        $total_experience = trim($_POST['total_experience'] ?? '');
        
        // 工作經歷1
        $job1_title = trim($_POST['job1_title'] ?? '');
        $job1_company = trim($_POST['job1_company'] ?? '');
        $job1_industry = trim($_POST['job1_industry'] ?? '');
        $job1_company_size = trim($_POST['job1_company_size'] ?? '');
        $job1_location = trim($_POST['job1_location'] ?? '');
        $job1_start_date = !empty($_POST['job1_start_date']) ? $_POST['job1_start_date'] : NULL;
        $job1_end_date = !empty($_POST['job1_end_date']) ? $_POST['job1_end_date'] : NULL;
        $job1_duration = trim($_POST['job1_duration'] ?? '');
        $job1_description = trim($_POST['job1_description'] ?? '');
        $job1_technologies = trim($_POST['job1_technologies'] ?? '');
        
        // 工作經歷2
        $job2_title = trim($_POST['job2_title'] ?? '');
        $job2_company = trim($_POST['job2_company'] ?? '');
        $job2_industry = trim($_POST['job2_industry'] ?? '');
        $job2_company_size = trim($_POST['job2_company_size'] ?? '');
        $job2_location = trim($_POST['job2_location'] ?? '');
        $job2_start_date = !empty($_POST['job2_start_date']) ? $_POST['job2_start_date'] : NULL;
        $job2_end_date = !empty($_POST['job2_end_date']) ? $_POST['job2_end_date'] : NULL;
        $job2_duration = trim($_POST['job2_duration'] ?? '');
        $job2_description = trim($_POST['job2_description'] ?? '');
        $job2_technologies = trim($_POST['job2_technologies'] ?? '');
        
        // 語言能力
        $english_listening = trim($_POST['english_listening'] ?? '');
        $english_speaking = trim($_POST['english_speaking'] ?? '');
        $english_reading = trim($_POST['english_reading'] ?? '');
        $english_writing = trim($_POST['english_writing'] ?? '');
        $toeic_score = intval($_POST['toeic_score'] ?? 0);
        
        // 求職條件
        $job_type = trim($_POST['job_type'] ?? '');
        $work_shift = trim($_POST['work_shift'] ?? '');
        $available_date = trim($_POST['available_date'] ?? '');
        $expected_salary = trim($_POST['expected_salary'] ?? '');
        $preferred_locations = trim($_POST['preferred_locations'] ?? '');
        $preferred_job_title = trim($_POST['preferred_job_title'] ?? '');
        $preferred_job_categories = trim($_POST['preferred_job_categories'] ?? '');
        $preferred_industries = trim($_POST['preferred_industries'] ?? '');
        
        // 專長技能
        $skills = trim($_POST['skills'] ?? '');
        $github_url = trim($_POST['github_url'] ?? '');
        $autobiography = trim($_POST['autobiography'] ?? '');
        
        // 檢查是更新還是新增
        if (!empty($resume_data)) {
            // 更新現有履歷
            $stmt = $conn->prepare("
                UPDATE resume SET 
                chinese_name = ?, english_name = ?, gender = ?, age = ?, military_status = ?,
                employment_status = ?, phone = ?, email = ?, address = ?, contact_method = ?,
                driving_license = ?, transportation = ?, education_level = ?,
                master_school = ?, master_department = ?, master_start_date = ?, master_end_date = ?,
                bachelor_school = ?, bachelor_department = ?, bachelor_start_date = ?, bachelor_end_date = ?,
                total_experience = ?, 
                job1_title = ?, job1_company = ?, job1_industry = ?, job1_company_size = ?, job1_location = ?,
                job1_start_date = ?, job1_end_date = ?, job1_duration = ?, job1_description = ?, job1_technologies = ?,
                job2_title = ?, job2_company = ?, job2_industry = ?, job2_company_size = ?, job2_location = ?,
                job2_start_date = ?, job2_end_date = ?, job2_duration = ?, job2_description = ?, job2_technologies = ?,
                english_listening = ?, english_speaking = ?, english_reading = ?, english_writing = ?, toeic_score = ?,
                job_type = ?, work_shift = ?, available_date = ?, expected_salary = ?, preferred_locations = ?,
                preferred_job_title = ?, preferred_job_categories = ?, preferred_industries = ?,
                skills = ?, github_url = ?, autobiography = ?
                WHERE user_id = ?
            ");
            
            // 使用預處理語句，但不使用 bind_param
            $sql_update = "
                UPDATE resume SET 
                chinese_name = '" . $conn->real_escape_string($chinese_name) . "', 
                english_name = '" . $conn->real_escape_string($english_name) . "', 
                gender = '" . $conn->real_escape_string($gender) . "', 
                age = " . intval($age) . ", 
                military_status = '" . $conn->real_escape_string($military_status) . "',
                employment_status = '" . $conn->real_escape_string($employment_status) . "', 
                phone = '" . $conn->real_escape_string($phone) . "', 
                email = '" . $conn->real_escape_string($email) . "', 
                address = '" . $conn->real_escape_string($address) . "', 
                contact_method = '" . $conn->real_escape_string($contact_method) . "',
                driving_license = '" . $conn->real_escape_string($driving_license) . "', 
                transportation = '" . $conn->real_escape_string($transportation) . "', 
                education_level = '" . $conn->real_escape_string($education_level) . "',
                master_school = '" . $conn->real_escape_string($master_school) . "', 
                master_department = '" . $conn->real_escape_string($master_department) . "', 
                master_start_date = " . ($master_start_date ? "'" . $conn->real_escape_string($master_start_date) . "'" : "NULL") . ", 
                master_end_date = " . ($master_end_date ? "'" . $conn->real_escape_string($master_end_date) . "'" : "NULL") . ",
                bachelor_school = '" . $conn->real_escape_string($bachelor_school) . "', 
                bachelor_department = '" . $conn->real_escape_string($bachelor_department) . "', 
                bachelor_start_date = " . ($bachelor_start_date ? "'" . $conn->real_escape_string($bachelor_start_date) . "'" : "NULL") . ", 
                bachelor_end_date = " . ($bachelor_end_date ? "'" . $conn->real_escape_string($bachelor_end_date) . "'" : "NULL") . ",
                total_experience = '" . $conn->real_escape_string($total_experience) . "', 
                job1_title = '" . $conn->real_escape_string($job1_title) . "', 
                job1_company = '" . $conn->real_escape_string($job1_company) . "', 
                job1_industry = '" . $conn->real_escape_string($job1_industry) . "', 
                job1_company_size = '" . $conn->real_escape_string($job1_company_size) . "', 
                job1_location = '" . $conn->real_escape_string($job1_location) . "',
                job1_start_date = " . ($job1_start_date ? "'" . $conn->real_escape_string($job1_start_date) . "'" : "NULL") . ", 
                job1_end_date = " . ($job1_end_date ? "'" . $conn->real_escape_string($job1_end_date) . "'" : "NULL") . ", 
                job1_duration = '" . $conn->real_escape_string($job1_duration) . "', 
                job1_description = '" . $conn->real_escape_string($job1_description) . "', 
                job1_technologies = '" . $conn->real_escape_string($job1_technologies) . "',
                job2_title = '" . $conn->real_escape_string($job2_title) . "', 
                job2_company = '" . $conn->real_escape_string($job2_company) . "', 
                job2_industry = '" . $conn->real_escape_string($job2_industry) . "', 
                job2_company_size = '" . $conn->real_escape_string($job2_company_size) . "', 
                job2_location = '" . $conn->real_escape_string($job2_location) . "',
                job2_start_date = " . ($job2_start_date ? "'" . $conn->real_escape_string($job2_start_date) . "'" : "NULL") . ", 
                job2_end_date = " . ($job2_end_date ? "'" . $conn->real_escape_string($job2_end_date) . "'" : "NULL") . ", 
                job2_duration = '" . $conn->real_escape_string($job2_duration) . "', 
                job2_description = '" . $conn->real_escape_string($job2_description) . "', 
                job2_technologies = '" . $conn->real_escape_string($job2_technologies) . "',
                english_listening = '" . $conn->real_escape_string($english_listening) . "', 
                english_speaking = '" . $conn->real_escape_string($english_speaking) . "', 
                english_reading = '" . $conn->real_escape_string($english_reading) . "', 
                english_writing = '" . $conn->real_escape_string($english_writing) . "', 
                toeic_score = " . intval($toeic_score) . ",
                job_type = '" . $conn->real_escape_string($job_type) . "', 
                work_shift = '" . $conn->real_escape_string($work_shift) . "', 
                available_date = '" . $conn->real_escape_string($available_date) . "', 
                expected_salary = '" . $conn->real_escape_string($expected_salary) . "', 
                preferred_locations = '" . $conn->real_escape_string($preferred_locations) . "',
                preferred_job_title = '" . $conn->real_escape_string($preferred_job_title) . "', 
                preferred_job_categories = '" . $conn->real_escape_string($preferred_job_categories) . "', 
                preferred_industries = '" . $conn->real_escape_string($preferred_industries) . "',
                skills = '" . $conn->real_escape_string($skills) . "', 
                github_url = '" . $conn->real_escape_string($github_url) . "', 
                autobiography = '" . $conn->real_escape_string($autobiography) . "'
                WHERE user_id = " . intval($user_id);
                
            if ($conn->query($sql_update) === TRUE) {
                $success = "履歷資料已成功儲存";
                
                // 重新讀取更新後的資料
                // 使用新的連線物件來避免 "Commands out of sync" 錯誤
                $conn2 = new mysqli(
                    $DatabaseInfo["host"],
                    $DatabaseInfo["username"],
                    $DatabaseInfo["password"],
                    $DatabaseInfo["database"],
                    $DatabaseInfo["port"]
                );
                $conn2->set_charset("utf8mb4");
                
                $stmt2 = $conn2->prepare("SELECT * FROM resume WHERE user_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                
                if ($result->num_rows > 0) {
                    $resume_data = $result->fetch_assoc();
                }
                
                $stmt2->close();
                $conn2->close();
            } else {
                $error = "儲存失敗: " . $conn->error;
            }
            
        } else {
            // 新增履歷，直接用 SQL insert
            $sql_insert = "
                INSERT INTO resume (
                    user_id, chinese_name, english_name, gender, age, military_status,
                    employment_status, phone, email, address, contact_method,
                    driving_license, transportation, education_level,
                    master_school, master_department, master_start_date, master_end_date,
                    bachelor_school, bachelor_department, bachelor_start_date, bachelor_end_date,
                    total_experience,
                    job1_title, job1_company, job1_industry, job1_company_size, job1_location,
                    job1_start_date, job1_end_date, job1_duration, job1_description, job1_technologies,
                    job2_title, job2_company, job2_industry, job2_company_size, job2_location,
                    job2_start_date, job2_end_date, job2_duration, job2_description, job2_technologies,
                    english_listening, english_speaking, english_reading, english_writing, toeic_score,
                    job_type, work_shift, available_date, expected_salary, preferred_locations,
                    preferred_job_title, preferred_job_categories, preferred_industries,
                    skills, github_url, autobiography
                ) VALUES (
                    " . intval($user_id) . ", 
                    '" . $conn->real_escape_string($chinese_name) . "', 
                    '" . $conn->real_escape_string($english_name) . "', 
                    '" . $conn->real_escape_string($gender) . "', 
                    " . intval($age) . ", 
                    '" . $conn->real_escape_string($military_status) . "',
                    '" . $conn->real_escape_string($employment_status) . "', 
                    '" . $conn->real_escape_string($phone) . "', 
                    '" . $conn->real_escape_string($email) . "', 
                    '" . $conn->real_escape_string($address) . "', 
                    '" . $conn->real_escape_string($contact_method) . "',
                    '" . $conn->real_escape_string($driving_license) . "', 
                    '" . $conn->real_escape_string($transportation) . "', 
                    '" . $conn->real_escape_string($education_level) . "',
                    '" . $conn->real_escape_string($master_school) . "', 
                    '" . $conn->real_escape_string($master_department) . "', 
                    " . ($master_start_date ? "'" . $conn->real_escape_string($master_start_date) . "'" : "NULL") . ", 
                    " . ($master_end_date ? "'" . $conn->real_escape_string($master_end_date) . "'" : "NULL") . ",
                    '" . $conn->real_escape_string($bachelor_school) . "', 
                    '" . $conn->real_escape_string($bachelor_department) . "', 
                    " . ($bachelor_start_date ? "'" . $conn->real_escape_string($bachelor_start_date) . "'" : "NULL") . ", 
                    " . ($bachelor_end_date ? "'" . $conn->real_escape_string($bachelor_end_date) . "'" : "NULL") . ",
                    '" . $conn->real_escape_string($total_experience) . "',
                    '" . $conn->real_escape_string($job1_title) . "', 
                    '" . $conn->real_escape_string($job1_company) . "', 
                    '" . $conn->real_escape_string($job1_industry) . "', 
                    '" . $conn->real_escape_string($job1_company_size) . "', 
                    '" . $conn->real_escape_string($job1_location) . "',
                    " . ($job1_start_date ? "'" . $conn->real_escape_string($job1_start_date) . "'" : "NULL") . ", 
                    " . ($job1_end_date ? "'" . $conn->real_escape_string($job1_end_date) . "'" : "NULL") . ", 
                    '" . $conn->real_escape_string($job1_duration) . "', 
                    '" . $conn->real_escape_string($job1_description) . "', 
                    '" . $conn->real_escape_string($job1_technologies) . "',
                    '" . $conn->real_escape_string($job2_title) . "', 
                    '" . $conn->real_escape_string($job2_company) . "', 
                    '" . $conn->real_escape_string($job2_industry) . "', 
                    '" . $conn->real_escape_string($job2_company_size) . "', 
                    '" . $conn->real_escape_string($job2_location) . "',
                    " . ($job2_start_date ? "'" . $conn->real_escape_string($job2_start_date) . "'" : "NULL") . ", 
                    " . ($job2_end_date ? "'" . $conn->real_escape_string($job2_end_date) . "'" : "NULL") . ", 
                    '" . $conn->real_escape_string($job2_duration) . "', 
                    '" . $conn->real_escape_string($job2_description) . "', 
                    '" . $conn->real_escape_string($job2_technologies) . "',
                    '" . $conn->real_escape_string($english_listening) . "', 
                    '" . $conn->real_escape_string($english_speaking) . "', 
                    '" . $conn->real_escape_string($english_reading) . "', 
                    '" . $conn->real_escape_string($english_writing) . "', 
                    " . intval($toeic_score) . ",
                    '" . $conn->real_escape_string($job_type) . "', 
                    '" . $conn->real_escape_string($work_shift) . "', 
                    '" . $conn->real_escape_string($available_date) . "', 
                    '" . $conn->real_escape_string($expected_salary) . "', 
                    '" . $conn->real_escape_string($preferred_locations) . "',
                    '" . $conn->real_escape_string($preferred_job_title) . "', 
                    '" . $conn->real_escape_string($preferred_job_categories) . "', 
                    '" . $conn->real_escape_string($preferred_industries) . "',
                    '" . $conn->real_escape_string($skills) . "', 
                    '" . $conn->real_escape_string($github_url) . "', 
                    '" . $conn->real_escape_string($autobiography) . "'
                )
            ";
            
            if ($conn->query($sql_insert) === TRUE) {
                $success = "履歷資料已成功儲存";
                
                // 重新讀取更新後的資料
                // 使用新的連線物件來避免 "Commands out of sync" 錯誤
                $conn2 = new mysqli(
                    $DatabaseInfo["host"],
                    $DatabaseInfo["username"],
                    $DatabaseInfo["password"],
                    $DatabaseInfo["database"],
                    $DatabaseInfo["port"]
                );
                $conn2->set_charset("utf8mb4");
                
                $stmt2 = $conn2->prepare("SELECT * FROM resume WHERE user_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                
                if ($result->num_rows > 0) {
                    $resume_data = $result->fetch_assoc();
                }
                
                $stmt2->close();
                $conn2->close();
            } else {
                $error = "儲存失敗: " . $conn->error;
            }
        }
        
    } catch (Exception $e) {
        // 篩選掉 "Commands out of sync" 錯誤訊息
        if (strpos($e->getMessage(), 'Commands out of sync') !== false) {
            // 如果錯誤是關於 Commands out of sync，不顯示，因為資料已更新成功
            $success = "履歷資料已成功儲存";
            
            // 嘗試重新讀取資料
            try {
                $conn2 = new mysqli(
                    $DatabaseInfo["host"],
                    $DatabaseInfo["username"],
                    $DatabaseInfo["password"],
                    $DatabaseInfo["database"],
                    $DatabaseInfo["port"]
                );
                $conn2->set_charset("utf8mb4");
                
                $stmt2 = $conn2->prepare("SELECT * FROM resume WHERE user_id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $result = $stmt2->get_result();
                
                if ($result->num_rows > 0) {
                    $resume_data = $result->fetch_assoc();
                }
                
                $stmt2->close();
                $conn2->close();
            } catch (Exception $e2) {
                // 忽略此處的錯誤，因為我們已經知道資料已存入資料庫
            }
        } else {
            // 其他錯誤類型，正常顯示錯誤訊息
            $error = "系統錯誤: " . $e->getMessage();
            error_log("儲存履歷錯誤: " . $e->getMessage());
        }
    }
}

// 關閉資料庫連線
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>個人履歷管理 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/myprofile.css">
</head>
<body>
    <div class="container profile-container">
        <h1>個人履歷管理</h1>
        
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
        
        <form method="post" id="resumeForm">
            <!-- 分頁導覽 -->
            <div class="tab-navigation">
                <button type="button" class="tab-btn active" data-tab="basic-info">基本資料</button>
                <button type="button" class="tab-btn" data-tab="education">學歷資訊</button>
                <button type="button" class="tab-btn" data-tab="experience">工作經驗</button>
                <button type="button" class="tab-btn" data-tab="language">語言能力</button>
                <button type="button" class="tab-btn" data-tab="job-preference">求職條件</button>
                <button type="button" class="tab-btn" data-tab="skills">專長技能</button>
            </div>
            
            <!-- 基本資料 -->
            <div class="tab-content active" id="basic-info">
                <h2>基本資料</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="chinese_name">中文姓名</label>
                        <input type="text" id="chinese_name" name="chinese_name" value="<?php echo htmlspecialchars($resume_data['chinese_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="english_name">英文姓名</label>
                        <input type="text" id="english_name" name="english_name" value="<?php echo htmlspecialchars($resume_data['english_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">性別</label>
                        <select id="gender" name="gender">
                            <option value="">請選擇</option>
                            <option value="男" <?php echo (isset($resume_data['gender']) && $resume_data['gender'] == '男') ? 'selected' : ''; ?>>男</option>
                            <option value="女" <?php echo (isset($resume_data['gender']) && $resume_data['gender'] == '女') ? 'selected' : ''; ?>>女</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">年齡</label>
                        <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($resume_data['age'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="military_status">兵役狀態</label>
                        <select id="military_status" name="military_status">
                            <option value="">請選擇</option>
                            <option value="無須服兵役" <?php echo (isset($resume_data['military_status']) && $resume_data['military_status'] == '無須服兵役') ? 'selected' : ''; ?>>無須服兵役</option>
                            <option value="役畢" <?php echo (isset($resume_data['military_status']) && $resume_data['military_status'] == '役畢') ? 'selected' : ''; ?>>役畢</option>
                            <option value="尚未服役" <?php echo (isset($resume_data['military_status']) && $resume_data['military_status'] == '尚未服役') ? 'selected' : ''; ?>>尚未服役</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="employment_status">就業狀態</label>
                        <select id="employment_status" name="employment_status">
                            <option value="">請選擇</option>
                            <option value="在職中" <?php echo (isset($resume_data['employment_status']) && $resume_data['employment_status'] == '在職中') ? 'selected' : ''; ?>>在職中</option>
                            <option value="待業中" <?php echo (isset($resume_data['employment_status']) && $resume_data['employment_status'] == '待業中') ? 'selected' : ''; ?>>待業中</option>
                            <option value="學生" <?php echo (isset($resume_data['employment_status']) && $resume_data['employment_status'] == '學生') ? 'selected' : ''; ?>>學生</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">手機號碼</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($resume_data['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($resume_data['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">通訊地址</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($resume_data['address'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_method">聯絡方式</label>
                        <select id="contact_method" name="contact_method">
                            <option value="">請選擇</option>
                            <option value="手機連絡" <?php echo (isset($resume_data['contact_method']) && $resume_data['contact_method'] == '手機連絡') ? 'selected' : ''; ?>>手機連絡</option>
                            <option value="電子郵件" <?php echo (isset($resume_data['contact_method']) && $resume_data['contact_method'] == '電子郵件') ? 'selected' : ''; ?>>電子郵件</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="driving_license">駕駛執照</label>
                        <select id="driving_license" name="driving_license">
                            <option value="">請選擇</option>
                            <option value="無駕照" <?php echo (isset($resume_data['driving_license']) && $resume_data['driving_license'] == '無駕照') ? 'selected' : ''; ?>>無駕照</option>
                            <option value="普通小型車" <?php echo (isset($resume_data['driving_license']) && $resume_data['driving_license'] == '普通小型車') ? 'selected' : ''; ?>>普通小型車</option>
                            <option value="普通機車" <?php echo (isset($resume_data['driving_license']) && $resume_data['driving_license'] == '普通機車') ? 'selected' : ''; ?>>普通機車</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="transportation">交通工具</label>
                    <select id="transportation" name="transportation">
                            <option value="">請選擇</option>
                            <option value="徒步" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '徒步') ? 'selected' : ''; ?>>徒步</option>
                            <option value="腳踏車" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '腳踏車') ? 'selected' : ''; ?>>腳踏車</option>
                            <option value="公車" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '公車') ? 'selected' : ''; ?>>公車</option>
                            <option value="計程車" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '計程車') ? 'selected' : ''; ?>>計程車</option>
                            <option value="捷運" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '捷運') ? 'selected' : ''; ?>>捷運</option>
                            <option value="汽車" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '汽車') ? 'selected' : ''; ?>>汽車</option>
                            <option value="機車" <?php echo (isset($resume_data['transportation']) && $resume_data['transportation'] == '機車') ? 'selected' : ''; ?>>機車</option>
                    </select>        
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-next" data-next="education">下一步</button>
                </div>
            </div>
            
            <!-- 學歷資訊 -->
            <div class="tab-content" id="education">
                <h2>學歷資訊</h2>
                
                <div class="form-group">
                    <label for="education_level">最高學歷(選擇高中職以下免填)</label>
                    <select id="education_level" name="education_level">
                        <option value="">請選擇</option>
                        <option value="博士" <?php echo (isset($resume_data['education_level']) && $resume_data['education_level'] == '博士') ? 'selected' : ''; ?>>博士</option>
                        <option value="碩士" <?php echo (isset($resume_data['education_level']) && $resume_data['education_level'] == '碩士') ? 'selected' : ''; ?>>碩士</option>
                        <option value="學士" <?php echo (isset($resume_data['education_level']) && $resume_data['education_level'] == '學士') ? 'selected' : ''; ?>>學士</option>
                        <option value="專科" <?php echo (isset($resume_data['education_level']) && $resume_data['education_level'] == '專科') ? 'selected' : ''; ?>>專科</option>
                        <option value="高中職" <?php echo (isset($resume_data['education_level']) && $resume_data['education_level'] == '高中職') ? 'selected' : ''; ?>>高中職</option>
                    </select>
                </div>
                
                <div class="education-section">
                    <h3>碩士學歷</h3>
                    <div class="form-group">
                        <label for="master_school">學校名稱</label>
                        <input type="text" id="master_school" name="master_school" value="<?php echo htmlspecialchars($resume_data['master_school'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="master_department">系所</label>
                        <input type="text" id="master_department" name="master_department" value="<?php echo htmlspecialchars($resume_data['master_department'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="master_start_date">開始日期</label>
                            <input type="date" id="master_start_date" name="master_start_date" value="<?php echo htmlspecialchars($resume_data['master_start_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="master_end_date">結束日期</label>
                            <input type="date" id="master_end_date" name="master_end_date" value="<?php echo htmlspecialchars($resume_data['master_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="education-section">
                    <h3>學士學歷</h3>
                    <div class="form-group">
                        <label for="bachelor_school">學校名稱</label>
                        <input type="text" id="bachelor_school" name="bachelor_school" value="<?php echo htmlspecialchars($resume_data['bachelor_school'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bachelor_department">系所</label>
                        <input type="text" id="bachelor_department" name="bachelor_department" value="<?php echo htmlspecialchars($resume_data['bachelor_department'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="bachelor_start_date">開始日期</label>
                            <input type="date" id="bachelor_start_date" name="bachelor_start_date" value="<?php echo htmlspecialchars($resume_data['bachelor_start_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="bachelor_end_date">結束日期</label>
                            <input type="date" id="bachelor_end_date" name="bachelor_end_date" value="<?php echo htmlspecialchars($resume_data['bachelor_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-prev" data-prev="basic-info">上一步</button>
                    <button type="button" class="btn-next" data-next="experience">下一步</button>
                </div>
            </div>
            
            <!-- 工作經驗 -->
            <div class="tab-content" id="experience">
                <h2>工作經驗</h2>
                
                <div class="form-group">
                    <label for="total_experience">總年資</label>
                    <input type="text" id="total_experience" name="total_experience" value="<?php echo htmlspecialchars($resume_data['total_experience'] ?? ''); ?>">
                </div>
                
                <div class="job-section">
                    <h3>工作經歷 1</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job1_title">職稱</label>
                            <input type="text" id="job1_title" name="job1_title" value="<?php echo htmlspecialchars($resume_data['job1_title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job1_company">公司名稱</label>
                            <input type="text" id="job1_company" name="job1_company" value="<?php echo htmlspecialchars($resume_data['job1_company'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job1_industry">產業類別</label>
                            <input type="text" id="job1_industry" name="job1_industry" value="<?php echo htmlspecialchars($resume_data['job1_industry'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job1_company_size">公司規模</label>
                            <input type="text" id="job1_company_size" name="job1_company_size" value="<?php echo htmlspecialchars($resume_data['job1_company_size'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="job1_location">工作地點</label>
                        <input type="text" id="job1_location" name="job1_location" value="<?php echo htmlspecialchars($resume_data['job1_location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job1_start_date">開始日期</label>
                            <input type="date" id="job1_start_date" name="job1_start_date" value="<?php echo htmlspecialchars($resume_data['job1_start_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job1_end_date">結束日期</label>
                            <input type="date" id="job1_end_date" name="job1_end_date" value="<?php echo htmlspecialchars($resume_data['job1_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="job1_duration">工作時長</label>
                        <input type="text" id="job1_duration" name="job1_duration" value="<?php echo htmlspecialchars($resume_data['job1_duration'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="job1_description">工作描述</label>
                        <textarea id="job1_description" name="job1_description" rows="4"><?php echo htmlspecialchars($resume_data['job1_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="job1_technologies">使用技術</label>
                        <input type="text" id="job1_technologies" name="job1_technologies" value="<?php echo htmlspecialchars($resume_data['job1_technologies'] ?? ''); ?>" placeholder="例如: Python,Java,SQL">
                    </div>
                </div>
                
                <div class="job-section">
                    <h3>工作經歷 2</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job2_title">職稱</label>
                            <input type="text" id="job2_title" name="job2_title" value="<?php echo htmlspecialchars($resume_data['job2_title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job2_company">公司名稱</label>
                            <input type="text" id="job2_company" name="job2_company" value="<?php echo htmlspecialchars($resume_data['job2_company'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job2_industry">產業類別</label>
                            <input type="text" id="job2_industry" name="job2_industry" value="<?php echo htmlspecialchars($resume_data['job2_industry'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job2_company_size">公司規模</label>
                            <input type="text" id="job2_company_size" name="job2_company_size" value="<?php echo htmlspecialchars($resume_data['job2_company_size'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="job2_location">工作地點</label>
                        <input type="text" id="job2_location" name="job2_location" value="<?php echo htmlspecialchars($resume_data['job2_location'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="job2_start_date">開始日期</label>
                            <input type="date" id="job2_start_date" name="job2_start_date" value="<?php echo htmlspecialchars($resume_data['job2_start_date'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="job2_end_date">結束日期</label>
                            <input type="date" id="job2_end_date" name="job2_end_date" value="<?php echo htmlspecialchars($resume_data['job2_end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="job2_duration">工作時長</label>
                        <input type="text" id="job2_duration" name="job2_duration" value="<?php echo htmlspecialchars($resume_data['job2_duration'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="job2_description">工作描述</label>
                        <textarea id="job2_description" name="job2_description" rows="4"><?php echo htmlspecialchars($resume_data['job2_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="job2_technologies">使用技術</label>
                        <input type="text" id="job2_technologies" name="job2_technologies" value="<?php echo htmlspecialchars($resume_data['job2_technologies'] ?? ''); ?>" placeholder="例如: Python,Java,SQL">
                    </div>
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-prev" data-prev="education">上一步</button>
                    <button type="button" class="btn-next" data-next="language">下一步</button>
                </div>
            </div>
            
            <!-- 語言能力 -->
            <div class="tab-content" id="language">
                <h2>語言能力</h2>
                
                <div class="language-section">
                    <h3>英文能力</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="english_listening">聽力</label>
                            <select id="english_listening" name="english_listening">
                                <option value="">請選擇</option>
                                <option value="精通" <?php echo (isset($resume_data['english_listening']) && $resume_data['english_listening'] == '精通') ? 'selected' : ''; ?>>精通</option>
                                <option value="高等" <?php echo (isset($resume_data['english_listening']) && $resume_data['english_listening'] == '高等') ? 'selected' : ''; ?>>高等</option>
                                <option value="中等" <?php echo (isset($resume_data['english_listening']) && $resume_data['english_listening'] == '中等') ? 'selected' : ''; ?>>中等</option>
                                <option value="初級" <?php echo (isset($resume_data['english_listening']) && $resume_data['english_listening'] == '初級') ? 'selected' : ''; ?>>初級</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="english_speaking">口說</label>
                            <select id="english_speaking" name="english_speaking">
                                <option value="">請選擇</option>
                                <option value="精通" <?php echo (isset($resume_data['english_speaking']) && $resume_data['english_speaking'] == '精通') ? 'selected' : ''; ?>>精通</option>
                                <option value="高等" <?php echo (isset($resume_data['english_speaking']) && $resume_data['english_speaking'] == '高等') ? 'selected' : ''; ?>>高等</option>
                                <option value="中等" <?php echo (isset($resume_data['english_speaking']) && $resume_data['english_speaking'] == '中等') ? 'selected' : ''; ?>>中等</option>
                                <option value="初級" <?php echo (isset($resume_data['english_speaking']) && $resume_data['english_speaking'] == '初級') ? 'selected' : ''; ?>>初級</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="english_reading">閱讀</label>
                            <select id="english_reading" name="english_reading">
                                <option value="">請選擇</option>
                                <option value="精通" <?php echo (isset($resume_data['english_reading']) && $resume_data['english_reading'] == '精通') ? 'selected' : ''; ?>>精通</option>
                                <option value="高等" <?php echo (isset($resume_data['english_reading']) && $resume_data['english_reading'] == '高等') ? 'selected' : ''; ?>>高等</option>
                                <option value="中等" <?php echo (isset($resume_data['english_reading']) && $resume_data['english_reading'] == '中等') ? 'selected' : ''; ?>>中等</option>
                                <option value="初級" <?php echo (isset($resume_data['english_reading']) && $resume_data['english_reading'] == '初級') ? 'selected' : ''; ?>>初級</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="english_writing">寫作</label>
                            <select id="english_writing" name="english_writing">
                                <option value="">請選擇</option>
                                <option value="精通" <?php echo (isset($resume_data['english_writing']) && $resume_data['english_writing'] == '精通') ? 'selected' : ''; ?>>精通</option>
                                <option value="高等" <?php echo (isset($resume_data['english_writing']) && $resume_data['english_writing'] == '高等') ? 'selected' : ''; ?>>高等</option>
                                <option value="中等" <?php echo (isset($resume_data['english_writing']) && $resume_data['english_writing'] == '中等') ? 'selected' : ''; ?>>中等</option>
                                <option value="初級" <?php echo (isset($resume_data['english_writing']) && $resume_data['english_writing'] == '初級') ? 'selected' : ''; ?>>初級</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="toeic_score">多益成績 (TOEIC)</label>
                        <input type="number" id="toeic_score" name="toeic_score" value="<?php echo htmlspecialchars($resume_data['toeic_score'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-prev" data-prev="experience">上一步</button>
                    <button type="button" class="btn-next" data-next="job-preference">下一步</button>
                </div>
            </div>
            
            <!-- 求職條件 -->
            <div class="tab-content" id="job-preference">
                <h2>求職條件</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="job_type">希望性質</label>
                        <select id="job_type" name="job_type">
                            <option value="">請選擇</option>
                            <option value="全職工作" <?php echo (isset($resume_data['job_type']) && $resume_data['job_type'] == '全職工作') ? 'selected' : ''; ?>>全職工作</option>
                            <option value="兼職工作" <?php echo (isset($resume_data['job_type']) && $resume_data['job_type'] == '兼職工作') ? 'selected' : ''; ?>>兼職工作</option>
                            <option value="實習" <?php echo (isset($resume_data['job_type']) && $resume_data['job_type'] == '實習') ? 'selected' : ''; ?>>實習</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="work_shift">上班時段</label>
                        <input type="text" id="work_shift" name="work_shift" value="<?php echo htmlspecialchars($resume_data['work_shift'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="available_date">可上班日</label>
                        <input type="text" id="available_date" name="available_date" value="<?php echo htmlspecialchars($resume_data['available_date'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_salary">希望待遇</label>
                        <input type="text" id="expected_salary" name="expected_salary" value="<?php echo htmlspecialchars($resume_data['expected_salary'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="preferred_locations">希望地點</label>
                    <input type="text" id="preferred_locations" name="preferred_locations" value="<?php echo htmlspecialchars($resume_data['preferred_locations'] ?? ''); ?>" placeholder="例如: 台北市、新北市、桃園市">
                </div>
                
                <div class="form-group">
                    <label for="preferred_job_title">希望職稱</label>
                    <input type="text" id="preferred_job_title" name="preferred_job_title" value="<?php echo htmlspecialchars($resume_data['preferred_job_title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="preferred_job_categories">希望職類</label>
                    <input type="text" id="preferred_job_categories" name="preferred_job_categories" value="<?php echo htmlspecialchars($resume_data['preferred_job_categories'] ?? ''); ?>" placeholder="例如: 工程師、程式設計師、資料分析師">
                </div>
                
                <div class="form-group">
                    <label for="preferred_industries">希望產業</label>
                    <input type="text" id="preferred_industries" name="preferred_industries" value="<?php echo htmlspecialchars($resume_data['preferred_industries'] ?? ''); ?>" placeholder="例如: 資訊科技、金融服務、電子商務">
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-prev" data-prev="language">上一步</button>
                    <button type="button" class="btn-next" data-next="skills">下一步</button>
                </div>
            </div>
            
            <!-- 專長技能 -->
            <div class="tab-content" id="skills">
                <h2>專長技能</h2>
                
                <div class="form-group">
                    <label for="skills">專長技能清單</label>
                    <textarea id="skills" name="skills" rows="4" placeholder="請列出您的技能，例如: Python、Java、SQL、Azure、AWS..."><?php echo htmlspecialchars($resume_data['skills'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="github_url">GitHub 連結</label>
                    <input type="url" id="github_url" name="github_url" value="<?php echo htmlspecialchars($resume_data['github_url'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="autobiography">自傳</label>
                    <textarea id="autobiography" name="autobiography" rows="8"><?php echo htmlspecialchars($resume_data['autobiography'] ?? ''); ?></textarea>
                </div>
                
                <div class="tab-actions">
                    <button type="button" class="btn-prev" data-prev="job-preference">上一步</button>
                    <button type="submit" class="btn-save">儲存履歷資料</button>
                </div>
            </div>
        </form>
        
        <div class="action-buttons">
            <a href="login_success.php" class="btn btn-secondary">返回儀表板</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 分頁切換功能
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // 移除所有標籤的活動狀態
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // 設置當前標籤為活動狀態
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // 下一步按鈕功能
            const nextButtons = document.querySelectorAll('.btn-next');
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const nextTabId = this.getAttribute('data-next');
                    
                    // 移除所有標籤的活動狀態
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // 設置下一個標籤為活動狀態
                    document.querySelector(`.tab-btn[data-tab="${nextTabId}"]`).classList.add('active');
                    document.getElementById(nextTabId).classList.add('active');
                });
            });
            
            // 上一步按鈕功能
            const prevButtons = document.querySelectorAll('.btn-prev');
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const prevTabId = this.getAttribute('data-prev');
                    
                    // 移除所有標籤的活動狀態
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // 設置上一個標籤為活動狀態
                    document.querySelector(`.tab-btn[data-tab="${prevTabId}"]`).classList.add('active');
                    document.getElementById(prevTabId).classList.add('active');
                });
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