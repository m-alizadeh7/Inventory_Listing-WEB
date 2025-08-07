<?php
// تنظیمات پایگاه داده
define('DB_HOST', 'localhost');
define('DB_USER', 'h312810_usranb22');
define('DB_PASS', 'v8xNDnmlOY4e');
define('DB_NAME', 'h312810_anbar22');

// اتصال به پایگاه داده
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

// بررسی اتصال
if ($conn->connect_error) {
    die("خطا در اتصال به پایگاه داده: " . $conn->connect_error);
}

// نسخه سیستم
define('SYSTEM_VERSION', '1.0.0');

// فراخوانی functions.php برای دسترسی به توابع اطمینان از ساختار دیتابیس
require_once __DIR__ . '/includes/functions.php';