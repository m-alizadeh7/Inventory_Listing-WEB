<?php
/**
 * فایل تنظیمات سیستم
 * 
 * این فایل شامل تنظیمات پایگاه داده و سایر تنظیمات سیستم است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// تنظیمات پایگاه داده
$db_config = [
    'host'     => 'localhost',
    'username' => 'h312810_usranb22',  // نام کاربری دیتابیس
    'password' => 'v8xNDnmlOY4e',      // رمز عبور دیتابیس
    'database' => 'h312810_anbar22',   // نام دیتابیس
    'charset'  => 'utf8mb4',
    'collate'  => 'utf8mb4_general_ci'
];


// تعریف ثابت‌های دیتابیس
define('DB_HOST', $db_config['host']);
define('DB_USER', $db_config['username']);
define('DB_PASS', $db_config['password']);
define('DB_NAME', $db_config['database']);
define('DB_PORT', 3306); // پورت پیش‌فرض MySQL
define('DB_PREFIX', 'inv_'); // پیشوند جداول دیتابیس

// تنظیمات کلی سیستم
$config = [
    'site_title'    => 'سیستم مدیریت انبار',
    'default_theme' => 'default',
    'version'       => '1.0.0',
    'debug'         => false,
    'author'        => 'Mahdi Alizadeh',
    'website'       => 'https://alizadehx.ir',
    'email'         => 'm.alizadeh7@live.com',
    'github'        => 'https://github.com/m-alizadeh7',
    'telegram'      => 'https://t.me/alizadeh_channel'
];

// مسیرهای اصلی سیستم
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__));
}
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('DEFAULT_TEMPLATE', $config['default_theme']);
define('COOKIE_LIFETIME', 30 * 24 * 60 * 60); // 30 روز

// مسیرهای URL برای دسترسی به فایل‌ها
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url .= '://' . $_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

define('BASE_URL', $base_url);
define('ASSETS_URL', BASE_URL . 'assets');

// متغیر سراسری دیتابیس
$db = null;

// اتصال به دیتابیس
try {
    if (defined('DB_PORT') && DB_PORT) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    } else {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    
    // تنظیم کاراکتر ست اتصال
    $db->set_charset('utf8mb4');
    
    // بررسی خطای اتصال
    if ($db->connect_error) {
        throw new Exception('خطا در اتصال به دیتابیس: ' . $db->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $db = null;
}
?>