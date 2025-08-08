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

// تعریف مسیر ریشه (اگر قبلاً تعریف نشده باشد)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// بررسی وجود فایل کانفیگ
if (file_exists(ROOT_PATH . '/config.php')) {
    require_once ROOT_PATH . '/config.php';
    // متغیر $db از config.php آماده است
} else {
    // اگر فایل کانفیگ وجود نداشت، به صفحه نصب هدایت
    $db = null;
}

// لود کردن توابع کمکی
if (file_exists(ROOT_PATH . '/includes/functions.php')) {
    require_once ROOT_PATH . '/includes/functions.php';
}

// لود کردن کلاس پایه کنترلرها
if (file_exists(ROOT_PATH . '/controllers/BaseController.php')) {
    require_once ROOT_PATH . '/controllers/BaseController.php';
}

// لود کردن مدل‌های مورد نیاز
$model_files = [
    ROOT_PATH . '/models/DatabaseModel.php',
    ROOT_PATH . '/models/UserModel.php'
];

foreach ($model_files as $model_file) {
    if (file_exists($model_file)) {
        require_once $model_file;
    }
}

// لود کردن MainController
$mainControllerPath = ROOT_PATH . '/controllers/MainController.php';
if (file_exists($mainControllerPath)) {
    require_once $mainControllerPath;
} else {
    die('<div style="color: red; direction: rtl; font-family: Tahoma; padding: 20px;">
         فایل کنترلر اصلی یافت نشد: ' . htmlspecialchars($mainControllerPath) . '</div>');
}

// تنظیم نوع درخواست و پارامترهای آن
$controller_name = isset($_GET['controller']) ? $_GET['controller'] : 'main';
$action_name = isset($_GET['action']) ? $_GET['action'] : 'index';

// لیست کنترلرهای مجاز
$allowed_controllers = ['main', 'user', 'inventory', 'device', 'supplier', 'production'];

// اعتبارسنجی نام کنترلر
if (!in_array($controller_name, $allowed_controllers)) {
    $controller_name = 'main';
    $action_name = 'index';
}

// لود کردن کنترلر مناسب
if ($controller_name === 'main') {
    $controller = new MainController();
} else {
    $controller_class = ucfirst($controller_name) . 'Controller';
    $controller_path = ROOT_PATH . '/controllers/' . $controller_class . '.php';
    
    if (file_exists($controller_path)) {
        require_once $controller_path;
        
        if (class_exists($controller_class)) {
            $controller = new $controller_class();
        } else {
            // اگر کلاس وجود نداشت، به کنترلر اصلی برو
            $controller = new MainController();
            $controller_name = 'main';
            $action_name = 'index';
        }
    } else {
        // اگر فایل کنترلر وجود نداشت، به کنترلر اصلی برو
        $controller = new MainController();
        $controller_name = 'main';
        $action_name = 'index';
    }
}

// اجرای اکشن مناسب
if (method_exists($controller, $action_name)) {
    try {
        $controller->$action_name();
    } catch (Exception $e) {
        error_log("Controller error: " . $e->getMessage());
        
        // نمایش پیام خطا برای admin
        if (isset($_SESSION['user_data']) && $_SESSION['user_data']['role'] === 'admin') {
            echo '<div class="alert alert-danger" style="direction: rtl; font-family: Tahoma;">
                  خطا در اجرای عملیات: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } else {
            echo '<div class="alert alert-danger" style="direction: rtl; font-family: Tahoma;">
                  خطایی در سیستم رخ داده است.</div>';
        }
    }
} else {
    // اگر اکشن مورد نظر وجود نداشت، به اکشن پیش‌فرض هدایت
    if (method_exists($controller, 'index')) {
        $controller->index();
    } else {
        echo '<div class="alert alert-danger" style="direction: rtl; font-family: Tahoma;">
              متد مورد نظر یافت نشد.</div>';
    }
}
?>