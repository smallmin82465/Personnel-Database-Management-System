# HR Management System

[中文版本](#人力資源管理系統)

## Overview

This project is a comprehensive HR Management System designed to streamline the process of managing employee information, resumes, and administrative tasks. Built with PHP and MySQL, it provides a secure and efficient platform for HR operations.

## Features

- **User Authentication System**
  - Secure login/registration with password hashing
  - Role-based access control (admin/regular users)

- **Profile Management**
  - Detailed personal information management
  - Education history tracking
  - Work experience documentation
  - Language proficiency recording
  - Job preferences and skill sets

- **Resume Management(not finish)**
  - 104 Resume (Taiwan job platform) PDF upload capability
  - Resume data extraction and parsing
  - Resume data editing and updating

- **Admin Dashboard**
  - User data management
  - Resume data management
  - Excel export functionality for data analysis
  - Batch import via Excel files

## Technical Structure

- **Frontend**: HTML, CSS, JavaScript with responsive design
- **Backend**: PHP 
- **Database**: MySQL
- **Libraries**:
  - PhpSpreadsheet for Excel processing
  - Various PHP core libraries (Remember to open ZIP extension in php.ini)

## Installation

1. Clone the repository to your web server directory:
   ```bash
   git clone [repository-url] hr-management-system
   ```

2. Set up the MySQL database:
   - Create a new database in MySQL
   - Import the database schema (SQL file not included in the repository)

3. Configure the database connection:
   - Edit the `config.cfg` file with your database credentials:
   ```
   $DatabaseInfo = [
       "host" => "your-db-host",
       "database" => "your-db-name",
       "username" => "your-username",
       "password" => "your-password",
       "port" => "3306"
   ];
   $AdminPassword = "your-admin-password";
   ```

4. Install dependencies:
   ```bash
   composer install
   ```

5. Ensure proper directory permissions:
   ```bash
   chmod 755 -R ./temp
   ```

6. Access the system through your web browser.

## Security Notes

- Ensure your web server is properly configured with SSL
- Regularly update the admin password
- Consider implementing additional security measures like:
  - IP restriction for admin access
  - Two-factor authentication
  - Regular security audits

## File Structure

```
├── config.cfg               # Configuration file
├── config.php               # Configuration processor
├── index.php                # Main entry point
├── login.php                # Authentication handler
├── register.php             # User registration
├── login_success.php        # Dashboard after login
├── logout.php               # Session termination
├── myprofile.php            # Profile management
├── uploadresume.php         # Resume upload functionality
├── changepassword.php       # Password management
├── enteradmin.php           # Admin panel
├── uploadxlsx.php           # Excel upload functionality
├── style/                   # CSS style files
│   ├── main.css             # Main stylesheet
│   ├── login.css            # Login page styles
│   └── ...                  # Other style files
├── temp/                    # Temporary file storage
└── vendor/                  # Composer dependencies
```


---

# 人力資源管理系統

[English Version](#hr-management-system)

## 概述

本專案是一個綜合性的人力資源管理系統，旨在簡化員工資訊、履歷和行政任務的管理流程。使用 PHP 和 MySQL 構建，為人力資源操作提供安全高效的平台。

## 功能特點

- **使用者認證系統**
  - 密碼雜湊的安全登入/註冊功能
  - 基於角色的存取控制（管理員/一般使用者）

- **個人資料管理**
  - 詳細的個人資訊管理
  - 教育經歷追蹤
  - 工作經驗記錄
  - 語言能力紀錄
  - 求職偏好與專業技能

- **履歷管理(尚未開放)**
  - 104人力銀行履歷 PDF 上傳功能
  - 履歷資料提取與分析
  - 履歷資料編輯與更新

- **管理後台**
  - 使用者資料管理
  - 履歷資料管理
  - Excel資料匯出功能以供分析
  - 透過Excel檔案批量匯入

## 技術架構

- **前端**: HTML, CSS, JavaScript, rwd響應式設計
- **後端**: PHP
- **資料庫**: MySQL
- **函式庫**:
  - PhpSpreadsheet 用於Excel處理
  - 各種PHP核心函式庫 (php.ini中ZIP拓展記得要開)

## 安裝步驟

1. 將儲存庫克隆到您的網頁伺服器目錄：
   ```bash
   git clone [儲存庫網址] hr-management-system
   ```

2. 設置MySQL資料庫：
   - 在MySQL中創建新資料庫
   - 匯入資料庫結構（SQL檔案未包含在儲存庫中）

3. 配置資料庫連接：
   - 編輯 `config.cfg` 檔案，填入您的資料庫憑證：
   ```
   $DatabaseInfo = [
       "host" => "你的資料庫主機",
       "database" => "你的資料庫名稱",
       "username" => "你的使用者名稱",
       "password" => "你的密碼",
       "port" => "3306"
   ];
   $AdminPassword = "你的管理員密碼";
   ```

4. 安裝依賴項：
   ```bash
   composer install
   ```

5. 確保目錄權限正確：
   ```bash
   chmod 755 -R ./temp
   ```

6. 透過網頁瀏覽器訪問系統。

## 安全注意事項

- 確保您的網頁伺服器已正確配置SSL
- 定期更新管理員密碼
- 考慮實施其他安全措施，如：
  - 管理員訪問的IP限制
  - 雙因素認證
  - 定期安全審計

## 檔案結構

```
├── config.cfg               # 配置檔案
├── config.php               # 配置處理器
├── index.php                # 主入口點
├── login.php                # 認證處理器
├── register.php             # 使用者註冊
├── login_success.php        # 登入後儀表板
├── logout.php               # 登出處理
├── myprofile.php            # 個人資料管理
├── uploadresume.php         # 履歷上傳功能
├── changepassword.php       # 密碼管理
├── enteradmin.php           # 管理員面板
├── uploadxlsx.php           # Excel上傳功能
├── style/                   # CSS樣式檔案
│   ├── main.css             # 主樣式表
│   ├── login.css            # 登入頁面樣式
│   └── ...                  # 其他樣式檔案
├── temp/                    # 臨時檔案儲存
└── vendor/                  # Composer依賴項
```
