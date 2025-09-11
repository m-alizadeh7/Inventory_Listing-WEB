<?php
/**
 * New Inventory Template
 * Form for creating new inventory items
 */

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
?>

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
                            <div class="form-text">کد منحصر به فرد برای شناسایی کالا</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="item_name" class="form-label">نام کالا *</label>
                            <input type="text" class="form-control" id="item_name" name="item_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="توضیحات تکمیلی درباره کالا..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="initial_quantity" class="form-label">موجودی اولیه *</label>
                            <input type="number" class="form-control" id="initial_quantity" name="initial_quantity" step="0.01" min="0" required>
                            <div class="form-text">مقدار فعلی موجودی کالا</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="unit" class="form-label">واحد *</label>
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="مثال: عدد، کیلوگرم، لیتر" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">مکان نگهداری</label>
                            <input type="text" class="form-control" id="location" name="location" placeholder="مثال: انبار A، قفسه 5">
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
                            <input type="number" class="form-control" id="min_stock" name="min_stock" step="0.01" min="0" value="0">
                            <div class="form-text">حداقل موجودی مجاز</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="max_stock" class="form-label">حداکثر موجودی</label>
                            <input type="number" class="form-control" id="max_stock" name="max_stock" step="0.01" min="0" value="0">
                            <div class="form-text">حداکثر موجودی مجاز</div>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>نکته:</strong> پس از ایجاد کالا، می‌توانید موجودی آن را از طریق بخش مدیریت موجودی انبار بروزرسانی کنید.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="create_inventory" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            ایجاد کالا
                        </button>
                        <a href="inventory_records.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            بازگشت به لیست
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate inventory code
document.getElementById('item_name').addEventListener('input', function() {
    const itemName = this.value.trim();
    if (itemName && !document.getElementById('inventory_code').value) {
        // Generate a simple code based on item name
        const code = itemName.substring(0, 3).toUpperCase() + Math.floor(Math.random() * 1000);
        document.getElementById('inventory_code').value = code;
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const minStock = parseFloat(document.getElementById('min_stock').value) || 0;
    const maxStock = parseFloat(document.getElementById('max_stock').value) || 0;

    if (maxStock > 0 && minStock >= maxStock) {
        e.preventDefault();
        alert('حداقل موجودی نمی‌تواند بزرگتر یا مساوی حداکثر موجودی باشد.');
        return false;
    }
});
</script>
