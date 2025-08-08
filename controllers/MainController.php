<?php
/**
 * کنترلر اصلی
 * 
 * این کلاس مسئول مدیریت عملیات اصلی سیستم است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class MainController {
    private $db;
    private $user_model;
    private $database_model;
    private $current_user;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->user_model = new UserModel();
        $this->database_model = new DatabaseModel();
        
        // بررسی احراز هویت کاربر
        $this->current_user = $this->checkAuthentication();
        
        // ایجاد توکن CSRF
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * بررسی نصب سیستم
     * 
     * @return bool
     */
    public function checkInstallation() {
        // بررسی وجود فایل کانفیگ
        if (!$this->database_model->isConfigFileExists()) {
            $this->showInstallPage();
            return false;
        }
        
        // بررسی نصب دیتابیس
        if (!$this->database_model->isDatabaseInstalled()) {
            $this->showInstallPage();
            return false;
        }
        
        // بررسی وجود کاربر مدیر
        if (!$this->database_model->isAdminUserExists()) {
            $this->showInstallPage();
            return false;
        }
        
        return true;
    }
    
    /**
     * بررسی احراز هویت کاربر
     * 
     * @return array|bool
     */
    private function checkAuthentication() {
        // بررسی وجود کاربر در سشن
        if (isset($_SESSION['user_id'])) {
            return $this->user_model->getUserById($_SESSION['user_id']);
        }
        
        // بررسی وجود کوکی "مرا به خاطر بسپار"
        if (isset($_COOKIE['remember_token'])) {
            $user = $this->user_model->authenticateByToken($_COOKIE['remember_token']);
            
            if ($user) {
                // ذخیره اطلاعات کاربر در سشن
                $_SESSION['user_id'] = $user['id'];
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * بررسی ورود کاربر
     * 
     * @return bool
     */
    public function isUserLoggedIn() {
        return $this->current_user !== false;
    }
    
    /**
     * بررسی دسترسی کاربر
     * 
     * @param string $action
     * @return bool
     */
    public function hasPermission($action) {
        if (!$this->isUserLoggedIn()) {
            return false;
        }
        
        return $this->user_model->hasPermission($this->current_user['role'], $action);
    }
    
    /**
     * نمایش صفحه نصب
     */
    public function showInstallPage() {
        // بررسی وضعیت نصب
        $config_exists = $this->database_model->isConfigFileExists();
        $db_installed = $this->database_model->isDatabaseInstalled();
        $admin_created = $this->database_model->isAdminUserExists();
        
        // بررسی پیش‌نیازها
        $requirements = $this->database_model->checkRequirements();
        $requirements_met = $requirements['all_met'];
        
        $page_title = 'نصب سیستم مدیریت انبار';
        
        // نمایش صفحه نصب
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/install.php');
        exit;
    }
    
    /**
     * نمایش صفحه لاگین
     */
    public function showLoginPage($error = null, $username = '') {
        $page_title = 'ورود به سیستم';
        $csrf_token = $_SESSION['csrf_token'];
        
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/login.php');
        exit;
    }
    
    /**
     * پردازش فرم لاگین
     */
    public function processLogin() {
        // بررسی CSRF توکن
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $this->showLoginPage('خطای امنیتی رخ داده است. لطفاً دوباره تلاش کنید.');
            return;
        }
        
        // دریافت اطلاعات فرم
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember']) && $_POST['remember'] == 1;
        
        // بررسی خالی نبودن فیلدها
        if (empty($username) || empty($password)) {
            $this->showLoginPage('لطفاً نام کاربری و رمز عبور را وارد کنید.', $username);
            return;
        }
        
        // احراز هویت کاربر
        $user = $this->user_model->authenticate($username, $password);
        
        if ($user) {
            // ذخیره اطلاعات کاربر در سشن
            $_SESSION['user_id'] = $user['id'];
            
            // ایجاد کوکی "مرا به خاطر بسپار"
            if ($remember) {
                $token = $this->user_model->createRememberToken($user['id']);
                
                if ($token) {
                    setcookie('remember_token', $token, time() + COOKIE_LIFETIME, '/');
                }
            }
            
            // هدایت به صفحه اصلی
            header('Location: index.php');
            exit;
        } else {
            $this->showLoginPage('نام کاربری یا رمز عبور اشتباه است.', $username);
        }
    }
    
    /**
     * خروج از سیستم
     */
    public function logout() {
        // حذف توکن "مرا به خاطر بسپار"
        if ($this->isUserLoggedIn()) {
            $this->user_model->clearRememberToken($this->current_user['id']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // حذف سشن
        session_destroy();
        
        // هدایت به صفحه لاگین
        header('Location: index.php');
        exit;
    }
    
    /**
     * پردازش تنظیمات دیتابیس
     */
    public function processDbConfig() {
        // دریافت اطلاعات فرم
        $config = [
            'db_host' => isset($_POST['db_host']) ? trim($_POST['db_host']) : 'localhost',
            'db_port' => isset($_POST['db_port']) ? (int)$_POST['db_port'] : 3306,
            'db_name' => isset($_POST['db_name']) ? trim($_POST['db_name']) : '',
            'db_user' => isset($_POST['db_user']) ? trim($_POST['db_user']) : '',
            'db_pass' => isset($_POST['db_pass']) ? $_POST['db_pass'] : '',
            'db_prefix' => isset($_POST['db_prefix']) ? trim($_POST['db_prefix']) : 'inv_'
        ];
        
        // بررسی خالی نبودن فیلدهای ضروری
        if (empty($config['db_name']) || empty($config['db_user'])) {
            $error = 'لطفاً تمام فیلدهای ضروری را پر کنید.';
            $this->showInstallPage();
            return;
        }
        
        // تست اتصال به دیتابیس
        try {
            $test_db = new mysqli(
                $config['db_host'], 
                $config['db_user'], 
                $config['db_pass'], 
                $config['db_name'], 
                $config['db_port']
            );
            
            if ($test_db->connect_error) {
                $error = 'خطا در اتصال به دیتابیس: ' . $test_db->connect_error;
                $this->showInstallPage();
                return;
            }
            
            $test_db->close();
        } catch (Exception $e) {
            $error = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
            $this->showInstallPage();
            return;
        }
        
        // ایجاد فایل کانفیگ
        if (!$this->database_model->createConfigFile($config)) {
            $error = 'خطا در ایجاد فایل پیکربندی. لطفاً دسترسی نوشتن را بررسی کنید.';
            $this->showInstallPage();
            return;
        }
        
        // بارگذاری مجدد صفحه
        $success = 'فایل پیکربندی با موفقیت ایجاد شد.';
        header('Location: index.php');
        exit;
    }
    
    /**
     * نصب دیتابیس
     */
    public function installDb() {
        if (!$this->database_model->installDatabase()) {
            $error = 'خطا در نصب دیتابیس. لطفاً دوباره تلاش کنید.';
            $this->showInstallPage();
            return;
        }
        
        $success = 'دیتابیس با موفقیت نصب شد.';
        header('Location: index.php');
        exit;
    }
    
    /**
     * ایجاد کاربر مدیر
     */
    public function createAdmin() {
        // دریافت اطلاعات فرم
        $admin_data = [
            'admin_username' => isset($_POST['admin_username']) ? trim($_POST['admin_username']) : '',
            'admin_password' => isset($_POST['admin_password']) ? $_POST['admin_password'] : '',
            'admin_email' => isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '',
            'admin_name' => isset($_POST['admin_name']) ? trim($_POST['admin_name']) : ''
        ];
        
        // بررسی خالی نبودن فیلدها
        if (empty($admin_data['admin_username']) || empty($admin_data['admin_password']) || 
            empty($admin_data['admin_email']) || empty($admin_data['admin_name'])) {
            $error = 'لطفاً تمام فیلدها را پر کنید.';
            $this->showInstallPage();
            return;
        }
        
        // ایجاد کاربر مدیر
        if (!$this->database_model->createAdminUser($admin_data)) {
            $error = 'خطا در ایجاد کاربر مدیر. لطفاً دوباره تلاش کنید.';
            $this->showInstallPage();
            return;
        }
        
        $success = 'کاربر مدیر با موفقیت ایجاد شد.';
        header('Location: index.php');
        exit;
    }
    
    /**
     * نمایش صفحه داشبورد
     */
    public function showDashboard() {
        // بررسی ورود کاربر
        if (!$this->isUserLoggedIn()) {
            $this->showLoginPage();
            return;
        }
        
        $page_title = 'داشبورد';
        $user = $this->current_user;
        
        // نمایش صفحه داشبورد
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/dashboard.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * نمایش صفحه پروفایل
     */
    public function showProfile() {
        // بررسی ورود کاربر
        if (!$this->isUserLoggedIn()) {
            $this->showLoginPage();
            return;
        }
        
        $page_title = 'پروفایل کاربری';
        $user = $this->current_user;
        
        // نمایش صفحه پروفایل
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/profile.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * بروزرسانی پروفایل
     */
    public function updateProfile() {
        // بررسی ورود کاربر
        if (!$this->isUserLoggedIn()) {
            $this->showLoginPage();
            return;
        }
        
        // بررسی CSRF توکن
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'خطای امنیتی رخ داده است. لطفاً دوباره تلاش کنید.';
            header('Location: index.php?controller=main&action=show_profile');
            exit;
        }
        
        // دریافت اطلاعات فرم
        $user_data = [
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : ''
        ];
        
        // بررسی خالی نبودن فیلدها
        if (empty($user_data['name']) || empty($user_data['email'])) {
            $_SESSION['error'] = 'لطفاً تمام فیلدها را پر کنید.';
            header('Location: index.php?controller=main&action=show_profile');
            exit;
        }
        
        // بروزرسانی پروفایل
        if ($this->user_model->updateUser($this->current_user['id'], $user_data)) {
            $_SESSION['success'] = 'پروفایل با موفقیت بروزرسانی شد.';
        } else {
            $_SESSION['error'] = 'خطا در بروزرسانی پروفایل. لطفاً دوباره تلاش کنید.';
        }
        
        header('Location: index.php?controller=main&action=show_profile');
        exit;
    }
    
    /**
     * تغییر رمز عبور
     */
    public function changePassword() {
        // بررسی ورود کاربر
        if (!$this->isUserLoggedIn()) {
            $this->showLoginPage();
            return;
        }
        
        // بررسی CSRF توکن
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'خطای امنیتی رخ داده است. لطفاً دوباره تلاش کنید.';
            header('Location: index.php?controller=main&action=show_profile');
            exit;
        }
        
        // دریافت اطلاعات فرم
        $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // بررسی خالی نبودن فیلدها
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = 'لطفاً تمام فیلدها را پر کنید.';
            header('Location: index.php?controller=main&action=show_profile');
            exit;
        }
        
        // بررسی یکسان بودن رمز عبور جدید و تکرار آن
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'رمز عبور جدید و تکرار آن یکسان نیستند.';
            header('Location: index.php?controller=main&action=show_profile');
            exit;
        }
        
        // تغییر رمز عبور
        if ($this->user_model->changePassword($this->current_user['id'], $current_password, $new_password)) {
            $_SESSION['success'] = 'رمز عبور با موفقیت تغییر یافت.';
        } else {
            $_SESSION['error'] = 'خطا در تغییر رمز عبور. لطفاً رمز عبور فعلی را بررسی کنید.';
        }
        
        header('Location: index.php?controller=main&action=show_profile');
        exit;
    }
}
