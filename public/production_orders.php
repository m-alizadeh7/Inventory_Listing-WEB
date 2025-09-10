<?php
require_once '../config/config.php';
require_once '../app/includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// حذف سفارش تولید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = clean($_POST['order_id']);
    // حذف آیتم‌های سفارش
    $stmt = $conn->prepare("DELETE FROM production_order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    // حذف سفارش
    $stmt = $conn->prepare("DELETE FROM production_orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    set_flash_message('سفارش با موفقیت حذف شد', 'success');
    header("Location: production_orders.php");
    exit;
}

// شروع یا تکمیل سفارش
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = clean($_POST['order_id']);
    $action = clean($_POST['action']);
    
    if ($action === 'start') {
        $stmt = $conn->prepare("UPDATE production_orders SET status = 'in_progress' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        set_flash_message('سفارش شروع شد', 'success');
    } elseif ($action === 'complete') {
        $stmt = $conn->prepare("UPDATE production_orders SET status = 'completed', completed_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        set_flash_message('سفارش تکمیل شد', 'success');
    }
    
    header("Location: production_orders.php");
    exit;
}

// اگر هیچ سفارشی وجود ندارد، به صفحه ایجاد سفارش جدید هدایت شود
$result_check = $conn->query("SELECT order_id FROM production_orders ORDER BY created_at DESC LIMIT 1");
if ($result_check && $result_check->num_rows === 0) {
    header('Location: new_production_order.php');
    exit;
}

// دریافت آمار سفارشات
$total_orders = 0;
$pending_orders = 0;
$in_progress_orders = 0;
$completed_orders = 0;

$sql = "SELECT status, COUNT(*) as count FROM production_orders GROUP BY status";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        switch ($row['status']) {
            case 'pending':
                $pending_orders = $row['count'];
                break;
            case 'in_progress':
                $in_progress_orders = $row['count'];
                break;
            case 'completed':
                $completed_orders = $row['count'];
                break;
        }
        $total_orders += $row['count'];
    }
}

// دریافت لیست سفارشات با تعداد آیتم‌ها
$orders = [];
$sql = "SELECT po.*, COUNT(poi.order_id) as items_count 
        FROM production_orders po 
        LEFT JOIN production_order_items poi ON po.order_id = poi.order_id 
        GROUP BY po.order_id 
        ORDER BY po.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Page title and meta
$page_title = 'مدیریت سفارشات تولید';
$page_description = 'مشاهده و مدیریت کلیه سفارشات تولید';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/production_orders.php'; ?>
</div>

<?php get_footer(); ?>
