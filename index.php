<?php
/**
 * صفحه اصلی سیستم
 * 
 * فایل ورودی اصلی برنامه که درخواست‌ها را به کنترلرهای مناسب هدایت می‌کند
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// شروع session
session_start();

// بررسی وجود فایل کانفیگ
if (file_exists('config.php')) {
    require_once 'config.php';
    
    // اتصال به دیتابیس
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    // تنظیم کاراکتر ست اتصال
    $db->set_charset('utf8mb4');
    
    // بررسی خطای اتصال
    if ($db->connect_error) {
        die('خطا در اتصال به دیتابیس: ' . $db->connect_error);
    }
} else {
    // اگر فایل کانفیگ وجود نداشت، متغیر دیتابیس را خالی می‌گذاریم
    $db = null;
}

// لود کردن مدل‌های مورد نیاز
$model_files = glob(ROOT_PATH . '/models/*.php');
if ($model_files) {
    foreach ($model_files as $model_file) {
        require_once $model_file;
    }
}

// لود کردن MainController ابتدا (برای استفاده در سایر کنترلرها)
require_once ROOT_PATH . '/controllers/MainController.php';

// تنظیم نوع درخواست و پارامترهای آن
$controller_name = isset($_GET['controller']) ? $_GET['controller'] : 'main';
$action_name = isset($_GET['action']) ? $_GET['action'] : 'index';

// لود کردن کنترلر مناسب
$controller_path = ROOT_PATH . '/controllers/' . ucfirst($controller_name) . 'Controller.php';

if (file_exists($controller_path) && $controller_name != 'main') {
    require_once $controller_path;
    $controller_class = ucfirst($controller_name) . 'Controller';
    $controller = new $controller_class();
} else {
    // اگر کنترلر مورد نظر وجود نداشت، به کنترلر اصلی هدایت می‌کنیم
    $controller = new MainController();
    $controller_name = 'main';
    $action_name = 'index';
}

// اجرای اکشن مناسب
if (method_exists($controller, $action_name)) {
    $controller->$action_name();
} else {
    // اگر اکشن مورد نظر وجود نداشت، به اکشن پیش‌فرض هدایت می‌کنیم
    $controller->index();
}
?>