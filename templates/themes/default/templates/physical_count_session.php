<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0"><?php echo htmlspecialchars($session['session_name']); ?></h2>
        <p class="text-muted mb-0">
            شمارشگر: <?php echo htmlspecialchars($session['counted_by']); ?>
            <?php if ($session['category_name']): ?>
                | گروه: <?php echo htmlspecialchars($session['category_name']); ?>
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="physical_count.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-right me-2"></i>بازگشت
        </a>
        <?php if ($session['status'] == 'active'): ?>
            <button type="button" class="btn btn-success" onclick="finalizeSession(<?php echo $session['session_id']; ?>)">
                <i class="fas fa-check me-2"></i>تکمیل جلسه
            </button>
        <?php endif; ?>
    </div>
</div>

<?php display_flash_messages(); ?>

<!-- Session Info -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">اطلاعات جلسه</h6>
                <div class="row">
                    <div class="col-sm-6">
                        <strong>تاریخ شروع:</strong> <?php echo jdate('Y/m/d - H:i', strtotime($session['start_date'])); ?>
                    </div>
                    <div class="col-sm-6">
                        <strong>وضعیت:</strong>
                        <?php if ($session['status'] == 'active'): ?>
                            <span class="badge bg-warning">در حال انجام</span>
                        <?php else: ?>
                            <span class="badge bg-success">تکمیل شده</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($session['notes']): ?>
                    <div class="mt-2">
                        <strong>یادداشت:</strong> <?php echo htmlspecialchars($session['notes']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php 
                $total_items = count($count_details);
                $counted_items = 0;
                foreach ($count_details as $detail) {
                    if (!is_null($detail['counted_quantity'])) $counted_items++;
                }
                $progress = $total_items > 0 ? round(($counted_items / $total_items) * 100) : 0;
                $progress_class = $progress < 30 ? 'bg-danger' : ($progress < 70 ? 'bg-warning' : 'bg-success');
                ?>
                <h4><?php echo $progress; ?>%</h4>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <small class="text-muted"><?php echo number_format($counted_items); ?> از <?php echo number_format($total_items); ?> قلم شمارش شده</small>
            </div>
        </div>
    </div>
</div>

<!-- Count Form -->
<?php if ($session['status'] == 'active'): ?>
<form method="POST" id="countForm">
    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">شمارش اقلام</h5>
        <div>
            <?php if ($session['status'] == 'active'): ?>
                <button type="submit" name="save_count" class="btn btn-primary me-2">
                    <i class="fas fa-save me-1"></i>ذخیره شمارش
                </button>
            <?php endif; ?>
            <button class="btn btn-outline-primary btn-sm" onclick="exportTable('countTable', 'physical_count_details')">
                <i class="fas fa-file-excel me-1"></i>Excel
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="printTable('countTable')">
                <i class="fas fa-print me-1"></i>پرینت
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($count_details)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5>هیچ قلمی برای شمارش یافت نشد</h5>
                <p class="text-muted">در گروه انتخاب شده هیچ کالایی وجود ندارد</p>
            </div>
        <?php else: ?>
            <!-- Filter Controls -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-control" id="statusFilter">
                        <option value="">همه موارد</option>
                        <option value="counted">شمارش شده</option>
                        <option value="not_counted">شمارش نشده</option>
                        <option value="has_difference">دارای اختلاف</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" id="itemSearch" placeholder="جستجو در نام کالا...">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="categoryFilter">
                        <option value="">همه گروه‌ها</option>
                        <?php 
                        $used_categories = array_unique(array_column($count_details, 'category_name'));
                        foreach ($used_categories as $cat): 
                            if ($cat):
                        ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>حذف فیلترها
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover" id="countTable">
                    <thead class="table-dark">
                        <tr>
                            <th>کد کالا</th>
                            <th>نام کالا</th>
                            <th>گروه</th>
                            <th>موجودی سیستم</th>
                            <th>موجودی فعلی</th>
                            <?php if ($session['status'] == 'active'): ?>
                                <th>شمارش</th>
                            <?php else: ?>
                                <th>شمارش شده</th>
                            <?php endif; ?>
                            <th>اختلاف</th>
                            <th>وضعیت</th>
                            <?php if ($session['status'] == 'active'): ?>
                                <th>یادداشت</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($count_details as $detail): ?>
                            <tr data-category="<?php echo htmlspecialchars($detail['category_name'] ?? ''); ?>"
                                data-item-name="<?php echo htmlspecialchars($detail['item_name']); ?>">
                                <td>
                                    <code><?php echo htmlspecialchars($detail['inventory_code']); ?></code>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($detail['item_name']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($detail['category_name']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($detail['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo number_format($detail['system_quantity']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo number_format($detail['current_stock']); ?></span>
                                </td>
                                <td>
                                    <?php if ($session['status'] == 'active'): ?>
                                        <input type="number" 
                                               class="form-control form-control-sm" 
                                               name="counts[<?php echo $detail['inventory_code']; ?>]" 
                                               value="<?php echo $detail['counted_quantity'] ?? ''; ?>"
                                               min="0"
                                               placeholder="تعداد شمارش"
                                               style="width: 100px;"
                                               onchange="calculateDifference(this, <?php echo $detail['system_quantity']; ?>)">
                                    <?php else: ?>
                                        <?php if (!is_null($detail['counted_quantity'])): ?>
                                            <span class="badge bg-success"><?php echo number_format($detail['counted_quantity']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">شمارش نشده</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!is_null($detail['counted_quantity'])): ?>
                                        <?php 
                                        $diff = $detail['counted_quantity'] - $detail['system_quantity'];
                                        $diff_class = $diff > 0 ? 'bg-success' : ($diff < 0 ? 'bg-danger' : 'bg-secondary');
                                        $diff_icon = $diff > 0 ? '+' : '';
                                        ?>
                                        <span class="badge <?php echo $diff_class; ?> difference-badge">
                                            <?php echo $diff_icon . number_format($diff); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!is_null($detail['counted_quantity'])): ?>
                                        <?php if ($detail['counted_quantity'] == $detail['system_quantity']): ?>
                                            <span class="badge bg-success">مطابق</span>
                                        <?php elseif ($detail['counted_quantity'] > $detail['system_quantity']): ?>
                                            <span class="badge bg-warning">اضافه</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">کسری</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">شمارش نشده</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($session['status'] == 'active'): ?>
                                    <td>
                                        <input type="text" 
                                               class="form-control form-control-sm" 
                                               name="notes[<?php echo $detail['inventory_code']; ?>]" 
                                               value="<?php echo htmlspecialchars($detail['notes'] ?? ''); ?>"
                                               placeholder="یادداشت..."
                                               style="width: 150px;">
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($session['status'] == 'active'): ?>
</form>
<?php endif; ?>

<!-- Finalize Session Modal -->
<div class="modal fade" id="finalizeSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="finalizeSessionForm">
                <div class="modal-header">
                    <h5 class="modal-title">تکمیل جلسه شمارش</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        آیا مطمئن هستید که می‌خواهید این جلسه شمارش را تکمیل کنید؟
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="update_inventory" name="update_inventory" checked>
                        <label class="form-check-label" for="update_inventory">
                            <strong>به‌روزرسانی موجودی انبار بر اساس شمارش</strong>
                        </label>
                        <div class="form-text">
                            در صورت فعال بودن این گزینه، موجودی انبار بر اساس مقادیر شمارش شده به‌روزرسانی می‌شود
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>خلاصه تغییرات:</h6>
                        <div id="changesSummary">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="finalize_count_session" class="btn btn-success">تکمیل جلسه</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calculateDifference(input, systemQty) {
    const countedQty = parseInt(input.value) || 0;
    const difference = countedQty - systemQty;
    
    // Find the difference badge in the same row
    const row = input.closest('tr');
    const diffBadge = row.querySelector('.difference-badge');
    
    if (diffBadge) {
        const diffIcon = difference > 0 ? '+' : '';
        diffBadge.textContent = diffIcon + difference.toLocaleString();
        
        // Update badge color
        diffBadge.className = 'badge difference-badge ' + 
            (difference > 0 ? 'bg-success' : (difference < 0 ? 'bg-danger' : 'bg-secondary'));
    }
}

function finalizeSession(sessionId) {
    // Calculate summary of changes
    updateChangesSummary();
    new bootstrap.Modal(document.getElementById('finalizeSessionModal')).show();
}

function updateChangesSummary() {
    const summary = document.getElementById('changesSummary');
    let totalItems = 0;
    let changedItems = 0;
    let positiveChanges = 0;
    let negativeChanges = 0;
    
    document.querySelectorAll('input[name^="counts["]').forEach(input => {
        const countedQty = parseInt(input.value) || 0;
        const row = input.closest('tr');
        const systemQtyBadge = row.querySelector('.badge.bg-info');
        const systemQty = parseInt(systemQtyBadge.textContent.replace(/,/g, '')) || 0;
        const difference = countedQty - systemQty;
        
        totalItems++;
        if (input.value !== '') {
            if (difference !== 0) {
                changedItems++;
                if (difference > 0) positiveChanges++;
                else negativeChanges++;
            }
        }
    });
    
    summary.innerHTML = `
        <div class="row text-center">
            <div class="col-3">
                <div class="border p-2 rounded">
                    <h6 class="mb-0">${totalItems.toLocaleString()}</h6>
                    <small class="text-muted">کل اقلام</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border p-2 rounded">
                    <h6 class="mb-0 text-warning">${changedItems.toLocaleString()}</h6>
                    <small class="text-muted">دارای تغییر</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border p-2 rounded">
                    <h6 class="mb-0 text-success">${positiveChanges.toLocaleString()}</h6>
                    <small class="text-muted">افزایش</small>
                </div>
            </div>
            <div class="col-3">
                <div class="border p-2 rounded">
                    <h6 class="mb-0 text-danger">${negativeChanges.toLocaleString()}</h6>
                    <small class="text-muted">کاهش</small>
                </div>
            </div>
        </div>
    `;
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('itemSearch').value = '';
    document.getElementById('categoryFilter').value = '';
    filterTable();
}

function filterTable() {
    const statusFilter = document.getElementById('statusFilter').value;
    const itemSearch = document.getElementById('itemSearch').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    
    document.querySelectorAll('#countTable tbody tr').forEach(row => {
        let show = true;
        
        // Status filter
        if (statusFilter) {
            const countInput = row.querySelector('input[name^="counts["]');
            const hasCount = countInput && countInput.value !== '';
            const diffBadge = row.querySelector('.difference-badge');
            const hasDifference = diffBadge && !diffBadge.textContent.includes('0');
            
            switch (statusFilter) {
                case 'counted':
                    show = hasCount;
                    break;
                case 'not_counted':
                    show = !hasCount;
                    break;
                case 'has_difference':
                    show = hasCount && hasDifference;
                    break;
            }
        }
        
        // Item search filter
        if (show && itemSearch) {
            const itemName = row.dataset.itemName.toLowerCase();
            show = itemName.includes(itemSearch);
        }
        
        // Category filter
        if (show && categoryFilter) {
            const category = row.dataset.category;
            show = category === categoryFilter;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Initialize filters
document.getElementById('statusFilter').addEventListener('change', filterTable);
document.getElementById('itemSearch').addEventListener('input', filterTable);
document.getElementById('categoryFilter').addEventListener('change', filterTable);

// Auto-save functionality
let autoSaveTimeout;
document.querySelectorAll('input[name^="counts["], input[name^="notes["]').forEach(input => {
    input.addEventListener('input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
            // Auto-save logic can be implemented here
        }, 5000);
    });
});

// Initialize enhanced table
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#countTable').DataTable({
            language: {
                url: 'assets/js/dataTables.persian.json'
            },
            pageLength: 50,
            responsive: true,
            searching: false, // We use custom filters
            paging: false, // Show all items for counting
            info: false
        });
    }
});
</script>
