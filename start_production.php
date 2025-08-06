<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی و ایجاد جدول production_orders اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_orders'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_orders: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول production_order_items اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_order_items'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        device_id INT NOT NULL,
        quantity INT NOT NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_order_items: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول inventory_records اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_records'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT NOT NULL,
        inventory_session VARCHAR(50) NOT NULL,
        current_inventory FLOAT,
        required FLOAT,
        notes TEXT,
        updated_at DATETIME,
        completed_by VARCHAR(255),
        completed_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_records: ' . $conn->error);
    }
}

// بررسی و اضافه کردن ستون item_code به جدول‌های مرتبط
$res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'item_code'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE device_bom ADD COLUMN item_code VARCHAR(50) NULL");
}

$res = $conn->query("SHOW COLUMNS FROM inventory_records LIKE 'item_code'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_records ADD COLUMN item_code VARCHAR(50) NULL");
}

// تغییر collation ستون‌های مرتبط به utf8mb4_unicode_ci
$conn->query("ALTER TABLE device_bom MODIFY item_code VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->query("ALTER TABLE inventory_records MODIFY item_code VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: production_orders.php');
    exit;
}

$order_id = clean($_POST['order_id']);

try {
    $conn->begin_transaction();

    // بررسی وضعیت فعلی سفارش
    $order = $conn->query("SELECT status FROM production_orders WHERE order_id = $order_id")->fetch_assoc();
    if ($order['status'] !== 'confirmed') {
        throw new Exception('این سفارش هنوز تایید نشده است.');
    }

    // بررسی موجودی کافی برای همه قطعات
    $result = $conn->query("
        SELECT b.item_code, b.item_name,
               SUM(b.quantity_needed * i.quantity) as total_needed,
               (
                   SELECT SUM(current_inventory)
                   FROM inventory_records
                   WHERE item_code = b.item_code
               ) as current_stock
        FROM production_order_items i
        JOIN device_bom b ON i.device_id = b.device_id
        WHERE i.order_id = $order_id
        GROUP BY b.item_code, b.item_name
        HAVING total_needed > COALESCE(current_stock, 0)
    ");

    $missing_parts = [];
    while ($row = $result->fetch_assoc()) {
        $missing_parts[] = $row['item_name'] . ' (' . $row['item_code'] . ')';
    }

    if (!empty($missing_parts)) {
        throw new Exception('موجودی قطعات زیر کافی نیست: ' . implode('، ', $missing_parts));
    }

    // کم کردن موجودی قطعات
    $sql = "
        INSERT INTO inventory_records (item_code, current_inventory, notes)
        SELECT 
            b.item_code,
            -(b.quantity_needed * i.quantity) as amount,
            CONCAT('کسر موجودی برای سفارش ', p.order_code)
        FROM production_order_items i
        JOIN device_bom b ON i.device_id = b.device_id
        JOIN production_orders p ON i.order_id = p.order_id
        WHERE i.order_id = $order_id
    ";
    if (!$conn->query($sql)) {
        throw new Exception('خطا در بروزرسانی موجودی.');
    }

    // شروع تولید
    $sql = "UPDATE production_orders SET status = 'in_progress' WHERE order_id = $order_id";
    if (!$conn->query($sql)) {
        throw new Exception('خطا در شروع تولید.');
    }

    $conn->commit();
    header("Location: production_order.php?id=$order_id&msg=started");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("خطا: " . $e->getMessage());
}

// نمایش گزارش موجودی ناکافی به صورت خوانا و ساختار یافته
if (!empty($missing_parts)) {
    echo "<html lang='fa' dir='rtl'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>گزارش موجودی ناکافی</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>";
    echo "<style>";
    echo "body { background-color: #f8f9fa; font-family: 'Tahoma', sans-serif; }";
    echo "table { border-collapse: collapse; width: 100%; margin-top: 20px; }";
    echo "th, td { border: 1px solid #dee2e6; padding: 8px; text-align: center; }";
    echo "th { background-color: #e9ecef; font-weight: bold; }";
    echo "@media print { .btn { display: none; } }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container mt-4'>";
    echo "<h3 class='text-center text-danger'>گزارش موجودی ناکافی</h3>";
    echo "<p class='text-center'>این گزارش شامل قطعاتی است که موجودی کافی برای تولید ندارند.</p>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>ردیف</th><th>کد انبار</th><th>نام کالا</th><th>واحد کالا</th><th>تعداد مورد نیاز</th><th>تاریخ</th></tr></thead>";
    echo "<tbody>";
    $row_number = 1;
    foreach ($missing_parts as $part) {
        echo "<tr>";
        echo "<td>" . $row_number++ . "</td>";
        echo "<td>" . htmlspecialchars($part['item_code']) . "</td>";
        echo "<td>" . htmlspecialchars($part['item_name']) . "</td>";
        echo "<td>واحد</td>";
        echo "<td>" . htmlspecialchars($part['total_needed']) . "</td>";
        echo "<td>" . date('Y-m-d') . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<div class='text-center mt-4'>";
    echo "<button class='btn btn-primary' onclick='window.print()'>چاپ</button>";
    echo "</div>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
    exit;
}
