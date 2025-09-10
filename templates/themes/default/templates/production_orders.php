<?php
/**
 * Production Orders List Template
 * Professional design for managing production orders
 */

// Page header data
$header_args = array(
    'title' => 'مدیریت سفارشات تولید',
    'subtitle' => 'مشاهده و مدیریت کلیه سفارشات تولید',
    'icon' => 'bi bi-list-check',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => 'index.php'),
        array('text' => 'مدیریت سفارشات تولید')
    ),
    'actions' => array(
        array(
            'text' => 'سفارش جدید',
            'url' => 'new_production_order.php',
            'class' => 'btn-primary',
            'icon' => 'bi bi-plus-lg'
        ),
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
        'value' => number_format($total_orders),
        'label' => 'کل سفارشات',
        'icon_color' => 'text-primary'
    ),
    array(
        'icon' => 'bi bi-clock-history',
        'value' => number_format($pending_orders),
        'label' => 'در انتظار',
        'icon_color' => 'text-warning'
    ),
    array(
        'icon' => 'bi bi-gear-fill',
        'value' => number_format($in_progress_orders),
        'label' => 'در حال انجام',
        'icon_color' => 'text-info'
    ),
    array(
        'icon' => 'bi bi-check-circle-fill',
        'value' => number_format($completed_orders),
        'label' => 'تکمیل شده',
        'icon_color' => 'text-success'
    )
);

include ACTIVE_THEME_PATH . '/template-parts/stats-cards.php';
?>

<!-- Orders List -->
<div class="row">
    <div class="col-12">
        <div class="card fade-in hover-lift">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>لیست سفارشات تولید
                </h5>
                <div class="d-flex gap-2">
                    <!-- Filter buttons -->
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="statusFilter" id="filter-all" checked>
                        <label class="btn btn-outline-primary" for="filter-all">همه</label>
                        
                        <input type="radio" class="btn-check" name="statusFilter" id="filter-pending">
                        <label class="btn btn-outline-warning" for="filter-pending">در انتظار</label>
                        
                        <input type="radio" class="btn-check" name="statusFilter" id="filter-progress">
                        <label class="btn btn-outline-info" for="filter-progress">در حال انجام</label>
                        
                        <input type="radio" class="btn-check" name="statusFilter" id="filter-completed">
                        <label class="btn btn-outline-success" for="filter-completed">تکمیل شده</label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                        <h5 class="text-muted">هیچ سفارشی یافت نشد</h5>
                        <p class="text-muted">برای شروع، یک سفارش تولید جدید ایجاد کنید</p>
                        <a href="new_production_order.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>ایجاد سفارش جدید
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <?php enhanced_table_start('ordersTable', 'گزارش سفارشات تولید', 'سیستم مدیریت انبار'); ?>
                            <thead class="table-light">
                                <tr>
                                    <th width="100">کد سفارش</th>
                                    <th>تاریخ ثبت</th>
                                    <th>وضعیت</th>
                                    <th>تعداد آیتم</th>
                                    <th>یادداشت</th>
                                    <th width="150">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $index => $order): ?>
                                    <tr class="order-row fade-in" 
                                        data-status="<?php echo $order['status']; ?>"
                                        style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded fw-bold">
                                                <?php echo htmlspecialchars($order['order_code']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?php echo gregorianToJalali($order['created_at']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_classes = [
                                                'pending' => 'bg-warning text-dark',
                                                'in_progress' => 'bg-info text-white',
                                                'completed' => 'bg-success text-white'
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
                                            <span class="badge bg-light text-dark">
                                                <?php echo number_format($order['items_count']); ?> آیتم
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($order['notes'])): ?>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      title="<?php echo htmlspecialchars($order['notes']); ?>">
                                                    <?php echo htmlspecialchars($order['notes']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="production_order.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-outline-primary" title="مشاهده جزئیات">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="startOrder(<?php echo $order['order_id']; ?>)"
                                                            title="شروع تولید">
                                                        <i class="bi bi-play-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteOrder(<?php echo $order['order_id']; ?>)"
                                                            title="حذف سفارش">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php elseif ($order['status'] === 'in_progress'): ?>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="completeOrder(<?php echo $order['order_id']; ?>)"
                                                            title="تکمیل سفارش">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php enhanced_table_end(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>تأیید حذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>آیا از حذف این سفارش تولید اطمینان دارید؟</p>
                <p class="text-muted small">این عمل قابل بازگشت نیست و تمام اطلاعات مرتبط حذف خواهد شد.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="order_id" id="deleteOrderId">
                    <button type="submit" name="delete_order" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript -->
<script>
// Filter orders by status
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('input[name="statusFilter"]');
    const orderRows = document.querySelectorAll('.order-row');
    
    filterButtons.forEach(button => {
        button.addEventListener('change', function() {
            const filterValue = this.id.replace('filter-', '');
            
            orderRows.forEach(row => {
                if (filterValue === 'all' || row.dataset.status === filterValue) {
                    row.style.display = '';
                    row.classList.add('fade-in');
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});

// Delete order function
function deleteOrder(orderId) {
    document.getElementById('deleteOrderId').value = orderId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Start order function
function startOrder(orderId) {
    if (confirm('آیا می‌خواهید این سفارش را شروع کنید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="action" value="start">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Complete order function
function completeOrder(orderId) {
    if (confirm('آیا این سفارش تکمیل شده است؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="action" value="complete">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Add search functionality
const searchInput = document.createElement('input');
searchInput.type = 'text';
searchInput.className = 'form-control form-control-sm me-2';
searchInput.placeholder = 'جستجو در سفارشات...';
searchInput.style.width = '200px';

const cardHeader = document.querySelector('.card-header .d-flex');
if (cardHeader) {
    cardHeader.insertBefore(searchInput, cardHeader.lastElementChild);
}

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    orderRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
