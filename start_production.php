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
