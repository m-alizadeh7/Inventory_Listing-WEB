<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// دریافت اطلاعات کسب و کار
$business_info = getBusinessInfo();

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

$order_id = clean($_GET['id'] ?? '');
if (!$order_id) {
    header('Location: production_orders.php');
    exit;
}

// دریافت اطلاعات سفارش
$order = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT i.device_id) as devices_count,
           SUM(i.quantity) as total_quantity
    FROM production_orders p
    LEFT JOIN production_order_items i ON p.order_id = i.order_id
    WHERE p.order_id = $order_id
    GROUP BY p.order_id
")->fetch_assoc();

if (!$order) {
    header('Location: production_orders.php');
    exit;
}

// دریافت لیست دستگاه‌ها
$result = $conn->query("
    SELECT i.*, d.device_code, d.device_name,
           (
               SELECT COUNT(DISTINCT b.item_code)
               FROM device_bom b
               WHERE b.device_id = i.device_id
           ) as parts_count
    FROM production_order_items i
    JOIN devices d ON i.device_id = d.device_id
    WHERE i.order_id = $order_id
    ORDER BY d.device_name
");

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}

// محاسبه قطعات مورد نیاز
$result = $conn->query("
    SELECT b.item_code,
           inv.item_name,
           SUM(b.quantity_needed * i.quantity) as total_needed,
           SUM(inv.current_inventory) as current_stock,
           inv.supplier as supplier_name
    FROM production_order_items i
    JOIN device_bom b ON i.device_id = b.device_id
    LEFT JOIN inventory inv ON inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
    WHERE i.order_id = $order_id
    GROUP BY b.item_code, inv.item_name, inv.supplier
    ORDER BY b.item_code
");

$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}

// اصلاح شرط تایید سفارش
$can_confirm = $order['status'] === 'draft';
$can_start = $order['status'] === 'confirmed';
$total_missing_parts = 0;
$missing_parts_list = [];

foreach ($parts as $part) {
    $stock = (int)($part['current_stock'] ?? 0);
    $needed = (int)$part['total_needed'];
    if ($stock < $needed) {
        $total_missing_parts++;
        $missing_parts_list[] = [
            'item_code' => $part['item_code'],
            'item_name' => $part['item_name'],
            'needed' => $needed,
            'stock' => $stock,
            'missing' => $needed - $stock
        ];
    }
}

$all_parts_available = true;

// Load template using new system
// Defensive defaults for template keys to avoid undefined index warnings
$order['devices_count'] = $order['devices_count'] ?? (is_array($devices) ? count($devices) : 0);
$order['total_quantity'] = $order['total_quantity'] ?? array_sum(array_column($devices ?: [], 'quantity'));

if (!is_array($devices)) $devices = [];

get_template('production_order');
?>
