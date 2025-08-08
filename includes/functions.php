<?php
// تبدیل تاریخ میلادی به شمسی
function gregorianToJalali($date) {
    if (empty($date)) return '-';
    $datetime = new DateTime($date);
    $timestamp = $datetime->getTimestamp();
    
    $year = date('Y', $timestamp);
    $month = date('n', $timestamp);
    $day = date('j', $timestamp);
    $hour = date('H', $timestamp);
    $minute = date('i', $timestamp);
    
    $jYear = $jMonth = $jDay = 0;
    convertToJalali($year, $month, $day, $jYear, $jMonth, $jDay);
    
    return sprintf('%04d/%02d/%02d %02d:%02d', $jYear, $jMonth, $jDay, $hour, $minute);
}

// تابع کمکی برای تبدیل تاریخ
function convertToJalali($g_y, $g_m, $g_d, &$j_y, &$j_m, &$j_d) {
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    $gy = $g_y-1600;
    $gm = $g_m-1;
    $gd = $g_d-1;
    
    $g_day_no = 365*$gy+div($gy+3,4)-div($gy+99,100)+div($gy+399,400);
    
    for ($i=0; $i < $gm; ++$i)
        $g_day_no += $g_days_in_month[$i];
    if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
        $g_day_no++;
    $g_day_no += $gd;
    
    $j_day_no = $g_day_no-79;
    
    $j_np = div($j_day_no, 12053);
    $j_day_no = $j_day_no % 12053;
    
    $jy = 979+33*$j_np+4*div($j_day_no,1461);
    
    $j_day_no %= 1461;
    
    if ($j_day_no >= 366) {
        $jy += div($j_day_no-1, 365);
        $j_day_no = ($j_day_no-1)%365;
    }
    
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
        $j_day_no -= $j_days_in_month[$i];
    $jm = $i+1;
    $jd = $j_day_no+1;
    
    $j_y = $jy;
    $j_m = $jm;
    $j_d = $jd;
}

function div($a, $b) {
    return (int)($a / $b);
}

// تابع نمایش پیغام
function showMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// تابع اعتبارسنجی فایل CSV
function validateCSV($file) {
    $allowed = ['text/csv', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowed)) {
        return 'فرمت فایل باید CSV باشد.';
    }
    if ($file['size'] > 5000000) { // 5MB
        return 'حجم فایل نباید بیشتر از 5 مگابایت باشد.';
    }
    return true;
}

// تابع تمیز کردن ورودی
function clean($string) {
    return trim($string);
}
/**
 * بررسی و نمایش اعلان مایگریشن‌های جدید
 */
function checkMigrationsPrompt() {
    global $conn;
    
    // اطمینان از وجود جدول migrations
    $conn->query("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $migrationsDir = __DIR__ . '/../migrations';
    if (!is_dir($migrationsDir)) {
        return;
    }
    
    $files = glob($migrationsDir . '/*.sql');
    if (!$files) {
        return;
    }
    
    $pending = [];
    foreach ($files as $file) {
        $name = basename($file);
        $stmt = $conn->prepare("SELECT id FROM migrations WHERE migration = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $pending[] = $name;
        }
        $stmt->close();
    }
    
    if (count($pending) > 0) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
        echo '<i class="bi bi-exclamation-triangle me-2"></i>';
        echo '<strong>نسخه دیتابیس قدیمی است!</strong> ';
        echo 'برای بروز رسانی جداول ';
        echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'آیا مطمئن هستید که می‌خواهید دیتابیس را به‌روزرسانی کنید؟\')">';
        echo '<button name="run_migrations" class="btn btn-sm btn-primary mx-1">';
        echo '<i class="bi bi-arrow-clockwise me-1"></i>اجرای به‌روزرسانی';
        echo '</button>';
        echo '</form>';
        echo ' کلیک کنید.';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * اطمینان از وجود ساختار کامل جدول suppliers
 */
function ensureSupplierSchema() {
    global $conn;
    
    // اطمینان از وجود جدول suppliers
    $conn->query("CREATE TABLE IF NOT EXISTS suppliers (
        supplier_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_name VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // ستون‌هایی که باید در جدول suppliers وجود داشته باشند
    $required_columns = [
        'supplier_code' => 'VARCHAR(50) NULL',
        'contact_person' => 'VARCHAR(100) NULL',
        'phone' => 'VARCHAR(30) NULL',
        'email' => 'VARCHAR(100) NULL',
        'address' => 'TEXT NULL'
    ];
    
    foreach ($required_columns as $column => $definition) {
        $res = $conn->query("SHOW COLUMNS FROM suppliers LIKE '$column'");
        if ($res && $res->num_rows === 0) {
            $conn->query("ALTER TABLE suppliers ADD COLUMN $column $definition");
        }
    }
}

/**
 * دریافت اطلاعات کسب و کار
 */
function getBusinessInfo() {
    global $conn;
    
    $business_info = [
        'business_name' => 'سیستم انبارداری',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => '',
        'business_website' => ''
    ];
    
    $business_fields = ['business_name', 'business_address', 'business_phone', 'business_email', 'business_website'];
    foreach ($business_fields as $field) {
        $result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = '$field'");
        if ($result && $row = $result->fetch_assoc()) {
            $business_info[$field] = $row['setting_value'];
        }
    }
    
    return $business_info;
}

/**
 * اجرای فایل‌های migration
 */
function runMigrations() {
    global $conn;
    
    // اطمینان از وجود جدول migrations
    $conn->query("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    $migrationsDir = __DIR__ . '/../migrations';
    if (!is_dir($migrationsDir)) {
        return false;
    }
    
    $files = glob($migrationsDir . '/*.sql');
    sort($files); // مرتب‌سازی بر اساس نام فایل
    
    foreach ($files as $file) {
        $migrationName = basename($file);
        // بررسی اینکه آیا این migration قبلاً اجرا شده
        $stmt = $conn->prepare("SELECT id FROM migrations WHERE migration = ?");
        $stmt->bind_param("s", $migrationName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $sql = file_get_contents($file);
            if ($sql) {
                // اجرای هر دستور جداگانه و نادیده گرفتن خطاهای harmless
                $queries = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
                $hasFatalError = false;
                foreach ($queries as $query) {
                    if ($query === '') continue;
                    try {
                        if (!$conn->query($query)) {
                            $err = strtolower($conn->error);
                            if (
                                strpos($err, 'already exists') !== false ||
                                strpos($err, 'duplicate') !== false ||
                                strpos($err, 'exists') !== false
                            ) {
                                // نادیده گرفتن خطاهای harmless
                                continue;
                            } else {
                                $hasFatalError = true;
                                throw new Exception("خطا در اجرای migration $migrationName: " . $conn->error);
                            }
                        }
                    } catch (Exception $e) {
                        // اگر خطا fatal نبود، ادامه بده
                        if (!$hasFatalError) continue;
                        else throw $e;
                    }
                }
                if (!$hasFatalError) {
                    // ثبت migration
                    $stmt2 = $conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
                    $stmt2->bind_param("s", $migrationName);
                    $stmt2->execute();
                    $stmt2->close();
                }
            }
        }
        $stmt->close();
    }
    
    return true;
}
