<?php
/**
 * Suppliers Template
 * Displays suppliers with management functionality
 */

// Get suppliers with item counts
$suppliers = [];
$sql = "SELECT s.*, COUNT(i.inventory_code) as items_count
        FROM suppliers s
        LEFT JOIN inventory i ON s.supplier_id = i.supplier_id
        GROUP BY s.supplier_id
        ORDER BY s.supplier_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suppliers[] = $row;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">مدیریت تامین‌کنندگان</h4>
                <p class="text-muted mb-0">مشاهده و مدیریت تامین‌کنندگان و اطلاعات تماس آنها</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="bi bi-plus-circle me-1"></i>
                تامین‌کننده جدید
            </button>
        </div>
    </div>
</div>

<!-- Suppliers Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>کد تامین‌کننده</th>
                                <th>نام تامین‌کننده</th>
                                <th>شخص تماس</th>
                                <th>تلفن</th>
                                <th>ایمیل</th>
                                <th>تعداد کالا</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($suppliers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-truck fs-1 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">هیچ تامین‌کننده‌ای یافت نشد</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($supplier['supplier_code']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $supplier['items_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editSupplier(<?php echo $supplier['supplier_id']; ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewItems(<?php echo $supplier['supplier_id']; ?>)">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(<?php echo $supplier['supplier_id']; ?>)">
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن تامین‌کننده جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="supplier_code" class="form-label">کد تامین‌کننده *</label>
                            <input type="text" class="form-control" id="supplier_code" name="supplier_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="supplier_name" class="form-label">نام تامین‌کننده *</label>
                            <input type="text" class="form-control" id="supplier_name" name="supplier_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">شخص تماس</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">تلفن</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">ایمیل</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">آدرس</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_supplier" class="btn btn-primary">افزودن تامین‌کننده</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSupplier(supplierId) {
    // TODO: Implement edit functionality
    alert('ویرایش تامین‌کننده ' + supplierId);
}

function viewItems(supplierId) {
    // TODO: Implement view items functionality
    alert('مشاهده کالاهای تامین‌کننده ' + supplierId);
}

function deleteSupplier(supplierId) {
    if (confirm('آیا از حذف این تامین‌کننده اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="supplier_id" value="${supplierId}">
            <input type="hidden" name="delete_supplier" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
