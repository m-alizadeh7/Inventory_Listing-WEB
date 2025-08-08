<?php
/**
 * فایل مایگریشن دیتابیس
 * 
 * این فایل مسئول اعمال تغییرات دیتابیس و مایگریشن‌ها است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// شروع session
session_start();

// بارگذاری تنظیمات
require_once 'config.php';

// تعیین عنوان صفحه
$page_title = 'مایگریشن دیتابیس - سیستم مدیریت انبار';
$migrations_applied = [];
$migrations_skipped = [];
$errors = [];

// بررسی و ایجاد جدول 'migrations' در صورت عدم وجود
$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
$res = $db->query("SHOW TABLES LIKE '{$prefix}migrations'");
if (!$res || $res->num_rows === 0) {
    $createTable = "CREATE TABLE {$prefix}migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";
    
    if (!$db->query($createTable)) {
        $errors[] = 'خطا در ایجاد جدول مایگریشن‌ها: ' . $db->error;
    }
}

// مسیر پوشه مایگریشن‌ها
$migrationsDir = __DIR__ . '/migrations';

// بررسی وجود پوشه مایگریشن‌ها
if (!is_dir($migrationsDir)) {
    $errors[] = 'پوشه مایگریشن‌ها یافت نشد: ' . $migrationsDir;
} else {
    // دریافت همه فایل‌های SQL
    $files = glob($migrationsDir . '/*.sql');
    
    if (empty($files)) {
        $migrations_skipped[] = 'هیچ فایل مایگریشنی یافت نشد.';
    } else {
        // مرتب‌سازی فایل‌ها بر اساس نام
        sort($files);
        
        foreach ($files as $file) {
            $name = basename($file);
            
            // بررسی اعمال شدن مایگریشن
            $stmt = $db->prepare("SELECT id FROM {$prefix}migrations WHERE migration = ?");
            if ($stmt) {
                $stmt->bind_param('s', $name);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    // اعمال مایگریشن
                    $sql = file_get_contents($file);
                    
                    if ($sql === false) {
                        $errors[] = "خطا در خواندن فایل: $name";
                        continue;
                    }
                    
                    if ($db->multi_query($sql)) {
                        // اجرای همه نتایج
                        do {
                            // پردازش نتایج
                        } while ($db->more_results() && $db->next_result());

                        // ثبت مایگریشن اجرا شده
                        $ins = $db->prepare("INSERT INTO {$prefix}migrations (migration, applied_at) VALUES (?, NOW())");
                        if ($ins) {
                            $ins->bind_param('s', $name);
                            $ins->execute();
                            $ins->close();
                            
                            $migrations_applied[] = $name;
                        } else {
                            $errors[] = "خطا در ثبت مایگریشن: $name";
                        }
                    } else {
                        $errors[] = "خطا در اعمال مایگریشن $name: " . $db->error;
                    }
                } else {
                    $migrations_skipped[] = $name;
                }
                $stmt->close();
            } else {
                $errors[] = "خطا در آماده‌سازی کوئری برای: $name";
            }
        }
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- استایل‌ها -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .migration-container {
            flex: 1;
            padding: 3rem 0;
        }
        .migration-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            margin-bottom: 1.5rem;
        }
        .migration-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem 1rem 0 0;
            text-align: center;
        }
        .migration-body {
            padding: 2rem;
        }
        .migration-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            border-left: 4px solid transparent;
        }
        .migration-applied {
            background-color: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .migration-skipped {
            background-color: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .migration-error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .migration-icon {
            margin-left: 0.5rem;
            font-size: 1.2rem;
        }
        .summary-stats {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6;
        }
        .stat-item {
            flex: 1;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-applied { color: #28a745; }
        .stat-skipped { color: #ffc107; }
        .stat-error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container migration-container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="migration-card">
                    <div class="migration-header">
                        <i class="bi bi-database-gear" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                        <h1 class="h3 mb-0">مایگریشن دیتابیس</h1>
                        <p class="mb-0 mt-2">اعمال تغییرات و به‌روزرسانی‌های دیتابیس</p>
                    </div>
                    
                    <div class="migration-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <h5><i class="bi bi-exclamation-triangle"></i> خطاهای رخ داده:</h5>
                                <?php foreach ($errors as $error): ?>
                                    <div class="migration-item migration-error">
                                        <i class="bi bi-x-circle migration-icon"></i>
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($migrations_applied)): ?>
                            <div class="mb-4">
                                <h5 class="text-success"><i class="bi bi-check-circle"></i> مایگریشن‌های اعمال شده:</h5>
                                <?php foreach ($migrations_applied as $migration): ?>
                                    <div class="migration-item migration-applied">
                                        <i class="bi bi-check-circle migration-icon"></i>
                                        <?php echo htmlspecialchars($migration); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($migrations_skipped)): ?>
                            <div class="mb-4">
                                <h5 class="text-warning"><i class="bi bi-skip-end"></i> مایگریشن‌های رد شده:</h5>
                                <?php foreach ($migrations_skipped as $migration): ?>
                                    <div class="migration-item migration-skipped">
                                        <i class="bi bi-skip-end migration-icon"></i>
                                        <?php echo htmlspecialchars($migration); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($migrations_applied) && empty($migrations_skipped) && empty($errors)): ?>
                            <div class="text-center text-muted">
                                <i class="bi bi-info-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                                <p class="mt-2">هیچ مایگریشنی برای اعمال یافت نشد.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- خلاصه آمار -->
                        <div class="summary-stats">
                            <div class="stat-item">
                                <div class="stat-number stat-applied"><?php echo count($migrations_applied); ?></div>
                                <div class="small text-muted">اعمال شده</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number stat-skipped"><?php echo count($migrations_skipped); ?></div>
                                <div class="small text-muted">رد شده</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number stat-error"><?php echo count($errors); ?></div>
                                <div class="small text-muted">خطا</div>
                            </div>
                        </div>
                        
                        <!-- دکمه‌های عمل -->
                        <div class="text-center mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-house"></i> بازگشت به صفحه اصلی
                            </a>
                            <a href="migrate.php" class="btn btn-outline-secondary ms-2">
                                <i class="bi bi-arrow-clockwise"></i> اجرای مجدد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- اسکریپت‌ها -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
