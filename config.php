<?php
// تنظیمات دیتابیس
define('DB_HOST', 'localhost');     // آدرس سرور دیتابیس
define('DB_USER', 'h312810_anbarus');   // نام کاربری دیتابیس
define('DB_PASS', '-XZ!)MwBW.ae');   // رمز عبور دیتابیس
define('DB_NAME', 'h312810_anbar');   // نام دیتابیس

// تنظیمات عمومی
define('SITE_TITLE', 'سیستم انبارداری');
define('UPLOAD_DIR', 'uploads/');
ini_set('display_errors', 0);

// اتصال به دیتابیس
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("خطا در اتصال به دیتابیس");
}

// توابع کمکی
function redirect($url) {
    header("Location: $url");
    exit();
}

function show_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

// شروع جلسه
session_start();
?>