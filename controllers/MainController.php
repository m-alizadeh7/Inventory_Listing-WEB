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
     * متد پیش‌فرض برای صفحه اصلی
     */
    public function index() {
        // بررسی نصب سیستم
        if (!$this->checkInstallation()) {
            return;
        }
        
        // بررسی ورود کاربر
        if (!$this->isUserLoggedIn()) {
            $this->showLoginPage();
            return;
        }
        
        // هدایت به داشبورد
        $this->showDashboard();
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
        $db_config_exists = $this->database_model->isConfigFileExists();
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
        
        // تولید CSRF توکن در صورت عدم وجود
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
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
        try {
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
                $_SESSION['success'] = 'ورود شما با موفقیت انجام شد.';
                header('Location: index.php');
                exit;
            } else {
                $this->showLoginPage('نام کاربری یا رمز عبور اشتباه است.', $username);
            }
        } catch (Exception $e) {
            $this->showLoginPage('خطا در احراز هویت: ' . $e->getMessage(), $username);
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
     * نصب دیتابیس (متد کمکی برای سازگاری با URL)
     */
    public function install_db() {
        return $this->installDb();
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
     * ایجاد کاربر مدیر (متد کمکی برای سازگاری با URL)
     */
    public function create_admin() {
        return $this->createAdmin();
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
        
        // دریافت اطلاعات آماری
        $stats = [
            'total_inventory' => 0,
            'low_stock' => 0,
            'pending_orders' => 0,
            'total_devices' => 0
        ];
        
        // محاسبه آمار واقعی در صورت وجود دیتابیس
        if ($this->db) {
            try {
                $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
                
                // کل اقلام انبار
                $result = $this->db->query("SELECT COUNT(*) as count FROM {$prefix}inventory");
                if ($result) {
                    $stats['total_inventory'] = $result->fetch_assoc()['count'];
                }
                
                // اقلام کم موجود
                $result = $this->db->query("SELECT COUNT(*) as count FROM {$prefix}inventory WHERE stock < 10");
                if ($result) {
                    $stats['low_stock'] = $result->fetch_assoc()['count'];
                }
                
                // سفارشات در انتظار
                $result = $this->db->query("SELECT COUNT(*) as count FROM {$prefix}production_orders WHERE status = 'pending'");
                if ($result) {
                    $stats['pending_orders'] = $result->fetch_assoc()['count'];
                }
                
                // کل دستگاه‌ها
                $result = $this->db->query("SELECT COUNT(*) as count FROM {$prefix}devices");
                if ($result) {
                    $stats['total_devices'] = $result->fetch_assoc()['count'];
                }
            } catch (Exception $e) {
                // در صورت خطا، آمار پیش‌فرض باقی می‌ماند
            }
        }
        
        // دریافت اطلاعات کسب و کار
        $business_info = [
            'business_name' => 'سیستم مدیریت انبار',
            'business_owner' => 'مهدی علیزاده'
        ];
        
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
            header('Location: index.php?controller=main&action=showberry');
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
    
    /**
     * پردازش پیکربندی دیتابیس (متد کمکی برای سازگاری با URL)
     */
    public function process_db_config() {
        return $this->processDbConfig();
    }
    
    /**
     * نمایش صفحه تنظیمات
     */
    public function settings() {
        // بررسی احراز هویت
        if (!$this->checkAuth()) {
            header('Location: index.php?controller=user&action=login');
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
        if (!$this->checkAuth()) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $business_name = $_POST['business_name'] ?? '';
                $business_phone = $_POST['business_phone'] ?? '';
                $business_email = $_POST['business_email'] ?? '';
                $business_address = $_POST['business_address'] ?? '';
                $business_website = $_POST['business_website'] ?? '';
                
                $stmt = $this->db->prepare("UPDATE settings SET 
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
        if (!$this->checkAuth()) {
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
        if (!$this->checkAuth()) {
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
                $stmt = $this->db->prepare("UPDATE users SET full_name = :full_name, email = :email WHERE id = :user_id");
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
                    $stmt = $this->db->prepare("SELECT password FROM users WHERE id = :user_id");
                    $stmt->execute([':user_id' => $user_id]);
                    $stored_password = $stmt->fetchColumn();
                    
                    if (!password_verify($current_password, $stored_password)) {
                        throw new Exception('رمز عبور فعلی اشتباه است.');
                    }
                    
                    // به‌روزرسانی رمز عبور
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :user_id");
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
        // پاک کردن تمام اطلاعات نشست
        session_destroy();
        
        // حذف کوکی remember me در صورت وجود
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        header('Location: index.php?controller=user&action=login');
        exit;
    }
    
    /**
     * پردازش ورود کاربر (متد کمکی برای سازگاری با URL)
     */
    public function process_login() {
        return $this->processLogin();
    }
}