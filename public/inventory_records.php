<?php
require_once 'bootstrap.php';

// Handle inventory operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_inventory'])) {
        $inventory_code = clean($_POST['inventory_code']);
        $item_name = clean($_POST['item_name']);
        $description = clean($_POST['description']);
        $quantity = (float)$_POST['quantity'];
        $unit = clean($_POST['unit']);
        $location = clean($_POST['location']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $min_stock = (float)$_POST['min_stock'];
        $max_stock = (float)$_POST['max_stock'];

        $stmt = $conn->prepare("INSERT INTO inventory (inventory_code, item_name, description, quantity, unit, location, category_id, supplier_id, min_stock, max_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->bind_param('sssdssiiidd', $inventory_code, $item_name, $description, $quantity, $unit, $location, $category_id, $supplier_id, $min_stock, $max_stock) && $stmt->execute()) {
            set_flash_message('کالا با موفقیت اضافه شد', 'success');
        } else {
            set_flash_message('خطا در افزودن کالا: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['edit_inventory'])) {
        $id = (int)$_POST['inventory_id'];
        $inventory_code = clean($_POST['inventory_code']);
        $item_name = clean($_POST['item_name']);
        $description = clean($_POST['description']);
        $quantity = (float)$_POST['quantity'];
        $unit = clean($_POST['unit']);
        $location = clean($_POST['location']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $min_stock = (float)$_POST['min_stock'];
        $max_stock = (float)$_POST['max_stock'];

        $stmt = $conn->prepare("UPDATE inventory SET inventory_code = ?, item_name = ?, description = ?, quantity = ?, unit = ?, location = ?, category_id = ?, supplier_id = ?, min_stock = ?, max_stock = ? WHERE id = ?");
        if ($stmt->bind_param('sssdssiiiddi', $inventory_code, $item_name, $description, $quantity, $unit, $location, $category_id, $supplier_id, $min_stock, $max_stock, $id) && $stmt->execute()) {
            set_flash_message('کالا با موفقیت ویرایش شد', 'success');
        } else {
            set_flash_message('خطا در ویرایش کالا: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['delete_inventory'])) {
        $id = (int)$_POST['inventory_id'];

        if ($conn->query("DELETE FROM inventory WHERE id = $id")) {
            set_flash_message('کالا با موفقیت حذف شد', 'success');
        } else {
            set_flash_message('خطا در حذف کالا: ' . $conn->error, 'danger');
        }
    }

    header('Location: inventory_records.php');
    exit;
}

// Get inventory items with category and supplier info
$inventory_items = [];
$sql = "SELECT i.*, i.supplier as supplier_name
        FROM inventory i
        ORDER BY i.item_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $inventory_items[] = $row;
    }
}

// Get categories for dropdown
$categories = [];
$cat_result = $conn->query("SELECT category_id, category_name FROM inventory_categories ORDER BY category_name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get suppliers for dropdown
$suppliers = [];
$sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
if ($sup_result) {
    while ($row = $sup_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}

$page_title = 'مدیریت موجودی انبار';
$page_description = 'مشاهده و مدیریت موجودی کالاها در انبار';

get_header();
?>

<div class="container-fluid px-4">
    <?php include ACTIVE_THEME_PATH . '/templates/inventory_records.php'; ?>
</div>

<?php get_footer(); ?>
