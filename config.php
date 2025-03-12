<?php
// 設定錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 讀取配置文件
$config_file = 'config.cfg';

// 檢查配置文件是否存在
if (!file_exists($config_file)) {
    die('錯誤：配置文件不存在');
}

// 讀取配置文件內容
$config_content = file_get_contents($config_file);

// 使用正規表達式解析配置文件
preg_match('/\$DatabaseInfo\s*=\s*\[(.*?)\];/s', $config_content, $matches);

if (empty($matches[1])) {
    die('錯誤：配置文件格式不正確');
}

// 解析配置項
$config_items = explode(',', $matches[1]);
$DatabaseInfo = [];

foreach ($config_items as $item) {
    // 移除多餘的空白
    $item = trim($item);
    
    // 解析鍵值對
    if (preg_match('/"([^"]+)"\s*=>\s*"([^"]*)"/', $item, $kv_matches)) {
        $key = $kv_matches[1];
        $value = $kv_matches[2];
        $DatabaseInfo[$key] = $value;
    }
}

// 檢查是否包含所有必要的配置項
$required_keys = ['host', 'database', 'username', 'password', 'port'];
foreach ($required_keys as $key) {
    if (!isset($DatabaseInfo[$key])) {
        die("錯誤：配置文件缺少 '{$key}' 項");
    }
}

// 設置時區
date_default_timezone_set('Asia/Taipei');