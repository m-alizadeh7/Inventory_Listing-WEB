<?php
/**
 * کلاس مدیریت احراز هویت و امنیت
 * Authentication & Authorization System
 * Namespace: App\Core
 */

namespace App\Core;

class SecurityManager {
    private $conn;
    private $current_user = null;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->initializeSession();
        // ensure security logs table exists early to avoid fatal errors when logging
        try {
            $this->ensureSecurityLogsTableExists();
        } catch (Exception $e) {
            // ignore - ensureSecurityLogsTableExists handles its own errors
        }
    }
    
    /**
     * راه‌اندازی جلسه امن
     */
    private function initializeSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // تنظیمات امنیتی جلسه
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
        }
        
        // بررسی معتبر بودن جلسه
        if (isset($_SESSION['user_id'])) {
            $this->validateSession();
        }
    }
    
    /**
     * ورود کاربر
     */
    public function login($username, $password, $remember_me = false) {
        try {
            // پاکسازی ورودی
            $username = trim($username);
            
            // بررسی قفل شدن کاربر
            if ($this->isUserLocked($username)) {
                $this->logSecurityEvent(null, 'login_attempt_locked', 'users', 'تلاش ورود روی حساب قفل شده: ' . $username, false);
                return ['success' => false, 'message' => 'حساب کاربری قفل شده است. لطفاً بعداً تلاش کنید.'];
            }
            
            // دریافت اطلاعات کاربر
            $stmt = $this->conn->prepare("
                SELECT u.* 
                FROM users u 
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if (!$user) {
                $this->recordFailedLogin($username);
                $this->logSecurityEvent(null, 'login_failed', 'users', 'تلاش ورود با نام کاربری اشتباه: ' . $username, false);
                return ['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است.'];
            }
            
            // بررسی رمز عبور
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedLogin($username, $user['user_id']);
                $this->logSecurityEvent($user['user_id'], 'login_failed', 'users', 'رمز عبور اشتباه', false);
                return ['success' => false, 'message' => 'نام کاربری یا رمز عبور اشتباه است.'];
            }
            
            // ورود موفق
            $this->createUserSession($user, $remember_me);
            $this->resetFailedLoginAttempts($user['user_id']);
            $this->logSecurityEvent($user['user_id'], 'login_success', 'users', 'ورود موفق', true);
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            $this->logSecurityEvent(null, 'login_error', 'users', 'خطا در ورود: ' . $e->getMessage(), false);
            return ['success' => false, 'message' => 'خطای سیستم. لطفاً با پشتیبانی تماس بگیرید.'];
        }
    }
    
    /**
     * خروج کاربر
     */
    public function logout() {
        if ($this->current_user) {
            $this->logSecurityEvent($this->current_user['user_id'], 'logout', 'users', 'خروج از سیستم', true);
            
            // حذف جلسه از دیتابیس
            if (isset($_SESSION['session_id'])) {
                $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $stmt->bind_param('s', $_SESSION['session_id']);
                $stmt->execute();
            }
        }
        
        // پاک کردن جلسه
        session_unset();
        session_destroy();
        
        // حذف کوکی‌ها
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * بررسی دسترسی کاربر به عملکرد خاص
     */
    public function hasPermission($permission_name) {
        if (!$this->current_user) {
            return false;
        }
        
        // ادمین سیستم دسترسی کامل دارد
        if ($this->current_user['role_name'] === 'system_admin') {
            return true;
        }
        
        // بررسی مجوز در دیتابیس
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as has_permission 
            FROM role_permissions rp 
            JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = ? AND p.permission_name = ?
        ");
        $stmt->bind_param('is', $this->current_user['role_id'], $permission_name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result['has_permission'] > 0;
    }
    
    /**
     * بررسی دسترسی به گروه کالای خاص
     */
    public function hasCategoryAccess($category_id, $access_type = 'read') {
        if (!$this->current_user) {
            return false;
        }
        
        // مدیران و ادمین‌ها دسترسی کامل دارند
        if ($this->current_user['hierarchy_level'] <= 3) {
            return true;
        }
        
        // بررسی محدودیت‌های خاص کاربر
        $stmt = $this->conn->prepare("
            SELECT access_type 
            FROM user_category_restrictions 
            WHERE user_id = ? AND category_id = ?
        ");
        $stmt->bind_param('ii', $this->current_user['user_id'], $category_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            // اگر محدودیت خاصی نداشت، فقط واحدهای فنی محدود هستند
            return $this->current_user['role_name'] !== 'technical_unit_limited';
        }
        
        // بررسی نوع دسترسی
        switch ($access_type) {
            case 'read':
                return in_array($result['access_type'], ['read', 'write', 'full']);
            case 'write':
                return in_array($result['access_type'], ['write', 'full']);
            case 'full':
                return $result['access_type'] === 'full';
            default:
                return false;
        }
    }
    
    /**
     * دریافت کاربر فعلی
     */
    public function getCurrentUser() {
        return $this->current_user;
    }
    
    /**
     * بررسی ورود کاربر
     */
    public function isLoggedIn() {
        return $this->current_user !== null;
    }
    
    /**
     * درخواست ورود اجباری
     */
    public function requireLogin($redirect_to = 'login.php') {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirect_to);
            exit;
        }
    }
    
    /**
     * درخواست مجوز خاص
     */
    public function requirePermission($permission_name, $error_message = 'شما دسترسی لازم را ندارید.') {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission_name)) {
            $this->logSecurityEvent(
                $this->current_user['user_id'], 
                'access_denied', 
                'permissions', 
                "دسترسی غیرمجاز به: $permission_name", 
                false
            );
            
            http_response_code(403);
            die($error_message);
        }
    }
    
    /**
     * اعتبارسنجی جلسه
     */
    private function validateSession() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
            $this->logout();
            return;
        }
        
        // بررسی جلسه در دیتابیس
        $stmt = $this->conn->prepare("
            SELECT u.*, ur.role_name, ur.hierarchy_level, us.expires_at
            FROM user_sessions us
            JOIN users u ON us.user_id = u.user_id
            JOIN user_roles ur ON u.role_id = ur.role_id
            WHERE us.session_id = ? AND u.is_active = 1
        ");
        $stmt->bind_param('s', $_SESSION['session_id']);
        $stmt->execute();
        $session_data = $stmt->get_result()->fetch_assoc();
        
        if (!$session_data || strtotime($session_data['expires_at']) < time()) {
            $this->logout();
            return;
        }
        
        // به‌روزرسانی آخرین فعالیت - ستون last_activity وجود ندارد
        // $stmt = $this->conn->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_id = ?");
        // $stmt->bind_param('s', $_SESSION['session_id']);
        // $stmt->execute();
        
        $this->current_user = $session_data;
    }
    
    /**
     * ایجاد جلسه کاربری
     */
    private function createUserSession($user, $remember_me = false) {
        $session_id = bin2hex(random_bytes(32));
        $expires_at = $remember_me ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+8 hours'));
        
        // ذخیره جلسه در دیتابیس
        $stmt = $this->conn->prepare("INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $uid = isset($user['user_id']) ? (int)$user['user_id'] : 0;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param('sisss', $session_id, $uid, $ip, $ua, $expires_at);
            $stmt->execute();
            $stmt->close();
        }
        
        // به‌روزرسانی آخرین ورود
        $stmt = $this->conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        if ($stmt) {
            $uid2 = isset($user['user_id']) ? (int)$user['user_id'] : 0;
            $stmt->bind_param('i', $uid2);
            $stmt->execute();
            $stmt->close();
        }
        
        // تنظیم متغیرهای جلسه
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['session_id'] = $session_id;
        $_SESSION['role_name'] = $user['role_name'];
        
        $this->current_user = $user;
        
        // پاکسازی جلسات منقضی
        $this->cleanupExpiredSessions();
    }
    
    /**
     * ثبت تلاش ورود ناموفق
     */
    private function recordFailedLogin($username, $user_id = null) {
        if ($user_id) {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1,
                    locked_until = CASE 
                        WHEN failed_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                        ELSE locked_until 
                    END
                WHERE user_id = ?
            ");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
        }
    }
    
    /**
     * بررسی قفل بودن کاربر
     */
    private function isUserLocked($username) {
        $stmt = $this->conn->prepare("
            SELECT locked_until 
            FROM users 
            WHERE username = ? AND locked_until > NOW()
        ");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result !== null;
    }
    
    /**
     * ریست تلاش‌های ورود ناموفق
     */
    private function resetFailedLoginAttempts($user_id) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, locked_until = NULL 
            WHERE user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    
    /**
     * ثبت رویداد امنیتی
     */
    private function logSecurityEvent($user_id, $action, $module, $description, $success) {
        try {
            // ensure the table exists (safe idempotent operation)
            $this->ensureSecurityLogsTableExists();

            // use a safe, escaped INSERT so NULL user_id is handled gracefully
            $user_id_sql = is_null($user_id) ? 'NULL' : intval($user_id);
            $action_esc = $this->conn->real_escape_string($action ?: '');
            $module_esc = $this->conn->real_escape_string($module ?: '');
            $description_esc = $this->conn->real_escape_string($description ?: '');
            $ip_esc = $this->conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            $ua_esc = $this->conn->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            $success_int = $success ? 1 : 0;

            $sql = "INSERT INTO security_logs (user_id, action, module, description, ip_address, user_agent, success) VALUES ($user_id_sql, '$action_esc', '$module_esc', '$description_esc', '$ip_esc', '$ua_esc', $success_int)";
            $this->conn->query($sql);
        } catch (mysqli_sql_exception $e) {
            // If table missing or other DB-level error occurs, log to PHP error log but do not throw
            error_log('SecurityManager::logSecurityEvent DB error: ' . $e->getMessage());
        } catch (Exception $e) {
            error_log('SecurityManager::logSecurityEvent error: ' . $e->getMessage());
        }
    }

    /**
     * Ensure security_logs table exists. Safe to call repeatedly.
     */
    private function ensureSecurityLogsTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS security_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100) DEFAULT NULL,
            module VARCHAR(100) DEFAULT NULL,
            description TEXT,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            success TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // run as simple query; ignore failures here (handled by caller)
        $this->conn->query($sql);
    }
    
    /**
     * پاکسازی جلسات منقضی
     */
    private function cleanupExpiredSessions() {
        $stmt = $this->conn->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $stmt->execute();
    }
    
    /**
     * تولید رمز عبور امن
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * تولید توکن امن
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
