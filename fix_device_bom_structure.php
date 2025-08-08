<?php
require_once 'config.php';
require_once 'includes/functions.php';

// برای رفع مشکل AUTO_INCREMENT در جدول device_bom
echo "<h1>رفع مشکل جدول device_bom</h1>";

try {
    // بررسی ساختار فعلی جدول
    echo "<h2>ساختار فعلی جدول:</h2>";
    $result = $conn->query("SHOW COLUMNS FROM device_bom");
    $columns = [];
    $auto_columns = [];
    $primary_key = [];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>نام ستون</th><th>نوع</th><th>کلید</th><th>ویژگی‌های اضافی</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
        
        $columns[] = $row['Field'];
        if (strpos($row['Extra'], 'auto_increment') !== false) {
            $auto_columns[] = $row['Field'];
        }
        if ($row['Key'] === 'PRI') {
            $primary_key[] = $row['Field'];
        }
    }
    echo "</table>";
    
    // تشخیص مشکل
    echo "<h2>تشخیص مشکل:</h2>";
    if (count($auto_columns) > 1) {
        echo "<p style='color:red;'>مشکل: بیش از یک ستون AUTO_INCREMENT وجود دارد: " . implode(", ", $auto_columns) . "</p>";
    } elseif (count($auto_columns) === 1 && $auto_columns[0] !== 'bom_id') {
        echo "<p style='color:orange;'>مشکل: ستون AUTO_INCREMENT مناسب نیست: " . $auto_columns[0] . "</p>";
    } elseif (count($auto_columns) === 0) {
        echo "<p style='color:orange;'>مشکل: هیچ ستون AUTO_INCREMENT وجود ندارد.</p>";
    } else {
        echo "<p style='color:green;'>ستون AUTO_INCREMENT به درستی تنظیم شده است.</p>";
    }
    
    if (empty($primary_key)) {
        echo "<p style='color:red;'>مشکل: هیچ کلید اصلی تعریف نشده است.</p>";
    } elseif (!in_array('bom_id', $primary_key)) {
        echo "<p style='color:orange;'>مشکل: کلید اصلی نامناسب است: " . implode(", ", $primary_key) . "</p>";
    } else {
        echo "<p style='color:green;'>کلید اصلی به درستی تنظیم شده است.</p>";
    }
    
    // رفع مشکل
    echo "<h2>اقدامات انجام شده:</h2>";
    
    // گام 1: حذف AUTO_INCREMENT از همه ستون‌ها
    if (count($auto_columns) > 0) {
        foreach ($auto_columns as $col) {
            $conn->query("ALTER TABLE device_bom MODIFY $col INT NOT NULL");
            echo "<p>ویژگی AUTO_INCREMENT از ستون $col حذف شد.</p>";
        }
    }
    
    // گام 2: تغییر یا اضافه کردن کلید اصلی به bom_id
    if (!in_array('bom_id', $columns)) {
        // اضافه کردن ستون bom_id
        $conn->query("ALTER TABLE device_bom ADD COLUMN bom_id INT NOT NULL FIRST");
        echo "<p>ستون bom_id اضافه شد.</p>";
    }
    
    // گام 3: تنظیم کلید اصلی
    if (!empty($primary_key)) {
        $conn->query("ALTER TABLE device_bom DROP PRIMARY KEY");
        echo "<p>کلید اصلی قبلی حذف شد.</p>";
    }
    
    $conn->query("ALTER TABLE device_bom ADD PRIMARY KEY (bom_id)");
    echo "<p>ستون bom_id به عنوان کلید اصلی تنظیم شد.</p>";
    
    // گام 4: اضافه کردن ویژگی AUTO_INCREMENT به bom_id
    $conn->query("ALTER TABLE device_bom MODIFY bom_id INT AUTO_INCREMENT");
    echo "<p>ویژگی AUTO_INCREMENT به ستون bom_id اضافه شد.</p>";
    
    // گام 5: بررسی ستون‌های دیگر
    if (!in_array('item_name', $columns)) {
        $conn->query("ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NOT NULL AFTER item_code");
        echo "<p>ستون item_name اضافه شد.</p>";
    }
    
    if (!in_array('quantity_needed', $columns)) {
        $conn->query("ALTER TABLE device_bom ADD COLUMN quantity_needed INT NOT NULL DEFAULT 1 AFTER item_name");
        echo "<p>ستون quantity_needed اضافه شد.</p>";
    }
    
    // گام 6: بررسی نهایی
    echo "<h2>بررسی نهایی ساختار جدول:</h2>";
    $result = $conn->query("SHOW COLUMNS FROM device_bom");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>نام ستون</th><th>نوع</th><th>کلید</th><th>ویژگی‌های اضافی</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color:green; font-weight:bold;'>عملیات اصلاح با موفقیت انجام شد.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>خطا در اجرای عملیات: " . $e->getMessage() . "</p>";
}
?>

<p><a href="device_bom.php?id=1" style="display:inline-block; padding:10px 15px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;">بازگشت به صفحه BOM</a></p>

<style>
    body { font-family: Tahoma, Arial, sans-serif; margin: 20px; line-height: 1.6; direction: rtl; }
    h1, h2 { color: #333; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th { background-color: #f2f2f2; }
    p { margin: 10px 0; }
</style>
