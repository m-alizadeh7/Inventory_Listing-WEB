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

/**
 * Jalali date helper similar to jdate from jdf library (lightweight)
 * Supports tokens: Y, y, m, n, d, j, H, i, s
 * $timestamp may be integer or strtotime-compatible string
 */
function jdate($format, $timestamp = null) {
    if ($timestamp === null) {
        $ts = time();
    } else {
        $ts = is_numeric($timestamp) ? (int)$timestamp : strtotime($timestamp);
        if ($ts === false) $ts = time();
    }

    $gY = (int)date('Y', $ts);
    $gM = (int)date('n', $ts);
    $gD = (int)date('j', $ts);
    $gH = date('H', $ts);
    $gI = date('i', $ts);
    $gS = date('s', $ts);

    $jY = $jM = $jD = 0;
    convertToJalali($gY, $gM, $gD, $jY, $jM, $jD);

    $replacements = array(
        'Y' => sprintf('%04d', $jY),
        'y' => substr(sprintf('%04d', $jY), -2),
        'm' => sprintf('%02d', $jM),
        'n' => $jM,
        'd' => sprintf('%02d', $jD),
        'j' => $jD,
        'H' => $gH,
        'i' => $gI,
        's' => $gS,
    );

    // Simple token replacement
    return strtr($format, $replacements);
}

// تابع نمایش پیغام
function showMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// Fallback flash message helpers (guarded) - theme may provide these, so only define if missing
if (!function_exists('set_flash_message')) {
    function set_flash_message($message, $type = 'info') {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                session_start();
            } else {
                return false;
            }
        }
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        return true;
    }
}

if (!function_exists('get_flash_message')) {
    function get_flash_message() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                session_start();
            } else {
                return null;
            }
        }
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return array('message' => $message, 'type' => $type);
        }
        return null;
    }
}

if (!function_exists('display_flash_messages')) {
    function display_flash_messages() {
        if (session_status() !== PHP_SESSION_ACTIVE && headers_sent()) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $fm = get_flash_message();
        if ($fm && !empty($fm['message'])) {
            $type = in_array($fm['type'], ['success','danger','warning','info']) ? $fm['type'] : 'info';
            $msg = htmlspecialchars($fm['message']);
            echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">{$msg}";
            echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
            echo "</div>";
        }
    }
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
function checkMigrationsPrompt($conn = null) {
    if ($conn === null) {
        // First try to use the global $conn from config.php
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // If global $conn is not available, try getDbConnection()
            if (function_exists('getDbConnection')) {
                try {
                    $conn = getDbConnection();
                } catch (Exception $e) {
                    $conn = null;
                }
            } else {
                $conn = null;
            }
        }
    }
    // Ensure we have a mysqli connection when called from bootstrap; try to obtain one if not present
    if (!isset($conn) || !($conn instanceof mysqli)) {
        if (function_exists('getDbConnection')) {
            try { $conn = getDbConnection(false); } catch (Exception $e) { $conn = null; }
        } else { $conn = null; }
    }

    // If no DB connection, skip migrations prompt
    if (!isset($conn) || !($conn instanceof mysqli)) return;

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
function ensureSupplierSchema($conn = null) {
    if ($conn === null) {
        // First try to use the global $conn from config.php
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // If global $conn is not available, try getDbConnection()
            if (function_exists('getDbConnection')) {
                try {
                    $conn = getDbConnection();
                } catch (Exception $e) {
                    $conn = null;
                }
            } else {
                $conn = null;
            }
        }
    }
    
    // اطمینان از وجود جدول suppliers
    // Ensure DB connection
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return;
    }

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
function getBusinessInfo($conn = null) {
    if ($conn === null) {
        // First try to use the global $conn from config.php
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // If global $conn is not available, try getDbConnection()
            if (function_exists('getDbConnection')) {
                try {
                    $conn = getDbConnection();
                } catch (Exception $e) {
                    $conn = null;
                }
            } else {
                $conn = null;
            }
        }
    }
    
    $business_info = [
        'business_name' => 'سیستم انبارداری',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => '',
        'business_website' => ''
    ];
    
    // If no database connection, return default values
    if ($conn === null || !($conn instanceof mysqli)) {
        return $business_info;
    }
    
    $business_fields = ['business_name', 'business_address', 'business_phone', 'business_email', 'business_website'];

    // بررسی وجود جدول settings قبل از اجرای SELECT
    $res = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($res && $res->num_rows > 0) {
        foreach ($business_fields as $field) {
            $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
            if ($stmt) {
                $stmt->bind_param('s', $field);
                if ($stmt->execute()) {
                    $r = $stmt->get_result();
                    if ($r && $row = $r->fetch_assoc()) {
                        $business_info[$field] = $row['setting_value'];
                    }
                }
                $stmt->close();
            }
        }
    }
    
    return $business_info;
}

/**
 * دریافت یک تنظیم از جدول settings
 */
function getSetting($name, $default = null, $conn = null) {
    if ($conn === null) {
        // First try to use the global $conn from config.php
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // If global $conn is not available, try getDbConnection()
            if (function_exists('getDbConnection')) {
                try {
                    $conn = getDbConnection();
                } catch (Exception $e) {
                    $conn = null;
                }
            } else {
                $conn = null;
            }
        }
    }
    // Ensure we have a mysqli connection; try to obtain one if not present
    if (!isset($conn) || !($conn instanceof mysqli)) {
        if (function_exists('getDbConnection')) {
            try {
                $conn = getDbConnection(false);
            } catch (Exception $e) {
                $conn = null;
            }
        } else {
            $conn = null;
        }
    }

    // If still no connection, return default safely
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return $default;
    }

    try {
        $res = $conn->query("SHOW TABLES LIKE 'settings'");
        if (!($res && $res->num_rows > 0)) return $default;

        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = ? LIMIT 1");
        if (!$stmt) return $default;
        $stmt->bind_param('s', $name);
        if (!$stmt->execute()) { $stmt->close(); return $default; }
        $r = $stmt->get_result();
        if ($r && $row = $r->fetch_assoc()) {
            $stmt->close();
            return $row['setting_value'];
        }
        $stmt->close();
        return $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * اجرای فایل‌های migration
 */
function runMigrations($conn = null) {
    if ($conn === null) {
        // First try to use the global $conn from config.php
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            // If global $conn is not available, try getDbConnection()
            if (function_exists('getDbConnection')) {
                try {
                    $conn = getDbConnection();
                } catch (Exception $e) {
                    $conn = null;
                }
            } else {
                $conn = null;
            }
        }
    }
    
    // If no database connection, skip migrations
    if ($conn === null || !($conn instanceof mysqli)) {
        return;
    }
    
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
                    $stmt2 = $conn->prepare("INSERT INTO migrations (migration, applied_at) VALUES (?, NOW())");
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

/**
 * Get database connection
 */
function getDatabaseConnection($exitOnError = true) {
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
        if ($exitOnError) {
            throw new Exception('Database configuration not found');
        }
        return null;
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        if ($exitOnError) {
            throw $e;
        }
        return null;
    }
}
/**
 * بررسی آیا کاربر ادمین است
 * @return bool آیا کاربر ادمین است
 */
if (!function_exists('is_admin')) {
    function is_admin() {
        // بررسی وجود سشن ادمین
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            return true;
        }
        
        // بررسی وجود سشن نقش کاربر
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            return true;
        }
        
        return false;
    }
}

/**
 * دریافت URL پنل ادمین
 * @return string آدرس پنل ادمین
 */
if (!function_exists('get_admin_url')) {
    function get_admin_url() {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        $admin_path = dirname($_SERVER['PHP_SELF']);
        
        // اطمینان از پایان یافتن مسیر با '/'
        if (substr($admin_path, -1) !== '/') {
            $admin_path .= '/';
        }
        
        return $base_url . $admin_path . 'admin/';
    }
}
