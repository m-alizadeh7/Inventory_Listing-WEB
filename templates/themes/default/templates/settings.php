<?php
/**
 * Template for settings page with sidebar layout
 * Professional design for system settings management
 */

// Make database connection available
global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
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

// Load alerts component
get_theme_part('alerts');

// Check for pending migrations
checkMigrationsPrompt();

// Set dashboard flag for header
$_GET['dashboard'] = '1';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <?php get_theme_part('sidebar'); ?>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="bi bi-gear text-info me-2"></i>
                        تنظیمات سیستم
                    </h1>
                    <p class="text-muted mb-0">مدیریت تنظیمات و پیکربندی سیستم مدیریت انبار</p>
                </div>
                <a href="../index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-house"></i>
                    بازگشت به داشبرد
                </a>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="../index.php">
                            <i class="bi bi-house"></i>
                            داشبرد
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">تنظیمات سیستم</li>
                </ol>
            </nav>

            <!-- Alerts -->
            <?php get_theme_part('alerts'); ?>

            <!-- بررسی و ایجاد جدول settings اگر وجود نداشته باشد -->
            <?php
            $createSql = "CREATE TABLE IF NOT EXISTS settings (
                setting_name VARCHAR(64) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            if (!$conn->query($createSql)) {
                // اگر ایجاد با ستون updated_at سازگار نبود، یک نسخه ساده‌تر امتحان کن
                $fallback = "CREATE TABLE IF NOT EXISTS settings (
                    setting_name VARCHAR(64) PRIMARY KEY,
                    setting_value TEXT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                if (!$conn->query($fallback)) {
                    // اگر هنوز هم خطا دارد، خطا را لاگ کن و ادامه بده (ترجیح می‌دهیم صفحه نمایش داده شود به جای fatal)
                    $error = 'خطا در ایجاد جدول settings: ' . $conn->error;
                    // از اینجا به بعد، به جای توقف برنامه، مقادیر پیش‌فرض خالی استفاده می‌کنیم
                }
            }

            $message = '';
            $error = '';

            // مدیریت عملیات POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                // ریست دیتابیس
                if (isset($_POST['reset_db'])) {
                    $pw = $_POST['reset_password'] ?? '';
                    if ($pw === '2581') {
                        $conn->query("SET FOREIGN_KEY_CHECKS=0");
                        $res = $conn->query("SHOW TABLES");
                        while ($tbl = $res->fetch_array()) {
                            $conn->query("DROP TABLE `{$tbl[0]}`");
                        }
                        $conn->query("SET FOREIGN_KEY_CHECKS=1");
                        require_once __DIR__ . '/../../../../app/migrate.php';
                        set_flash_message('دیتابیس با موفقیت ریست شد', 'success');
                        header('Location: settings.php');
                        exit;
                    } else {
                        set_flash_message('رمز عبور اشتباه است', 'danger');
                    }
                }

                // بک‌آپ دیتابیس بدون exec
                if (isset($_POST['backup_db'])) {
                    try {
                        $backupDir = __DIR__ . '/../../../backups';
                        if (!is_dir($backupDir)) {
                            mkdir($backupDir, 0755, true);
                        }
                        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                        $filepath = $backupDir . '/' . $filename;
                        $tables = [];
                        $result = $conn->query('SHOW TABLES');
                        while ($row = $result->fetch_array()) {
                            $tables[] = $row[0];
                        }
                        $sqlScript = "SET NAMES utf8mb4;\n";
                        foreach ($tables as $table) {
                            $res = $conn->query("SHOW CREATE TABLE `$table`");
                            $row2 = $res->fetch_assoc();
                            $sqlScript .= "\n-- ----------------------------\n";
                            $sqlScript .= "-- Table structure for `$table`\n";
                            $sqlScript .= "-- ----------------------------\n";
                            $sqlScript .= $row2['Create Table'] . ";\n\n";
                            $sqlScript .= "-- Dumping data for table `$table`\n";
                            $res = $conn->query("SELECT * FROM `$table`");
                            while ($data = $res->fetch_assoc()) {
                                $cols = array_map(function($v){return '`'.$v.'`';}, array_keys($data));
                                $vals = array_map(function($v) use ($conn){return "'".$conn->real_escape_string($v)."'";}, array_values($data));
                                $sqlScript .= "INSERT INTO `$table` (".implode(",",$cols).") VALUES (".implode(",",$vals).");\n";
                            }
                            $sqlScript .= "\n";
                        }
                        file_put_contents($filepath, $sqlScript);
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        header('Content-Length: ' . filesize($filepath));
                        readfile($filepath);
                        unlink($filepath);
                        exit;
                    } catch (Exception $e) {
                        $error = 'خطا در ایجاد بک‌آپ: ' . $e->getMessage();
                    }
                }

                // ریستور دیتابیس
                if (isset($_POST['restore_db']) && isset($_FILES['backup_file'])) {
                    try {
                        $uploadedFile = $_FILES['backup_file']['tmp_name'];
                        if (is_uploaded_file($uploadedFile)) {
                            $sql = file_get_contents($uploadedFile);

                            // استخراج نام جداولی که در فایل بک‌آپ ساخته می‌شوند
                            preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\"]?(?<tbl>[A-Za-z0-9_]+)[`\"]?/i', $sql, $matches);
                            $tablesToDrop = array_unique($matches['tbl']);

                            // غیرفعال کردن چک‌های FK و حذف جداول قبل از ریستور تا خطای 'Table already exists' رخ ندهد
                            $conn->query('SET FOREIGN_KEY_CHECKS=0');
                            foreach ($tablesToDrop as $t) {
                                if (trim($t) === '') continue;
                                $conn->query("DROP TABLE IF EXISTS `$t`");
                            }
                            $conn->query('SET FOREIGN_KEY_CHECKS=1');

                            // اکنون اجرای SQL فایل - اجرای کل بلاک با FOREIGN_KEY_CHECKS=0 و غیرفعال کردن sql_mode strict تا مقادیر خالی تاریخ باعث خطا نشوند
                            $sql_exec = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;";

                            // ذخیره sql_mode قبلی و غیرفعال کردن strict modes موقتاً
                            $conn->query("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'STRICT_TRANS_TABLES', '')");
                            $conn->query("SET SESSION sql_mode = REPLACE(@@SESSION.sql_mode, 'STRICT_ALL_TABLES', '')");

                            // اجرای SQL در چند بخش
                            $statements = array_filter(array_map('trim', explode(';', $sql_exec)));
                            foreach ($statements as $statement) {
                                if (!empty($statement)) {
                                    $conn->query($statement);
                                }
                            }

                            set_flash_message('دیتابیس با موفقیت بازیابی شد', 'success');
                            header('Location: settings.php');
                            exit;
                        }
                    } catch (Exception $e) {
                        $error = 'خطا در بازیابی دیتابیس: ' . $e->getMessage();
                    }
                }

                // ذخیره تنظیمات
                if (isset($_POST['save_settings'])) {
                    $settings = [
                        'business_name' => $_POST['business_name'] ?? '',
                        'business_address' => $_POST['business_address'] ?? '',
                        'business_phone' => $_POST['business_phone'] ?? '',
                        'business_email' => $_POST['business_email'] ?? '',
                        'default_theme' => $_POST['default_theme'] ?? 'default',
                        'items_per_page' => $_POST['items_per_page'] ?? '25',
                        'enable_notifications' => isset($_POST['enable_notifications']) ? '1' : '0',
                        'backup_frequency' => $_POST['backup_frequency'] ?? 'weekly'
                    ];

                    foreach ($settings as $key => $value) {
                        $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->bind_param("sss", $key, $value, $value);
                        $stmt->execute();
                        $stmt->close();
                    }

                    set_flash_message('تنظیمات با موفقیت ذخیره شد', 'success');
                    header('Location: settings.php');
                    exit;
                }
            }

            // دریافت تنظیمات فعلی
            $settings = [];
            $result = $conn->query("SELECT setting_name, setting_value FROM settings");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_name']] = $row['setting_value'];
                }
            }
            ?>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Settings Tabs -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab" aria-controls="business" aria-selected="true">
                                <i class="bi bi-building"></i> اطلاعات کسب‌وکار
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false">
                                <i class="bi bi-gear"></i> تنظیمات سیستم
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab" aria-controls="database" aria-selected="false">
                                <i class="bi bi-database"></i> مدیریت دیتابیس
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="settingsTabContent">
                        <!-- Business Information Tab -->
                        <div class="tab-pane fade show active" id="business" role="tabpanel" aria-labelledby="business-tab">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="business_name" class="form-label">نام کسب‌وکار</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($settings['business_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="business_phone" class="form-label">شماره تلفن</label>
                                            <input type="text" class="form-control" id="business_phone" name="business_phone" value="<?php echo htmlspecialchars($settings['business_phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="business_email" class="form-label">ایمیل</label>
                                            <input type="email" class="form-control" id="business_email" name="business_email" value="<?php echo htmlspecialchars($settings['business_email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="default_theme" class="form-label">تم پیش‌فرض</label>
                                            <select class="form-select" id="default_theme" name="default_theme">
                                                <option value="default" <?php echo ($settings['default_theme'] ?? 'default') === 'default' ? 'selected' : ''; ?>>پیش‌فرض</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="business_address" class="form-label">آدرس</label>
                                    <textarea class="form-control" id="business_address" name="business_address" rows="3"><?php echo htmlspecialchars($settings['business_address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" name="save_settings" class="btn btn-primary">
                                    <i class="bi bi-save"></i> ذخیره تنظیمات
                                </button>
                            </form>
                        </div>

                        <!-- System Settings Tab -->
                        <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                            <form method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="items_per_page" class="form-label">تعداد آیتم در هر صفحه</label>
                                            <select class="form-select" id="items_per_page" name="items_per_page">
                                                <option value="10" <?php echo ($settings['items_per_page'] ?? '25') === '10' ? 'selected' : ''; ?>>10</option>
                                                <option value="25" <?php echo ($settings['items_per_page'] ?? '25') === '25' ? 'selected' : ''; ?>>25</option>
                                                <option value="50" <?php echo ($settings['items_per_page'] ?? '25') === '50' ? 'selected' : ''; ?>>50</option>
                                                <option value="100" <?php echo ($settings['items_per_page'] ?? '25') === '100' ? 'selected' : ''; ?>>100</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="backup_frequency" class="form-label">فرکانس پشتیبان‌گیری</label>
                                            <select class="form-select" id="backup_frequency" name="backup_frequency">
                                                <option value="daily" <?php echo ($settings['backup_frequency'] ?? 'weekly') === 'daily' ? 'selected' : ''; ?>>روزانه</option>
                                                <option value="weekly" <?php echo ($settings['backup_frequency'] ?? 'weekly') === 'weekly' ? 'selected' : ''; ?>>هفتگی</option>
                                                <option value="monthly" <?php echo ($settings['backup_frequency'] ?? 'weekly') === 'monthly' ? 'selected' : ''; ?>>ماهانه</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo ($settings['enable_notifications'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_notifications">
                                            فعال‌سازی اعلان‌ها
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" name="save_settings" class="btn btn-primary">
                                    <i class="bi bi-save"></i> ذخیره تنظیمات
                                </button>
                            </form>
                        </div>

                        <!-- Database Management Tab -->
                        <div class="tab-pane fade" id="database" role="tabpanel" aria-labelledby="database-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="bi bi-database-exclamation text-warning"></i>
                                                ریست دیتابیس
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">این عملیات تمام داده‌ها را حذف کرده و دیتابیس را به حالت اولیه برمی‌گرداند.</p>
                                            <form method="post" onsubmit="return confirm('آیا مطمئن هستید؟ تمام داده‌ها حذف خواهند شد!')">
                                                <div class="mb-3">
                                                    <label for="reset_password" class="form-label">رمز عبور تایید</label>
                                                    <input type="password" class="form-control" id="reset_password" name="reset_password" required>
                                                </div>
                                                <button type="submit" name="reset_db" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-exclamation-triangle"></i> ریست دیتابیس
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">
                                                <i class="bi bi-download text-success"></i>
                                                پشتیبان‌گیری
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="text-muted small">ایجاد فایل پشتیبان از تمام جداول دیتابیس</p>
                                            <form method="post">
                                                <button type="submit" name="backup_db" class="btn btn-success btn-sm">
                                                    <i class="bi bi-download"></i> ایجاد پشتیبان
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="bi bi-upload text-info"></i>
                                        بازیابی پشتیبان
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small">بازیابی دیتابیس از فایل پشتیبان</p>
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="backup_file" class="form-label">فایل پشتیبان (.sql)</label>
                                            <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                                        </div>
                                        <button type="submit" name="restore_db" class="btn btn-info btn-sm">
                                            <i class="bi bi-upload"></i> بازیابی
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Toggle -->
<button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-lg-none" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close sidebar when clicking on a link (mobile)
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            toggleSidebar();
        }
    });
});
</script>
}
