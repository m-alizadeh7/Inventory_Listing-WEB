<?php
require_once 'bootstrap.php';

// Handle new inventory operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_inventory'])) {
        $inventory_code = clean($_POST['inventory_code']);
        $item_name = clean($_POST['item_name']);
        $description = clean($_POST['description']);
        $initial_quantity = (float)$_POST['initial_quantity'];
        $unit = clean($_POST['unit']);
        $location = clean($_POST['location']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $min_stock = (float)$_POST['min_stock'];
        $max_stock = (float)$_POST['max_stock'];

        // Check if inventory code already exists
        $check_stmt = $conn->prepare("SELECT id FROM inventory WHERE inventory_code = ?");
        $check_stmt->bind_param('s', $inventory_code);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            set_flash_message('کد انبار تکراری است', 'danger');
        } else {
            $stmt = $conn->prepare("INSERT INTO inventory (inventory_code, item_name, description, quantity, unit, location, category_id, supplier_id, min_stock, max_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->bind_param('sssdssiiidd', $inventory_code, $item_name, $description, $initial_quantity, $unit, $location, $category_id, $supplier_id, $min_stock, $max_stock) && $stmt->execute()) {
                set_flash_message('انبارگردانی جدید با موفقیت ایجاد شد', 'success');
                header('Location: inventory_records.php');
                exit;
            } else {
                set_flash_message('خطا در ایجاد انبارگردانی: ' . $conn->error, 'danger');
            }
        }
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

$page_title = 'انبارگردانی جدید';
$page_description = 'ایجاد کالا جدید در سیستم انبار';

get_header();
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>
                        افزودن کالا جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="inventory_code" class="form-label">کد انبار *</label>
                                <input type="text" class="form-control" id="inventory_code" name="inventory_code" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="item_name" class="form-label">نام کالا *</label>
                                <input type="text" class="form-control" id="item_name" name="item_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="initial_quantity" class="form-label">موجودی اولیه *</label>
                                <input type="number" class="form-control" id="initial_quantity" name="initial_quantity" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">واحد *</label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">مکان نگهداری</label>
                                <input type="text" class="form-control" id="location" name="location">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">گروه کالا</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">انتخاب گروه...</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="supplier_id" class="form-label">تامین‌کننده</label>
                                <select class="form-select" id="supplier_id" name="supplier_id">
                                    <option value="">انتخاب تامین‌کننده...</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="min_stock" class="form-label">حداقل موجودی</label>
                                <input type="number" class="form-control" id="min_stock" name="min_stock" step="0.01" value="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="max_stock" class="form-label">حداکثر موجودی</label>
                                <input type="number" class="form-control" id="max_stock" name="max_stock" step="0.01" value="0">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="create_inventory" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                ایجاد کالا
                            </button>
                            <a href="inventory_records.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                بازگشت
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
