<?php
/**
 * توابع کمکی سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

/**
 * دریافت اطلاعات کسب و کار
 * 
 * @return array
 */
if (!function_exists('getBusinessInfo')) {
    function getBusinessInfo() {
        global $db;
        
        $business_info = [
            'business_name' => 'سیستم مدیریت انبار',
            'business_phone' => '',
            'business_email' => '',
            'business_address' => '',
            'business_website' => '',
            'business_owner' => 'مهدی علیزاده'
        ];
        
        if (!$db) {
            return $business_info;
        }
        
        try {
            $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
            $result = $db->query("SELECT setting_name, setting_value FROM " . $prefix . "settings WHERE setting_name LIKE 'business_%'");
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $business_info[$row['setting_name']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            // در صورت خطا، مقادیر پیش‌فرض برگردانده می‌شود
        }
        
        return $business_info;
    }
}

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $date
 * @return string
 */
if (!function_exists('toJalali')) {
    function toJalali($date) {
        if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
            return '';
        }
        
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $date;
        }
        
        // تبدیل ساده تاریخ (برای استفاده کامل از کتابخانه jdf استفاده کنید)
        return date('Y/m/d H:i', $timestamp);
    }
}

/**
 * فرمت قیمت با جداکننده هزارگان
 * 
 * @param float $price
 * @return string
 */
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 0, '.', ',');
    }
}

/**
 * تولید CSRF توکن
 * 
 * @return string
 */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * بررسی CSRF توکن
 * 
 * @param string $token
 * @return bool
 */
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * تمیز کردن ورودی از تگ‌های HTML
 * 
 * @param string $input
 * @return string
 */
if (!function_exists('cleanInput')) {
    function cleanInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * بررسی صحت ایمیل
 * 
 * @param string $email
 * @return bool
 */
if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * تولید رمز تصادفی
 * 
 * @param int $length
 * @return string
 */
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr(str_shuffle($chars), 0, $length);
    }
}

/**
 * لاگ کردن خطاها
 * 
 * @param string $message
 * @param string $level
 */
if (!function_exists('logError')) {
    function logError($message, $level = 'ERROR') {
        $log_file = ROOT_PATH . '/logs/error.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
    }
}

/**
 * نمایش پیام‌های flash
 */
if (!function_exists('showFlashMessages')) {
    function showFlashMessages() {
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['success_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['success_message']);
        }
        
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['error_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['error_message']);
        }
        
        if (isset($_SESSION['warning_message'])) {
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['warning_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['warning_message']);
        }
        
        if (isset($_SESSION['info_message'])) {
            echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['info_message']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION['info_message']);
        }
    }
}
?>
