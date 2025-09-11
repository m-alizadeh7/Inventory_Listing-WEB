<?php
/**
 * Inventory Records Template
 * Displays inventory items with management functionality
 */

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

// Get categories for filter dropdown
$categories = [];
$cat_result = $conn->query("SELECT category_id, category_name FROM inventory_categories ORDER BY category_name");
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get suppliers for filter dropdown
$suppliers = [];
$sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
if ($sup_result) {
    while ($row = $sup_result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">مدیریت موجودی انبار</h4>
                <p class="text-muted mb-0">مشاهده و مدیریت موجودی کالاها در انبار</p>
            </div>
            <div>
                <a href="#" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="bi bi-plus-circle me-1"></i>
                    افزودن کالا
                </a>
                <a href="new_inventory.php" class="btn btn-success">
                    <i class="bi bi-plus-square me-1"></i>
                    انبارگردانی جدید
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label for="category_filter" class="form-label">فیلتر بر اساس گروه</label>
                        <select class="form-select" id="category_filter">
                            <option value="">همه گروه‌ها</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="supplier_filter" class="form-label">فیلتر بر اساس تامین‌کننده</label>
                        <select class="form-select" id="supplier_filter">
                            <option value="">همه تامین‌کنندگان</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">فیلتر بر اساس وضعیت</label>
                        <select class="form-select" id="status_filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="low_stock">کمبود موجودی</option>
                            <option value="normal">وضعیت عادی</option>
                            <option value="over_stock">موجودی اضافی</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-outline-primary w-100" onclick="applyFilters()">
                                <i class="bi bi-funnel me-1"></i>
                                اعمال فیلتر
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>کد انبار</th>
                                <th>نام کالا</th>
                                <th>گروه</th>
                                <th>تامین‌کننده</th>
                                <th>موجودی</th>
                                <th>واحد</th>
                                <th>مکان</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory_items)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="bi bi-box-seam fs-1 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">هیچ کالایی یافت نشد</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['inventory_code']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'بدون گروه'); ?></td>
                                        <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'بدون تامین‌کننده'); ?></td>
                                        <td>
                                            <span class="fw-bold"><?php echo number_format($item['current_inventory'], 2); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                        <td><?php echo htmlspecialchars($item['location'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $quantity = $item['quantity'];
                                            $min_stock = $item['min_stock'];
                                            $max_stock = $item['max_stock'];

                                            if ($quantity <= $min_stock) {
                                                echo '<span class="badge bg-danger">کمبود موجودی</span>';
                                            } elseif ($quantity >= $max_stock) {
                                                echo '<span class="badge bg-warning">موجودی اضافی</span>';
                                            } else {
                                                echo '<span class="badge bg-success">وضعیت عادی</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editInventory(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewHistory(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInventory(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن کالا جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
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
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">موجودی فعلی *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="unit" class="form-label">واحد *</label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">مکان نگهداری</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="category_id" class="form-label">گروه کالا</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">انتخاب گروه...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="supplier_id" class="form-label">تامین‌کننده</label>
                            <select class="form-select" id="supplier_id" name="supplier_id">
                                <option value="">انتخاب تامین‌کننده...</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="min_stock" class="form-label">حداقل موجودی</label>
                            <input type="number" class="form-control" id="min_stock" name="min_stock" step="0.01" value="0">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="max_stock" class="form-label">حداکثر موجودی</label>
                            <input type="number" class="form-control" id="max_stock" name="max_stock" step="0.01" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_inventory" class="btn btn-primary">افزودن کالا</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter functionality
function applyFilters() {
    const categoryFilter = document.getElementById('category_filter').value;
    const supplierFilter = document.getElementById('supplier_filter').value;
    const statusFilter = document.getElementById('status_filter').value;

    const rows = document.querySelectorAll('#inventoryTable tbody tr');

    rows.forEach(row => {
        if (row.cells.length < 9) return; // Skip empty rows

        const categoryCell = row.cells[2].textContent;
        const supplierCell = row.cells[3].textContent;
        const statusBadge = row.cells[7].querySelector('.badge');

        let showRow = true;

        // Category filter
        if (categoryFilter && !categoryCell.includes(document.querySelector(`#category_filter option[value="${categoryFilter}"]`).textContent)) {
            showRow = false;
        }

        // Supplier filter
        if (supplierFilter && !supplierCell.includes(document.querySelector(`#supplier_filter option[value="${supplierFilter}"]`).textContent)) {
            showRow = false;
        }

        // Status filter
        if (statusFilter && statusBadge) {
            const statusText = statusBadge.textContent;
            if (statusFilter === 'low_stock' && !statusText.includes('کمبود موجودی')) {
                showRow = false;
            } else if (statusFilter === 'normal' && !statusText.includes('وضعیت عادی')) {
                showRow = false;
            } else if (statusFilter === 'over_stock' && !statusText.includes('موجودی اضافی')) {
                showRow = false;
            }
        }

        row.style.display = showRow ? '' : 'none';
    });
}

function editInventory(inventoryId) {
    // TODO: Implement edit functionality
    alert('ویرایش کالا ' + inventoryId);
}

function viewHistory(inventoryId) {
    // TODO: Implement history view functionality
    alert('مشاهده تاریخچه کالا ' + inventoryId);
}

function deleteInventory(inventoryId) {
    if (confirm('آیا از حذف این کالا اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="inventory_id" value="${inventoryId}">
            <input type="hidden" name="delete_inventory" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
