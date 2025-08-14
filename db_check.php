<?php
require_once 'bootstrap.php';

// بررسی و اصلاح خودکار جداول
// این اسکریپت برای اطمینان از وجود تمام ستون‌های مورد نیاز در جداول اصلی استفاده می‌شود

// ========== بررسی جدول device_bom ==========
$tables = [
    'device_bom' => [
        'create' => "CREATE TABLE IF NOT EXISTS device_bom (
            bom_id INT AUTO_INCREMENT PRIMARY KEY,
            device_id INT NOT NULL,
            item_code VARCHAR(100) NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            quantity_needed INT NOT NULL DEFAULT 1,
            supplier_id INT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (device_id),
            INDEX (item_code),
            FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'columns' => [
            'bom_id' => "ALTER TABLE device_bom ADD COLUMN bom_id INT AUTO_INCREMENT PRIMARY KEY FIRST",
            'device_id' => "ALTER TABLE device_bom ADD COLUMN device_id INT NOT NULL",
            'item_code' => "ALTER TABLE device_bom ADD COLUMN item_code VARCHAR(100) NOT NULL",
            'item_name' => "ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NOT NULL",
            'quantity_needed' => "ALTER TABLE device_bom ADD COLUMN quantity_needed INT NOT NULL DEFAULT 1",
            'supplier_id' => "ALTER TABLE device_bom ADD COLUMN supplier_id INT NULL",
            'notes' => "ALTER TABLE device_bom ADD COLUMN notes TEXT",
            'created_at' => "ALTER TABLE device_bom ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ]
    ],
    'inventory_records' => [
        'create' => "CREATE TABLE IF NOT EXISTS inventory_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_code VARCHAR(50) NOT NULL,
            current_inventory INT NOT NULL DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (item_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        'columns' => [
            'id' => "ALTER TABLE inventory_records ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST",
            'item_code' => "ALTER TABLE inventory_records ADD COLUMN item_code VARCHAR(50) NOT NULL",
            'current_inventory' => "ALTER TABLE inventory_records ADD COLUMN current_inventory INT NOT NULL DEFAULT 0",
            'notes' => "ALTER TABLE inventory_records ADD COLUMN notes TEXT",
            'created_at' => "ALTER TABLE inventory_records ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ]
    ]
];

// لاگ کردن مراحل بررسی
function logMessage($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] db_check.php: $message");
    return $message . "<br>";
}

// اجرای بررسی و اصلاح جداول
$output = "";
foreach ($tables as $table_name => $table_info) {
    // بررسی وجود جدول
    $tableExists = $conn->query("SHOW TABLES LIKE '$table_name'")->num_rows > 0;
    $output .= logMessage("بررسی جدول $table_name: " . ($tableExists ? "موجود است" : "موجود نیست"));
    
    if (!$tableExists) {
        // ایجاد جدول
        if ($conn->query($table_info['create'])) {
            $output .= logMessage("جدول $table_name با موفقیت ایجاد شد");
        } else {
            $output .= logMessage("خطا در ایجاد جدول $table_name: " . $conn->error);
        }
    } else {
        // بررسی ستون‌ها
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM $table_name");
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }
        
        $output .= logMessage("ستون‌های موجود در جدول $table_name: " . implode(", ", array_keys($columns)));
        
        // اضافه کردن ستون‌های لازم
        foreach ($table_info['columns'] as $column => $sql) {
            if (!isset($columns[$column])) {
                $output .= logMessage("ستون $column در جدول $table_name یافت نشد. در حال اضافه کردن...");
                
                if ($conn->query($sql)) {
                    $output .= logMessage("ستون $column با موفقیت به جدول $table_name اضافه شد");
                } else {
                    $output .= logMessage("خطا در اضافه کردن ستون $column به جدول $table_name: " . $conn->error);
                }
            }
        }
    }
}

// ایجاد مسیر برای لاگ‌ها اگر وجود ندارد
$logDir = __DIR__ . '/Serverlog';
if (!file_exists($logDir)) {
    if (mkdir($logDir, 0755, true)) {
        $output .= logMessage("پوشه لاگ با موفقیت ایجاد شد");
    } else {
        $output .= logMessage("خطا در ایجاد پوشه لاگ");
    }
}

// تنظیم مسیر لاگ
ini_set('error_log', $logDir . '/error_log');

// تنظیم تمام کوئری‌های insert و update برای استفاده از ستون‌های درست
// ...

// بررسی اتمام موفقیت‌آمیز
if (strpos($output, "خطا") === false) {
    $status = "success";
    $message = "تمام جداول و ستون‌ها با موفقیت بررسی و اصلاح شدند.";
} else {
    $status = "warning";
    $message = "برخی خطاها در بررسی و اصلاح جداول رخ داده است. لطفا جزئیات را بررسی کنید.";
}

// اجرای خودکار این اسکریپت در شروع سیستم
// این اسکریپت می‌تواند در فایل index.php یا هر فایل دیگری که به عنوان نقطه ورودی استفاده می‌شود، include شود
?>

<?php get_template_part('header'); ?>
        <div class="card">
            <div class="card-header bg-<?= $status ?>">
                <h3 class="card-title text-white"><?= $message ?></h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h4>جزئیات عملیات:</h4>
                    <?= $output ?>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">بازگشت به صفحه اصلی</a>
                    <a href="devices.php" class="btn btn-secondary">مدیریت دستگاه‌ها</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
