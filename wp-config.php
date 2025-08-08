<?php
/**
 * کانفیگ اصلی سیستم
 * این فایل، تنظیمات پایگاه داده و سایر تنظیمات مهم را مشخص می‌کند.
 */

// پیکربندی پایگاه داده
define('DB_NAME', ''); // نام پایگاه داده
define('DB_USER', ''); // نام کاربری دیتابیس
define('DB_PASSWORD', ''); // رمز عبور دیتابیس
define('DB_HOST', 'localhost'); // میزبان دیتابیس
define('DB_CHARSET', 'utf8mb4'); // کدبندی کاراکترها
define('DB_COLLATE', 'utf8mb4_general_ci'); // collation دیتابیس

// پیشوند جداول
$table_prefix = 'inv_';

// کلیدهای امنیتی
define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');

// نسخه سیستم
define('INVENTORY_VERSION', '1.0.0');

// حالت دیباگ
define('WP_DEBUG', false);

// مسیر پوشه‌ها
define('ABSPATH', dirname(__FILE__) . '/');
define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
define('WP_CONTENT_URL', '/wp-content');
define('WP_ADMIN_DIR', ABSPATH . 'wp-admin');
define('WP_INCLUDES_DIR', ABSPATH . 'wp-includes');

// فراخوانی فایل تنظیمات محلی اگر وجود داشته باشد
if (file_exists(ABSPATH . 'wp-config-local.php')) {
    include(ABSPATH . 'wp-config-local.php');
}

// اتصال به دیتابیس
require_once(ABSPATH . 'wp-includes/db.php');
