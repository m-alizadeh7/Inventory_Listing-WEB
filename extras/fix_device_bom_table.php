<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی ساختار جدول device_bom
echo "<h1>بررسی و اصلاح ساختار جدول device_bom</h1>";

// بررسی وجود جدول
$tableExists = $conn->query("SHOW TABLES LIKE 'device_bom'")->num_rows > 0;
echo "<p>وجود جدول device_bom: " . ($tableExists ? "بله" : "خیر") . "</p>";

if (!$tableExists) {
    // ایجاد جدول با ساختار کامل
    $createTable = "CREATE TABLE device_bom (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        item_code VARCHAR(100) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity_needed INT NOT NULL DEFAULT 1,
        supplier_id INT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($createTable)) {
        echo "<p class='text-success'>جدول device_bom با موفقیت ایجاد شد.</p>";
    } else {
        echo "<p class='text-danger'>خطا در ایجاد جدول device_bom: " . $conn->error . "</p>";
    }
} else {
    // بررسی ستون‌های موجود
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM device_bom");
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    echo "<h2>ستون‌های موجود در جدول:</h2>";
    echo "<ul>";
    foreach ($columns as $name => $info) {
        echo "<li>$name (" . $info['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // بررسی و اضافه کردن ستون‌های لازم
    $requiredColumns = [
        'bom_id' => "ALTER TABLE device_bom ADD COLUMN bom_id INT AUTO_INCREMENT PRIMARY KEY FIRST",
        'item_name' => "ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NOT NULL AFTER item_code",
        'quantity_needed' => "ALTER TABLE device_bom ADD COLUMN quantity_needed INT NOT NULL DEFAULT 1 AFTER item_name"
    ];
    
    $changes = false;
    foreach ($requiredColumns as $column => $sql) {
        if (!isset($columns[$column])) {
            echo "<p>ستون $column یافت نشد. در حال اضافه کردن...</p>";
            
            if ($conn->query($sql)) {
                echo "<p class='text-success'>ستون $column با موفقیت اضافه شد.</p>";
                $changes = true;
            } else {
                echo "<p class='text-danger'>خطا در اضافه کردن ستون $column: " . $conn->error . "</p>";
            }
        }
    }
    
    if (!$changes) {
        echo "<p>تمام ستون‌های مورد نیاز وجود دارند.</p>";
    }
}

// بررسی تنظیمات قفل‌گذاری
echo "<h2>بررسی تنظیمات قفل‌گذاری</h2>";
$tempDir = sys_get_temp_dir();
echo "<p>مسیر موقت سیستم: $tempDir</p>";
echo "<p>آیا مسیر قابل نوشتن است؟ " . (is_writable($tempDir) ? "بله" : "خیر") . "</p>";

// بررسی وضعیت لاگ‌ها
echo "<h2>بررسی وضعیت لاگ‌ها</h2>";
$logDir = __DIR__ . '/Serverlog';
echo "<p>مسیر لاگ‌ها: $logDir</p>";
echo "<p>آیا مسیر وجود دارد؟ " . (file_exists($logDir) ? "بله" : "خیر") . "</p>";
echo "<p>آیا مسیر قابل نوشتن است؟ " . (is_writable($logDir) ? "بله" : "خیر") . "</p>";

if (!file_exists($logDir)) {
    if (mkdir($logDir, 0755)) {
        echo "<p class='text-success'>پوشه لاگ با موفقیت ایجاد شد.</p>";
    } else {
        echo "<p class='text-danger'>خطا در ایجاد پوشه لاگ.</p>";
    }
}

// بازگشت به صفحه اصلی
echo "<hr>";
echo "<a href='devices.php' class='btn btn-primary'>بازگشت به لیست دستگاه‌ها</a>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }
    h1, h2 {
        color: #333;
    }
    .text-success {
        color: green;
    }
    .text-danger {
        color: red;
    }
    .btn {
        display: inline-block;
        padding: 10px 15px;
        background-color: #0275d8;
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
</style>
