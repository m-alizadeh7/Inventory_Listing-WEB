<?php
require_once 'config.php';
require_once 'includes/functions.php';

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
