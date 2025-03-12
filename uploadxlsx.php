<?php
// 啟動 session
session_start();

// 檢查用戶是否已登入且具有管理員認證
if (!isset($_SESSION['user_id']) || !isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header("Location: login.php");
    exit;
}

// 引入配置文件
require_once 'config.php';

// 設定上傳文件的限制
$maxFileSize = 10 * 1024 * 1024; // 10MB
$allowedExtensions = ['xlsx', 'xls'];

// 初始化變數
$error = '';
$success = '';
$sheetType = '';
$processingResult = [
    'total' => 0,
    'inserted' => 0,
    'updated' => 0,
    'failed' => 0,
    'errors' => []
];

// 處理表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
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
    } else {
        // 檢查檔案大小
        if ($file['size'] > $maxFileSize) {
            $error = "檔案過大，請上傳小於10MB的檔案。";
        } else {
            // 檢查檔案類型和副檔名
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = "僅支援 XLSX 或 XLS 檔案格式。";
            } else {
                // 使用臨時文件夾存儲上傳的文件
                $tempDir = './temp/';
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                $tempFilePath = $tempDir . basename($file['name']);
                
                // 移動上傳的文件到臨時目錄
                if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
                    try {
                        // 確認PHP有正確載入必要的擴展
                        if (!extension_loaded('zip')) {
                            throw new Exception("缺少 ZIP 擴展，無法處理 XLSX 文件。");
                        }
                        
                        // 檢查是否已經安裝了 PhpSpreadsheet
                        if (!file_exists('vendor/autoload.php')) {
                            throw new Exception("缺少必要的依賴庫，請確保已執行 'composer require phpoffice/phpspreadsheet'。");
                        }
                        
                        // 引入 PhpSpreadsheet 庫
                        require_once 'vendor/autoload.php';
                        
                        // 讀取 Excel 檔案
                        if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFilePath);
                            $worksheet = $spreadsheet->getActiveSheet();
                            
                            // 獲取表頭
                            $headers = [];
                            $firstRow = $worksheet->getRowIterator(1, 1)->current();
                            $cellIterator = $firstRow->getCellIterator();
                            $cellIterator->setIterateOnlyExistingCells(false);
                            
                            foreach ($cellIterator as $cell) {
                                $headers[] = $cell->getValue() !== null ? trim($cell->getValue()) : '';
                            }
                            
                            // 判斷表格類型
                            $sheetType = determineSheetType($headers);
                            
                            if ($sheetType === 'users') {
                                $processingResult = processUsersSheet($worksheet, $headers, $DatabaseInfo);
                                $success = "用戶資料匯入完成！總計處理 {$processingResult['total']} 筆資料，成功新增 {$processingResult['inserted']} 筆，更新 {$processingResult['updated']} 筆，失敗 {$processingResult['failed']} 筆。";
                            } elseif ($sheetType === 'resumes') {
                                $processingResult = processResumesSheet($worksheet, $headers, $DatabaseInfo);
                                $success = "履歷資料匯入完成！總計處理 {$processingResult['total']} 筆資料，成功新增 {$processingResult['inserted']} 筆，更新 {$processingResult['updated']} 筆，失敗 {$processingResult['failed']} 筆。";
                            } else {
                                $error = "無法識別上傳的 Excel 文件格式，請確保表頭欄位符合系統要求。";
                            }
                        } else {
                            throw new Exception("無法載入 PhpSpreadsheet 庫，請確保已正確安裝。");
                        }
                    } catch (Exception $e) {
                        $error = "處理 Excel 檔案時發生錯誤：" . $e->getMessage();
                        error_log("Excel 處理錯誤: " . $e->getMessage());
                    }
                    
                    // 清理臨時文件
                    if (file_exists($tempFilePath)) {
                        unlink($tempFilePath);
                    }
                } else {
                    $error = "無法移動上傳的檔案，請檢查目錄權限或重試。";
                }
            }
        }
    }
}

/**
 * 判斷上傳的 Excel 表格類型
 * @param array $headers 表頭欄位名稱數組
 * @return string 返回識別的表格類型：'users', 'resumes', 或 'unknown'
 */
function determineSheetType($headers) {
    // 用戶表的關鍵欄位
    $userKeyFields = ['username', '用戶名', 'email', '電子郵件', 'password', '密碼', 'role', '角色'];
    
    // 履歷表的關鍵欄位
    $resumeKeyFields = ['chinese_name', '中文姓名', 'english_name', '英文姓名', 'age', '年齡', 'education_level', '最高學歷', 
                       'job1_title', '工作1職稱', 'skills', '專長技能'];
    
    // 計算匹配度
    $userMatchCount = 0;
    $resumeMatchCount = 0;
    
    foreach ($headers as $header) {
        $header = strtolower(trim($header));
        if (in_array($header, $userKeyFields)) {
            $userMatchCount++;
        }
        if (in_array($header, $resumeKeyFields)) {
            $resumeMatchCount++;
        }
    }
    
    // 基於匹配度判斷表格類型
    if ($userMatchCount >= 3 && $userMatchCount > $resumeMatchCount) {
        return 'users';
    } elseif ($resumeMatchCount >= 3 && $resumeMatchCount > $userMatchCount) {
        return 'resumes';
    } else {
        return 'unknown';
    }
}

/**
 * 處理用戶表格數據
 * @param object $worksheet PhpSpreadsheet 工作表對象
 * @param array $headers 表頭欄位名稱數組
 * @param array $dbConfig 資料庫連接配置
 * @return array 處理結果統計
 */
function processUsersSheet($worksheet, $headers, $dbConfig) {
    $result = [
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    try {
        // 建立資料庫連接
        $conn = new mysqli(
            $dbConfig["host"],
            $dbConfig["username"],
            $dbConfig["password"],
            $dbConfig["database"],
            $dbConfig["port"]
        );
        
        // 檢查連線
        if ($conn->connect_error) {
            throw new Exception("資料庫連線失敗: " . $conn->connect_error);
        }
        
        // 設定字符集
        $conn->set_charset("utf8mb4");
        
        // 獲取所有行數據
        $rows = $worksheet->getRowIterator(2); // 從第二行開始，跳過表頭
        
        // 預處理語句
        $checkUserStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $insertUserStmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $updateUserStmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
        $updatePasswordStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        foreach ($rows as $row) {
            $result['total']++;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            // 準備每行的數據
            $userData = [];
            $index = 0;
            
            foreach ($cellIterator as $cell) {
                $columnName = isset($headers[$index]) ? strtolower(trim($headers[$index])) : '';
                
                // 標準化欄位名稱（處理中英文欄位名稱）
                switch ($columnName) {
                    case '用戶名':
                        $columnName = 'username';
                        break;
                    case '電子郵件':
                        $columnName = 'email';
                        break;
                    case '密碼':
                        $columnName = 'password';
                        break;
                    case '角色':
                        $columnName = 'role';
                        break;
                    case '狀態':
                        $columnName = 'status';
                        break;
                }
                
                if ($columnName) {
                    $cellValue = $cell->getValue();
                    $userData[$columnName] = $cellValue !== null ? trim($cellValue) : '';
                }
                
                $index++;
            }
            
            // 必填欄位檢查
            if (empty($userData['username']) || empty($userData['email'])) {
                $result['failed']++;
                $result['errors'][] = "第 {$result['total']} 行：用戶名和電子郵件為必填項";
                continue;
            }
            
            // 設置默認值
            if (empty($userData['role'])) {
                $userData['role'] = 'user';
            }
            if (empty($userData['status'])) {
                $userData['status'] = 'active';
            }
            
            // 檢查用戶是否已存在
            $checkUserStmt->bind_param("ss", $userData['username'], $userData['email']);
            $checkUserStmt->execute();
            $checkResult = $checkUserStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // 用戶已存在，進行更新
                $user = $checkResult->fetch_assoc();
                $userId = $user['id'];
                
                // 更新用戶基本信息
                $updateUserStmt->bind_param("ssssi", $userData['username'], $userData['email'], $userData['role'], $userData['status'], $userId);
                
                if ($updateUserStmt->execute()) {
                    $result['updated']++;
                    
                    // 如果提供了新密碼，則更新密碼
                    if (!empty($userData['password'])) {
                        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
                        $updatePasswordStmt->bind_param("si", $hashedPassword, $userId);
                        $updatePasswordStmt->execute();
                    }
                } else {
                    $result['failed']++;
                    $result['errors'][] = "第 {$result['total']} 行：更新用戶失敗 - " . $updateUserStmt->error;
                }
            } else {
                // 新用戶，進行插入
                if (empty($userData['password'])) {
                    // 如果沒有提供密碼，使用隨機生成的密碼
                    $randomPassword = substr(md5(rand()), 0, 8);
                    $userData['password'] = $randomPassword;
                    $result['errors'][] = "第 {$result['total']} 行：為 {$userData['username']} 生成隨機密碼：{$randomPassword}";
                }
                
                $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
                $insertUserStmt->bind_param("sssss", $userData['username'], $userData['email'], $hashedPassword, $userData['role'], $userData['status']);
                
                if ($insertUserStmt->execute()) {
                    $result['inserted']++;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "第 {$result['total']} 行：插入用戶失敗 - " . $insertUserStmt->error;
                }
            }
        }
        
        // 關閉所有語句和連接
        $checkUserStmt->close();
        $insertUserStmt->close();
        $updateUserStmt->close();
        if (isset($updatePasswordStmt)) {
            $updatePasswordStmt->close();
        }
        $conn->close();
        
    } catch (Exception $e) {
        $result['failed'] = $result['total'];
        $result['errors'][] = "處理用戶資料時發生錯誤：" . $e->getMessage();
    }
    
    return $result;
}

/**
 * 處理履歷表格數據
 * @param object $worksheet PhpSpreadsheet 工作表對象
 * @param array $headers 表頭欄位名稱數組
 * @param array $dbConfig 資料庫連接配置
 * @return array 處理結果統計
 */
function processResumesSheet($worksheet, $headers, $dbConfig) {
    $result = [
        'total' => 0,
        'inserted' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    try {
        // 建立資料庫連接
        $conn = new mysqli(
            $dbConfig["host"],
            $dbConfig["username"],
            $dbConfig["password"],
            $dbConfig["database"],
            $dbConfig["port"]
        );
        
        // 檢查連線
        if ($conn->connect_error) {
            throw new Exception("資料庫連線失敗: " . $conn->connect_error);
        }
        
        // 設定字符集
        $conn->set_charset("utf8mb4");
        
        // 獲取所有行數據
        $rows = $worksheet->getRowIterator(2); // 從第二行開始，跳過表頭
        
        // 欄位名稱映射（中文到英文）
        $fieldMapping = [
            'ID' => 'id',
            '用戶ID' => 'user_id',
            '中文姓名' => 'chinese_name',
            '英文姓名' => 'english_name',
            '性別' => 'gender',
            '年齡' => 'age',
            '兵役狀態' => 'military_status',
            '就業狀態' => 'employment_status',
            '電話' => 'phone',
            '電子郵件' => 'email',
            '地址' => 'address',
            '聯絡方式' => 'contact_method',
            '駕駛執照' => 'driving_license',
            '交通工具' => 'transportation',
            '最高學歷' => 'education_level',
            '碩士學校' => 'master_school',
            '碩士系所' => 'master_department',
            '碩士開始日期' => 'master_start_date',
            '碩士結束日期' => 'master_end_date',
            '學士學校' => 'bachelor_school',
            '學士系所' => 'bachelor_department',
            '學士開始日期' => 'bachelor_start_date',
            '學士結束日期' => 'bachelor_end_date',
            '總年資' => 'total_experience',
            '工作1職稱' => 'job1_title',
            '工作1公司' => 'job1_company',
            '工作1產業' => 'job1_industry',
            '工作1公司規模' => 'job1_company_size',
            '工作1地點' => 'job1_location',
            '工作1開始日期' => 'job1_start_date',
            '工作1結束日期' => 'job1_end_date',
            '工作1時長' => 'job1_duration',
            '工作1描述' => 'job1_description',
            '工作1技術' => 'job1_technologies',
            '工作2職稱' => 'job2_title',
            '工作2公司' => 'job2_company',
            '工作2產業' => 'job2_industry',
            '工作2公司規模' => 'job2_company_size',
            '工作2地點' => 'job2_location',
            '工作2開始日期' => 'job2_start_date',
            '工作2結束日期' => 'job2_end_date',
            '工作2時長' => 'job2_duration',
            '工作2描述' => 'job2_description',
            '工作2技術' => 'job2_technologies',
            '英語聽力' => 'english_listening',
            '英語口說' => 'english_speaking',
            '英語閱讀' => 'english_reading',
            '英語寫作' => 'english_writing',
            '多益成績' => 'toeic_score',
            '希望工作性質' => 'job_type',
            '上班時段' => 'work_shift',
            '可上班日' => 'available_date',
            '期望薪資' => 'expected_salary',
            '希望地點' => 'preferred_locations',
            '希望職稱' => 'preferred_job_title',
            '希望職類' => 'preferred_job_categories',
            '希望產業' => 'preferred_industries',
            '專長技能' => 'skills',
            'GitHub連結' => 'github_url',
            '自傳' => 'autobiography'
        ];
        
        // 檢查是否有用戶ID和用戶名對應的欄位
        $hasUserIdColumn = false;
        $hasUsernameColumn = false;
        
        foreach ($headers as $header) {
            $headerLower = strtolower(trim($header));
            if ($headerLower === 'user_id' || $headerLower === '用戶id') {
                $hasUserIdColumn = true;
            }
            if ($headerLower === 'username' || $headerLower === '用戶名') {
                $hasUsernameColumn = true;
            }
        }
        
        foreach ($rows as $row) {
            $result['total']++;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            // 準備每行的數據
            $resumeData = [];
            $username = null;
            $userId = null;
            $index = 0;
            
            foreach ($cellIterator as $cell) {
                $headerName = isset($headers[$index]) ? trim($headers[$index]) : '';
                $columnName = strtolower($headerName);
                
                // 處理中英文欄位名稱
                if (isset($fieldMapping[$headerName])) {
                    $columnName = $fieldMapping[$headerName];
                }
                
                // 處理用户名和用户ID
                if ($columnName === 'username' || $columnName === '用户名') {
                    $cellValue = $cell->getValue();
                    $username = $cellValue !== null ? trim($cellValue) : '';
                } elseif ($columnName === 'user_id' || $columnName === '用户id') {
                    $cellValue = $cell->getValue();
                    $userId = $cellValue !== null ? trim($cellValue) : '';
                    if (!empty($userId) && is_numeric($userId)) {
                        $userId = (int)$userId;
                    } else {
                        $userId = null;
                    }
                } elseif ($columnName && $columnName !== 'id') { // 排除ID列
                    $cellValue = $cell->getValue();
                    
                    // 標準化日期格式
                    $isDateField = strpos($columnName, 'date') !== false || 
                                  strpos($columnName, 'start_date') !== false || 
                                  strpos($columnName, 'end_date') !== false;
                    
                    // 日期字段需要特殊處理
                    if ($isDateField && $cellValue !== null) {
                        // 如果是Excel日期数值（数字格式）
                        if (is_numeric($cellValue)) {
                            // Excel日期是从1900-01-01开始的天数
                            // 转换为PHP时间戳再转为MySQL日期格式
                            $unixTimestamp = ($cellValue - 25569) * 86400; // 25569是1900-01-01到1970-01-01的天数差
                            $cellValue = date('Y-m-d', $unixTimestamp);
                        } 
                        // 如果已经是字符串格式但需要标准化
                        else if (is_string($cellValue)) {
                            // 尝试将各种日期格式转换为Y-m-d
                            $dateTime = date_create_from_format('Y/m/d', $cellValue) ?: 
                                       date_create_from_format('d/m/Y', $cellValue) ?: 
                                       date_create_from_format('m/d/Y', $cellValue) ?: 
                                       date_create_from_format('Y-m-d', $cellValue);
                            
                            if ($dateTime) {
                                $cellValue = $dateTime->format('Y-m-d');
                            } else {
                                // 如果无法识别日期格式，设为NULL
                                $cellValue = null;
                            }
                        }
                    }
                    
                    $resumeData[$columnName] = $cellValue !== null ? trim($cellValue) : '';
                }
                
                $index++;
            }
            
            // 如果有用戶名但沒有用戶ID，查詢用戶ID
            if ($username && !$userId) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $userResult = $stmt->get_result();
                
                if ($userResult->num_rows > 0) {
                    $userRow = $userResult->fetch_assoc();
                    $userId = $userRow['id'];
                } else {
                    // 找不到用戶，仍然允許創建履歷，只是userId為null
                    $result['errors'][] = "第 {$result['total']} 行：找不到用戶名為 '{$username}' 的用戶，履歷將創建但不關聯到任何用戶";
                }
                
                $stmt->close();
            }
            
            // 如果有userId，檢查該用戶是否已有履歷
            if ($userId) {
                $checkResumeStmt = $conn->prepare("SELECT id FROM resume WHERE user_id = ?");
                $checkResumeStmt->bind_param("i", $userId);
                $checkResumeStmt->execute();
                $checkResult = $checkResumeStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    // 已有履歷，進行更新
                    $resume = $checkResult->fetch_assoc();
                    $resumeId = $resume['id'];
                    
                    // 構建更新SQL
                    $updateFields = [];
                    $updateValues = [];
                    $updateTypes = "";
                    
                    foreach ($resumeData as $field => $value) {
                        $updateFields[] = "`$field` = ?";
                        $updateValues[] = $value;
                        
                        // 判斷數據類型，設置對應的類型標識
                        if (is_numeric($value) && strpos($field, 'phone') === false && strpos($field, 'date') === false) {
                            $updateTypes .= "i"; // 整數
                        } else {
                            $updateTypes .= "s"; // 字符串
                        }
                    }
                    
                    // 添加更新時間和用戶ID
                    $updateFields[] = "updated_at = NOW()";
                    
                    $updateSQL = "UPDATE resume SET " . implode(", ", $updateFields) . " WHERE id = ?";
                    $updateValues[] = $resumeId;
                    $updateTypes .= "i";
                    
                    $updateStmt = $conn->prepare($updateSQL);
                    if ($updateStmt) {
                        $updateStmt->bind_param($updateTypes, ...$updateValues);
                        
                        if ($updateStmt->execute()) {
                            $result['updated']++;
                        } else {
                            $result['failed']++;
                            $result['errors'][] = "第 {$result['total']} 行：更新履歷失敗 - " . $updateStmt->error;
                        }
                        
                        $updateStmt->close();
                    } else {
                        $result['failed']++;
                        $result['errors'][] = "第 {$result['total']} 行：準備更新語句失敗 - " . $conn->error;
                    }
                    
                    $checkResumeStmt->close();
                } else {
                    // 沒有履歷且有userId，直接插入
                    $resumeData['user_id'] = $userId;
                    insertResumeData($conn, $resumeData, $result);
                    $checkResumeStmt->close();
                }
            } else {
                // 沒有userId，直接插入新履歷，不關聯到任何用戶
                // 確保不設置user_id，讓它保持為NULL
                if (array_key_exists('user_id', $resumeData)) {
                    unset($resumeData['user_id']);
                }
                insertResumeData($conn, $resumeData, $result);
            }
        }
        
        // 關閉資料庫連接
        $conn->close();
        
    } catch (Exception $e) {
        $result['failed'] = $result['total'];
        $result['errors'][] = "處理履歷資料時發生錯誤：" . $e->getMessage();
    }
    
    return $result;
}

/**
 * 插入履歷數據的輔助函數
 * @param object $conn 資料庫連接對象
 * @param array $resumeData 履歷數據
 * @param array &$result 處理結果統計，按引用傳遞
 */
function insertResumeData($conn, $resumeData, &$result) {
    // 確保沒有提供的user_id是NULL而不是空字串
    if (isset($resumeData['user_id']) && ($resumeData['user_id'] === '' || $resumeData['user_id'] === null)) {
        unset($resumeData['user_id']);
    }
    
    // 構建插入SQL
    $insertFields = array_keys($resumeData);
    $insertFields[] = "created_at";
    $insertFields[] = "updated_at";
    
    $placeholders = array_fill(0, count($insertFields), '?');
    $insertSQL = "INSERT INTO resume (`" . implode("`, `", $insertFields) . "`) VALUES (" . implode(", ", $placeholders) . ")";
    
    $insertValues = array_values($resumeData);
    $now = date('Y-m-d H:i:s');
    $insertValues[] = $now;
    $insertValues[] = $now;
    
    $insertTypes = "";
    foreach ($insertValues as $value) {
        if (is_numeric($value) && !is_string($value)) {
            $insertTypes .= "i"; // 整數
        } else {
            $insertTypes .= "s"; // 字符串
        }
    }
    
    $insertStmt = $conn->prepare($insertSQL);
    if ($insertStmt) {
        $insertStmt->bind_param($insertTypes, ...$insertValues);
        
        if ($insertStmt->execute()) {
            $result['inserted']++;
        } else {
            $result['failed']++;
            $result['errors'][] = "第 {$result['total']} 行：插入履歷失敗 - " . $insertStmt->error;
        }
        
        $insertStmt->close();
    } else {
        $result['failed']++;
        $result['errors'][] = "第 {$result['total']} 行：準備插入語句失敗 - " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳 Excel 檔案 - 人力資料庫管理系統</title>
    <link rel="stylesheet" href="style/main.css">
    <link rel="stylesheet" href="style/uploadxlsx.css">

</head>
<body>
    <div class="container upload-container">
        <h1>上傳 Excel 檔案</h1>
        
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
        
        <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="upload-area" id="upload_area">
                <div class="upload-icon">
                    <i class="fa fa-file-excel-o"></i>
                </div>
                <p class="upload-instruction">選擇或拖放 Excel 檔案</p>
                <p class="upload-note">支援 .xlsx 或 .xls 格式，檔案大小不超過 10MB</p>
                <input type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls" hidden>
                <button type="button" id="select_file_btn" class="btn btn-select">選擇檔案</button>
                <p id="selected_file_name" class="file-name"></p>
            </div>
            <button type="submit" class="btn btn-upload">上傳並處理檔案</button>
        </form>
        
        <?php if (!empty($processingResult['errors']) || $processingResult['total'] > 0): ?>
            <div class="processing-result">
                <h2>處理結果</h2>
                
                <div class="result-summary">
                    <p><strong>表格類型：</strong> <?php echo $sheetType === 'users' ? '用戶資料' : ($sheetType === 'resumes' ? '履歷資料' : '未識別'); ?></p>
                    <p><strong>總處理資料：</strong> <?php echo $processingResult['total']; ?> 筆</p>
                    <p><strong>成功新增：</strong> <?php echo $processingResult['inserted']; ?> 筆</p>
                    <p><strong>成功更新：</strong> <?php echo $processingResult['updated']; ?> 筆</p>
                    <p><strong>處理失敗：</strong> <?php echo $processingResult['failed']; ?> 筆</p>
                </div>
                
                <?php if (!empty($processingResult['errors'])): ?>
                    <div class="result-details">
                        <h3>處理記錄</h3>
                        <?php foreach ($processingResult['errors'] as $error): ?>
                            <div class="error-item">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="template-section">
            <h3>下載 Excel 範本</h3>
            <p>您可以下載以下範本文件，並按照模板格式填寫資料後上傳：</p>
            
            <div class="template-buttons">
                <a href="templates/users_template.xlsx" class="btn btn-template" download>用戶資料範本</a>
                <a href="templates/resumes_template.xlsx" class="btn btn-template" download>履歷資料範本</a>
            </div>
            
            <div class="template-note">
                <p><strong>注意事項：</strong></p>
                <ul>
                    <li>請勿修改範本中的欄位名稱，以確保系統能正確識別和處理資料。</li>
                    <li>用戶資料表中，如果密碼欄位留空，系統將自動生成隨機密碼。</li>
                    <li>履歷資料表可以不提供 user_id 或用戶名，系統將建立不關聯任何用戶的履歷記錄。</li>
                    <li>日期格式建議使用 YYYY-MM-DD 格式（例如：2025-03-07）。</li>
                </ul>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="enteradmin.php" class="btn btn-back">返回管理後台</a>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 檔案選擇按鈕功能
            const selectFileBtn = document.getElementById('select_file_btn');
            const fileInput = document.getElementById('excel_file');
            const fileNameDisplay = document.getElementById('selected_file_name');
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
                        
                        if (fileExt === 'xlsx' || fileExt === 'xls') {
                            fileInput.files = e.dataTransfer.files;
                            fileNameDisplay.textContent = file.name;
                        } else {
                            alert('僅支援 .xlsx 或 .xls 檔案格式');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>