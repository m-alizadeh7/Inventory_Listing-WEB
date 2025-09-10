<?php
/**
 * بررسی لایسنس در هنگام اجرای برنامه
 * این فایل مسئول بررسی اعتبار لایسنس و هدایت کاربر به صفحه فعال‌سازی لایسنس در صورت نیاز است
 */

require_once dirname(__FILE__) . '/LicenseManager.php';

/**
 * بررسی لایسنس برنامه
 * @return bool آیا لایسنس معتبر است
 */
function check_license() {
    $licenseManager = new LicenseManager();
    return $licenseManager->isLicenseValid();
}

/**
 * اجرای بررسی لایسنس و محدودسازی دسترسی
 * @param bool $redirect هدایت به صفحه فعال‌سازی در صورت عدم اعتبار لایسنس
 * @return bool نتیجه بررسی
 */
function enforce_license($redirect = true) {
    // مسیرهای مستثنی از بررسی لایسنس
    $exempt_paths = [
        '/admin/license_manager.php',
        '/admin/login.php',
        '/admin/logout.php',
        '/assets/',
    ];
    
    // بررسی آیا صفحه جاری از بررسی لایسنس مستثنی است
    $current_path = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    foreach ($exempt_paths as $path) {
        if (strpos($current_path, $path) === 0) {
            return true;
        }
    }
    
    // بررسی لایسنس
    $is_valid = check_license();
    
    // اگر لایسنس معتبر نباشد و هدایت فعال باشد
    if (!$is_valid && $redirect && is_admin()) {
        // هدایت به صفحه فعال‌سازی لایسنس
        header('Location: ' . get_admin_url() . 'license_manager.php');
        exit();
    }
    
    return $is_valid;
}

/**
 * محدودسازی امکانات سیستم در نسخه دمو/منقضی شده
 * @return bool آیا امکانات محدود شده‌اند
 */
function is_limited_functionality() {
    return !check_license();
}

/**
 * تعیین روزهای باقی‌مانده تا انقضای لایسنس
 * @return int|null تعداد روزهای باقی‌مانده یا null در صورت عدم وجود لایسنس
 */
function get_license_days_remaining() {
    $licenseManager = new LicenseManager();
    $license_info = $licenseManager->getLicenseInfo();
    
    if (!$license_info || !isset($license_info['expires_at'])) {
        return null;
    }
    
    $expires_at = strtotime($license_info['expires_at']);
    $now = time();
    
    if ($expires_at <= $now) {
        return 0;
    }
    
    $days_remaining = ceil(($expires_at - $now) / (60 * 60 * 24));
    return $days_remaining;
}

/**
 * نمایش اخطار مربوط به لایسنس
 * این تابع اخطارهای مربوط به انقضای لایسنس را نمایش می‌دهد
 */
function display_license_warnings() {
    if (!is_admin()) {
        return;
    }
    
    $days_remaining = get_license_days_remaining();
    
    if ($days_remaining !== null && $days_remaining <= 30) {
        echo '<div class="alert alert-warning license-warning">';
        echo '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
        
        if ($days_remaining <= 0) {
            echo '<strong>اخطار!</strong> لایسنس شما منقضی شده است. برای جلوگیری از قطع دسترسی، لطفاً لایسنس خود را تمدید کنید. ';
            echo '<a href="' . get_admin_url() . 'license_manager.php" class="btn btn-xs btn-danger">مدیریت لایسنس</a>';
        } else {
            echo '<strong>توجه!</strong> لایسنس شما تا ' . $days_remaining . ' روز دیگر اعتبار دارد. برای جلوگیری از وقفه در کار، لطفاً لایسنس خود را تمدید کنید. ';
            echo '<a href="' . get_admin_url() . 'license_manager.php" class="btn btn-xs btn-info">مدیریت لایسنس</a>';
        }
        
        echo '</div>';
    }
}

// افزودن اخطار لایسنس به صفحات ادمین
if (function_exists('add_admin_header_content')) {
    add_admin_header_content('display_license_warnings');
}

// بارگذاری توابع کمکی سیستم لایسنس
require_once dirname(__FILE__) . '/functions.php';
