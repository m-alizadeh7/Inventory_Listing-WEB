<?php
/**
 * Production Order Details Template
 * Professional design for viewing production order details
 */

// Make database connection available
global $conn;

// Default order data in case it's not set
if (!isset($order) || !$order) {
    $order = array(
        'order_code' => 'نامشخص',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'notes' => ''
    );
}

// Page header data
$header_args = array(
    'title' => 'سفارش تولید شماره: ' . htmlspecialchars($order['order_code'] ?? 'نامشخص'),
    'subtitle' => 'جزئیات کامل سفارش تولید و لیست قطعات مورد نیاز',
    'icon' => 'bi bi-factory',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => '../index.php'),
        array('text' => 'سفارشات تولید', 'url' => 'production_orders.php'),
        array('text' => 'جزئیات سفارش')
    ),
    'actions' => array(
        array(
            'text' => 'بازگشت',
            'url' => 'production_orders.php',
            'class' => 'btn-secondary',
            'icon' => 'bi bi-arrow-right'
        )
    )
);

get_theme_part('page-header', $header_args);

// Load alerts
get_theme_part('alerts');

// Status configuration
$status_classes = [
    'draft' => 'bg-warning',
    'confirmed' => 'bg-info',
    'in_progress' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger'
];

$status_labels = [
    'draft' => 'پیش‌نویس',
    'confirmed' => 'تایید شده',
    'in_progress' => 'در حال تولید',
    'completed' => 'تکمیل شده',
    'cancelled' => 'لغو شده'
];
?>

<!-- Order Overview Card -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card fade-in hover-lift">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-1">
                        <i class="bi bi-factory me-2"></i>
                        <?php echo htmlspecialchars($order['order_code']); ?>
                    </h3>
                    <small class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i>
                        تاریخ ثبت: <?php echo gregorianToJalali($order['created_at']); ?>
                    </small>
                </div>
                <div>
                    <span class="status-badge <?php echo $status_classes[$order['status']] ?? 'bg-secondary'; ?>">
                        <?php echo $status_labels[$order['status']] ?? $order['status']; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Statistics Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 text-center">
                        <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-hdd-stack fs-2 text-primary mb-2"></i>
                            <h4 class="fs-3 mb-1"><?php echo $order['devices_count']; ?></h4>
                            <p class="text-muted mb-0">تعداد دستگاه‌ها</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-boxes fs-2 text-success mb-2"></i>
                            <h4 class="fs-3 mb-1"><?php echo $order['total_quantity']; ?></h4>
                            <p class="text-muted mb-0">مجموع تعداد</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-tools fs-2 text-warning mb-2"></i>
                            <h4 class="fs-3 mb-1"><?php echo count($parts); ?></h4>
                            <p class="text-muted mb-0">تعداد قطعات مختلف</p>
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                            <i class="bi bi-exclamation-triangle fs-2 <?php echo $total_missing_parts > 0 ? 'text-danger' : 'text-success'; ?> mb-2"></i>
                            <h4 class="fs-3 mb-1 <?php echo $total_missing_parts > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $total_missing_parts; ?></h4>
                            <p class="text-muted mb-0">قطعات ناموجود</p>
                        </div>
                    </div>
                </div>

                <?php if ($order['notes']): ?>
                    <div class="alert alert-info rounded-3 shadow-sm">
                        <div class="d-flex">
                            <i class="bi bi-info-circle-fill fs-4 me-2 text-primary"></i>
                            <div><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <?php if ($can_confirm): ?>
                        <form method="POST" action="confirm_production_order.php" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <button type="submit" class="btn btn-success btn-icon">
                                <i class="bi bi-check-lg me-1"></i> تایید سفارش
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($can_start): ?>
                        <form method="POST" action="start_production.php" class="d-inline">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <button type="submit" class="btn btn-primary btn-icon" <?php echo !$all_parts_available ? 'disabled' : ''; ?>>
                                <i class="bi bi-play"></i> شروع تولید
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$all_parts_available && ($can_confirm || $can_start)): ?>
                        <a href="create_purchase_request.php?order_id=<?php echo $order_id; ?>" 
                           class="btn btn-warning btn-icon">
                            <i class="bi bi-cart"></i> ایجاد درخواست خرید
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices List -->
    <div class="col-lg-6 mb-4">
        <div class="card fade-in-delay-1 hover-lift">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-box-seam me-2"></i>لیست دستگاه‌ها
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>کد دستگاه</th>
                            <th>نام دستگاه</th>
                            <th>تعداد</th>
                            <th>تعداد قطعات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                            <tr>
                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($device['device_code']); ?></code></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($device['device_name']); ?></td>
                                <td><span class="badge bg-primary rounded-pill"><?php echo $device['quantity']; ?></span></td>
                                <td><span class="badge bg-info rounded-pill"><?php echo $device['parts_count']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Parts List -->
    <div class="col-lg-6 mb-4">
        <div class="card fade-in-delay-2 hover-lift">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-tools me-2"></i>لیست قطعات مورد نیاز
                </h5>
                <div class="btn-group" role="group">
                    <button id="btnPrintParts" class="btn btn-outline-primary btn-sm" 
                            data-bs-toggle="tooltip" title="پرینت لیست قطعات">
                        <i class="bi bi-printer"></i>
                    </button>
                    <button id="btnExportParts" class="btn btn-outline-success btn-sm"
                            data-bs-toggle="tooltip" title="خروجی اکسل">
                        <i class="bi bi-file-excel"></i>
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>کد قطعه</th>
                            <th>نام قطعه</th>
                            <th>مورد نیاز</th>
                            <th>موجودی</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parts as $part): ?>
                            <?php 
                            $stock = (int)($part['current_stock'] ?? 0);
                            $needed = (int)$part['total_needed'];
                            $is_sufficient = $stock >= $needed;
                            ?>
                            <tr class="<?php echo !$is_sufficient ? 'table-warning' : ''; ?>">
                                <td><code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($part['item_code']); ?></code></td>
                                <td><?php echo htmlspecialchars($part['item_name']); ?></td>
                                <td><span class="badge bg-primary rounded-pill"><?php echo $needed; ?></span></td>
                                <td><span class="badge bg-info rounded-pill"><?php echo $stock; ?></span></td>
                                <td>
                                    <?php if ($is_sufficient): ?>
                                        <span class="badge bg-success">کافی</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">کمبود: <?php echo ($needed - $stock); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Missing Parts Alert -->
<?php if (!empty($missing_parts_list)): ?>
<div class="row">
    <div class="col-12">
        <div class="card border-danger fade-in-delay-3">
            <div class="card-header bg-danger bg-opacity-10 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                        <span class="card-title fw-bold text-danger">موجودی قطعات زیر کافی نیست:</span>
                    </div>
                    <button class="btn btn-outline-danger btn-sm" onclick="printShortageReport()">
                        <i class="bi bi-printer"></i> پرینت لیست کسری
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-danger table-striped mb-0">
                        <thead>
                            <tr>
                                <th>کد قطعه</th>
                                <th>نام قطعه</th>
                                <th>مورد نیاز</th>
                                <th>موجودی فعلی</th>
                                <th>کسری</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($missing_parts_list as $mp): ?>
                            <tr>
                                <td class="fw-bold">
                                    <code class="bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($mp['item_code']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($mp['item_name']); ?></td>
                                <td><span class="badge bg-primary rounded-pill"><?php echo $mp['needed']; ?></span></td>
                                <td><span class="badge bg-warning rounded-pill"><?php echo $mp['stock']; ?></span></td>
                                <td><span class="badge bg-danger rounded-pill"><?php echo $mp['missing']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Enhanced JavaScript -->
<script>
// Print functionality
function printShortageReport() {
    const printContent = `
        <html dir="rtl">
        <head>
            <title>گزارش کسری قطعات - سفارش <?php echo htmlspecialchars($order['order_code']); ?></title>
            <style>
                body { font-family: Tahoma, Arial, sans-serif; font-size: 12pt; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .header { text-align: center; margin-bottom: 30px; }
                .footer { margin-top: 30px; font-size: 10pt; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>گزارش کسری قطعات</h2>
                <p>سفارش تولید: <?php echo htmlspecialchars($order['order_code']); ?></p>
                <p>تاریخ: <?php echo gregorianToJalali(date('Y-m-d H:i:s')); ?></p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>کد قطعه</th>
                        <th>نام قطعه</th>
                        <th>مورد نیاز</th>
                        <th>موجودی فعلی</th>
                        <th>کسری</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missing_parts_list as $mp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mp['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($mp['item_name']); ?></td>
                        <td><?php echo $mp['needed']; ?></td>
                        <td><?php echo $mp['stock']; ?></td>
                        <td><?php echo $mp['missing']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="footer">
                <p>تاریخ چاپ: <?php echo gregorianToJalali(date('Y-m-d H:i:s')); ?></p>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.print();
}

// Enhanced tooltip initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Add animation delays
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>
