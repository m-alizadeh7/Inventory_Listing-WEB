<?php
// تنظیمات پایگاه داده
define('DB_HOST', 'localhost');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_NAME', 'database_name');

// نسخه سیستم
if (!defined('SYSTEM_VERSION')) {
    define('SYSTEM_VERSION', '1.0.0');
}

// تابع اتصال ایمن به دیتابیس
function getDbConnection($createDbIfMissing = false) {
    // اول بدون نام دیتابیس وصل شو تا ببینیم سرور در دسترس است
    $testConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($testConn->connect_error) {
        throw new Exception("خطا در اتصال به سرور دیتابیس: " . $testConn->connect_error);
    }
    $testConn->set_charset("utf8mb4");
    
    // بررسی وجود دیتابیس
    $dbExists = $testConn->select_db(DB_NAME);
    
    if (!$dbExists && $createDbIfMissing) {
        // ایجاد دیتابیس اگر موجود نباشد
        if (!$testConn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            $testConn->close();
            throw new Exception("خطا در ایجاد دیتابیس: " . $testConn->error);
        }
        $testConn->select_db(DB_NAME);
    } elseif (!$dbExists) {
        $testConn->close();
        throw new Exception("دیتابیس '" . DB_NAME . "' وجود ندارد");
    }
    
    return $testConn;
}

// اتصال به پایگاه داده (فقط اگر دیتابیس موجود باشد)
$conn = null;
try {
    $conn = getDbConnection(false);
} catch (Exception $e) {
    // در حالت installer یا اگر دیتابیس وجود ندارد، اتصال برقرار نمی‌شود
    // کد باید از getDbConnection() استفاده کند
}