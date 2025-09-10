<?php
/**
 * توابع کمکی برای سیستم لایسنس
 * این فایل شامل توابع کمکی برای استفاده در سیستم لایسنس است
 */

/**
 * بررسی اینکه آیا عملیات مورد نظر در حالت محدود امکان‌پذیر است
 * @param string $feature ویژگی مورد نظر
 * @return bool آیا این ویژگی در حالت محدود در دسترس است
 */
function is_feature_available($feature) {
    // اگر لایسنس معتبر باشد، همه ویژگی‌ها در دسترس هستند
    if (function_exists('check_license') && check_license()) {
        return true;
    }
    
    // ویژگی‌های محدود در نسخه دمو/منقضی شده
    $limited_features = [
        'export' => false,      // صادرات اطلاعات
        'import' => false,      // واردات اطلاعات
        'backup' => false,      // پشتیبان‌گیری
        'report' => false,      // گزارش‌های پیشرفته
        'api' => false,         // دسترسی به API
        'batch' => false,       // پردازش دسته‌ای
    ];
    
    // ویژگی‌های پایه که همیشه در دسترس هستند
    $basic_features = [
        'view' => true,         // مشاهده اطلاعات
        'add' => true,          // افزودن (با محدودیت تعداد)
        'edit' => true,         // ویرایش
        'delete' => true,       // حذف
        'search' => true,       // جستجو
    ];
    
    // بررسی ویژگی‌های محدود
    if (isset($limited_features[$feature])) {
        return $limited_features[$feature];
    }
    
    // بررسی ویژگی‌های پایه
    if (isset($basic_features[$feature])) {
        return $basic_features[$feature];
    }
    
    // به طور پیش‌فرض، ویژگی‌ها در حالت محدود در دسترس نیستند
    return false;
}

/**
 * دریافت پیام هشدار برای نمایش به کاربر در حالت محدود
 * @param string $feature ویژگی مورد نظر
 * @return string پیام هشدار
 */
function get_limited_feature_message($feature) {
    $messages = [
        'export' => 'صادرات اطلاعات تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'import' => 'واردات اطلاعات تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'backup' => 'پشتیبان‌گیری تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'report' => 'گزارش‌های پیشرفته تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'api' => 'دسترسی به API تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'batch' => 'پردازش دسته‌ای تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.',
        'default' => 'این ویژگی تنها در نسخه دارای لایسنس فعال امکان‌پذیر است.'
    ];
    
    if (isset($messages[$feature])) {
        return $messages[$feature];
    }
    
    return $messages['default'];
}

/**
 * محدود کردن تعداد رکوردها در حالت محدود
 * @param int $count تعداد رکوردها
 * @param int $limit محدودیت تعداد (پیش‌فرض: 50)
 * @return bool آیا محدودیت رعایت شده است
 */
function check_record_limit($count, $limit = 50) {
    // اگر لایسنس معتبر باشد، محدودیتی وجود ندارد
    if (function_exists('check_license') && check_license()) {
        return true;
    }
    
    // بررسی محدودیت تعداد
    return $count < $limit;
}

/**
 * بررسی اینکه آیا واترمارک دمو باید نمایش داده شود
 * @return bool آیا واترمارک دمو نمایش داده شود
 */
function show_demo_watermark() {
    // اگر لایسنس معتبر باشد، واترمارک نمایش داده نمی‌شود
    if (function_exists('check_license') && check_license()) {
        return false;
    }
    
    return true;
}

/**
 * اضافه کردن واترمارک دمو به صفحه
 */
function add_demo_watermark() {
    if (show_demo_watermark()) {
        echo '<div class="demo-watermark">نسخه آزمایشی - برای استفاده کامل، لایسنس تهیه کنید</div>';
        echo '<style>
            .demo-watermark {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: rgba(255, 0, 0, 0.7);
                color: white;
                padding: 10px 15px;
                border-radius: 5px;
                z-index: 9999;
                font-size: 14px;
            }
        </style>';
    }
}

// افزودن واترمارک به صفحات
if (function_exists('add_footer_content')) {
    add_footer_content('add_demo_watermark');
}
