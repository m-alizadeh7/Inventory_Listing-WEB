<?php
/**
 * کنترلر اصلی سیستم
 *
 * کنترلر اصلی برای مدیریت صفحات عمومی سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');
require_once(CORE_PATH . '/models/inventory.php');
require_once(CORE_PATH . '/models/device    /**
     * بررسی اینکه آیا کاربر دسترسی به یک قابلیت را دارد
     */
    protected function hasPermission($permission) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            return false;
        }
        
        $role = $_SESSION['role'];
        
        // تعیین دسترسی‌های هر نقش
        $permissions = [
            'admin' => [
                'manage_settings',
                'manage_users',
                'manage_inventory',
                'manage_devices',
                'manage_production',
                'manage_suppliers',
                'view_reports'
            ],
            'manager' => [
                'manage_inventory',
                'manage_devices',
                'manage_production',
                'manage_suppliers',
                'view_reports'
            ],
            'inventory' => [
                'manage_inventory',
                'view_reports'
            ],
            'production' => [
                'manage_production',
                'view_reports'
            ]
        ];
        
        if (!isset($permissions[$role])) {
            return false;
        }
        
        return in_array($permission, $permissions[$role]);
    }
    
    /**
     * تایید کاربر با نام کاربری و رمز عبور
     */
    private function verifyUser($username, $password) {
        $username = $this->db->real_escape_string($username);
        
        $query = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // بررسی رمز عبور
        if (password_verify($password, $user['password'])) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * ایجاد توکن امنیتی
     */
    private function generateAuthToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * ذخیره توکن "مرا به خاطر بسپار"
     */
    private function saveRememberToken($user_id, $token, $expire) {
        $user_id = (int)$user_id;
        $token = $this->db->real_escape_string($token);
        $expire = (int)$expire;
        
        // حذف توکن‌های قبلی
        $this->removeRememberTokens($user_id);
        
        // ذخیره توکن جدید
        $query = "INSERT INTO user_tokens (user_id, token, expire) VALUES ($user_id, '$token', $expire)";
        $this->db->query($query);
    }
    
    /**
     * حذف توکن‌های "مرا به خاطر بسپار"
     */
    private function removeRememberTokens($user_id) {
        $user_id = (int)$user_id;
        
        $query = "DELETE FROM user_tokens WHERE user_id = $user_id";
        $this->db->query($query);
    }
    
    /**
     * دریافت کاربر با توکن "مرا به خاطر بسپار"
     */
    private function getUserByRememberToken($token) {
        $token = $this->db->real_escape_string($token);
        $now = time();
        
        $query = "SELECT u.* FROM users u
                  JOIN user_tokens t ON u.id = t.user_id
                  WHERE t.token = '$token' AND t.expire > $now AND u.status = 'active'";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
}once(CORE_PATH . '/models/production_order.php');

class MainController extends BaseController {
    private $inventoryModel;
    private $deviceModel;
    private $productionOrderModel;
    
    /**
     * سازنده کنترلر اصلی
     */
    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
        $this->deviceModel = new DeviceModel();
        $this->productionOrderModel = new ProductionOrderModel();
    }
    
    /**
     * صفحه اصلی
     */
    public function index() {
        // بررسی نصب سیستم
        $this->checkInstallation();
        
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        // اطلاعات آماری
        $stats = [
            'total_inventory' => $this->inventoryModel->getTotalItems(),
            'low_stock' => $this->inventoryModel->getLowStockCount(),
            'pending_orders' => $this->productionOrderModel->getPendingOrdersCount(),
            'total_devices' => $this->deviceModel->getTotalDevices()
        ];
        
        $this->loadView('dashboard', [
            'stats' => $stats,
            'page_title' => 'صفحه اصلی - سیستم مدیریت انبار'
        ]);
    }
    
    /**
     * صفحه تنظیمات
     */
    public function settings() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_settings')) {
            $this->redirect('index.php?controller=main&action=access_denied');
        }
        
        $business_info = getBusinessInfo();
        
        $this->loadView('settings', [
            'business_info' => $business_info,
            'page_title' => 'تنظیمات سیستم',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره تنظیمات
     */
    public function saveSettings() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_settings')) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'business_name' => $_POST['business_name'] ?? '',
            'business_phone' => $_POST['business_phone'] ?? '',
            'business_email' => $_POST['business_email'] ?? '',
            'business_address' => $_POST['business_address'] ?? ''
        ];
        
        $result = saveBusinessInfo($data);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'تنظیمات با موفقیت ذخیره شدند']);
        } else {
            $this->jsonResponse(['error' => 'خطا در ذخیره تنظیمات'], 400);
        }
    }
    
    /**
     * بررسی نصب سیستم
     */
    public function checkInstallation() {
        // بررسی وجود فایل config.php
        if (!file_exists(BASE_PATH . '/config.php')) {
            $this->redirect('setup.php');
            return false;
        }
        
        // بررسی اتصال به دیتابیس
        if (!$this->db) {
            $this->redirect('setup.php?step=database');
            return false;
        }
        
        // بررسی وجود جداول مورد نیاز
        $tables_exist = $this->checkTablesExist();
        if (!$tables_exist) {
            $this->redirect('setup.php?step=tables');
            return false;
        }
        
        // بررسی نیاز به به‌روزرسانی
        $updates_needed = $this->checkUpdatesNeeded();
        if ($updates_needed) {
            $this->redirect('migrate.php');
            return false;
        }
        
        return true;
    }
    
    /**
     * بررسی وجود جداول مورد نیاز
     */
    private function checkTablesExist() {
        $required_tables = [
            'inventory_records',
            'inventory_sessions',
            'devices',
            'device_bom',
            'production_orders',
            'suppliers',
            'users',
            'migrations'
        ];
        
        $query = "SHOW TABLES";
        $result = $this->db->query($query);
        
        if (!$result) {
            return false;
        }
        
        $existing_tables = [];
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $existing_tables[] = $row[0];
        }
        
        foreach ($required_tables as $table) {
            if (!in_array($table, $existing_tables)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * بررسی نیاز به به‌روزرسانی
     */
    private function checkUpdatesNeeded() {
        if (!$this->checkTablesExist()) {
            return true;
        }
        
        $query = "SELECT version FROM migrations ORDER BY id DESC LIMIT 1";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return true;
        }
        
        $row = $result->fetch_assoc();
        $db_version = $row['version'];
        
        // نسخه فعلی سیستم
        $current_version = $this->config['version'];
        
        return version_compare($db_version, $current_version, '<');
    }
    
    /**
     * نمایش صفحه لاگین
     */
    public function login() {
        // اگر کاربر قبلاً لاگین کرده باشد، به صفحه اصلی هدایت می‌شود
        if ($this->isUserLoggedIn()) {
            $this->redirect('index.php');
        }
        
        $this->loadView('login', [
            'page_title' => 'ورود به سیستم',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * پردازش لاگین
     */
    public function processLogin() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] == '1';
        
        $user = $this->verifyUser($username, $password);
        
        if ($user) {
            // ایجاد سشن کاربر
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // ایجاد توکن امنیتی
            $auth_token = $this->generateAuthToken();
            $_SESSION['auth_token'] = $auth_token;
            
            // اگر گزینه "مرا به خاطر بسپار" انتخاب شده باشد
            if ($remember) {
                $cookie_token = $this->generateAuthToken();
                $expire = time() + (30 * 24 * 60 * 60); // 30 روز
                
                // ذخیره توکن در دیتابیس
                $this->saveRememberToken($user['id'], $cookie_token, $expire);
                
                // ذخیره توکن در کوکی
                setcookie('remember_token', $cookie_token, $expire, '/', '', true, true);
            }
            
            $this->redirect('index.php');
        } else {
            $this->loadView('login', [
                'page_title' => 'ورود به سیستم',
                'csrf_token' => $this->generateCsrfToken(),
                'error' => 'نام کاربری یا رمز عبور اشتباه است',
                'username' => $username
            ]);
        }
    }
    
    /**
     * خروج از سیستم
     */
    public function logout() {
        // حذف توکن "مرا به خاطر بسپار" از دیتابیس
        if (isset($_SESSION['user_id'])) {
            $this->removeRememberTokens($_SESSION['user_id']);
        }
        
        // حذف سشن کاربر
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        unset($_SESSION['login_time']);
        unset($_SESSION['auth_token']);
        
        // حذف کوکی
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        
        // نابود کردن کامل سشن
        session_destroy();
        
        $this->redirect('index.php?controller=main&action=login');
    }
    
    /**
     * صفحه دسترسی غیرمجاز
     */
    public function accessDenied() {
        $this->loadView('access_denied', [
            'page_title' => 'دسترسی غیرمجاز'
        ]);
    }
    
    /**
     * بررسی اینکه آیا کاربر لاگین کرده است
     */
    protected function isUserLoggedIn() {
        // بررسی سشن کاربر
        if (isset($_SESSION['user_id']) && isset($_SESSION['auth_token'])) {
            return true;
        }
        
        // بررسی توکن "مرا به خاطر بسپار"
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            $user = $this->getUserByRememberToken($token);
            
            if ($user) {
                // ایجاد سشن کاربر
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // ایجاد توکن امنیتی جدید
                $auth_token = $this->generateAuthToken();
                $_SESSION['auth_token'] = $auth_token;
                
                return true;
            }
        }
        
        return false;
    }
    private function getPendingOrdersCount() {
        $sql = "SELECT COUNT(*) as count FROM production_orders WHERE status IN ('pending', 'confirmed')";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * تعداد کل دستگاه‌ها
     */
    private function getDevicesCount() {
        $sql = "SELECT COUNT(*) as count FROM devices";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
