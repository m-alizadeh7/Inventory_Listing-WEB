<?php
// این فایل برای بررسی خودکار ساختار پایگاه داده و اجرای اصلاحات لازم هنگام اجرای سیستم استفاده می‌شود
// می‌توانید این فایل را در ابتدای فایل‌های اصلی سیستم include کنید

// بررسی اینکه آیا قبلاً اجرا شده است
$db_check_lock = sys_get_temp_dir() . '/db_check_lock_' . date('Y-m-d');
if (!file_exists($db_check_lock)) {
    // ایجاد فایل قفل برای جلوگیری از اجرای مکرر
    file_put_contents($db_check_lock, date('Y-m-d H:i:s'));
    
    // اطمینان از وجود جدول device_bom با ساختار کامل
    $conn->query("CREATE TABLE IF NOT EXISTS device_bom (
        bom_id INT PRIMARY KEY AUTO_INCREMENT,
        device_id INT NOT NULL,
        item_code VARCHAR(100) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity_needed INT NOT NULL DEFAULT 1,
        supplier_id INT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (device_id),
        INDEX (item_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // بررسی ستون‌های موجود با ویژگی AUTO_INCREMENT
    $auto_columns = [];
    $result = $conn->query("SHOW COLUMNS FROM device_bom");
    while ($row = $result->fetch_assoc()) {
        if (strpos($row['Extra'], 'auto_increment') !== false) {
            $auto_columns[] = $row['Field'];
        }
    }
    
    // اگر هیچ ستون AUTO_INCREMENT وجود ندارد یا ستون bom_id وجود ندارد
    $res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'bom_id'");
    if ($res && $res->num_rows === 0 && empty($auto_columns)) {
        // اضافه کردن ستون bom_id به عنوان کلید اصلی با AUTO_INCREMENT
        $conn->query("ALTER TABLE device_bom ADD COLUMN bom_id INT AUTO_INCREMENT PRIMARY KEY FIRST");
    } 
    // اگر ستون AUTO_INCREMENT وجود دارد اما bom_id نیست
    elseif ($res && $res->num_rows === 0 && !empty($auto_columns)) {
        // ابتدا ستون AUTO_INCREMENT موجود را تغییر دهید
        foreach ($auto_columns as $col) {
            $conn->query("ALTER TABLE device_bom MODIFY $col INT NOT NULL");
        }
        // سپس ستون bom_id را اضافه کنید
        $conn->query("ALTER TABLE device_bom ADD COLUMN bom_id INT NOT NULL FIRST");
        // و آن را به AUTO_INCREMENT تبدیل کنید
        $conn->query("ALTER TABLE device_bom DROP PRIMARY KEY");
        $conn->query("ALTER TABLE device_bom ADD PRIMARY KEY (bom_id)");
        $conn->query("ALTER TABLE device_bom MODIFY bom_id INT AUTO_INCREMENT");
    }
    
    // بررسی ستون item_name
    $res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'item_name'");
    if ($res && $res->num_rows === 0) {
        // اضافه کردن ستون item_name اگر وجود ندارد
        $conn->query("ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NOT NULL AFTER item_code");
    }
    
    // بررسی ستون quantity_needed
    $res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'quantity_needed'");
    if ($res && $res->num_rows === 0) {
        // اضافه کردن ستون quantity_needed اگر وجود ندارد
        $conn->query("ALTER TABLE device_bom ADD COLUMN quantity_needed INT NOT NULL DEFAULT 1 AFTER item_name");
    }
    
    // اطمینان از وجود جدول inventory_records با ساختار کامل
    $conn->query("CREATE TABLE IF NOT EXISTS inventory_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_code VARCHAR(50) NOT NULL,
        current_inventory INT NOT NULL DEFAULT 0,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (item_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // اطمینان از وجود مسیر برای لاگ‌ها
    $logDir = __DIR__ . '/../Serverlog';
    if (!file_exists($logDir) && !is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
}
