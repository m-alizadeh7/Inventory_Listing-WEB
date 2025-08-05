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
    if ($order['status'] !== 'draft') {
        throw new Exception('این سفارش قبلاً تایید شده است.');
    }

    // بررسی تامین‌کننده برای همه قطعات
    $result = $conn->query("
        SELECT DISTINCT b.item_code, b.item_name
        FROM production_order_items i
        JOIN device_bom b ON i.device_id = b.device_id
        WHERE i.order_id = $order_id AND b.supplier_id IS NULL
    ");

    $missing_suppliers = [];
    while ($row = $result->fetch_assoc()) {
        $missing_suppliers[] = $row['item_name'] . ' (' . $row['item_code'] . ')';
    }

    if (!empty($missing_suppliers)) {
        throw new Exception('قطعات زیر تامین‌کننده ندارند: ' . implode('، ', $missing_suppliers));
    }

    // تایید سفارش
    $sql = "UPDATE production_orders SET status = 'confirmed' WHERE order_id = $order_id";
    if (!$conn->query($sql)) {
        throw new Exception('خطا در تایید سفارش.');
    }

    $conn->commit();
    header("Location: production_order.php?id=$order_id&msg=confirmed");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("خطا: " . $e->getMessage());
}
