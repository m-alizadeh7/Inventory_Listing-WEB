<?php
/**
 * فایل پیکربندی اصلی
 * 
 * این فایل تنظیمات اصلی سیستم را تعیین می‌کند
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// تنظیمات پایگاه داده
$db_config = [
    'host'     => 'localhost',
    'username' => '',
    'password' => '',
    'database' => '',
    'charset'  => 'utf8mb4',
    'collate'  => 'utf8mb4_general_ci'
];

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
define('BASE_PATH', dirname(__FILE__));
define('CORE_PATH', BASE_PATH . '/core');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('INCLUDES_PATH', BASE_PATH . '/includes');

// مسیرهای URL
define('BASE_URL', '');
define('ASSETS_URL', BASE_URL . '/assets');

// برای لود اتوماتیک فایل های سیستم از پیکربندی محلی استفاده می‌شود اگر وجود داشته باشد
if (file_exists(BASE_PATH . '/config.local.php')) {
    include_once(BASE_PATH . '/config.local.php');
}

// لود فایل‌های مورد نیاز سیستم
require_once(INCLUDES_PATH . '/functions.php');
