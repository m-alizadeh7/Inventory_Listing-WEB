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
    if (defined('DB_PORT')) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    } else {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    
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

// اطمینان از تعریف مسیر ریشه در هر شرایطی (پس از لود config)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// محاسبه مسیر ریشه مطمئن
$rootPath = ROOT_PATH;

// لود کردن مدل‌های مورد نیاز
$model_files = glob($rootPath . '/models/*.php');
if ($model_files) {
    foreach ($model_files as $model_file) {
        require_once $model_file;
    }
}

// لود کردن MainController ابتدا (برای استفاده در سایر کنترلرها)
$mainControllerCandidates = [
    $rootPath . '/controllers/MainController.php',
    $rootPath . '/core/controllers/MainController.php',
    __DIR__ . '/controllers/MainController.php',
    '/home/h312810/public_html/anbar2/controllers/MainController.php',
    getcwd() . '/controllers/MainController.php',
];

$mainIncluded = false;
$debugInfo = [];
foreach ($mainControllerCandidates as $cand) {
    $debugInfo[] = $cand . ' => ' . (file_exists($cand) ? 'EXISTS' : 'NOT_FOUND');
    if (file_exists($cand)) {
        require_once $cand;
        $mainIncluded = true;
        break;
    }
}

if (!$mainIncluded) {
    // پیام خطای دقیق‌تر برای دیباگ
    header('Content-Type: text/html; charset=utf-8');
    echo '<div style="direction:rtl;font-family:tahoma,iransans,sans-serif;padding:16px;color:#b71c1c;background:#ffebee;border:1px solid #ffcdd2;">'
        . '<h3>فایل کنترلر اصلی یافت نشد</h3>'
        . '<p>ROOT_PATH: ' . htmlspecialchars($rootPath) . '</p>'
        . '<p>__DIR__: ' . htmlspecialchars(__DIR__) . '</p>'
        . '<p>getcwd(): ' . htmlspecialchars(getcwd()) . '</p>'
        . '<p>مسیرهای بررسی‌شده:</p>'
        . '<pre>' . htmlspecialchars(implode("\n", $debugInfo)) . '</pre>'
        . '<p>لطفاً فایل MainController.php را در یکی از مسیرهای فوق قرار دهید.</p>'
        . '</div>';
    exit;
}

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
    if (method_exists($controller, 'index')) {
        $controller->index();
    } else {
        echo '<div class="alert alert-danger">متد مورد نظر یافت نشد.</div>';
    }
}
?>