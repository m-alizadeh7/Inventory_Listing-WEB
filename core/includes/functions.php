<?php
/**
 * توابع اصلی سیستم
 * 
 * این فایل شامل توابع اصلی مورد نیاز سیستم است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

/**
 * دریافت اطلاعات کسب و کار
 * 
 * @return array اطلاعات کسب و کار
 */
function getBusinessInfo() {
    global $conn;
    
    // بررسی وجود جدول business_info
    $table_check = $conn->query("SHOW TABLES LIKE 'business_info'");
    if (!$table_check || $table_check->num_rows === 0) {
        return [
            'business_name' => 'سیستم مدیریت انبار',
            'business_owner' => 'مهدی علیزاده',
            'business_address' => '',
            'business_phone' => '',
            'business_email' => 'm.alizadeh7@live.com',
            'business_website' => 'https://alizadehx.ir'
        ];
    }
    
    $result = $conn->query("SELECT * FROM business_info LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [
        'business_name' => 'سیستم مدیریت انبار',
        'business_owner' => 'مهدی علیزاده',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => 'm.alizadeh7@live.com',
        'business_website' => 'https://alizadehx.ir'
    ];
}

/**
 * پاکسازی داده‌های ورودی
 * 
 * @param mixed $data داده ورودی
 * @return mixed داده پاکسازی شده
 */
function clean($data) {
    global $conn;
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = clean($value);
        }
        return $data;
    }
    
    if (is_null($data)) {
        return '';
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    if ($conn) {
        $data = $conn->real_escape_string($data);
    }
    
    return $data;
}

/**
 * بررسی نصب بودن سیستم
 * 
 * @return bool وضعیت نصب
 */
function isSystemInstalled() {
    return file_exists(BASE_PATH . '/config.php');
}

/**
 * بارگذاری قالب
 * 
 * @param string $template نام قالب
 * @param array $data داده‌های مورد نیاز قالب
 * @return void
 */
function loadTemplate($template, $data = []) {
    $theme = getOption('active_theme', 'default');
    $template_file = TEMPLATES_PATH . '/' . $theme . '/' . $template . '.php';
    
    if (!file_exists($template_file)) {
        $template_file = TEMPLATES_PATH . '/default/' . $template . '.php';
    }
    
    if (file_exists($template_file)) {
        extract($data);
        include($template_file);
    } else {
        echo "قالب {$template} یافت نشد!";
    }
}

/**
 * دریافت تنظیمات
 * 
 * @param string $option_name نام تنظیم
 * @param mixed $default مقدار پیش‌فرض
 * @return mixed مقدار تنظیم
 */
function getOption($option_name, $default = '') {
    global $conn;
    
    if (!$conn) {
        return $default;
    }
    
    $option_name = clean($option_name);
    $result = $conn->query("SELECT option_value FROM options WHERE option_name = '{$option_name}' LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['option_value'];
    }
    
    return $default;
}

/**
 * به‌روزرسانی تنظیمات
 * 
 * @param string $option_name نام تنظیم
 * @param mixed $option_value مقدار تنظیم
 * @return bool نتیجه به‌روزرسانی
 */
function updateOption($option_name, $option_value) {
    global $conn;
    
    if (!$conn) {
        return false;
    }
    
    $option_name = clean($option_name);
    $option_value = clean($option_value);
    
    $result = $conn->query("SELECT option_id FROM options WHERE option_name = '{$option_name}' LIMIT 1");
    
    if ($result && $result->num_rows > 0) {
        return $conn->query("UPDATE options SET option_value = '{$option_value}' WHERE option_name = '{$option_name}'");
    } else {
        return $conn->query("INSERT INTO options (option_name, option_value) VALUES ('{$option_name}', '{$option_value}')");
    }
}

/**
 * نمایش پیام خطا
 * 
 * @param string $message پیام خطا
 * @return void
 */
function showError($message) {
    echo '<div class="alert alert-danger">' . $message . '</div>';
}

/**
 * نمایش پیام موفقیت
 * 
 * @param string $message پیام موفقیت
 * @return void
 */
function showSuccess($message) {
    echo '<div class="alert alert-success">' . $message . '</div>';
}

/**
 * تبدیل تاریخ میلادی به شمسی
 * 
 * @param string $date تاریخ میلادی
 * @param string $format فرمت خروجی
 * @return string تاریخ شمسی
 */
function jdate($format = 'Y/m/d', $timestamp = null) {
    // اینجا کد تبدیل تاریخ به شمسی قرار می‌گیرد
    // برای سادگی فعلاً از تاریخ میلادی استفاده می‌کنیم
    $timestamp = $timestamp ?: time();
    return date($format, $timestamp);
}

/**
 * بررسی نیاز به اجرای مهاجرت‌ها
 */
function checkMigrationsPrompt() {
    global $conn;
    
    if (!$conn) {
        return;
    }
    
    // بررسی وجود جدول migrations
    $result = $conn->query("SHOW TABLES LIKE 'migrations'");
    if ($result && $result->num_rows === 0) {
        echo '<div class="alert alert-warning mb-4">';
        echo '<h5 class="alert-heading"><i class="bi bi-database-gear"></i> نیاز به به‌روزرسانی ساختار پایگاه داده</h5>';
        echo '<p>ساختار پایگاه داده نیاز به به‌روزرسانی دارد.</p>';
        echo '<a href="migrate.php" class="btn btn-primary">اجرای به‌روزرسانی</a>';
        echo '</div>';
        return;
    }
    
    // بررسی وجود مهاجرت‌های اجرا نشده
    $result = $conn->query("SELECT COUNT(*) as count FROM migrations");
    $migrated_count = $result->fetch_assoc()['count'];
    
    // استفاده از ROOT_PATH یا تعریف مسیر
    $base_path = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(dirname(__FILE__)));
    $migration_files = glob($base_path . '/migrations/*.sql');
    $total_migrations = count($migration_files);
    
    if ($total_migrations > $migrated_count) {
        echo '<div class="alert alert-warning mb-4">';
        echo '<h5 class="alert-heading"><i class="bi bi-database-gear"></i> نیاز به به‌روزرسانی ساختار پایگاه داده</h5>';
        echo '<p>' . ($total_migrations - $migrated_count) . ' به‌روزرسانی جدید موجود است.</p>';
        echo '<a href="migrate.php" class="btn btn-primary">اجرای به‌روزرسانی</a>';
        echo '</div>';
    }
}
