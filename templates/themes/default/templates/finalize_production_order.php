<?php
/**
 * Finalize Production Order Template
 * Review inventory and confirm finalization
 */

// Make database connection available
global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (function_exists('getDbConnection')) {
        try {
            $conn = getDbConnection();
        } catch (Exception $e) {
            $conn = null;
        }
    } else {
        $conn = null;
    }
}

// Page header data
$header_args = array(
    'title' => 'نهایی کردن سفارش تولید: ' . htmlspecialchars($order['order_code'] ?? 'نامشخص'),
    'subtitle' => 'بررسی موجودی انبار و تأیید نهایی کردن سفارش تولید',
    'icon' => 'bi bi-check2-square',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => '../index.php'),
        array('text' => 'سفارشات تولید', 'url' => 'production_orders.php'),
        array('text' => 'سفارش ' . ($order['order_code'] ?? ''), 'url' => 'production_order.php?id=' . $order_id),
        array('text' => 'نهایی کردن')
    ),
    'actions' => array(
        array(
            'text' => 'بازگشت به سفارش',
            'url' => 'production_order.php?id=' . $order_id,
            'class' => 'btn-secondary',
            'icon' => 'bi bi-arrow-left'
        )
    )
);

get_theme_part('page-header', $header_args);

// Load alerts
get_theme_part('alerts');

// Show inventory warning if exists
if ($show_warning && !empty($inventory_issues)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h5 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>هشدار کمبود موجودی
        </h5>
        <p>برخی از قطعات مورد نیاز موجودی کافی ندارند. در صورت ادامه، موجودی این اقلام منفی خواهد شد:</p>
        <ul class="mb-0">
            <?php foreach ($inventory_issues as $issue): ?>
                <li>
                    <strong><?php echo htmlspecialchars($issue['item_name'] ?? $issue['item_code']); ?></strong>
                    - مورد نیاز: <span class="text-danger"><?php echo number_format($issue['needed']); ?></span>
                    - موجود: <span class="text-warning"><?php echo number_format($issue['available']); ?></span>
                    - کمبود: <span class="text-danger"><?php echo number_format($issue['shortage']); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Order Summary -->
    <div class="col-md-4">
        <div class="card fade-in hover-lift">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle me-2"></i>خلاصه سفارش
                </h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-5">کد سفارش:</dt>
                    <dd class="col-sm-7">
                        <code class="bg-light px-2 py-1 rounded">
                            <?php echo htmlspecialchars($order['order_code']); ?>
                        </code>
                    </dd>
                    
                    <dt class="col-sm-5">وضعیت:</dt>
                    <dd class="col-sm-7">
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
                    </dd>
                    
                    <dt class="col-sm-5">تعداد دستگاه:</dt>
                    <dd class="col-sm-7"><?php echo number_format($order['devices_count']); ?> نوع</dd>
                    
                    <dt class="col-sm-5">تعداد کل:</dt>
                    <dd class="col-sm-7"><?php echo number_format($order['total_quantity']); ?> عدد</dd>
                    
                    <dt class="col-sm-5">تاریخ ایجاد:</dt>
                    <dd class="col-sm-7">
                        <small><?php echo gregorianToJalali($order['created_at']); ?></small>
                    </dd>
                    
                    <?php if ($order['finalized_at']): ?>
                        <dt class="col-sm-5">نهایی شده:</dt>
                        <dd class="col-sm-7">
                            <small class="text-success">
                                <i class="bi bi-check-circle me-1"></i>
                                <?php echo gregorianToJalali($order['finalized_at']); ?>
                            </small>
                        </dd>
                    <?php endif; ?>
                </dl>
                
                <?php if ($order['notes']): ?>
                    <hr>
                    <div>
                        <strong>یادداشت:</strong>
                        <p class="text-muted mt-2"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$order['finalized_at']): ?>
            <!-- Finalization Actions -->
            <div class="card mt-3 fade-in-delay-1">
                <div class="card-header">
                    <h5 class="card-title mb-0 text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>عملیات نهایی کردن
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($has_issues): ?>
                        <div class="alert alert-warning">
                            <small>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                این سفارش دارای مشکل موجودی است. لطفاً جدول زیر را بررسی کنید.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="finalizeForm">
                        <?php if ($has_issues): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="confirm_negative" name="confirm_negative" required>
                                <label class="form-check-label text-danger" for="confirm_negative">
                                    <strong>تأیید می‌کنم که با منفی شدن موجودی برخی اقلام موافقم</strong>
                                </label>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="finalize_order" class="btn btn-danger btn-lg" 
                                    <?php echo $has_issues ? 'disabled' : ''; ?> id="finalizeBtn">
                                <i class="bi bi-check2-square me-2"></i>نهایی کردن سفارش
                            </button>
                            <small class="text-muted text-center">
                                این عمل قابل بازگشت نیست و موجودی انبار کسر خواهد شد
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Already Finalized -->
            <div class="card mt-3 fade-in-delay-1 border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle-fill text-success display-6"></i>
                    <h5 class="text-success mt-2">سفارش نهایی شده</h5>
                    <p class="text-muted">
                        این سفارش در تاریخ <?php echo gregorianToJalali($order['finalized_at']); ?> نهایی شده است.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Parts Analysis -->
    <div class="col-md-8">
        <div class="card fade-in-delay-2">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-check me-2"></i>تحلیل موجودی قطعات
                </h5>
                <span class="badge bg-light text-dark">
                    <?php echo count($parts); ?> قطعه
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($parts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-circle display-4 text-muted mb-3"></i>
                        <p class="text-muted">هیچ قطعه‌ای برای این سفارش تعریف نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <?php enhanced_table_start('partsAnalysisTable', 'تحلیل موجودی قطعات سفارش ' . $order['order_code'], 'سیستم مدیریت انبار'); ?>
                            <thead class="table-light">
                                <tr>
                                    <th>کد قطعه</th>
                                    <th>نام قطعه</th>
                                    <th>مورد نیاز</th>
                                    <th>موجودی فعلی</th>
                                    <th>موجودی پس از تولید</th>
                                    <th>وضعیت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($parts as $index => $part): ?>
                                    <tr class="<?php echo $part['has_shortage'] ? 'table-warning' : ''; ?>"
                                        style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td>
                                            <code class="bg-light px-2 py-1 rounded">
                                                <?php echo htmlspecialchars($part['item_code']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($part['item_name'] ?? 'نامشخص'); ?></strong>
                                            <?php if ($part['supplier_name']): ?>
                                                <br><small class="text-muted">تأمین‌کننده: <?php echo htmlspecialchars($part['supplier_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo number_format($part['total_needed']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo number_format($part['current_stock']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $part['remaining_stock'] < 0 ? 'bg-danger' : 'bg-success'; ?>">
                                                <?php echo number_format($part['remaining_stock']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($part['has_shortage']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    کمبود <?php echo number_format($part['shortage_amount']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    کافی
                                                </span>
                                            <?php endif; ?>
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

<script>
// Enable finalize button when checkbox is checked (if there are issues)
const confirmCheckbox = document.getElementById('confirm_negative');
const finalizeBtn = document.getElementById('finalizeBtn');

if (confirmCheckbox && finalizeBtn) {
    confirmCheckbox.addEventListener('change', function() {
        finalizeBtn.disabled = !this.checked;
    });
}

// Confirmation before finalizing
document.getElementById('finalizeForm')?.addEventListener('submit', function(e) {
    if (!confirm('آیا از نهایی کردن این سفارش اطمینان دارید؟ این عمل قابل بازگشت نیست.')) {
        e.preventDefault();
    }
});
</script>
