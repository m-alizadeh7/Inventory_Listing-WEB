<?php
/**
 * کلاس پایه برای کنترلرها
 * 
 * این کلاس شامل متدهای مشترک برای همه کنترلرها است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class BaseController {
    protected $db;
    protected $table_prefix;
    protected $current_user;
    
    /**
     * سازنده کلاس پایه
     */
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->table_prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
        $this->current_user = isset($_SESSION['user_data']) ? $_SESSION['user_data'] : false;
    }
    
    /**
     * بررسی احراز هویت
     * 
     * @return bool
     */
    protected function isAuthenticated() {
        return $this->current_user !== false;
    }
    
    /**
     * بررسی دسترسی کاربر
     * 
     * @param string $action
     * @return bool
     */
    protected function hasPermission($action) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $role = $this->current_user['role'];
        
        // تعریف دسترسی‌ها بر اساس نقش
        $permissions = [
            'admin' => ['*'], // مدیر به همه بخش‌ها دسترسی دارد
            'manager' => [
                'view_dashboard', 'view_inventory', 'add_inventory', 'edit_inventory', 'delete_inventory',
                'view_devices', 'add_device', 'edit_device', 'delete_device',
                'view_suppliers', 'add_supplier', 'edit_supplier', 'delete_supplier',
                'view_production', 'add_production', 'edit_production', 'delete_production',
                'view_users', 'add_user', 'edit_user',
                'view_reports'
            ],
            'inventory' => [
                'view_dashboard', 'view_inventory', 'add_inventory', 'edit_inventory',
                'view_devices', 'view_suppliers', 'view_production'
            ],
            'production' => [
                'view_dashboard', 'view_production', 'add_production', 'edit_production',
                'view_devices', 'view_suppliers', 'view_inventory'
            ],
            'user' => [
                'view_dashboard', 'view_inventory', 'view_devices', 'view_suppliers', 'view_production'
            ]
        ];
        
        // اگر نقش کاربر تعریف نشده باشد، دسترسی ندارد
        if (!isset($permissions[$role])) {
            return false;
        }
        
        // اگر کاربر مدیر است، به همه بخش‌ها دسترسی دارد
        if ($role === 'admin' || in_array('*', $permissions[$role])) {
            return true;
        }
        
        // بررسی دسترسی خاص
        return in_array($action, $permissions[$role]);
    }
    
    /**
     * هدایت به صفحه لاگین
     */
    protected function redirectToLogin() {
        header('Location: index.php?controller=main&action=login');
        exit;
    }
    
    /**
     * نمایش پیام عدم دسترسی
     */
    protected function showAccessDenied() {
        echo '<div class="alert alert-danger" style="direction: rtl; font-family: Tahoma;">
              شما دسترسی لازم برای مشاهده این بخش را ندارید.</div>';
    }
    
    /**
     * اعتبارسنجی ورودی‌ها
     * 
     * @param string $input
     * @return string
     */
    protected function sanitizeInput($input) {
        if ($this->db) {
            return $this->db->real_escape_string(trim($input));
        }
        return trim($input);
    }
    
    /**
     * نمایش پیام خطا
     * 
     * @param string $message
     */
    protected function showError($message) {
        echo '<div class="alert alert-danger" style="direction: rtl; font-family: Tahoma;">' 
             . htmlspecialchars($message) . '</div>';
    }
    
    /**
     * نمایش پیام موفقیت
     * 
     * @param string $message
     */
    protected function showSuccess($message) {
        echo '<div class="alert alert-success" style="direction: rtl; font-family: Tahoma;">' 
             . htmlspecialchars($message) . '</div>';
    }
    
    /**
     * بررسی CSRF توکن
     * 
     * @param string $token
     * @return bool
     */
    protected function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * تولید CSRF توکن
     * 
     * @return string
     */
    protected function generateCSRF() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
