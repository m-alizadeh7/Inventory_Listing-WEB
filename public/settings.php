<?php
require_once '../config/config.php';
require_once '../app/includes/functions.php';

// Load bootstrap for theme initialization
require_once 'bootstrap.php';

// بررسی و ایجاد جدول settings
$createSql = "CREATE TABLE IF NOT EXISTS settings (
    setting_name VARCHAR(64) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!$conn->query($createSql)) {
    // اگر ایجاد با ستون updated_at سازگار نبود، یک نسخه ساده‌تر امتحان کن
    $fallback = "CREATE TABLE IF NOT EXISTS settings (
        setting_name VARCHAR(64) PRIMARY KEY,
        setting_value TEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($fallback)) {
        // اگر هنوز هم خطا دارد، خطا را لاگ کن و ادامه بده (ترجیح می‌دهیم صفحه نمایش داده شود به جای fatal)
        $error = 'خطا در ایجاد جدول settings: ' . $conn->error;
        // از اینجا به بعد، به جای توقف برنامه، مقادیر پیش‌فرض خالی استفاده می‌کنیم
    }
}

// Fetch business info
$business_info = getBusinessInfo();

// Load header
get_header();

// Load settings template directly
$template_file = ACTIVE_THEME_PATH . '/templates/settings.php';
if (file_exists($template_file)) {
    include $template_file;
} else {
    echo '<div class="alert alert-danger">فایل تنظیمات یافت نشد.</div>';
}

// Load footer
get_footer();
?>
