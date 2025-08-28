<?php
require_once 'bootstrap.php';

// دریافت اطلاعات کسب و کار
$business_info = getBusinessInfo();

// بررسی و ایجاد جدول production_orders اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_orders'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_orders: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول production_order_items اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_order_items'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        device_id INT NOT NULL,
        quantity INT NOT NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_order_items: ' . $conn->error);
    }
}

$order_id = clean($_GET['id'] ?? '');
if (!$order_id) {
    header('Location: production_orders.php');
    exit;
}

// دریافت اطلاعات سفارش
$order = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT i.device_id) as devices_count,
           SUM(i.quantity) as total_quantity
    FROM production_orders p
    LEFT JOIN production_order_items i ON p.order_id = i.order_id
    WHERE p.order_id = $order_id
    GROUP BY p.order_id
")->fetch_assoc();

if (!$order) {
    header('Location: production_orders.php');
    exit;
}

// دریافت لیست دستگاه‌ها
$result = $conn->query("
    SELECT i.*, d.device_code, d.device_name,
           (
               SELECT COUNT(DISTINCT b.item_code)
               FROM device_bom b
               WHERE b.device_id = i.device_id
           ) as parts_count
    FROM production_order_items i
    JOIN devices d ON i.device_id = d.device_id
    WHERE i.order_id = $order_id
    ORDER BY d.device_name
");

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}

// محاسبه قطعات مورد نیاز
$result = $conn->query("
    SELECT b.item_code,
           inv.item_name,
           SUM(b.quantity_needed * i.quantity) as total_needed,
           SUM(inv.current_inventory) as current_stock,
           inv.supplier as supplier_name
    FROM production_order_items i
    JOIN device_bom b ON i.device_id = b.device_id
    LEFT JOIN inventory inv ON inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
    WHERE i.order_id = $order_id
    GROUP BY b.item_code, inv.item_name, inv.supplier
    ORDER BY b.item_code
");

$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}

// اصلاح شرط تایید سفارش
$can_confirm = $order['status'] === 'draft';
$can_start = $order['status'] === 'confirmed';
$total_missing_parts = 0;
$missing_parts_list = [];

foreach ($parts as $part) {
    $stock = (int)($part['current_stock'] ?? 0);
    $needed = (int)$part['total_needed'];
    if ($stock < $needed) {
        $total_missing_parts++;
        $missing_parts_list[] = [
            'item_code' => $part['item_code'],
            'item_name' => $part['item_name'],
            'needed' => $needed,
            'stock' => $stock,
            'missing' => $needed - $stock
        ];
    }
}

$all_parts_available = true;

// Load template using new system
get_template('production_order');

// بررسی و ایجاد جدول production_orders اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_orders'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_orders: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول production_order_items اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_order_items'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        device_id INT NOT NULL,
        quantity INT NOT NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_order_items: ' . $conn->error);
    }
}

$order_id = clean($_GET['id'] ?? '');
if (!$order_id) {
    header('Location: production_orders.php');
    exit;
}

// دریافت اطلاعات سفارش
$order = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT i.device_id) as devices_count,
           SUM(i.quantity) as total_quantity
    FROM production_orders p
    LEFT JOIN production_order_items i ON p.order_id = i.order_id
    WHERE p.order_id = $order_id
    GROUP BY p.order_id
")->fetch_assoc();

if (!$order) {
    header('Location: production_orders.php');
    exit;
}

// دریافت لیست دستگاه‌ها
$result = $conn->query("
    SELECT i.*, d.device_code, d.device_name,
           (
               SELECT COUNT(DISTINCT b.item_code)
               FROM device_bom b
               WHERE b.device_id = i.device_id
           ) as parts_count
    FROM production_order_items i
    JOIN devices d ON i.device_id = d.device_id
    WHERE i.order_id = $order_id
    ORDER BY d.device_name
");

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}

// محاسبه قطعات مورد نیاز

$result = $conn->query("
    SELECT b.item_code,
           inv.item_name,
           SUM(b.quantity_needed * i.quantity) as total_needed,
           SUM(inv.current_inventory) as current_stock,
           inv.supplier as supplier_name
    FROM production_order_items i
    JOIN device_bom b ON i.device_id = b.device_id
    LEFT JOIN inventory inv ON inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
    WHERE i.order_id = $order_id
    GROUP BY b.item_code, inv.item_name, inv.supplier
    ORDER BY b.item_code
");

$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}

// اصلاح شرط تایید سفارش
$can_confirm = $order['status'] === 'draft';
$can_start = $order['status'] === 'confirmed';
$total_missing_parts = 0;

foreach ($parts as $part) {
    $stock = (int)($part['current_stock'] ?? 0);
    $needed = (int)$part['total_needed'];
    if ($stock < $needed) {
        $total_missing_parts++;
        $missing_parts_list[] = [
            'item_code' => $part['item_code'],
            'item_name' => $part['item_name'],
            'needed' => $needed,
            'stock' => $stock,
            'missing' => $needed - $stock
        ];
    }
}

// صرف‌نظر کردن از خطای تامین‌کننده

$all_parts_available = true;

// نمایش پیام ساختار یافته کسری قطعات (در صورت وجود)
$missing_parts_html = '';
if (!empty($missing_parts_list)) {
    ob_start();
    ?>
    <div class="card mt-4 animate__animated animate__fadeIn animate__delay-2s">
        <div class="card-header d-flex justify-content-between align-items-center bg-danger bg-opacity-10">
            <div>
                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                <span class="card-title fw-bold text-danger">موجودی قطعات زیر کافی نیست:</span>
            </div>
            <button class="btn btn-outline-danger btn-sm d-print-none" onclick="printShortageReport()">
                <i class="bi bi-printer"></i> پرینت لیست کسری
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0 align-middle">
                    <thead class="table-danger">
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
                            <td class="fw-bold"><?= htmlspecialchars($mp['item_code']) ?></td>
                            <td><?= htmlspecialchars($mp['item_name']) ?></td>
                            <td><?= $mp['needed'] ?></td>
                            <td><?= $mp['stock'] ?></td>
                            <td class="text-danger fw-bold"><?= $mp['missing'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
    $missing_parts_html = ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جزئیات سفارش تولید - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: #f0f2f5; 
            padding-top: 1.5rem; 
            font-family: 'Tahoma', sans-serif;
        }
        
        .container {
            max-width: 1400px;
        }
        
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .table th {
            white-space: nowrap;
            background-color: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }
        
        .stats-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
        }
        
        .btn {
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
        }
        
        .table-responsive {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }
        
        .stock-warning { 
            color: #dc3545;
            font-weight: 500;
        }
        
        .stock-ok { 
            color: #28a745;
            font-weight: 500;
        }
        
        .print-header, .print-footer {
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .card-title {
                font-size: 1.2rem;
            }
            
            .btn {
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
            }
        }
        
        @media print {
            body {
                background: white;
                color: #000;
                font-size: 12pt;
            }
            
            .card {
                border: none;
                box-shadow: none;
            }
            
            .status-badge, .btn, .d-print-none {
                display: none !important;
            }
            
            .print-header, .print-footer {
                display: block;
            }
            
            .container {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .card-header, .card-body {
                padding: 0.5cm;
            }
            
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card animate__animated animate__fadeIn">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="bi bi-factory me-2"></i>سفارش شماره: <?= htmlspecialchars($order['order_code']) ?>
                        </h3>
                        <small class="text-muted">
                            <i class="bi bi-calendar3 me-1"></i>تاریخ ثبت: <?= gregorianToJalali($order['created_at']) ?>
                        </small>
                    </div>
                    <div>
                        <?php
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
                        <span class="status-badge <?= $status_classes[$order['status']] ?? 'bg-secondary' ?>">
                            <?= $status_labels[$order['status']] ?? $order['status'] ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-3 text-center">
                            <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-hdd-stack fs-2 text-primary mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= $order['devices_count'] ?></h4>
                                <p class="text-muted mb-0">تعداد دستگاه‌ها</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-boxes fs-2 text-success mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= $order['total_quantity'] ?></h4>
                                <p class="text-muted mb-0">مجموع تعداد</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-tools fs-2 text-warning mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= count($parts) ?></h4>
                                <p class="text-muted mb-0">تعداد قطعات مختلف</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stats-card p-3 h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-exclamation-triangle fs-2 <?= $total_missing_parts > 0 ? 'text-danger' : 'text-success' ?> mb-2"></i>
                                <h4 class="fs-3 mb-1 <?= $total_missing_parts > 0 ? 'text-danger' : 'text-success' ?>"><?= $total_missing_parts ?></h4>
                                <p class="text-muted mb-0">قطعات ناموجود</p>
                            </div>
                        </div>
                    </div>

                    <?php if ($order['notes']): ?>
                        <hr>
                        <div class="alert alert-secondary rounded-3 shadow-sm">
                            <div class="d-flex">
                                <i class="bi bi-info-circle-fill fs-4 me-2 text-primary"></i>
                                <div><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <?php if ($can_confirm): ?>
                            <form method="POST" action="confirm_production_order.php">
                                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                <button type="submit" class="btn btn-success btn-icon">
                                    <i class="bi bi-check-lg me-1"></i> تایید سفارش
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($can_start): ?>
                            <form method="POST" action="start_production.php">
                                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                <button type="submit" class="btn btn-primary btn-icon" <?= !$all_parts_available ? 'disabled' : '' ?>>
                                    <i class="bi bi-play"></i> شروع تولید
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$all_parts_available && ($can_confirm || $can_start)): ?>
                            <a href="create_purchase_request.php?order_id=<?= $order_id ?>" 
                               class="btn btn-warning btn-icon">
                                <i class="bi bi-cart"></i> ایجاد درخواست خرید
                            </a>
                        <?php endif; ?>

                        <a href="index.php" class="btn btn-secondary btn-icon">
                            <i class="bi bi-arrow-right"></i> بازگشت
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-4">
            <div class="card animate__animated animate__fadeIn animate__delay-1s">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-box-seam me-2"></i>لیست دستگاه‌ها
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
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
                                    <td><code><?= htmlspecialchars($device['device_code']) ?></code></td>
                                    <td class="fw-bold"><?= htmlspecialchars($device['device_name']) ?></td>
                                    <td><?= $device['quantity'] ?></td>
                                    <td><?= $device['parts_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card animate__animated animate__fadeIn animate__delay-2s">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tools me-2"></i>لیست قطعات مورد نیاز
                    </h5>
                    <div class="d-print-none">
                        <button id="btnSupplierReport" class="btn btn-outline-success btn-sm me-2" data-bs-toggle="tooltip" data-bs-title="پرینت برای تامین کننده">
                            <i class="bi bi-truck me-1"></i> پرینت برای تامین کننده
                        </button>
                        <button id="btnSupplierSplitReport" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="tooltip" data-bs-title="پرینت به تفکیک تامین‌کننده‌ها">
                            <i class="bi bi-files me-1"></i> پرینت به تفکیک تامین‌کننده‌ها
                        </button>
<script>
// پرینت به تفکیک تامین‌کننده‌ها
document.getElementById('btnSupplierSplitReport').addEventListener('click', function() {
    // گروه‌بندی قطعات بر اساس تامین‌کننده
    const parts = <?php echo json_encode($parts, JSON_UNESCAPED_UNICODE); ?>;
    const suppliers = {};
    parts.forEach(part => {
        let supplier = part.supplier_name ? part.supplier_name : 'بدون تامین‌کننده';
        if (!suppliers[supplier]) suppliers[supplier] = [];
        suppliers[supplier].push(part);
    });

    let printContent = '';
    let supplierIndex = 0;
        for (const [supplier, items] of Object.entries(suppliers)) {
            if (supplierIndex > 0) printContent += '<div style="page-break-before: always;"></div>';
            printContent += `
            <div class="print-header mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">سفارش: <?php echo htmlspecialchars($business_info['business_name']); ?></h3>
                        <div class="text-muted small">تاریخ درخواست: <?php echo function_exists('gregorianToJalali') ? gregorianToJalali($order['created_at']) : $order['created_at']; ?></div>
                    </div>
                    <div class="text-end">
                        <span class="fw-bold">شماره سفارش:</span> <?php echo htmlspecialchars($order['order_code']); ?>
                    </div>
                </div>
                <hr>
            </div>
            <h4 class="mb-3">برای تامین‌کننده: <span class="text-primary">${supplier}</span></h4>
            <table class="table table-bordered table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>کد قطعه</th>
                        <th>نام قطعه</th>
                        <th>تعداد مورد نیاز</th>
                        <th>موجودی فعلی</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
            `;
            items.forEach(part => {
                let needed = part.total_needed || 0;
                let stock = part.current_stock || 0;
                let status = stock >= needed ? '<span class="text-success">موجود</span>' : `<span class="text-danger">کسری: ${needed - stock}</span>`;
                printContent += `
                    <tr>
                        <td>${part.item_code}</td>
                        <td>${part.item_name}</td>
                        <td>${needed}</td>
                        <td>${stock}</td>
                        <td>${status}</td>
                    </tr>
                `;
            });
            printContent += `</tbody></table>`;
            supplierIndex++;
        }

    // باز کردن پنجره پرینت
    const printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write(`
        <html>
        <head>
            <title>پرینت به تفکیک تامین‌کننده‌ها</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
            <style>@media print { .print-header { display: block !important; } }</style>
        </head>
        <body dir="rtl" style="font-family: Tahoma, sans-serif;">
            ${printContent}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
});
</script>
                        <button id="btnWarehouseReport" class="btn btn-outline-warning btn-sm me-2" data-bs-toggle="tooltip" data-bs-title="پرینت برای انبار دار">
                            <i class="bi bi-box me-1"></i> پرینت برای انبار دار
                        </button>
                        <button id="btnManagerReport" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-title="پرینت برای مدیریت">
                            <i class="bi bi-clipboard-data me-1"></i> پرینت برای مدیریت
                        </button>
<script>
// پرینت رسمی و ساختاریافته برای مدیریت
document.getElementById('btnManagerReport').addEventListener('click', function() {
    const parts = <?php echo json_encode($parts, JSON_UNESCAPED_UNICODE); ?>;
    const devices = <?php echo json_encode($devices, JSON_UNESCAPED_UNICODE); ?>;
    const orderInfo = {
        code: '<?php echo addslashes($order['order_code']); ?>',
        date: '<?php echo function_exists('gregorianToJalali') ? gregorianToJalali($order['created_at']) : $order['created_at']; ?>',
        totalQuantity: '<?php echo (int)$order['total_quantity']; ?>',
        totalMissingParts: '<?php echo (int)$total_missing_parts; ?>',
        devicesCount: '<?php echo (int)$order['devices_count']; ?>',
        notes: `<?php echo addslashes($order['notes'] ?? ''); ?>`
    };
    const businessInfo = {
        name: '<?php echo addslashes($business_info['business_name'] ?? ''); ?>',
        phone: '<?php echo addslashes($business_info['business_phone'] ?? ''); ?>',
        address: '<?php echo addslashes($business_info['business_address'] ?? ''); ?>'
    };
    
    // محاسبه آمار و ارقام
    const availableParts = parts.length - orderInfo.totalMissingParts;
    const availablePercentage = parts.length > 0 ? Math.round((availableParts / parts.length) * 100) : 0;
    const today = new Date().toLocaleDateString('fa-IR');
    
    let content = '';
    
    // سربرگ رسمی
    content += `
    <div class="report-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="logo-area">
                <h1 class="mb-0">${businessInfo.name}</h1>
                <p class="text-muted small mb-0">سیستم مدیریت انبار و تولید</p>
            </div>
            <div class="report-meta text-start">
                <p class="mb-0"><strong>شماره سند:</strong> MGR-${orderInfo.code}</p>
                <p class="mb-0"><strong>تاریخ گزارش:</strong> ${today}</p>
            </div>
        </div>
        <div class="text-center mb-4">
            <h2 class="report-title">گزارش تحلیلی سفارش تولید</h2>
            <p class="report-subtitle">اطلاعات جامع سفارش شماره ${orderInfo.code}</p>
        </div>
        <hr class="divider">
    </div>`;
    
    // بخش خلاصه مدیریتی
    content += `
    <div class="executive-summary mb-4">
        <h3 class="section-title">خلاصه مدیریتی</h3>
        <div class="card">
            <div class="card-body">
                <p>مدیر محترم،</p>
                <p>گزارش تحلیلی ذیل جهت بررسی وضعیت سفارش تولید شماره <strong>${orderInfo.code}</strong> مورخ <strong>${orderInfo.date}</strong> تقدیم می‌گردد. این سفارش شامل <strong>${orderInfo.devicesCount}</strong> مدل دستگاه با مجموع <strong>${orderInfo.totalQuantity}</strong> عدد است.</p>
                <p>از مجموع <strong>${parts.length}</strong> قطعه مورد نیاز، <strong>${availableParts}</strong> قطعه (${availablePercentage}%) در انبار موجود می‌باشد و <strong>${orderInfo.totalMissingParts}</strong> قطعه نیاز به تامین دارد.</p>
                <p><strong>وضعیت کلی:</strong> ${orderInfo.totalMissingParts === 0 ? '<span class="text-success">آماده تولید</span>' : '<span class="text-warning">نیازمند تامین قطعات</span>'}</p>
            </div>
        </div>
    </div>`;
    
    // مشخصات سفارش
    content += `
    <div class="order-details mb-4">
        <h3 class="section-title">مشخصات سفارش</h3>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">اطلاعات پایه</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <th style="width: 40%">شماره سفارش:</th>
                                <td>${orderInfo.code}</td>
                            </tr>
                            <tr>
                                <th>تاریخ ثبت:</th>
                                <td>${orderInfo.date}</td>
                            </tr>
                            <tr>
                                <th>تعداد مدل دستگاه:</th>
                                <td>${orderInfo.devicesCount}</td>
                            </tr>
                            <tr>
                                <th>تعداد کل دستگاه:</th>
                                <td>${orderInfo.totalQuantity}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">آمار قطعات</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stat-box border p-2 rounded">
                                    <h3 class="mb-0">${parts.length}</h3>
                                    <p class="mb-0 small">کل قطعات</p>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-box border p-2 rounded">
                                    <h3 class="mb-0 ${availableParts === parts.length ? 'text-success' : ''}">${availableParts}</h3>
                                    <p class="mb-0 small">قطعات موجود</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box border p-2 rounded">
                                    <h3 class="mb-0 ${orderInfo.totalMissingParts > 0 ? 'text-danger' : ''}">${orderInfo.totalMissingParts}</h3>
                                    <p class="mb-0 small">قطعات ناموجود</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box border p-2 rounded">
                                    <h3 class="mb-0">${availablePercentage}%</h3>
                                    <p class="mb-0 small">درصد آمادگی</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // لیست دستگاه‌ها
    content += `
    <div class="devices-section mb-4">
        <h3 class="section-title">دستگاه‌های سفارش داده شده</h3>
        <div class="devices-list">
            <p class="mb-3"><strong>تعداد کل مدل‌های دستگاه:</strong> ${orderInfo.devicesCount} مدل</p>
            <p class="mb-3"><strong>مجموع دستگاه‌های سفارشی:</strong> ${orderInfo.totalQuantity} دستگاه</p>
            <div class="row">`;
    
    devices.forEach((device, index) => {
        content += `
                <div class="col-md-6 mb-3">
                    <div class="device-item">
                        <div class="row align-items-center">
                            <div class="col-1 text-center">
                                <span class="badge bg-primary rounded-pill">${index + 1}</span>
                            </div>
                            <div class="col-8">
                                <div class="device-name">${device.device_name}</div>
                                <div class="device-code">کد: ${device.device_code}</div>
                                <small class="text-muted">شامل ${device.parts_count} قطعه مختلف</small>
                            </div>
                            <div class="col-3 text-center">
                                <span class="device-quantity">${device.quantity} عدد</span>
                            </div>
                        </div>
                    </div>
                </div>`;
    });
    
    content += `
            </div>
        </div>
        
        <div class="table-responsive mt-4">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 50px">ردیف</th>
                        <th style="width: 120px">کد دستگاه</th>
                        <th>نام دستگاه و مشخصات</th>
                        <th style="width: 100px">تعداد سفارش</th>
                        <th style="width: 120px">تعداد قطعات</th>
                        <th style="width: 150px">توضیحات</th>
                    </tr>
                </thead>
                <tbody>`;
    
    devices.forEach((device, index) => {
        content += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center"><code class="device-code">${device.device_code}</code></td>
                        <td>
                            <strong class="device-name">${device.device_name}</strong>
                            <br><small class="text-muted">دستگاه تولیدی با کیفیت استاندارد</small>
                        </td>
                        <td class="text-center"><span class="device-quantity">${device.quantity}</span></td>
                        <td class="text-center">${device.parts_count}</td>
                        <td><small>برای تولید نهایی</small></td>
                    </tr>`;
    });
    
    content += `
                </tbody>
            </table>
        </div>
    </div>`;
    
    // لیست قطعات
    content += `
    <div class="parts-section mb-4">
        <h3 class="section-title">قطعات مورد نیاز</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-success">
                    <tr>
                        <th style="width: 50px">ردیف</th>
                        <th style="width: 120px">کد قطعه</th>
                        <th>نام قطعه</th>
                        <th>تامین‌کننده</th>
                        <th style="width: 100px">نیاز</th>
                        <th style="width: 100px">موجودی</th>
                        <th style="width: 120px">وضعیت</th>
                    </tr>
                </thead>
                <tbody>`;
    
    parts.forEach((part, index) => {
        let needed = part.total_needed || 0;
        let stock = part.current_stock || 0;
        let shortage = needed - stock;
        let statusClass = shortage > 0 ? 'table-danger' : '';
        let statusText = shortage > 0 ? 
            `<span class="text-danger">کسری (${shortage})</span>` : 
            '<span class="text-success">موجود</span>';
        
        content += `
                    <tr class="${statusClass}">
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center"><code>${part.item_code}</code></td>
                        <td>${part.item_name || ''}</td>
                        <td>${part.supplier_name || '---'}</td>
                        <td class="text-center">${needed}</td>
                        <td class="text-center">${stock}</td>
                        <td class="text-center">${statusText}</td>
                    </tr>`;
    });
    
    content += `
                </tbody>
            </table>
        </div>
    </div>`;
    
    // نمودار تحلیلی
    content += `
    <div class="analysis-section mb-4">
        <h3 class="section-title">تحلیل وضعیت</h3>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">نمودار وضعیت قطعات</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="chart-container" style="position: relative; height: 200px; width: 100%;">
                            <div class="pie-chart">
                                <div class="pie-segment" style="--percentage: ${availablePercentage}; --color: #28a745;"></div>
                                <div class="chart-label">
                                    <span class="percentage">${availablePercentage}%</span>
                                    <span class="label">آماده</span>
                                </div>
                            </div>
                        </div>
                        <div class="chart-legend d-flex justify-content-center mt-3">
                            <div class="legend-item me-4">
                                <span class="legend-color" style="background-color: #28a745;"></span>
                                <span>قطعات موجود (${availableParts})</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-color" style="background-color: #dc3545;"></span>
                                <span>قطعات ناموجود (${orderInfo.totalMissingParts})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">توصیه‌های عملیاتی</h5>
                    </div>
                    <div class="card-body">
                        <ul class="recommendation-list ps-3">
                            ${orderInfo.totalMissingParts > 0 ? 
                                `<li>تهیه فوری ${orderInfo.totalMissingParts} قطعه کسری با اولویت بندی</li>
                                 <li>هماهنگی با تامین‌کنندگان جهت تحویل سریع اقلام</li>
                                 <li>بررسی امکان جایگزینی قطعات مشابه</li>` : 
                                `<li>شروع عملیات تولید با توجه به موجود بودن تمامی قطعات</li>
                                 <li>برنامه‌ریزی زمان‌بندی تولید برای ${orderInfo.totalQuantity} دستگاه</li>`
                            }
                            <li>آماده‌سازی فضای انبار برای نگهداری محصولات نهایی</li>
                            <li>بررسی تجهیزات و نیروی انسانی مورد نیاز تولید</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // توضیحات و نکات
    if (orderInfo.notes && orderInfo.notes.trim() !== '') {
        content += `
        <div class="notes-section mb-4">
            <h3 class="section-title">توضیحات و نکات</h3>
            <div class="card">
                <div class="card-body">
                    <div class="notes-content">
                        ${orderInfo.notes}
                    </div>
                </div>
            </div>
        </div>`;
    }
    
    // امضاها
    content += `
    <div class="signatures-section mb-5 mt-5">
        <div class="row text-center">
            <div class="col-4">
                <div class="signature-box">
                    <p>تهیه کننده:</p>
                    <div class="signature-line"></div>
                    <p class="mt-2">مسئول انبار</p>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <p>تایید کننده:</p>
                    <div class="signature-line"></div>
                    <p class="mt-2">مدیر تولید</p>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <p>تصویب کننده:</p>
                    <div class="signature-line"></div>
                    <p class="mt-2">مدیر عامل</p>
                </div>
            </div>
        </div>
    </div>`;
    
    // فوتر
    content += `
    <div class="print-footer text-center mt-4" style="position: fixed; bottom: 1.5cm; left: 0; width: 100%;">
        <hr>
        <small>سیستم مدیریت انبار | توسعه‌دهنده: مهدی علیزاده | <a href='https://alizadehx.ir' target='_blank'>alizadehx.ir</a></small>
    </div>`;
    
    // باز کردن پنجره پرینت
    const printWindow = window.open('', '', 'width=1200,height=800');
    printWindow.document.write(`
        <html>
        <head>
            <title>گزارش مدیریت سفارش تولید</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
                
                body {
                    font-family: 'Vazirmatn', 'Segoe UI', Tahoma, Arial, sans-serif;
                    padding: 1.5cm;
                    color: #2c3e50;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    line-height: 1.6;
                }
                
                .report-header {
                    background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
                    color: white;
                    padding: 25px;
                    border-radius: 15px;
                    margin-bottom: 30px;
                    box-shadow: 0 8px 25px rgba(44, 90, 160, 0.2);
                }
                
                .logo-area h1 {
                    font-size: 28px;
                    font-weight: 700;
                    margin-bottom: 5px;
                }
                
                .report-title {
                    font-size: 26px;
                    font-weight: 700;
                    color: #fff;
                    margin-bottom: 8px;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                }
                
                .report-subtitle {
                    font-size: 16px;
                    color: #e3f2fd;
                    opacity: 0.9;
                }
                
                .report-meta {
                    background: rgba(255,255,255,0.1);
                    padding: 15px;
                    border-radius: 10px;
                    backdrop-filter: blur(10px);
                }
                
                .divider {
                    border: none;
                    height: 3px;
                    background: linear-gradient(90deg, #2c5aa0, #1e3c72, #2c5aa0);
                    margin: 25px 0;
                    border-radius: 2px;
                }
                
                .section-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #2c5aa0;
                    border-bottom: 2px solid #e3f2fd;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                    position: relative;
                }
                
                .section-title::before {
                    content: '';
                    position: absolute;
                    bottom: -2px;
                    left: 0;
                    width: 60px;
                    height: 2px;
                    background: #2c5aa0;
                }
                
                .card {
                    border-radius: 12px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
                    margin-bottom: 20px;
                    border: 1px solid #e3f2fd;
                    background: white;
                }
                
                .card-header {
                    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
                    padding: 15px 20px;
                    border-radius: 12px 12px 0 0;
                    border-bottom: 2px solid #2196f3;
                }
                
                .card-header h5 {
                    color: #1565c0;
                    font-weight: 600;
                    margin: 0;
                }
                
                .card-body {
                    padding: 20px;
                }
                
                .table {
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                
                .table th {
                    background: linear-gradient(135deg, #2c5aa0 0%, #1e3c72 100%);
                    color: white;
                    font-weight: 600;
                    padding: 15px 12px;
                    border: none;
                    text-align: center;
                }
                
                .table td {
                    padding: 12px;
                    vertical-align: middle;
                    border-color: #e9ecef;
                }
                
                .table-primary thead th {
                    background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
                }
                
                .table-success thead th {
                    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
                }
                
                .table-striped > tbody > tr:nth-of-type(odd) {
                    background-color: rgba(44, 90, 160, 0.03);
                }
                
                .signature-line {
                    height: 2px;
                    background: linear-gradient(90deg, transparent, #2c5aa0, transparent);
                    width: 200px;
                    margin: 25px auto 0;
                    border: none;
                }
                
                .signature-box {
                    padding: 20px;
                    border: 2px solid #e3f2fd;
                    border-radius: 10px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    text-align: center;
                }
                
                .signature-box p {
                    font-weight: 600;
                    color: #2c5aa0;
                    margin-bottom: 10px;
                }
                
                .stat-box {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: 2px solid #e3f2fd;
                    border-radius: 10px;
                    transition: all 0.3s ease;
                }
                
                .stat-box h3 {
                    font-weight: 700;
                    color: #2c5aa0;
                }
                
                .stat-box p {
                    color: #6c757d;
                    font-weight: 500;
                }
                
                .executive-summary {
                    background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
                    border: 2px solid #ffcc02;
                    border-radius: 15px;
                    padding: 25px;
                }
                
                .executive-summary p {
                    margin-bottom: 15px;
                    color: #5d4037;
                    line-height: 1.8;
                }
                
                .devices-list {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 15px 0;
                    border-left: 4px solid #2c5aa0;
                }
                
                .device-item {
                    background: white;
                    padding: 12px 15px;
                    margin: 8px 0;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    border-right: 3px solid #4caf50;
                }
                
                .device-code {
                    color: #2c5aa0;
                    font-weight: 600;
                    font-family: 'Courier New', monospace;
                }
                
                .device-name {
                    color: #2e7d32;
                    font-weight: 600;
                    font-size: 16px;
                }
                
                .device-quantity {
                    color: #f57c00;
                    font-weight: 700;
                }
                
                .legend-color {
                    display: inline-block;
                    width: 18px;
                    height: 18px;
                    margin-left: 8px;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                
                .recommendation-list li {
                    margin-bottom: 12px;
                    padding: 8px 12px;
                    background: #f8f9fa;
                    border-radius: 6px;
                    border-right: 3px solid #2c5aa0;
                    color: #495057;
                }
                
                .status-available {
                    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
                    color: #2e7d32;
                    font-weight: 600;
                }
                
                .status-shortage {
                    background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
                    color: #c62828;
                    font-weight: 600;
                }
                
                @media print {
                    body {
                        padding: 1cm;
                        background: white !important;
                    }
                    
                    .card {
                        box-shadow: none;
                        border: 1px solid #ddd;
                        page-break-inside: avoid;
                    }
                    
                    .report-header {
                        background: #2c5aa0 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .table th {
                        background: #2c5aa0 !important;
                        color: white !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .table-primary thead th {
                        background: #4caf50 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .table-success thead th {
                        background: #ff9800 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    @page {
                        size: A4;
                        margin: 1.5cm;
                    }
                    
                    .signatures-section {
                        page-break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body dir='rtl'>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
});
</script>
                    </div>
                </div>
                <div class="table-responsive" id="parts-table-print">
                    <div class="print-header mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="fw-bold mb-1">سفارش: <?= htmlspecialchars($business_info['business_name']) ?></h3>
                                <div class="text-muted small">تاریخ درخواست: <?= function_exists('gregorianToJalali') ? gregorianToJalali($order['created_at']) : $order['created_at'] ?></div>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold">شماره سفارش:</span> <?= htmlspecialchars($order['order_code']) ?>
                            </div>
                        </div>
                        <hr>
                    </div>
                    <table class="table table-hover mb-0 align-middle print-table table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>کد قطعه</th>
                                <th>نام قطعه</th>
                                <th>تامین‌کننده</th>
                                <th>تعداد مورد نیاز</th>
                                <th>موجودی فعلی</th>
                                <th>وضعیت</th>
                                <th class="d-print-table-cell">تطابق انبار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts as $part): 
                                $stock = (int)($part['current_stock'] ?? 0);
                                $needed = (int)$part['total_needed'];
                                $status = $stock >= $needed ? 'ok' : 'warning';
                            ?>
                                <tr class="<?= $status === 'ok' ? 'table-success' : '' ?>">
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($part['item_code']) ?></span></td>
                                    <td class="fw-bold"><?= htmlspecialchars($part['item_name']) ?></td>
                                    <td>
                                        <?php if (isset($part['supplier_name']) && $part['supplier_name']): ?>
                                            <span class="fw-bold"><?= htmlspecialchars($part['supplier_name']) ?></span>
                                            <small class="text-muted d-block">
                                                <?= isset($part['supplier_code']) ? htmlspecialchars($part['supplier_code']) : '' ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-warning">
                                                <i class="bi bi-exclamation-triangle"></i> بدون تامین‌کننده
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-primary rounded-pill"><?= $needed ?></span></td>
                                    <td><span class="badge bg-secondary rounded-pill"><?= $stock ?></span></td>
                                    <td>
                                        <?php if ($status === 'ok'): ?>
                                            <span class="stock-ok">
                                                <i class="bi bi-check-circle-fill"></i> موجود
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-warning">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                کسری: <?= $needed - $stock ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="d-print-table-cell">
                                        <div class="form-check d-flex justify-content-center">
                                            <input type="checkbox" class="form-check-input" style="transform: scale(1.3);">
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="row mt-4">
                        <div class="col-6">
                            <span class="fw-bold">امضا انباردار:</span> ______________________
                        </div>
                        <div class="col-6 text-end">
                            <span class="fw-bold">امضا تامین‌کننده:</span> ______________________
                        </div>
                    </div>
                    <div class="print-footer text-center mt-4">
                        <hr>
                    <small>طراحی و توسعه توسط مهدی علیزاده | <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a></small>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($missing_parts_html)) echo $missing_parts_html; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// پرینت ساده و ساختاریافته لیست کسری
function printShortageReport() {
    const missingParts = <?php echo json_encode($missing_parts_list ?? [], JSON_UNESCAPED_UNICODE); ?>;
    
    if (missingParts.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'اطلاعات',
            text: 'هیچ کسری قطعه‌ای وجود ندارد!',
            confirmButtonText: 'متوجه شدم'
        });
        return;
    }
    
    const businessInfo = {
        name: '<?php echo addslashes($business_info['business_name'] ?? ''); ?>',
        phone: '<?php echo addslashes($business_info['business_phone'] ?? ''); ?>',
        address: '<?php echo addslashes($business_info['business_address'] ?? ''); ?>'
    };
    
    const orderInfo = {
        code: '<?php echo addslashes($order['order_code']); ?>',
        date: '<?php echo function_exists('gregorianToJalali') ? gregorianToJalali($order['created_at']) : $order['created_at']; ?>'
    };
    
    const today = new Date().toLocaleDateString('fa-IR');
    
    let content = `
    <div class="shortage-header mb-4">
        <div class="text-center mb-4">
            <h2 class="company-name">${businessInfo.name}</h2>
            <p class="company-subtitle">سیستم مدیریت انبار و تولید</p>
        </div>
        
        <div class="report-info">
            <div class="row">
                <div class="col-6">
                    <strong>شماره سفارش:</strong> ${orderInfo.code}
                </div>
                <div class="col-6 text-end">
                    <strong>تاریخ گزارش:</strong> ${today}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6">
                    <strong>تاریخ سفارش:</strong> ${orderInfo.date}
                </div>
                <div class="col-6 text-end">
                    <strong>تعداد اقلام کسری:</strong> ${missingParts.length}
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4 mb-4">
            <h3 class="report-title">گزارش کسری قطعات</h3>
            <p class="report-subtitle">لیست قطعات دارای کسری موجودی</p>
        </div>
        <hr class="divider">
    </div>
    
    <div class="shortage-summary mb-4">
        <div class="alert alert-warning">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                <div>
                    <strong>هشدار:</strong> در این سفارش تولید، تعداد <strong>${missingParts.length}</strong> قطعه دارای کسری موجودی می‌باشد.
                    لطفاً جهت تکمیل سفارش، اقدام به تامین قطعات زیر نمایید.
                </div>
            </div>
        </div>
    </div>
    
    <div class="shortage-table">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th style="width: 60px">ردیف</th>
                    <th style="width: 120px">کد قطعه</th>
                    <th>نام قطعه</th>
                    <th style="width: 100px">مورد نیاز</th>
                    <th style="width: 100px">موجودی فعلی</th>
                    <th style="width: 100px">میزان کسری</th>
                    <th style="width: 120px">اولویت</th>
                </tr>
            </thead>
            <tbody>`;
    
    missingParts.forEach((part, index) => {
        const priority = part.missing > 50 ? 'بالا' : part.missing > 20 ? 'متوسط' : 'عادی';
        const priorityClass = part.missing > 50 ? 'text-danger' : part.missing > 20 ? 'text-warning' : 'text-info';
        
        content += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td class="text-center"><code>${part.item_code}</code></td>
                    <td>${part.item_name}</td>
                    <td class="text-center">${part.needed}</td>
                    <td class="text-center">${part.stock}</td>
                    <td class="text-center text-danger fw-bold">${part.missing}</td>
                    <td class="text-center ${priorityClass} fw-bold">${priority}</td>
                </tr>`;
    });
    
    content += `
            </tbody>
        </table>
    </div>
    
    <div class="shortage-footer mt-5">
        <div class="row">
            <div class="col-4 text-center">
                <div class="signature-section">
                    <p class="mb-4"><strong>تهیه کننده:</strong></p>
                    <div class="signature-line"></div>
                    <p class="mt-3">مسئول انبار</p>
                    <small class="text-muted">نام و امضا</small>
                </div>
            </div>
            <div class="col-4 text-center">
                <div class="signature-section">
                    <p class="mb-4"><strong>بررسی کننده:</strong></p>
                    <div class="signature-line"></div>
                    <p class="mt-3">مدیر تولید</p>
                    <small class="text-muted">نام و امضا</small>
                </div>
            </div>
            <div class="col-4 text-center">
                <div class="signature-section">
                    <p class="mb-4"><strong>تایید کننده:</strong></p>
                    <div class="signature-line"></div>
                    <p class="mt-3">مدیر خرید</p>
                    <small class="text-muted">نام و امضا</small>
                </div>
            </div>
        </div>
        
        <div class="notes-section mt-4">
            <h5>توضیحات و راهکارهای پیشنهادی:</h5>
            <div style="border: 1px solid #ddd; min-height: 80px; padding: 10px; background: #f9f9f9;">
                <p class="text-muted mb-2">• بررسی امکان جایگزینی قطعات مشابه</p>
                <p class="text-muted mb-2">• هماهنگی با تامین‌کنندگان جهت تسریع در تحویل</p>
                <p class="text-muted mb-0">• اولویت‌بندی تامین بر اساس میزان کسری</p>
            </div>
        </div>
    </div>
    
    <div class="print-footer text-center mt-4">
        <hr>
        <small>سیستم مدیریت انبار | توسعه‌دهنده: مهدی علیزاده | <a href='https://alizadehx.ir' target='_blank'>alizadehx.ir</a></small>
    </div>`;
    
    // باز کردن پنجره پرینت
    const printWindow = window.open('', '', 'width=900,height=700');
    printWindow.document.write(`
        <html>
        <head>
            <title>گزارش کسری قطعات - سفارش ${orderInfo.code}</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
                
                body {
                    font-family: 'Vazirmatn', 'Segoe UI', Tahoma, Arial, sans-serif;
                    padding: 1.5cm;
                    color: #2c3e50;
                    background: white;
                    line-height: 1.5;
                }
                
                .company-name {
                    font-size: 24px;
                    font-weight: 700;
                    color: #2c5aa0;
                    margin-bottom: 5px;
                }
                
                .company-subtitle {
                    font-size: 14px;
                    color: #6c757d;
                    margin-bottom: 0;
                }
                
                .report-info {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 8px;
                    border-right: 4px solid #2c5aa0;
                    margin: 20px 0;
                }
                
                .report-title {
                    font-size: 22px;
                    font-weight: 600;
                    color: #dc3545;
                    margin-bottom: 5px;
                }
                
                .report-subtitle {
                    font-size: 14px;
                    color: #6c757d;
                    margin-bottom: 0;
                }
                
                .divider {
                    border: none;
                    height: 2px;
                    background: linear-gradient(90deg, #dc3545, #ff6b6b, #dc3545);
                    margin: 20px 0;
                    border-radius: 2px;
                }
                
                .alert {
                    border-radius: 8px;
                    border: 1px solid #ffc107;
                    background: #fff3cd;
                    color: #856404;
                    padding: 15px;
                }
                
                .table {
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    margin-bottom: 0;
                }
                
                .table th {
                    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
                    color: white;
                    font-weight: 600;
                    padding: 15px 12px;
                    border: none;
                    text-align: center;
                    font-size: 14px;
                }
                
                .table td {
                    padding: 12px;
                    vertical-align: middle;
                    border-color: #e9ecef;
                    text-align: center;
                }
                
                .table-striped > tbody > tr:nth-of-type(odd) {
                    background-color: rgba(220, 53, 69, 0.05);
                }
                
                .signature-section {
                    padding: 20px 10px;
                    border: 1px solid #e9ecef;
                    border-radius: 8px;
                    background: #f8f9fa;
                    height: 100%;
                }
                
                .signature-line {
                    height: 1px;
                    background: #6c757d;
                    width: 150px;
                    margin: 0 auto;
                    border: none;
                }
                
                .notes-section {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }
                
                .notes-section h5 {
                    color: #2c5aa0;
                    font-weight: 600;
                    margin-bottom: 15px;
                }
                
                code {
                    background: #e9ecef;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-family: 'Courier New', monospace;
                    color: #495057;
                    font-weight: 600;
                }
                
                @media print {
                    body {
                        padding: 1cm;
                    }
                    
                    .table th {
                        background: #dc3545 !important;
                        color: white !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .alert {
                        background: #fff3cd !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .report-info {
                        background: #f8f9fa !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    @page {
                        size: A4;
                        margin: 1.5cm;
                    }
                }
            </style>
        </head>
        <body dir='rtl'>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

// اطلاعات مورد نیاز از PHP به جاوااسکریپت
const businessInfo = {
    name: "<?php echo addslashes($business_info['business_name'] ?? ''); ?>",
    phone: "<?php echo addslashes($business_info['business_phone'] ?? ''); ?>",
    address: "<?php echo addslashes($business_info['business_address'] ?? ''); ?>"
};

const orderInfo = {
    code: "<?php echo addslashes($order['order_code'] ?? ''); ?>",
    date: "<?php echo function_exists('gregorianToJalali') ? addslashes(gregorianToJalali($order['created_at'])) : date('Y/m/d'); ?>",
    totalQuantity: <?php echo (int)($order['total_quantity'] ?? 0); ?>,
    totalMissingParts: <?php echo (int)$total_missing_parts; ?>
};

// لیست قطعات با تبدیل صحیح داده‌ها
const partsList = [
<?php foreach ($parts as $part): 
    $stock = (int)($part['current_stock'] ?? 0);
    $needed = (int)$part['total_needed'];
    $shortage = $needed - $stock;
    $status_class = $shortage > 0 ? 'shortage' : 'available';
?>
    {
        code: "<?php echo addslashes($part['item_code'] ?? ''); ?>",
        name: "<?php echo addslashes($part['item_name'] ?? ''); ?>",
        supplierName: "<?php echo addslashes($part['supplier_name'] ?? 'نامشخص'); ?>",
        supplierCode: "<?php echo addslashes($part['supplier_code'] ?? ''); ?>",
        currentStock: <?php echo $stock; ?>,
        needed: <?php echo $needed; ?>,
        shortage: <?php echo $shortage; ?>,
        status: "<?php echo $shortage > 0 ? 'کسری' : 'موجود'; ?>",
        statusClass: "<?php echo $status_class; ?>"
    },
<?php endforeach; ?>
];

// گزارش تامین‌کننده - فقط کالاهای کسری
function printSupplierReport() {
    // فیلتر کردن قطعات با کسری موجودی
    const shortageItems = partsList.filter(part => part.shortage > 0);
    
    if (shortageItems.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'اطلاعات',
            text: 'هیچ کالای کسری برای تامین وجود ندارد!',
            confirmButtonText: 'متوجه شدم'
        });
        return;
    }
    
    let content = `
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>درخواست تامین کالا</title>
        <style>
            @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
            body { 
                font-family: 'Vazirmatn', Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
            }
            .header { 
                text-align: center; 
                border-bottom: 2px solid #333; 
                padding-bottom: 15px; 
                margin-bottom: 20px; 
            }
            .contact-info { 
                text-align: right; 
                margin-bottom: 20px; 
                padding: 10px; 
                background-color: #f9f9f9;
                border-radius: 5px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
            }
            th, td { 
                border: 1px solid #333; 
                padding: 10px; 
                text-align: center; 
            }
            th { 
                background-color: #f0f0f0; 
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer { 
                margin-top: 30px; 
                padding-top: 15px; 
                border-top: 1px solid #333; 
                font-size: 12px; 
                text-align: center; 
            }
            .signature-line {
                margin-top: 30px;
                border-top: 1px solid #333;
                display: inline-block;
                padding-top: 5px;
                min-width: 250px;
            }
            @page { 
                size: A4; 
                margin: 1.5cm; 
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>درخواست تامین کالا</h2>
            <p><strong>${businessInfo.name}</strong></p>
            <p>شماره سفارش: ${orderInfo.code} | تاریخ: ${orderInfo.date}</p>
        </div>
        
        <div class="contact-info">
            <p><strong>اطلاعات سفارش‌دهنده:</strong></p>
            ${businessInfo.phone ? '<p>تلفن تماس: ' + businessInfo.phone + '</p>' : ''}
            ${businessInfo.address ? '<p>آدرس: ' + businessInfo.address + '</p>' : ''}
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>کد کالا</th>
                    <th>نام کالا</th>
                    <th>تعداد مورد نیاز</th>
                    <th>توضیحات</th>
                </tr>
            </thead>
            <tbody>`;
    
    shortageItems.forEach((part, index) => {
        content += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${part.code}</td>
                    <td>${part.name}</td>
                    <td>${part.shortage}</td>
                    <td>فوری</td>
                </tr>`;
    });
    
    content += `
            </tbody>
        </table>
        
        <div style="margin-top: 30px; text-align: left;">
            <p>امضا و مهر تامین‌کننده: <span class="signature-line"></span></p>
        </div>
        
        <div class="footer">
            <p>طراحی و توسعه: مهدی علیزاده | <a href="https://alizadehx.ir">alizadehx.ir</a></p>
        </div>
    </body>
    </html>`;
    
    const newWindow = window.open('', '_blank');
    newWindow.document.write(content);
    newWindow.document.close();
    
    // نمایش اعلان موفقیت
    Swal.fire({
        icon: 'success',
        title: 'گزارش آماده شد',
        text: 'گزارش تامین‌کننده در پنجره جدید باز شد.',
        confirmButtonText: 'متوجه شدم'
    });
    
    // تاخیر برای اطمینان از لود شدن صفحه
    setTimeout(() => {
        newWindow.print();
    }, 500);
}

// گزارش انباردار - کالاهای تحویلی
function printWarehouseReport() {
    let content = `
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>فرم تحویل کالا به انبار</title>
        <style>
            @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
            body { 
                font-family: 'Vazirmatn', Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
            }
            .header { 
                text-align: center; 
                border-bottom: 2px solid #3f51b5; 
                padding-bottom: 15px; 
                margin-bottom: 20px; 
                color: #3f51b5;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
            }
            th, td { 
                border: 1px solid #333; 
                padding: 10px; 
                text-align: center; 
            }
            th { 
                background-color: #e3f2fd; 
                color: #0d47a1;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f5f5f5;
            }
            .checkbox { 
                width: 20px; 
                height: 20px; 
                display: inline-block;
                border: 1px solid #333;
                vertical-align: middle;
                line-height: 18px;
            }
            .footer { 
                margin-top: 30px; 
                padding-top: 15px; 
                border-top: 1px solid #333; 
                font-size: 12px; 
                text-align: center; 
            }
            .signature-area {
                margin-top: 30px;
                display: flex;
                justify-content: space-between;
            }
            .signature-line {
                margin-top: 5px;
                border-top: 1px solid #333;
                display: inline-block;
                padding-top: 5px;
                min-width: 200px;
            }
            @page { 
                size: A4; 
                margin: 1.5cm; 
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>فرم تحویل کالا به انبار</h2>
            <p><strong>${businessInfo.name}</strong></p>
            <p>شماره سفارش: ${orderInfo.code} | تاریخ: ${orderInfo.date}</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>کد کالا</th>
                    <th>نام کالا</th>
                    <th>تعداد مورد نیاز</th>
                    <th>تحویل شده</th>
                    <th>تایید انباردار</th>
                </tr>
            </thead>
            <tbody>`;
    
    partsList.forEach((part, index) => {
        content += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${part.code}</td>
                    <td>${part.name}</td>
                    <td>${part.needed}</td>
                    <td>☐</td>
                    <td>☐</td>
                </tr>`;
    });
    
    content += `
            </tbody>
        </table>
        
        <div class="signature-area">
            <p>تاریخ تحویل: <span class="signature-line"></span></p>
            <p>امضا انباردار: <span class="signature-line"></span></p>
        </div>
        
        <div class="footer">
            <p>طراحی و توسعه: <a href="https://alizadehx.ir">alizadehx.ir</a></p>
        </div>
    </body>
    </html>`;
    
    const newWindow = window.open('', '_blank');
    newWindow.document.write(content);
    newWindow.document.close();
    
    // نمایش اعلان موفقیت
    Swal.fire({
        icon: 'success',
        title: 'گزارش آماده شد',
        text: 'گزارش انباردار در پنجره جدید باز شد.',
        confirmButtonText: 'متوجه شدم'
    });
    
    // تاخیر برای اطمینان از لود شدن صفحه
    setTimeout(() => {
        newWindow.print();
    }, 500);
}

// گزارش مدیریت - تحلیل کامل سفارش
function printManagerReport() {
    let content = `
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>گزارش تحلیل سفارش تولید</title>
        <style>
            @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css');
            body { 
                font-family: 'Vazirmatn', Arial, sans-serif; 
                margin: 0; 
                padding: 20px; 
            }
            .header { 
                text-align: center; 
                border-bottom: 2px solid #333; 
                padding-bottom: 15px; 
                margin-bottom: 20px; 
            }
            .summary { 
                display: flex; 
                justify-content: space-around; 
                margin: 20px 0; 
                background-color: #f8f9fa; 
                padding: 15px; 
                border-radius: 10px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .summary div { 
                text-align: center; 
                padding: 10px 15px;
                border-radius: 5px;
            }
            .summary div strong {
                font-size: 24px;
                display: block;
                margin-bottom: 5px;
                color: #333;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0; 
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            th, td { 
                border: 1px solid #dee2e6; 
                padding: 12px 15px; 
                text-align: center; 
            }
            th { 
                background-color: #fff3e0; 
                color: #e65100;
                font-weight: bold;
            }
            .shortage { 
                background-color: #ffebee; 
            }
            .available { 
                background-color: #e8f5e8; 
            }
            .conclusion {
                background-color: #f5f5f5;
                padding: 15px;
                border-radius: 5px;
                margin-top: 30px;
            }
            .footer { 
                margin-top: 30px; 
                padding-top: 15px; 
                border-top: 1px solid #333; 
                font-size: 12px; 
                text-align: center; 
            }
            .signature-line {
                margin-top: 5px;
                border-top: 1px solid #333;
                display: inline-block;
                padding-top: 5px;
                min-width: 200px;
            }
            @page { 
                size: A4; 
                margin: 1.5cm; 
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>گزارش تحلیل سفارش تولید</h2>
            <p><strong>${businessInfo.name}</strong></p>
            <p>شماره سفارش: ${orderInfo.code} | تاریخ: ${orderInfo.date}</p>
        </div>
        
        <div class="summary">
            <div><strong>${partsList.length}</strong>کل اقلام</div>
            <div><strong>${orderInfo.totalMissingParts}</strong>اقلام کسری</div>
            <div><strong>${partsList.length - orderInfo.totalMissingParts}</strong>اقلام موجود</div>
            <div><strong>${orderInfo.totalQuantity}</strong>کل دستگاه‌ها</div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>کد کالا</th>
                    <th>نام کالا</th>
                    <th>موجودی فعلی</th>
                    <th>مورد نیاز</th>
                    <th>کسری/اضافه</th>
                    <th>وضعیت</th>
                    <th>تامین‌کننده</th>
                </tr>
            </thead>
            <tbody>`;
    
    partsList.forEach((part, index) => {
        content += `
                <tr class="${part.statusClass}">
                    <td>${index + 1}</td>
                    <td>${part.code}</td>
                    <td>${part.name}</td>
                    <td>${part.currentStock}</td>
                    <td>${part.needed}</td>
                    <td>${part.shortage > 0 ? part.shortage : (part.shortage < 0 ? '+' + Math.abs(part.shortage) : '0')}</td>
                    <td>${part.status}</td>
                    <td>${part.supplierName}</td>
                </tr>`;
    });
    
    content += `
            </tbody>
        </table>
        
        <div class="conclusion">
            <p><strong>نتیجه‌گیری:</strong></p>
            <p>• تعداد ${orderInfo.totalMissingParts} قلم کالا نیاز به تامین دارد</p>
            <p>• وضعیت کلی سفارش: ${orderInfo.totalMissingParts == 0 ? 'آماده تولید' : 'نیاز به تامین'}</p>
        </div>
        
        <div style="margin-top: 30px; display: flex; justify-content: space-between;">
            <p>تاریخ بررسی: ${new Date().toLocaleDateString('fa-IR')}</p>
            <p>امضا مدیر: <span class="signature-line"></span></p>
        </div>
        
        <div class="footer">
            <p>طراحی و توسعه: <a href="https://alizadehx.ir">alizadehx.ir</a></p>
        </div>
    </body>
    </html>`;
    
    const newWindow = window.open('', '_blank');
    newWindow.document.write(content);
    newWindow.document.close();
    
    // نمایش اعلان موفقیت
    Swal.fire({
        icon: 'success',
        title: 'گزارش آماده شد',
        text: 'گزارش مدیریت در پنجره جدید باز شد.',
        confirmButtonText: 'متوجه شدم'
    });
    
    // تاخیر برای اطمینان از لود شدن صفحه
    setTimeout(() => {
        newWindow.print();
    }, 500);
}

// اضافه کردن event listener به دکمه‌ها
document.addEventListener('DOMContentLoaded', function() {
    // دکمه گزارش تامین‌کننده
    document.getElementById('btnSupplierReport').addEventListener('click', function() {
        printSupplierReport();
    });
    
    // دکمه گزارش انباردار
    document.getElementById('btnWarehouseReport').addEventListener('click', function() {
        printWarehouseReport();
    });
    
    // دکمه گزارش مدیریت
    document.getElementById('btnManagerReport').addEventListener('click', function() {
        printManagerReport();
    });
    
    // افکت‌های انیمیشن به المان‌ها
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('animate__animated', 'animate__fadeIn');
    });
});
</script>
<style>
@media print {
    body { background: #fff !important; color: #000 !important; }
    .container, .row, .card, .card-body, .card-header { box-shadow: none !important; border: none !important; }
    .d-print-none { display: none !important; }
    .d-print-table-cell { display: table-cell !important; }
    .print-header, .print-footer { display: block !important; }
    .print-table { page-break-inside: auto; }
    tr { page-break-inside: avoid; page-break-after: auto; }
    @page { size: A4 portrait; margin: 1.5cm; }
    footer { display: none !important; }
    /* شماره صفحه */
    body:after {
        content: "صفحه " counter(page);
        position: fixed;
        left: 0;
        bottom: 0.5cm;
        width: 100vw;
        text-align: left;
        font-size: 0.9rem;
        color: #888;
        padding-left: 1.5cm;
    }
}
.d-print-table-cell { display: none; }
</style>
</body>
</html>
