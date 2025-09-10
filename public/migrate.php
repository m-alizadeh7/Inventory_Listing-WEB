<?php
// تلاش برای اتصال و ایجاد دیتابیس در صورت نیاز
try {
    // اول config.php را بارگذاری کن
    require_once dirname(__DIR__) . '/config/config.php';
    
    // اتصال به دیتابیس
    $tempConn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($tempConn->connect_error) {
        die("خطا در اتصال به سرور: " . $tempConn->connect_error);
    }
    
    $dbName = DB_NAME;
    
    // ایجاد دیتابیس در صورت عدم وجود
    $tempConn->query("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $tempConn->close();
    
    // حالا دوباره config.php را بارگذاری کن (این بار با دیتابیس موجود)
    require_once dirname(__DIR__) . '/config/config.php';
    
} catch (Exception $e) {
    die("خطا در آماده‌سازی دیتابیس: " . $e->getMessage());
}

// Check and create 'migrations' table if missing
$res = $conn->query("SHOW TABLES LIKE 'migrations'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('Error creating migrations table: ' . $conn->error);
    }
}

// مسیر پوشه مایگریشن‌ها
$migrationsDir = __DIR__ . '/migrations';
// دریافت همه فایل‌های SQL
$files = glob($migrationsDir . '/*.sql');

foreach ($files as $file) {
    $name = basename($file);
    // بررسی اعمال شدن مایگریشن
    $stmt = $conn->prepare("SELECT id FROM migrations WHERE migration = ?");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "Applying migration: $name...\n";
        $sql = file_get_contents($file);
        if ($conn->multi_query($sql)) {
            // اجرای همه نتایج
            do {
                // هیچ کاری لازم نیست
            } while ($conn->more_results() && $conn->next_result());

            // ثبت مایگریشن اجرا شده
            $ins = $conn->prepare("INSERT INTO migrations (migration, applied_at) VALUES (?, NOW())");
            $ins->bind_param('s', $name);
            $ins->execute();
            $ins->close();

            echo "Done.\n";
        } else {
            die("Error applying migration $name: " . $conn->error);
        }
    } else {
        echo "Skipping migration: $name (already applied)\n";
    }
    $stmt->close();
}

$conn->close();
echo "All migrations processed.\n";
