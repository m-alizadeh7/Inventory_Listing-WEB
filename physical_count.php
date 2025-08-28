<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// Ensure physical count tables exist
$conn->query("CREATE TABLE IF NOT EXISTS physical_count_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(255),
    category_id INT NULL,
    counted_by VARCHAR(255) NULL,
    notes TEXT NULL,
    status VARCHAR(20) DEFAULT 'active',
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS physical_count_details (
    count_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    inventory_code VARCHAR(100) NOT NULL,
    system_quantity INT DEFAULT 0,
    counted_quantity INT NULL,
    difference_quantity INT NULL,
    notes TEXT NULL,
    counted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_count_session'])) {
        $session_name = clean($_POST['session_name']);
        $category_id = $_POST['category_id'] ? (int)$_POST['category_id'] : null;
        $notes = clean($_POST['notes']);
        $counted_by = clean($_POST['counted_by']);
        
        $stmt = $conn->prepare("INSERT INTO physical_count_sessions (session_name, category_id, counted_by, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('siss', $session_name, $category_id, $counted_by, $notes);
        
        if ($stmt->execute()) {
            $session_id = $conn->insert_id;
            
            // Create count details for items in the category
            $where_clause = $category_id ? "WHERE category_id = $category_id" : "";
            $items_sql = "SELECT inventory_code, current_inventory FROM inventory $where_clause ORDER BY item_name";
            $items_result = $conn->query($items_sql);
            
            $detail_stmt = $conn->prepare("INSERT INTO physical_count_details (session_id, inventory_code, system_quantity) VALUES (?, ?, ?)");
            
            while ($item = $items_result->fetch_assoc()) {
                $detail_stmt->bind_param('isi', $session_id, $item['inventory_code'], $item['current_inventory']);
                $detail_stmt->execute();
            }
            
            set_flash_message('جلسه شمارش جدید شروع شد', 'success');
            header('Location: physical_count.php?session=' . $session_id);
            exit;
        } else {
            set_flash_message('خطا در ایجاد جلسه شمارش: ' . $conn->error, 'danger');
        }
    }
    
    if (isset($_POST['save_count'])) {
        $session_id = (int)$_POST['session_id'];
        $counts = $_POST['counts'] ?? [];
        $notes = $_POST['notes'] ?? [];
        
        foreach ($counts as $inventory_code => $counted_quantity) {
            $counted_qty = (int)$counted_quantity;
            $note = clean($notes[$inventory_code] ?? '');
            
            // Get system quantity
            $system_qty_result = $conn->query("SELECT system_quantity FROM physical_count_details WHERE session_id = $session_id AND inventory_code = '$inventory_code'");
            $system_qty = $system_qty_result->fetch_assoc()['system_quantity'];
            $difference = $counted_qty - $system_qty;
            
            // Update count details
            $stmt = $conn->prepare("UPDATE physical_count_details SET counted_quantity = ?, difference_quantity = ?, notes = ?, counted_at = NOW() WHERE session_id = ? AND inventory_code = ?");
            $stmt->bind_param('iisis', $counted_qty, $difference, $note, $session_id, $inventory_code);
            $stmt->execute();
        }
        
        set_flash_message('شمارش ذخیره شد', 'success');
        header('Location: physical_count.php?session=' . $session_id);
        exit;
    }
    
    if (isset($_POST['finalize_count_session'])) {
        $session_id = (int)$_POST['session_id'];
        $update_inventory = isset($_POST['update_inventory']) ? 1 : 0;
        
        try {
            $conn->begin_transaction();
            
            if ($update_inventory) {
                // Update inventory based on counted quantities
                $counts_sql = "SELECT inventory_code, counted_quantity, difference_quantity, notes FROM physical_count_details WHERE session_id = $session_id AND counted_quantity IS NOT NULL";
                $counts_result = $conn->query($counts_sql);
                
                while ($count = $counts_result->fetch_assoc()) {
                    if ($count['difference_quantity'] != 0) {
                        // Update inventory
                        $stmt = $conn->prepare("UPDATE inventory SET current_inventory = ?, last_physical_count_date = CURDATE(), last_physical_count_value = ? WHERE inventory_code = ?");
                        $stmt->bind_param('iis', $count['counted_quantity'], $count['counted_quantity'], $count['inventory_code']);
                        $stmt->execute();
                        
                        // Record transaction
                        $stmt = $conn->prepare("
                            INSERT INTO inventory_transactions 
                            (inventory_code, transaction_type, quantity_change, previous_quantity, new_quantity, reference_type, reference_id, notes, created_by) 
                            VALUES (?, 'physical_count', ?, ?, ?, 'physical_count_session', ?, ?, 'system')
                        ");
                        
                        $previous_qty = $count['counted_quantity'] - $count['difference_quantity'];
                        $notes = "شمارش فیزیکی - " . ($count['notes'] ?: 'بدون یادداشت');
                        
                        $stmt->bind_param('siiiis', 
                            $count['inventory_code'], 
                            $count['difference_quantity'], 
                            $previous_qty, 
                            $count['counted_quantity'], 
                            $session_id, 
                            $notes
                        );
                        $stmt->execute();
                    }
                }
            }
            
            // Close session
            $stmt = $conn->prepare("UPDATE physical_count_sessions SET status = 'completed', end_date = NOW() WHERE session_id = ?");
            $stmt->bind_param('i', $session_id);
            $stmt->execute();
            
            $conn->commit();
            
            $message = $update_inventory ? 'جلسه شمارش تکمیل شد و موجودی انبار به‌روزرسانی گردید' : 'جلسه شمارش تکمیل شد (بدون به‌روزرسانی موجودی)';
            set_flash_message($message, 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('خطا در تکمیل جلسه شمارش: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: physical_count.php');
        exit;
    }
}

// Get categories for dropdown
$categories = [];
$result = $conn->query("SELECT * FROM inventory_categories ORDER BY category_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle different views
$view = 'sessions'; // default view
$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;

if ($session_id) {
    $view = 'count';
    
    // Get session details
    $session = $conn->query("
        SELECT pcs.*, ic.category_name 
        FROM physical_count_sessions pcs 
        LEFT JOIN inventory_categories ic ON pcs.category_id = ic.category_id 
        WHERE pcs.session_id = $session_id
    ")->fetch_assoc();
    
    if (!$session) {
        header('Location: physical_count.php');
        exit;
    }
    
    // Get count details
    $count_details = [];
    $sql = "
        SELECT pcd.*, i.item_name, i.current_inventory as current_stock, ic.category_name
        FROM physical_count_details pcd
        JOIN inventory i ON pcd.inventory_code COLLATE utf8mb4_unicode_ci = i.inventory_code COLLATE utf8mb4_unicode_ci
        LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
        WHERE pcd.session_id = $session_id
        ORDER BY i.item_name
    ";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $count_details[] = $row;
        }
    }
} else {
    // Get count sessions
    $sessions = [];
    $sql = "
        SELECT pcs.*, ic.category_name, 
               COUNT(pcd.count_id) as total_items,
               COUNT(CASE WHEN pcd.counted_quantity IS NOT NULL THEN 1 END) as counted_items
        FROM physical_count_sessions pcs 
        LEFT JOIN inventory_categories ic ON pcs.category_id = ic.category_id
        LEFT JOIN physical_count_details pcd ON pcs.session_id = pcd.session_id
        GROUP BY pcs.session_id 
        ORDER BY pcs.start_date DESC
    ";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
    }
}

$page_title = $view == 'count' ? 'شمارش فیزیکی انبار' : 'جلسات شمارش فیزیکی';
$page_description = $view == 'count' ? 'ثبت شمارش فیزیکی اقلام انبار' : 'مدیریت جلسات شمارش فیزیکی انبار';

get_header();
?>

<div class="container-fluid px-4">
    <?php 
    if ($view == 'count') {
        include ACTIVE_THEME_PATH . '/templates/physical_count_session.php';
    } else {
        include ACTIVE_THEME_PATH . '/templates/physical_count_sessions.php';
    }
    ?>
</div>

<?php get_footer(); ?>
