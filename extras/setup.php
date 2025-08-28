<?php
session_start();

// تنظیمات اولیه
if (!defined('SYSTEM_VERSION')) {
    define('SYSTEM_VERSION', '1.0.0');
}
define('MIN_PHP_VERSION', '7.4.0');
define('CONFIG_FILE', __DIR__ . '/config.php');

// If a config.php already exists, include it early so constants (like SYSTEM_VERSION) come from it
if (file_exists(CONFIG_FILE)) {
    @include_once CONFIG_FILE;
}

// کلاس اصلی نصب و راه‌اندازی
class Setup {
    private $errors = [];
    private $requirements = [];
    private $dbConnection = null;
    
    public function __construct() {
        $this->checkRequirements();
    }

    // بررسی نیازمندی‌های سیستم
    private function checkRequirements() {
        // بررسی نسخه PHP
        $this->requirements['php_version'] = [
            'title' => 'نسخه PHP',
            'required' => MIN_PHP_VERSION,
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')
        ];

        // بررسی افزونه‌های مورد نیاز
        $requiredExtensions = ['mysqli', 'mbstring', 'json'];
        foreach ($requiredExtensions as $ext) {
            $this->requirements[$ext] = [
                'title' => "افزونه $ext",
                'required' => true,
                'current' => extension_loaded($ext),
                'status' => extension_loaded($ext)
            ];
        }

        // بررسی دسترسی نوشتن
        $writablePaths = [
            '.' => 'پوشه اصلی',
            './config.php' => 'فایل پیکربندی'
        ];
        foreach ($writablePaths as $path => $title) {
            $this->requirements["writable_$path"] = [
                'title' => "دسترسی نوشتن: $title",
                'required' => true,
                'current' => is_writable($path),
                'status' => is_writable($path)
            ];
        }
    }

    // بررسی اتصال به دیتابیس
    public function testDatabaseConnection($host, $username, $password, $database) {
        try {
            // استفاده از تابع getDbConnection برای اتصال ایمن
            $tempHost = defined('DB_HOST') ? DB_HOST : $host;
            $tempUser = defined('DB_USER') ? DB_USER : $username;
            $tempPass = defined('DB_PASS') ? DB_PASS : $password;
            $tempName = defined('DB_NAME') ? DB_NAME : $database;
            
            // موقتا مقادیر جدید را تنظیم کن
            if (!defined('DB_HOST')) define('DB_HOST', $host);
            if (!defined('DB_USER')) define('DB_USER', $username);
            if (!defined('DB_PASS')) define('DB_PASS', $password);
            if (!defined('DB_NAME')) define('DB_NAME', $database);
            
            $this->dbConnection = getDbConnection(true); // ایجاد دیتابیس اگر نبود
            return true;

        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    // ذخیره اطلاعات پیکربندی
    public function saveConfig($host, $username, $password, $database) {
        $config = <<<EOT
<?php
// تنظیمات پایگاه داده
define('DB_HOST', '$host');
define('DB_USER', '$username');
define('DB_PASS', '$password');
define('DB_NAME', '$database');

// اتصال به پایگاه داده
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
\$conn->set_charset("utf8mb4");

// بررسی اتصال
if (\$conn->connect_error) {
    die("خطا در اتصال به پایگاه داده: " . \$conn->connect_error);
}

// نسخه سیستم
define('SYSTEM_VERSION', '1.0.0');
EOT;

        if (file_put_contents(CONFIG_FILE, $config) === false) {
            $this->errors[] = "خطا در ذخیره فایل پیکربندی";
            return false;
        }
        return true;
    }

    // نصب جداول پایه دیتابیس
    public function installDatabase() {
        if (!$this->dbConnection) {
            $this->errors[] = "اتصال به دیتابیس برقرار نیست";
            return false;
        }

        try {
            // ایجاد جدول تنظیمات
            $this->dbConnection->query("
                CREATE TABLE IF NOT EXISTS settings (
                    setting_name VARCHAR(64) PRIMARY KEY,
                    setting_value TEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // ذخیره نسخه سیستم
            $this->dbConnection->query("
                INSERT INTO settings (setting_name, setting_value) 
                VALUES ('system_version', '" . SYSTEM_VERSION . "')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");

            // خواندن و اجرای فایل اصلی دیتابیس
            $sql = file_get_contents('db.sql');
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                if (!$this->dbConnection->query($query)) {
                    $err = $this->dbConnection->error;
                    // ignore harmless duplicate constraint/index errors so installer is idempotent
                    $errLower = strtolower($err);
                    $harmless = [
                        'duplicate foreign key',
                        'duplicate key name',
                        'duplicate entry',
                        'already exists',
                        'duplicate',
                        'cannot add foreign key constraint',
                        'constraint already exists',
                    ];

                    $isHarmless = false;
                    foreach ($harmless as $h) {
                        if (strpos($errLower, $h) !== false) { $isHarmless = true; break; }
                    }

                    if ($isHarmless) {
                        continue;
                    }

                    throw new Exception($err);
                }
            }

            // اجرای مایگریشن‌های اولیه
            $this->runMigrations();

            return true;

        } catch (Exception $e) {
            $this->errors[] = "خطا در نصب دیتابیس: " . $e->getMessage();
            return false;
        }
    }

    // اجرای مایگریشن‌ها
    private function runMigrations() {
        // ایجاد جدول مایگریشن‌ها
        $this->dbConnection->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // اجرای فایل‌های مایگریشن
        $migrations_dir = __DIR__ . '/migrations';
        if (is_dir($migrations_dir)) {
            $files = glob($migrations_dir . '/*.sql');
            sort($files); // اجرا به ترتیب نام

            foreach ($files as $file) {
                $migration_name = basename($file);
                
                // بررسی اجرا نشدن قبلی
                $result = $this->dbConnection->query(
                    "SELECT id FROM migrations WHERE migration = '$migration_name'"
                );
                
                if ($result->num_rows === 0) {
                    $sql = file_get_contents($file);
                    if ($this->dbConnection->multi_query($sql)) {
                        // اجرای همه نتایج
                        while ($this->dbConnection->more_results() && $this->dbConnection->next_result()) {;}
                        
                        // ثبت مایگریشن
                        $this->dbConnection->query(
                            "INSERT INTO migrations (migration, applied_at) VALUES ('$migration_name', NOW())"
                        );
                    }
                }
            }
        }
    }

    // بررسی نیاز به آپدیت
    public function needsUpdate() {
        if (file_exists(CONFIG_FILE)) {
            // بررسی ناقص نبودن اطلاعات دیتابیس
            $config = file_get_contents(CONFIG_FILE);
            if (
                strpos($config, "define('DB_USER', '')") !== false ||
                strpos($config, "define('DB_PASS', '')") !== false ||
                strpos($config, "define('DB_NAME', '')") !== false
            ) {
                return false;
            }
            
            try {
                // تلاش برای اتصال به دیتابیس
                $conn = getDbConnection(false);
                $result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'system_version'");
                if ($result && $row = $result->fetch_assoc()) {
                    return version_compare($row['setting_value'], SYSTEM_VERSION, '<');
                }
                $conn->close();
            } catch (Exception $e) {
                // دیتابیس موجود نیست یا جدول settings ندارد - نیاز به نصب
                return true;
            }
        }
        return false;
    }

    // گرفتن خطاها
    public function getErrors() {
        return $this->errors;
    }

    // گرفتن وضعیت نیازمندی‌ها
    public function getRequirements() {
        return $this->requirements;
    }
}

// ایجاد نمونه کلاس
$setup = new Setup();

// پردازش درخواست‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_connection'])) {
        // تست اتصال
        $result = $setup->testDatabaseConnection(
            $_POST['db_host'],
            $_POST['db_user'],
            $_POST['db_pass'],
            $_POST['db_name']
        );
        
        if ($result) {
            $_SESSION['db_tested'] = true;
            $_SESSION['db_info'] = $_POST;
        }
        
    } elseif (isset($_POST['install']) && isset($_SESSION['db_tested'])) {
        // نصب سیستم
        $db_info = $_SESSION['db_info'];
        
        if ($setup->saveConfig(
            $db_info['db_host'],
            $db_info['db_user'],
            $db_info['db_pass'],
            $db_info['db_name']
        )) {
            if ($setup->testDatabaseConnection(
                $db_info['db_host'],
                $db_info['db_user'],
                $db_info['db_pass'],
                $db_info['db_name']
            )) {
                if ($setup->installDatabase()) {
                    header('Location: index.php?setup=complete');
                    exit;
                }
            }
        }
    }
}

// نمایش نتیجه
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب و راه‌اندازی سیستم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .setup-container { max-width: 800px; margin: 2rem auto; }
        .requirement-item { margin-bottom: 1rem; }
        .status-icon { font-size: 1.2rem; }
    </style>
</head>
<body>
    <div class="container setup-container">
        <?php if (!empty(
            
            
            
            $_SESSION['setup_required']
        )): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($_SESSION['setup_required']); ?></div>
            <?php unset($_SESSION['setup_required']); ?>
        <?php endif; ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title mb-0">نصب و راه‌اندازی سیستم مدیریت انبار</h3>
            </div>
            <div class="card-body">
                <?php if ($setup->needsUpdate()): ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading">نیاز به به‌روزرسانی</h4>
                        <p>نسخه جدیدی از سیستم در دسترس است. لطفاً به‌روزرسانی را انجام دهید.</p>
                        <hr>
                        <a href="migrate.php" class="btn btn-warning">
                            <i class="bi bi-arrow-clockwise"></i> به‌روزرسانی سیستم
                        </a>
                    </div>
                <?php else: ?>
                    <!-- نمایش نیازمندی‌ها -->
                    <h5 class="card-title mb-4">بررسی نیازمندی‌های سیستم</h5>
                    <?php foreach ($setup->getRequirements() as $req): ?>
                        <div class="requirement-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><?= $req['title'] ?></span>
                                <?php if ($req['status']): ?>
                                    <span class="text-success status-icon">✓</span>
                                <?php else: ?>
                                    <span class="text-danger status-icon">✗</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                مقدار فعلی: <?= $req['current'] ? ($req['current'] === true ? 'بله' : $req['current']) : 'خیر' ?>
                                <?php if (isset($req['required']) && $req['required'] !== true): ?>
                                    (نیاز: <?= $req['required'] ?>)
                                <?php endif; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>

                    <hr>

                    <!-- فرم تنظیمات دیتابیس -->
                    <h5 class="card-title mb-4">تنظیمات پایگاه داده</h5>
                    <?php if (!empty($setup->getErrors())): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($setup->getErrors() as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="db_host" class="form-label">آدرس هاست</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="<?= $_POST['db_host'] ?? 'localhost' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_name" class="form-label">نام دیتابیس</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="<?= $_POST['db_name'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_user" class="form-label">نام کاربری</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" 
                                       value="<?= $_POST['db_user'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="db_pass" class="form-label">رمز عبور</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                       value="<?= $_POST['db_pass'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <?php if (!isset($_SESSION['db_tested'])): ?>
                                <button type="submit" name="test_connection" class="btn btn-primary">
                                    <i class="bi bi-database-check"></i> تست اتصال
                                </button>
                            <?php else: ?>
                                <button type="submit" name="install" class="btn btn-success">
                                    <i class="bi bi-check-lg"></i> شروع نصب
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if (file_exists(__DIR__ . '/finish_install.php')): ?>
                        <hr>
                        <h6>پایان نصب</h6>
                        <p>پس از اطمینان از عملکرد سیستم، می‌توانید فایل‌های نصب را حذف کنید.</p>
                        <form method="post" action="finish_install.php" onsubmit="return confirm('آیا مطمئن هستید؟ این فایل‌ها حذف خواهند شد.');">
                            <input type="hidden" name="confirm" value="1">
                            <button type="submit" class="btn btn-danger">حذف فایل‌های نصب</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // اعتبارسنجی فرم
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
