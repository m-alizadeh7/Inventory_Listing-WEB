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
    'username' => 'h312810_anbar2',  // نام کاربری دیتابیس را تغییر دهید
    'password' => '**********',      // رمز عبور دیتابیس را تغییر دهید
    'database' => 'h312810_anbar2', // نام دیتابیس را تغییر دهید
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
define('INCLUDES_PATH', CORE_PATH . '/includes');

// مسیرهای URL برای دسترسی به فایل‌ها
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url .= '://' . $_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

define('BASE_URL', $base_url);
define('ASSETS_URL', BASE_URL . 'assets');

// لود کردن فایل‌های مورد نیاز
require_once(INCLUDES_PATH . '/database.php');
require_once(INCLUDES_PATH . '/functions.php');
