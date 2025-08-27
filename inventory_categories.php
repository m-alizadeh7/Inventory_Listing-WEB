<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// Ensure table exists (safe create)
$conn->query("CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Ensure inventory table has category_id column (safe migration)
$resInv = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($resInv && $resInv->num_rows > 0) {
    $colCheck = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'category_id'");
    if ($colCheck) {
        $colRow = $colCheck->fetch_assoc();
        if ((int)$colRow['cnt'] === 0) {
            // add the column and an index
            $conn->query("ALTER TABLE inventory ADD COLUMN category_id INT NULL AFTER id");
            $conn->query("ALTER TABLE inventory ADD INDEX idx_inventory_category (category_id)");
        }
    }
}

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = clean($_POST['category_name']);
        $description = clean($_POST['category_description']);
        
        $stmt = $conn->prepare("INSERT INTO inventory_categories (category_name, category_description) VALUES (?, ?)");
        if ($stmt->bind_param('ss', $name, $description) && $stmt->execute()) {
            set_flash_message('گروه کالا با موفقیت اضافه شد', 'success');
        } else {
            set_flash_message('خطا در افزودن گروه کالا: ' . $conn->error, 'danger');
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $id = (int)$_POST['category_id'];
        $name = clean($_POST['category_name']);
        $description = clean($_POST['category_description']);
        
        $stmt = $conn->prepare("UPDATE inventory_categories SET category_name = ?, category_description = ? WHERE category_id = ?");
        if ($stmt->bind_param('ssi', $name, $description, $id) && $stmt->execute()) {
            set_flash_message('گروه کالا با موفقیت ویرایش شد', 'success');
        } else {
            set_flash_message('خطا در ویرایش گروه کالا: ' . $conn->error, 'danger');
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $id = (int)$_POST['category_id'];
        
        // Check if category has items
        $check = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE category_id = $id");
        $count = $check->fetch_assoc()['count'];
        
        if ($count > 0) {
            set_flash_message("نمی‌توان این گروه را حذف کرد. $count کالا در این گروه موجود است", 'warning');
        } else {
            if ($conn->query("DELETE FROM inventory_categories WHERE category_id = $id")) {
                set_flash_message('گروه کالا با موفقیت حذف شد', 'success');
            } else {
                set_flash_message('خطا در حذف گروه کالا: ' . $conn->error, 'danger');
            }
        }
    }
    
    header('Location: inventory_categories.php');
    exit;
}

// Get categories with item counts
$categories = [];
$sql = "SELECT c.*, COUNT(i.inventory_code) as items_count 
        FROM inventory_categories c 
        LEFT JOIN inventory i ON c.category_id = i.category_id 
        GROUP BY c.category_id 
        ORDER BY c.category_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$page_title = 'مدیریت گروه‌های کالا';
$page_description = 'ایجاد و مدیریت گروه‌بندی کالاها برای شمارش و گزارش‌گیری دسته‌ای';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/inventory_categories.php'; ?>
</div>

<?php get_footer(); ?>
