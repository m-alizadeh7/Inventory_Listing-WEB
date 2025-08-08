<?php
/**
 * مدل دیتابیس
 * 
 * این کلاس مسئول بررسی وضعیت نصب دیتابیس و عملیات نصب آن است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class DatabaseModel {
    private $db;
    private $table_prefix;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $db;
        $this->db = $db;
        
        // دریافت پیشوند جداول از فایل کانفیگ
        if (defined('DB_PREFIX')) {
            $this->table_prefix = DB_PREFIX;
        } else {
            $this->table_prefix = 'inv_';
        }
    }
    
    /**
     * بررسی وجود فایل کانفیگ دیتابیس
     * 
     * @return bool
     */
    public function isConfigFileExists() {
        return file_exists(ROOT_PATH . '/config.php');
    }
    
    /**
     * بررسی نصب دیتابیس
     * 
     * @return bool
     */
    public function isDatabaseInstalled() {
        if (!$this->isConfigFileExists()) {
            return false;
        }
        
        try {
            // بررسی وجود جدول users
            $query = "SHOW TABLES LIKE '{$this->table_prefix}users'";
            $result = $this->db->query($query);
            
            return ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * بررسی وجود کاربر مدیر
     * 
     * @return bool
     */
    public function isAdminUserExists() {
        if (!$this->isDatabaseInstalled()) {
            return false;
        }
        
        try {
            $query = "SELECT * FROM {$this->table_prefix}users WHERE role = 'admin' LIMIT 1";
            $result = $this->db->query($query);
            
            return ($result && $result->num_rows > 0);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * بررسی پیش‌نیازهای نصب
     * 
     * @return array
     */
    public function checkRequirements() {
        $requirements = [
            'php_version' => [
                'name' => 'نسخه PHP',
                'requirement' => '7.4.0',
                'current' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
            ],
            'mysqli' => [
                'name' => 'افزونه MySQLi',
                'requirement' => 'فعال',
                'current' => extension_loaded('mysqli') ? 'فعال' : 'غیرفعال',
                'status' => extension_loaded('mysqli')
            ],
            'pdo' => [
                'name' => 'افزونه PDO',
                'requirement' => 'فعال',
                'current' => extension_loaded('pdo') ? 'فعال' : 'غیرفعال',
                'status' => extension_loaded('pdo')
            ],
            'json' => [
                'name' => 'افزونه JSON',
                'requirement' => 'فعال',
                'current' => extension_loaded('json') ? 'فعال' : 'غیرفعال',
                'status' => extension_loaded('json')
            ],
            'write_permission' => [
                'name' => 'دسترسی نوشتن',
                'requirement' => 'فعال',
                'current' => is_writable(ROOT_PATH) ? 'فعال' : 'غیرفعال',
                'status' => is_writable(ROOT_PATH)
            ]
        ];
        
        $all_met = true;
        foreach ($requirements as $req) {
            if (!$req['status']) {
                $all_met = false;
                break;
            }
        }
        
        return [
            'details' => $requirements,
            'all_met' => $all_met
        ];
    }
    
    /**
     * ایجاد فایل کانفیگ دیتابیس
     * 
     * @param array $config
     * @return bool
     */
    public function createConfigFile($config) {
        $template = "<?php
/**
 * فایل پیکربندی
 * 
 * این فایل توسط فرآیند نصب ایجاد شده است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// تنظیمات دیتابیس
define('DB_HOST', '{$config['db_host']}');
define('DB_PORT', '{$config['db_port']}');
define('DB_NAME', '{$config['db_name']}');
define('DB_USER', '{$config['db_user']}');
define('DB_PASS', '{$config['db_pass']}');
define('DB_PREFIX', '{$config['db_prefix']}');

// مسیرها
define('ROOT_PATH', dirname(__FILE__));
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('DEFAULT_TEMPLATE', 'default');
define('ASSETS_URL', './assets');

// تنظیمات عمومی
define('SITE_NAME', 'سیستم مدیریت انبار');
define('DEBUG_MODE', false);

// تنظیمات احراز هویت
define('AUTH_SALT', '" . bin2hex(random_bytes(32)) . "');
define('COOKIE_LIFETIME', 30 * 24 * 60 * 60); // 30 روز
";

        return file_put_contents(ROOT_PATH . '/config.php', $template) !== false;
    }
    
    /**
     * نصب جداول دیتابیس
     * 
     * @return bool
     */
    public function installDatabase() {
        try {
            // جداول مورد نیاز سیستم
            $tables = [
                // جدول کاربران
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL,
                    `password` varchar(255) COLLATE utf8mb4_persian_ci NOT NULL,
                    `email` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
                    `name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
                    `role` enum('admin','manager','inventory','production') COLLATE utf8mb4_persian_ci NOT NULL DEFAULT 'inventory',
                    `remember_token` varchar(255) COLLATE utf8mb4_persian_ci DEFAULT NULL,
                    `remember_expiry` datetime DEFAULT NULL,
                    `last_login` datetime DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول انبار
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}inventory` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `part_number` varchar(50) COLLATE utf8mb4_persian_ci NOT NULL,
                    `supplier_id` int(11) DEFAULT NULL,
                    `part_name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
                    `stock` int(11) NOT NULL DEFAULT '0',
                    `price` decimal(10,0) DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    `updated_by` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `part_number` (`part_number`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول تأمین‌کنندگان
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}suppliers` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
                    `contact_person` varchar(100) COLLATE utf8mb4_persian_ci DEFAULT NULL,
                    `phone` varchar(20) COLLATE utf8mb4_persian_ci DEFAULT NULL,
                    `email` varchar(100) COLLATE utf8mb4_persian_ci DEFAULT NULL,
                    `address` text COLLATE utf8mb4_persian_ci,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول دستگاه‌ها
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}devices` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) COLLATE utf8mb4_persian_ci NOT NULL,
                    `model` varchar(50) COLLATE utf8mb4_persian_ci DEFAULT NULL,
                    `description` text COLLATE utf8mb4_persian_ci,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول ارتباط دستگاه و قطعات
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}device_parts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `device_id` int(11) NOT NULL,
                    `part_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL DEFAULT '1',
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `device_part` (`device_id`,`part_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول سفارش‌های تولید
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}production_orders` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `order_number` varchar(20) COLLATE utf8mb4_persian_ci NOT NULL,
                    `device_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL DEFAULT '1',
                    `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_persian_ci NOT NULL DEFAULT 'pending',
                    `start_date` date DEFAULT NULL,
                    `end_date` date DEFAULT NULL,
                    `notes` text COLLATE utf8mb4_persian_ci,
                    `created_by` int(11) DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `order_number` (`order_number`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;",
                
                // جدول مهاجرت‌ها
                "CREATE TABLE IF NOT EXISTS `{$this->table_prefix}migrations` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `migration` varchar(255) COLLATE utf8mb4_persian_ci NOT NULL,
                    `batch` int(11) NOT NULL,
                    `applied_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;"
            ];
            
            // اجرای کوئری‌های ایجاد جداول
            foreach ($tables as $table_sql) {
                $this->db->query($table_sql);
                
                if ($this->db->error) {
                    throw new Exception("خطا در ایجاد جدول: " . $this->db->error);
                }
            }
            
            // ثبت مهاجرت پایه
            $migration_sql = "INSERT INTO `{$this->table_prefix}migrations` (`migration`, `batch`) VALUES ('initial_setup', 1)";
            $this->db->query($migration_sql);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * ایجاد کاربر مدیر
     * 
     * @param array $admin_data
     * @return bool
     */
    public function createAdminUser($admin_data) {
        try {
            // رمزنگاری رمز عبور
            $hashed_password = password_hash($admin_data['admin_password'], PASSWORD_DEFAULT);
            
            $query = "INSERT INTO `{$this->table_prefix}users` 
                      (`username`, `password`, `email`, `name`, `role`) 
                      VALUES (?, ?, ?, ?, 'admin')";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param(
                "ssss", 
                $admin_data['admin_username'], 
                $hashed_password, 
                $admin_data['admin_email'],
                $admin_data['admin_name']
            );
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
}
