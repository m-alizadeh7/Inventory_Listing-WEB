<?php
/**
 * کنترلر اصلی
 * 
 * این کلاس مسئول مدیریت عملیات اصلی سیستم است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class MainController extends BaseController {
    private $user_model;
    private $database_model;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        parent::__construct();
        
        // بررسی نصب سیستم ابتدا
        if (!$this->isDatabaseInstalled()) {
            // اگر دیتابیس نصب نشده، به صفحه نصب هدایت
            if (!isset($_GET['action']) || $_GET['action'] !== 'install') {
                header('Location: index.php?controller=main&action=install');
                exit;
            }
        }
        
        // لود کردن مدل‌های مورد نیاز
        if (class_exists('UserModel')) {
            try {
                $this->user_model = new UserModel();
            } catch (Exception $e) {
                // اگر UserModel نتواند بارگذاری شود، احتمالاً دیتابیس مشکل دارد
                error_log("UserModel loading error: " . $e->getMessage());
                if (!isset($_GET['action']) || $_GET['action'] !== 'install') {
                    header('Location: index.php?controller=main&action=install');
                    exit;
                }
            }
        }
        
        if (class_exists('DatabaseModel')) {
            $this->database_model = new DatabaseModel();
        }
        
        // بررسی remember token فقط اگر سیستم نصب باشد
        if ($this->isDatabaseInstalled() && !$this->isAuthenticated() && isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
            $this->checkRememberToken();
        }
    }
    
    /**
     * بررسی نصب دیتابیس
     */
    private function isDatabaseInstalled() {
        if (!$this->db) {
            return false;
        }
        
        try {
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
            $result = $this->db->query("SHOW TABLES LIKE '{$prefix}users'");
            return ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * بررسی remember token
     */
    private function checkRememberToken() {
        if ($this->user_model) {
            $token = $_COOKIE['remember_token'];
            $user = $this->user_model->getUserByRememberToken($token);
            
            if ($user) {
                $_SESSION['user_data'] = $user;
                $this->current_user = $user;
            } else {
                // توکن نامعتبر، حذف کوکی
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
    }
    
    /**
     * نمایش صفحه اصلی (داشبورد)
     */
    public function index() {
        // بررسی نصب دیتابیس ابتدا
        if (!$this->isDatabaseInstalled()) {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        // بررسی احراز هویت
        if (!$this->isAuthenticated()) {
            header('Location: index.php?controller=main&action=login');
            exit;
        }
        
        global $config;
        $page_title = 'داشبورد';
        $stats = $this->getStats();
        
        // دریافت اطلاعات کسب و کار با fallback
        $business_info = $this->getBusinessInfoLocal();
        
        // بررسی وجود فایل‌های template
        $header_file = ROOT_PATH . '/templates/default/header.php';
        $dashboard_file = ROOT_PATH . '/templates/default/dashboard.php';
        $footer_file = ROOT_PATH . '/templates/default/footer.php';
        
        if (file_exists($header_file)) {
            include $header_file;
        }
        
        if (file_exists($dashboard_file)) {
            include $dashboard_file;
        } else {
            // نمایش داشبورد ساده در صورت عدم وجود template
            echo '<div class="container mt-4">';
            echo '<h1>داشبورد سیستم</h1>';
            echo '<div class="row">';
            echo '<div class="col-md-3"><div class="card"><div class="card-body"><h5>موجودی انبار</h5><h2>' . $stats['inventory_count'] . '</h2></div></div></div>';
            echo '<div class="col-md-3"><div class="card"><div class="card-body"><h5>دستگاه‌ها</h5><h2>' . $stats['device_count'] . '</h2></div></div></div>';
            echo '<div class="col-md-3"><div class="card"><div class="card-body"><h5>تأمین‌کنندگان</h5><h2>' . $stats['supplier_count'] . '</h2></div></div></div>';
            echo '<div class="col-md-3"><div class="card"><div class="card-body"><h5>سفارشات تولید</h5><h2>' . $stats['production_count'] . '</h2></div></div></div>';
            echo '</div>';
            echo '</div>';
        }
        
        if (file_exists($footer_file)) {
            include $footer_file;
        }
    }
    
    /**
     * نمایش صفحه لاگین
     */
    public function login() {
        // بررسی نصب دیتابیس ابتدا
        if (!$this->isDatabaseInstalled()) {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        // اگر کاربر قبلاً لاگین کرده، به داشبورد هدایت کن
        if (isset($_SESSION['user_data'])) {
            header('Location: index.php');
            exit;
        }
        
        $page_title = 'ورود به سیستم';
        $error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
        $username = '';
        
        // پاک کردن خطا بعد از نمایش
        if (isset($_SESSION['login_error'])) {
            unset($_SESSION['login_error']);
        }
        
        // بررسی وجود فایل template لاگین
        $login_file = ROOT_PATH . '/templates/default/login.php';
        if (file_exists($login_file)) {
            include $login_file;
        } else {
            // نمایش فرم لاگین ساده
            $this->showSimpleLoginForm($error, $username);
        }
    }
    
    /**
     * نمایش فرم لاگین ساده
     */
    private function showSimpleLoginForm($error = '', $username = '') {
        echo '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: Tahoma; padding-top: 100px; }
        .login-card { max-width: 400px; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="card">
                <div class="card-header text-center">
                    <h4>ورود به سیستم</h4>
                    <small>سیستم مدیریت انبار</small>
                </div>
                <div class="card-body">';
                
        if (!empty($error)) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
        }
        
        echo '<form method="POST" action="index.php?controller=main&action=process_login">
                        <div class="mb-3">
                            <label class="form-label">نام کاربری</label>
                            <input type="text" class="form-control" name="username" value="' . htmlspecialchars($username) . '" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="remember" value="1">
                            <label class="form-check-label">مرا به خاطر بسپار</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">ورود</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * دریافت اطلاعات کسب و کار (متد محلی)
     * 
     * @return array
     */
    private function getBusinessInfoLocal() {
        // اگر تابع عمومی وجود دارد، از آن استفاده کن
        if (function_exists('getBusinessInfo')) {
            try {
                return getBusinessInfo();
            } catch (Exception $e) {
                // در صورت خطا، به پیش‌فرض برو
            }
        }
        
        // مقادیر پیش‌فرض
        $business_info = [
            'business_name' => 'سیستم مدیریت انبار',
            'business_phone' => '',
            'business_email' => '',
            'business_address' => '',
            'business_website' => '',
            'business_owner' => 'مهدی علیزاده'
        ];
        
        // تلاش برای دریافت از دیتابیس
        if ($this->db) {
            try {
                $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
                $result = $this->db->query("SELECT setting_name, setting_value FROM " . $prefix . "settings WHERE setting_name LIKE 'business_%'");
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $business_info[$row['setting_name']] = $row['setting_value'];
                    }
                }
            } catch (Exception $e) {
                // در صورت خطا، مقادیر پیش‌فرض باقی می‌ماند
            }
        }
        
        return $business_info;
    }
    
    /**
     * نمایش صفحه نصب
     */
    public function install() {
        $page_title = 'نصب سیستم';
        $db_config_exists = file_exists(ROOT_PATH . '/config.php');
        $database_installed = false;
        $admin_exists = false;
        
        // بررسی پیش‌نیازها
        $requirements_met = true; // همه پیش‌نیازها در محیط وب موجود است
        
        // متغیرهای خطا و موفقیت
        $error = isset($_SESSION['install_error']) ? $_SESSION['install_error'] : '';
        $success = isset($_SESSION['install_success']) ? $_SESSION['install_success'] : '';
        
        // پاک کردن پیام‌ها بعد از نمایش
        if (isset($_SESSION['install_error'])) {
            unset($_SESSION['install_error']);
        }
        if (isset($_SESSION['install_success'])) {
            unset($_SESSION['install_success']);
        }
        
        // بررسی وضعیت نصب
        if ($db_config_exists && $this->db) {
            $database_installed = $this->isDatabaseInstalled();
            if ($database_installed) {
                // بررسی وجود کاربر مدیر
                try {
                    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
                    $result = $this->db->query("SELECT COUNT(*) as count FROM {$prefix}users WHERE role = 'admin'");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $admin_exists = $row['count'] > 0;
                    }
                } catch (Exception $e) {
                    // خطا در بررسی کاربر مدیر
                }
            }
        }
        
        // متغیرهای مورد نیاز template
        $db_installed = $database_installed;
        $admin_created = $admin_exists;
        
        // اگر همه چیز نصب شده، به داشبورد هدایت
        if ($db_config_exists && $database_installed && $admin_exists) {
            header('Location: index.php');
            exit;
        }
        
        // نمایش صفحه نصب
        $install_file = ROOT_PATH . '/templates/default/install.php';
        if (file_exists($install_file)) {
            include $install_file;
        } else {
            $this->showSimpleInstallForm($db_config_exists, $database_installed, $admin_exists);
        }
    }
    
    /**
     * نمایش فرم نصب ساده
     */
    private function showSimpleInstallForm($db_config_exists, $database_installed, $admin_exists) {
        echo '<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم مدیریت انبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: Tahoma; padding-top: 50px; }
        .install-card { max-width: 600px; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <div class="card">
                <div class="card-header text-center">
                    <h4>نصب سیستم مدیریت انبار</h4>
                    <small>نسخه 1.0.0</small>
                </div>
                <div class="card-body">
                    <h5>وضعیت نصب:</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-' . ($db_config_exists ? 'check text-success' : 'times text-danger') . '"></i> فایل پیکربندی</li>
                        <li><i class="fas fa-' . ($database_installed ? 'check text-success' : 'times text-danger') . '"></i> جداول دیتابیس</li>
                        <li><i class="fas fa-' . ($admin_exists ? 'check text-success' : 'times text-danger') . '"></i> کاربر مدیر</li>
                    </ul>';
        
        if (!$db_config_exists || !$database_installed || !$admin_exists) {
            echo '<form method="POST" action="index.php?controller=main&action=processDbConfig">
                    <h5>تنظیمات دیتابیس:</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">آدرس سرور</label>
                            <input type="text" class="form-control" name="db_host" value="localhost" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام دیتابیس</label>
                            <input type="text" class="form-control" name="db_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام کاربری دیتابیس</label>
                            <input type="text" class="form-control" name="db_user" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رمز عبور دیتابیس</label>
                            <input type="password" class="form-control" name="db_pass">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">پیشوند جداول</label>
                        <input type="text" class="form-control" name="db_prefix" value="inv_">
                    </div>
                    
                    <h5>اطلاعات مدیر:</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام کاربری مدیر</label>
                            <input type="text" class="form-control" name="admin_username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">رمز عبور مدیر</label>
                            <input type="password" class="form-control" name="admin_password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ایمیل مدیر</label>
                            <input type="email" class="form-control" name="admin_email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">نام شرکت</label>
                            <input type="text" class="form-control" name="business_name" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">شروع نصب</button>
                </form>';
        }
        
        echo '        </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * نمایش صفحه لاگین
     * 
     * @param string $error پیام خطا
     * @param string $username نام کاربری
     */
    public function showLoginPage($error = '', $username = '') {
        $page_title = 'ورود به سیستم';
        include ROOT_PATH . '/templates/default/login.php';
        exit;
    }
    
    /**
     * پردازش لاگین
     * 
     * @return bool
     */
    public function processLogin() {
        // بررسی نصب دیتابیس ابتدا
        if (!$this->isDatabaseInstalled()) {
            $_SESSION['login_error'] = 'سیستم هنوز نصب نشده است.';
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=main&action=login');
            exit;
        }
        
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) ? true : false;
        
        // اعتبارسنجی ورودی‌ها
        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'لطفا نام کاربری و رمز عبور را وارد کنید.';
            header('Location: index.php?controller=main&action=login');
            exit;
        }
        
        // محدودیت طول ورودی‌ها
        if (strlen($username) > 50 || strlen($password) > 255) {
            $_SESSION['login_error'] = 'اطلاعات ورودی نامعتبر است.';
            header('Location: index.php?controller=main&action=login');
            exit;
        }
        
        // بررسی وجود UserModel
        if (!$this->user_model) {
            try {
                require_once ROOT_PATH . '/models/UserModel.php';
                $this->user_model = new UserModel();
            } catch (Exception $e) {
                error_log("UserModel loading error: " . $e->getMessage());
                $_SESSION['login_error'] = 'خطا در سیستم احراز هویت.';
                header('Location: index.php?controller=main&action=login');
                exit;
            }
        }
        
        try {
            // بررسی اعتبار کاربر
            $user = $this->user_model->validateUser($username, $password);
            
            if ($user) {
                // ثبت اطلاعات کاربر در سشن
                $_SESSION['user_data'] = $user;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // ثبت زمان آخرین ورود
                if (method_exists($this->user_model, 'updateLastLogin')) {
                    $this->user_model->updateLastLogin($user['id']);
                }
                
                // ایجاد توکن "مرا به خاطر بسپار" در صورت انتخاب
                if ($remember && method_exists($this->user_model, 'setRememberToken')) {
                    $token = bin2hex(random_bytes(32));
                    $this->user_model->setRememberToken($user['id'], $token);
                    setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
                }
                
                // پاک کردن خطاهای لاگین
                unset($_SESSION['login_error']);
                
                // هدایت به صفحه اصلی
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_error'] = 'نام کاربری یا رمز عبور اشتباه است.';
                header('Location: index.php?controller=main&action=login');
                exit;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $_SESSION['login_error'] = 'خطا در احراز هویت: ' . $e->getMessage();
            header('Location: index.php?controller=main&action=login');
            exit;
        }
    }
    
    /**
     * پردازش تنظیمات دیتابیس
     */
    public function processDbConfig() {
        // دریافت اطلاعات فرم
        $config = [
            'db_host' => isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost',
            'db_name' => isset($_POST['db_name']) ? trim($_POST['db_name']) : '',
            'db_user' => isset($_POST['db_user']) ? trim($_POST['db_user']) : '',
            'db_pass' => isset($_POST['db_pass']) ? $_POST['db_pass'] : '',
            'db_prefix' => isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : 'inv_',
            'admin_username' => isset($_POST['admin_username']) ? trim($_POST['admin_username']) : '',
            'admin_password' => isset($_POST['admin_password']) ? $_POST['admin_password'] : '',
            'admin_email' => isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '',
            'business_name' => isset($_POST['business_name']) ? trim($_POST['business_name']) : ''
        ];
        
        // بررسی صحت داده‌ها
        $errors = [];
        
        if (empty($config['db_name'])) {
            $errors[] = 'نام دیتابیس الزامی است.';
        }
        
        if (empty($config['db_user'])) {
            $errors[] = 'نام کاربری دیتابیس الزامی است.';
        }
        
        if (empty($config['admin_username'])) {
            $errors[] = 'نام کاربری مدیر الزامی است.';
        }
        
        if (empty($config['admin_password'])) {
            $errors[] = 'رمز عبور مدیر الزامی است.';
        } elseif (strlen($config['admin_password']) < 6) {
            $errors[] = 'رمز عبور مدیر باید حداقل ۶ کاراکتر باشد.';
        }
        
        if (empty($config['admin_email'])) {
            $errors[] = 'ایمیل مدیر الزامی است.';
        } elseif (!filter_var($config['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'فرمت ایمیل مدیر صحیح نیست.';
        }
        
        if (empty($config['business_name'])) {
            $errors[] = 'نام شرکت/کسب‌وکار الزامی است.';
        }
        
        if (!empty($errors)) {
            $_SESSION['install_errors'] = $errors;
            $_SESSION['install_data'] = $config;
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        try {
            // تست اتصال به دیتابیس
            $pdo = new PDO("mysql:host={$config['db_host']};charset=utf8mb4", $config['db_user'], $config['db_pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // بررسی وجود دیتابیس
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$config['db_name']}'");
            $db_exists = $stmt->rowCount() > 0;
            
            if (!$db_exists) {
                // ایجاد دیتابیس
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci");
            }
            
            // انتخاب دیتابیس
            $pdo->exec("USE `{$config['db_name']}`");
            
            // نصب اسکیما
            $sql_file_path = ROOT_PATH . '/db.sql';
            if (!file_exists($sql_file_path)) {
                throw new Exception('فایل دیتابیس (db.sql) یافت نشد.');
            }
            
            $sql_file = file_get_contents($sql_file_path);
            if ($sql_file === false) {
                throw new Exception('خطا در خواندن فایل دیتابیس.');
            }
            
            // جایگزینی پیشوند جداول
            $sql_file = str_replace('inv_', $config['db_prefix'], $sql_file);
            
            // تقسیم و اجرای کوئری‌ها
            $queries = explode(';', $sql_file);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $e) {
                        // ادامه در صورت خطاهای غیر مهم
                        error_log("SQL Query Error: " . $e->getMessage() . " - Query: " . $query);
                    }
                }
            }
            
            // ایجاد کاربر مدیر
            $hashed_password = password_hash($config['admin_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO `{$config['db_prefix']}users` 
                                   (username, password, email, full_name, role, is_active, created_at) 
                                   VALUES (:username, :password, :email, :full_name, 'admin', 1, NOW())");
                                   
            $stmt->execute([
                ':username' => $config['admin_username'],
                ':password' => $hashed_password,
                ':email' => $config['admin_email'],
                ':full_name' => 'مدیر سیستم'
            ]);
            
            // ذخیره اطلاعات شرکت
            $stmt = $pdo->prepare("INSERT INTO `{$config['db_prefix']}settings` 
                                   (setting_name, setting_value) 
                                   VALUES ('business_name', :business_name)");
                                   
            $stmt->execute([':business_name' => $config['business_name']]);
            
            // ذخیره تنظیمات در فایل کانفیگ
            $config_file = ROOT_PATH . '/config.php';
            $config_content = "<?php
/**
 * فایل تنظیمات
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// تنظیمات دیتابیس
define('DB_HOST', '{$config['db_host']}');
define('DB_NAME', '{$config['db_name']}');
define('DB_USER', '{$config['db_user']}');
define('DB_PASS', '{$config['db_pass']}');
define('DB_PREFIX', '{$config['db_prefix']}');

// مسیرهای سیستم
define('ROOT_PATH', __DIR__);
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('DEFAULT_TEMPLATE', 'default');
define('ASSETS_URL', 'assets');

// تنظیمات سیستم
\$config = [
    'app_name' => 'سیستم مدیریت انبار و تولید',
    'version' => '1.0.0',
    'author' => 'مهدی علیزاده',
    'email' => 'm.alizadeh7@live.com',
    'website' => 'https://alizadehx.ir',
    'github' => 'https://github.com/m-alizadeh7',
    'telegram' => 'https://t.me/mahdializadeh7',
    'installed' => true
];

// تنظیم منطقه زمانی
date_default_timezone_set('Asia/Tehran');

// تنظیمات نشست
session_start();
";
            file_put_contents($config_file, $config_content);
            
            // هدایت به صفحه تکمیل نصب
            header('Location: index.php?controller=main&action=install_complete');
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['install_errors'] = ['خطا در اتصال به دیتابیس: ' . $e->getMessage()];
            $_SESSION['install_data'] = $config;
            header('Location: index.php?controller=main&action=install');
            exit;
        }
    }
    
    /**
     * نمایش صفحه تکمیل نصب
     */
    public function install_complete() {
        $page_title = 'نصب با موفقیت انجام شد';
        include ROOT_PATH . '/templates/default/install/complete.php';
    }
    
    /**
     * بررسی نصب سیستم
     * 
     * @return bool
     */
    public function checkInstallation() {
        // بررسی وجود فایل config.php
        if (!file_exists(ROOT_PATH . '/config.php')) {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        // بررسی اتصال به دیتابیس
        if (!$this->db) {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        return true;
    }
    
    /**
     * دریافت آمار سیستم
     * 
     * @return array
     */
    public function getStats() {
        $stats = [
            'inventory_count' => 0,
            'device_count' => 0,
            'supplier_count' => 0,
            'production_count' => 0,
            'low_stock_count' => 0,
            'pending_orders' => 0
        ];
        
        if (!$this->db) {
            return $stats;
        }
        
        try {
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
            
            // تعداد موجودی
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "inventory");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['inventory_count'] = $row['count'];
            }
            
            // تعداد دستگاه‌ها
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "devices");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['device_count'] = $row['count'];
            }
            
            // تعداد تأمین‌کنندگان
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "suppliers");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['supplier_count'] = $row['count'];
            }
            
            // تعداد سفارشات تولید
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "production_orders");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['production_count'] = $row['count'];
            }
            
            // تعداد اقلام با موجودی کم
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "inventory WHERE quantity <= min_quantity");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['low_stock_count'] = $row['count'];
            }
            
            // تعداد سفارشات در انتظار
            $result = $this->db->query("SELECT COUNT(*) as count FROM " . $prefix . "production_orders WHERE status = 'pending'");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['pending_orders'] = $row['count'];
            }
        } catch (Exception $e) {
            // در صورت خطا آمار صفر برگردانده می‌شود
        }
        
        return $stats;
    }
    
    /**
     * نمایش صفحه تنظیمات
     */
    public function settings() {
        // بررسی احراز هویت
        if (!isset($_SESSION['user_data'])) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        // بررسی دسترسی مدیر
        if ($_SESSION['user_data']['role'] !== 'admin') {
            header('Location: index.php');
            exit;
        }
        
        global $config;
        $business_info = getBusinessInfo();
        
        $page_title = 'تنظیمات سیستم';
        include ROOT_PATH . '/templates/default/header.php';
        include ROOT_PATH . '/templates/default/main/settings.php';
        include ROOT_PATH . '/templates/default/footer.php';
    }
    
    /**
     * به‌روزرسانی تنظیمات
     */
    public function update_settings() {
        // بررسی احراز هویت
        if (!isset($_SESSION['user_data'])) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        // بررسی دسترسی مدیر
        if ($_SESSION['user_data']['role'] !== 'admin') {
            header('Location: index.php');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $business_name = $_POST['business_name'] ?? '';
                $business_phone = $_POST['business_phone'] ?? '';
                $business_email = $_POST['business_email'] ?? '';
                $business_address = $_POST['business_address'] ?? '';
                $business_website = $_POST['business_website'] ?? '';
                
                $stmt = $this->db->prepare("UPDATE " . DB_PREFIX . "settings SET 
                    setting_value = CASE setting_name 
                        WHEN 'business_name' THEN :business_name
                        WHEN 'business_phone' THEN :business_phone
                        WHEN 'business_email' THEN :business_email
                        WHEN 'business_address' THEN :business_address
                        WHEN 'business_website' THEN :business_website
                        ELSE setting_value
                    END
                    WHERE setting_name IN ('business_name', 'business_phone', 'business_email', 'business_address', 'business_website')");
                
                $stmt->execute([
                    ':business_name' => $business_name,
                    ':business_phone' => $business_phone,
                    ':business_email' => $business_email,
                    ':business_address' => $business_address,
                    ':business_website' => $business_website
                ]);
                
                $_SESSION['success_message'] = 'تنظیمات با موفقیت به‌روزرسانی شد.';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage();
            }
        }
        
        header('Location: index.php?controller=main&action=settings');
        exit;
    }
    
    /**
     * نمایش پروفایل کاربر
     */
    public function show_profile() {
        // بررسی احراز هویت
        if (!isset($_SESSION['user_data'])) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        $page_title = 'پروفایل کاربری';
        $user = $_SESSION['user_data'];
        
        include ROOT_PATH . '/templates/default/header.php';
        include ROOT_PATH . '/templates/default/main/profile.php';
        include ROOT_PATH . '/templates/default/footer.php';
    }
    
    /**
     * به‌روزرسانی پروفایل کاربر
     */
    public function update_profile() {
        // بررسی احراز هویت
        if (!isset($_SESSION['user_data'])) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $user_id = $_SESSION['user_data']['id'];
                $full_name = $_POST['full_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                // به‌روزرسانی اطلاعات پایه
                $stmt = $this->db->prepare("UPDATE " . DB_PREFIX . "users SET full_name = :full_name, email = :email WHERE id = :user_id");
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':user_id' => $user_id
                ]);
                
                // تغییر رمز عبور در صورت وارد کردن
                if (!empty($current_password) && !empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        throw new Exception('رمز عبور جدید و تکرار آن مطابقت ندارند.');
                    }
                    
                    // بررسی رمز عبور فعلی
                    $stmt = $this->db->prepare("SELECT password FROM " . DB_PREFIX . "users WHERE id = :user_id");
                    $stmt->execute([':user_id' => $user_id]);
                    $stored_password = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $stored_password)) {
                        throw new Exception('رمز عبور فعلی اشتباه است.');
                    }
                    
                    // به‌روزرسانی رمز عبور
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("UPDATE " . DB_PREFIX . "users SET password = :password WHERE id = :user_id");
                    $stmt->execute([
                        ':password' => $hashed_password,
                        ':user_id' => $user_id
                    ]);
                }
                
                // به‌روزرسانی اطلاعات نشست
                $_SESSION['user_data']['full_name'] = $full_name;
                $_SESSION['user_data']['email'] = $email;
                
                $_SESSION['success_message'] = 'پروفایل با موفقیت به‌روزرسانی شد.';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'خطا در به‌روزرسانی پروفایل: ' . $e->getMessage();
            }
        }
        
        header('Location: index.php?controller=main&action=show_profile');
        exit;
    }
    
    /**
     * خروج از سیستم
     */
    public function logout() {
        // پاک کردن remember token
        if (isset($_COOKIE['remember_token'])) {
            if ($this->user_model && method_exists($this->user_model, 'clearRememberToken')) {
                $this->user_model->clearRememberToken($_COOKIE['remember_token']);
            }
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // پاک کردن session
        session_destroy();
        
        // هدایت به صفحه لاگین
        header('Location: index.php?controller=main&action=login');
        exit;
    }
    
    /**
     * پردازش ورود کاربر (متد کمکی برای سازگاری با URL)
     */
    public function process_login() {
        return $this->processLogin();
    }
    
    /**
     * نصب دیتابیس
     */
    public function install_db() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        try {
            // اجرای فایل SQL نصب
            $sql_file = ROOT_PATH . '/db.sql';
            if (!file_exists($sql_file)) {
                throw new Exception('فایل SQL پیدا نشد.');
            }
            
            $sql_content = file_get_contents($sql_file);
            if ($sql_content === false) {
                throw new Exception('خطا در خواندن فایل SQL.');
            }
            
            // حذف کامنت‌ها و خطوط خالی
            $sql_content = preg_replace('/--.*$/m', '', $sql_content);
            $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
            
            // جداسازی کوئری‌ها
            $queries = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    $result = $this->db->query($query);
                    if (!$result) {
                        throw new Exception('خطا در اجرای کوئری: ' . $this->db->error);
                    }
                }
            }
            
            // هدایت به مرحله بعد
            header('Location: index.php?controller=main&action=install');
            exit;
            
        } catch (Exception $e) {
            error_log("Database installation error: " . $e->getMessage());
            $_SESSION['install_error'] = 'خطا در نصب دیتابیس: ' . $e->getMessage();
            header('Location: index.php?controller=main&action=install');
            exit;
        }
    }
    
    /**
     * ایجاد کاربر مدیر
     */
    public function create_admin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        $username = isset($_POST['admin_username']) ? trim($_POST['admin_username']) : '';
        $password = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
        $email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
        
        // اعتبارسنجی
        if (empty($username) || empty($password) || empty($email)) {
            $_SESSION['install_error'] = 'لطفا تمام فیلدها را پر کنید.';
            header('Location: index.php?controller=main&action=install');
            exit;
        }
        
        try {
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("INSERT INTO {$prefix}users (username, password, email, role, created_at) VALUES (?, ?, ?, 'admin', NOW())");
            $stmt->bind_param('sss', $username, $hashed_password, $email);
            
            if ($stmt->execute()) {
                // نصب کامل شد
                header('Location: index.php?controller=main&action=install_complete');
                exit;
            } else {
                throw new Exception('خطا در ایجاد کاربر مدیر: ' . $this->db->error);
            }
            
        } catch (Exception $e) {
            error_log("Admin creation error: " . $e->getMessage());
            $_SESSION['install_error'] = 'خطا در ایجاد کاربر مدیر: ' . $e->getMessage();
            header('Location: index.php?controller=main&action=install');
            exit;
        }
    }
    
    /**
     * پردازش پیکربندی دیتابیس (متد کمکی برای سازگاری با URL)
     */
    public function process_db_config() {
        return $this->processDbConfig();
    }
}