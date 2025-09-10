<?php
/**
 * New Production Order Template
 * Professional design for creating production orders
 */

// Page header data
$header_args = array(
    'title' => 'مدیریت سفارشات تولید',
    'subtitle' => 'ایجاد و مدیریت سفارشات تولید برای دستگاه‌های مختلف',
    'icon' => 'bi bi-boxes',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => 'index.php'),
        array('text' => 'مدیریت سفارشات تولید')
    ),
    'actions' => array(
        array(
            'text' => 'بازگشت',
            'url' => 'index.php',
            'class' => 'btn-secondary',
            'icon' => 'bi bi-house'
        )
    )
);

get_theme_part('page-header', $header_args);

// Load alerts
get_theme_part('alerts');

// Statistics cards
$stats = array(
    array(
        'icon' => 'bi bi-list-check',
        'value' => number_format($orders_count['all']),
        'label' => 'کل سفارشات',
        'icon_color' => 'text-primary'
    ),
    array(
        'icon' => 'bi bi-clock-history',
        'value' => number_format($orders_count['pending']),
        'label' => 'در انتظار',
        'icon_color' => 'text-warning'
    ),
    array(
        'icon' => 'bi bi-gear-fill',
        'value' => number_format($orders_count['in_progress']),
        'label' => 'در حال انجام',
        'icon_color' => 'text-info'
    ),
    array(
        'icon' => 'bi bi-check-circle-fill',
        'value' => number_format($orders_count['completed']),
        'label' => 'تکمیل شده',
        'icon_color' => 'text-success'
    )
);

include ACTIVE_THEME_PATH . '/template-parts/stats-cards.php';
?>

<!-- Add New Order Section -->
<div class="row">
    <div class="col-lg-5">
        <div class="card form-card fade-in hover-lift">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-lg me-2"></i>سفارش تولید جدید
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="orderForm">
                    <div class="mb-3">
                        <label for="notes" class="form-label">یادداشت سفارش</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                placeholder="توضیحات اضافی در مورد سفارش..."></textarea>
                    </div>
                    
                    <h6 class="mb-3 text-muted">
                        <i class="bi bi-hdd-stack me-1"></i>
                        دستگاه‌های مورد نیاز
                    </h6>
                    
                    <div id="itemsContainer">
                        <div class="item-row mb-3 p-3 bg-light rounded">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label">دستگاه</label>
                                    <select name="items[0][device_id]" class="form-select" required>
                                        <option value="">انتخاب دستگاه...</option>
                                        <?php foreach ($devices as $device): ?>
                                            <option value="<?php echo $device['device_id']; ?>">
                                                <?php echo htmlspecialchars($device['device_code'] . ' - ' . $device['device_name']); ?>
                                                (<?php echo $device['parts_count']; ?> قطعه)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تعداد</label>
                                    <input type="number" name="items[0][quantity]" class="form-control" 
                                           min="1" value="1" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-item" 
                                            title="حذف ردیف" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                            <i class="bi bi-plus-lg me-1"></i> افزودن دستگاه
                        </button>
                        <span class="text-muted small">حداقل یک دستگاه انتخاب کنید</span>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="save_new_order" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-lg me-2"></i>ثبت سفارش تولید
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Orders List -->
    <div class="col-lg-7">
        <div class="card fade-in-delay-1 hover-lift">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>سفارشات اخیر
                </h5>
                <a href="production_orders.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i> مشاهده همه
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                        <p class="text-muted">هنوز سفارشی ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>کد سفارش</th>
                                    <th>تاریخ ثبت</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($orders, 0, 8) as $order): ?>
                                    <tr>
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">
                                                <?php echo htmlspecialchars($order['order_code']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo gregorianToJalali($order['created_at']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'pending' => 'bg-warning',
                                                'in_progress' => 'bg-info',
                                                'completed' => 'bg-success'
                                            ];
                                            $status_labels = [
                                                'pending' => 'در انتظار',
                                                'in_progress' => 'در حال انجام',
                                                'completed' => 'تکمیل شده'
                                            ];
                                            ?>
                                            <span class="status-badge <?php echo $status_classes[$order['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo $status_labels[$order['status']] ?? $order['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="production_order.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm" title="مشاهده جزئیات">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="deleteOrder(<?php echo $order['order_id']; ?>)" 
                                                            title="حذف سفارش">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Device Information Modal -->
<div class="modal fade" id="deviceInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">اطلاعات دستگاه</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="deviceInfoContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript -->
<script>
let itemCounter = 1;

// Add new item row
document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'item-row mb-3 p-3 bg-light rounded fade-in';
    newRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label">دستگاه</label>
                <select name="items[${itemCounter}][device_id]" class="form-select" required>
                    <option value="">انتخاب دستگاه...</option>
                    <?php foreach ($devices as $device): ?>
                        <option value="<?php echo $device['device_id']; ?>">
                            <?php echo htmlspecialchars($device['device_code'] . ' - ' . $device['device_name']); ?>
                            (<?php echo $device['parts_count']; ?> قطعه)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">تعداد</label>
                <input type="number" name="items[${itemCounter}][quantity]" class="form-control" 
                       min="1" value="1" required>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger btn-sm remove-item" title="حذف ردیف">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    itemCounter++;
    
    // Show remove buttons if more than one item
    updateRemoveButtons();
});

// Remove item row
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
        updateRemoveButtons();
    }
});

// Update remove button visibility
function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    const removeButtons = document.querySelectorAll('.remove-item');
    
    removeButtons.forEach((btn, index) => {
        btn.style.display = rows.length > 1 ? 'block' : 'none';
    });
}

// Delete order function
function deleteOrder(orderId) {
    if (confirm('آیا از حذف این سفارش اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="delete_order_id" value="${orderId}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const deviceSelects = document.querySelectorAll('select[name*="device_id"]');
    const selectedDevices = [];
    let hasError = false;
    
    deviceSelects.forEach(select => {
        if (select.value && selectedDevices.includes(select.value)) {
            alert('نمی‌توانید یک دستگاه را بیش از یک بار انتخاب کنید!');
            hasError = true;
            e.preventDefault();
            return;
        }
        if (select.value) {
            selectedDevices.push(select.value);
        }
    });
    
    if (selectedDevices.length === 0) {
        alert('لطفاً حداقل یک دستگاه انتخاب کنید!');
        e.preventDefault();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateRemoveButtons();
    
    // Add fade-in animation to table rows
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach((row, index) => {
        row.style.animationDelay = `${index * 0.05}s`;
        row.classList.add('fade-in');
    });
});
</script>
