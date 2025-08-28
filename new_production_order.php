<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// حذف سفارش
if (isset($_POST['delete_order_id'])) {
    $delete_id = (int)$_POST['delete_order_id'];
    $conn->query("DELETE FROM production_order_items WHERE order_id = $delete_id");
    $conn->query("DELETE FROM production_orders WHERE order_id = $delete_id");
    set_flash_message('سفارش با موفقیت حذف شد', 'success');
    header('Location: new_production_order.php');
    exit;
}

// افزودن سفارش جدید
if (isset($_POST['save_new_order'])) {
    try {
        $conn->begin_transaction();
        
        // ایجاد کد سفارش جدید
        $prefix = 'ORD';
        $sql = "SELECT MAX(CAST(SUBSTRING(order_code, 4) AS UNSIGNED)) as max_num FROM production_orders WHERE order_code LIKE 'ORD%'";
        $result = $conn->query($sql)->fetch_assoc();
        $next_num = ($result['max_num'] ?? 0) + 1;
        $order_code = $prefix . sprintf('%03d', $next_num);
        
        $notes = clean($_POST['notes'] ?? '');
        
        // ایجاد سفارش جدید
        $sql = "INSERT INTO production_orders (order_code, status, notes) VALUES (?, 'pending', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $order_code, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // افزودن آیتم‌های سفارش
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $sql = "INSERT INTO production_order_items (order_id, device_id, quantity) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($_POST['items'] as $item) {
                $device_id = (int)$item['device_id'];
                $quantity = (int)$item['quantity'];
                
                if ($device_id > 0 && $quantity > 0) {
                    $stmt->bind_param('iii', $order_id, $device_id, $quantity);
                    $stmt->execute();
                }
            }
        }
        
        $conn->commit();
        set_flash_message("سفارش تولید {$order_code} با موفقیت ثبت شد", 'success');
        header('Location: production_order.php?id=' . $order_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('خطا در ثبت سفارش: ' . $e->getMessage(), 'danger');
    }
}

// دریافت لیست دستگاه‌ها
$devices = [];
$sql = "SELECT d.device_id, d.device_code, d.device_name, 
         COUNT(db.id) as parts_count
     FROM devices d 
     LEFT JOIN device_bom db ON d.device_id = db.device_id 
     GROUP BY d.device_id 
     ORDER BY d.device_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

// دریافت لیست سفارشات
$orders = [];
$result_orders = $conn->query("SELECT order_id, order_code, created_at, status FROM production_orders ORDER BY created_at DESC");
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}

// آمار سفارشات
$orders_count = [
    'all' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0
];

$sql = "SELECT status, COUNT(*) as count FROM production_orders GROUP BY status";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders_count[$row['status']] = $row['count'];
        $orders_count['all'] += $row['count'];
    }
}

// Page title and meta
$page_title = 'مدیریت سفارشات تولید';
$page_description = 'ایجاد و مدیریت سفارشات تولید برای دستگاه‌های مختلف';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/new_production_order.php'; ?>
</div>

<?php get_footer(); ?>
