<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once __DIR__ . '/../app/core/includes/theme.php';
init_theme();

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: production_orders.php');
    exit;
}

// Handle finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_order'])) {
    $confirm_negative = isset($_POST['confirm_negative']) ? 1 : 0;
    
    try {
        $conn->begin_transaction();
        
        // Get order details
        $order = $conn->query("SELECT * FROM production_orders WHERE order_id = $order_id")->fetch_assoc();
        if (!$order || $order['finalized_at']) {
            throw new Exception('سفارش یافت نشد یا قبلاً نهایی شده است');
        }
        
        // Get required parts with current stock
        $parts_sql = "
            SELECT b.item_code,
                   inv.item_name,
                   SUM(b.quantity_needed * i.quantity) as total_needed,
                   inv.current_inventory as current_stock,
                   inv.supplier as supplier_name
            FROM production_order_items i
            JOIN device_bom b ON i.device_id = b.device_id
            LEFT JOIN inventory inv ON inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
            WHERE i.order_id = $order_id
            GROUP BY b.item_code, inv.item_name, inv.current_inventory, inv.supplier
            ORDER BY b.item_code
        ";
        
        $parts_result = $conn->query($parts_sql);
        $inventory_issues = [];
        $deduction_queries = [];
        
        while ($part = $parts_result->fetch_assoc()) {
            $needed = (int)$part['total_needed'];
            $available = (int)$part['current_stock'];
            $new_stock = $available - $needed;
            
            if ($new_stock < 0 && !$confirm_negative) {
                $inventory_issues[] = [
                    'item_code' => $part['item_code'],
                    'item_name' => $part['item_name'],
                    'needed' => $needed,
                    'available' => $available,
                    'shortage' => abs($new_stock)
                ];
            }
            
            // Prepare deduction query
            if ($part['item_code']) {
                $deduction_queries[] = [
                    'item_code' => $part['item_code'],
                    'needed' => $needed,
                    'new_stock' => $new_stock
                ];
            }
        }
        
        // If there are inventory issues and user hasn't confirmed, show warning
        if (!empty($inventory_issues) && !$confirm_negative) {
            $conn->rollback();
            
            // Store issues in session for display
            $_SESSION['inventory_issues'] = $inventory_issues;
            $_SESSION['order_id_for_finalization'] = $order_id;
            
            header('Location: finalize_production_order.php?id=' . $order_id . '&show_warning=1');
            exit;
        }
        
        // Execute inventory deductions
        foreach ($deduction_queries as $deduction) {
            // Update inventory
            $stmt = $conn->prepare("UPDATE inventory SET current_inventory = ? WHERE inventory_code = ?");
            $stmt->bind_param('is', $deduction['new_stock'], $deduction['item_code']);
            $stmt->execute();
            
            // Record transaction
            $stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (inventory_code, transaction_type, quantity_change, previous_quantity, new_quantity, reference_type, reference_id, notes, created_by) 
                VALUES (?, 'production_use', ?, ?, ?, 'production_order', ?, ?, 'system')
            ");
            $quantity_change = -$deduction['needed'];
            $previous_quantity = $deduction['new_stock'] + $deduction['needed'];
            $notes = "استفاده در سفارش تولید {$order['order_code']}";
            
            $stmt->bind_param('siiiis', 
                $deduction['item_code'], 
                $quantity_change, 
                $previous_quantity, 
                $deduction['new_stock'], 
                $order_id, 
                $notes
            );
            $stmt->execute();
        }
        
        // Finalize the order
        $stmt = $conn->prepare("UPDATE production_orders SET finalized_at = NOW(), finalized_by = ?, inventory_deducted = TRUE WHERE order_id = ?");
        $finalized_by = 'system_user'; // You might want to implement proper user management
        $stmt->bind_param('si', $finalized_by, $order_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Clear session data
        unset($_SESSION['inventory_issues']);
        unset($_SESSION['order_id_for_finalization']);
        
        set_flash_message('سفارش تولید با موفقیت نهایی شد و موجودی انبار به‌روزرسانی گردید', 'success');
        header('Location: production_order.php?id=' . $order_id);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message('خطا در نهایی کردن سفارش: ' . $e->getMessage(), 'danger');
    }
}

// Get order details
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

// Get required parts analysis
$parts_sql = "
    SELECT b.item_code,
           inv.item_name,
           SUM(b.quantity_needed * i.quantity) as total_needed,
           inv.current_inventory as current_stock,
           inv.supplier as supplier_name,
           (inv.current_inventory - SUM(b.quantity_needed * i.quantity)) as remaining_stock
    FROM production_order_items i
    JOIN device_bom b ON i.device_id = b.device_id
    LEFT JOIN inventory inv ON inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
    WHERE i.order_id = $order_id
    GROUP BY b.item_code, inv.item_name, inv.current_inventory, inv.supplier
    ORDER BY b.item_code
";

$parts = [];
$has_issues = false;
$result = $conn->query($parts_sql);
while ($row = $result->fetch_assoc()) {
    $needed = (int)$row['total_needed'];
    $stock = (int)$row['current_stock'];
    $remaining = $stock - $needed;
    
    $row['has_shortage'] = $remaining < 0;
    $row['shortage_amount'] = $remaining < 0 ? abs($remaining) : 0;
    
    if ($row['has_shortage']) {
        $has_issues = true;
    }
    
    $parts[] = $row;
}

// Check for inventory issues from session
$show_warning = isset($_GET['show_warning']) && isset($_SESSION['inventory_issues']);
$inventory_issues = $show_warning ? $_SESSION['inventory_issues'] : [];

$page_title = 'نهایی کردن سفارش تولید';
$page_description = 'بررسی موجودی و نهایی کردن سفارش تولید';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/finalize_production_order.php'; ?>
</div>

<?php get_footer(); ?>
