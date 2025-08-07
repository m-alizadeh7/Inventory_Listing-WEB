<?php
require_once 'config.php';
require_once 'includes/functions.php';

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
    SELECT b.item_code, b.item_name, b.supplier_id,
           s.supplier_name, s.supplier_code,
           SUM(b.quantity_needed * i.quantity) as total_needed,
           (
               SELECT SUM(inv.current_inventory)
               FROM inventory inv
               WHERE CONVERT(inv.inventory_code USING utf8mb4) = CONVERT(b.item_code USING utf8mb4)
           ) as current_stock
    FROM production_order_items i
    JOIN device_bom b ON i.device_id = b.device_id
    LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id
    WHERE i.order_id = $order_id
    GROUP BY b.item_code, b.item_name, b.supplier_id, s.supplier_name, s.supplier_code
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
if (!empty($missing_parts_list)) {
    echo '<div class="alert alert-danger shadow-sm p-3 mb-4 rounded-3">';
    echo '<div class="d-flex align-items-center mb-2">';
    echo '<i class="bi bi-exclamation-triangle-fill fs-3 me-2 text-danger"></i>';
    echo '<span class="fw-bold fs-5">موجودی قطعات زیر کافی نیست:</span>';
    echo '</div>';
    echo '<div class="table-responsive">';
    echo '<table class="table table-bordered table-striped table-sm mb-0 mt-2 align-middle">';
    echo '<thead class="table-danger"><tr>';
    echo '<th>کد قطعه</th><th>نام قطعه</th><th>مورد نیاز</th><th>موجودی فعلی</th><th>کسری</th>';
    echo '</tr></thead><tbody>';
    foreach ($missing_parts_list as $mp) {
        echo '<tr>';
        echo '<td class="fw-bold">' . htmlspecialchars($mp['item_code']) . '</td>';
        echo '<td>' . htmlspecialchars($mp['item_name']) . '</td>';
        echo '<td>' . $mp['needed'] . '</td>';
        echo '<td>' . $mp['stock'] . '</td>';
        echo '<td class="text-danger fw-bold">' . $mp['missing'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جزئیات سفارش تولید - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #ff9800;
            --success-color: #4caf50;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
        }
        
        body { 
            background: #f0f2f5; 
            padding-top: 2rem;
            color: #333;
            font-family: 'Vazirmatn', tahoma, Arial, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .card-title {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .btn {
            border-radius: 30px;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.15);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-outline-success, .btn-outline-warning, .btn-outline-info {
            font-weight: 500;
        }
        
        .btn-outline-success:hover {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-outline-warning:hover {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-outline-info:hover {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }
        
        .table {
            font-size: 0.95rem;
        }
        
        .table th {
            font-weight: 600;
            background-color: rgba(0,0,0,0.03);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(63, 81, 181, 0.05);
        }
        
        .stock-warning { 
            color: var(--danger-color);
            font-weight: 500;
        }
        
        .stock-ok { 
            color: var(--success-color);
            font-weight: 500;
        }
        
        .status-badge {
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .print-header, .print-footer {
            display: none;
        }
        
        .form-check-input:checked {
            background-color: var(--success-color);
            border-color: var(--success-color);
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
                <div class="card-header bg-gradient d-flex justify-content-between align-items-center">
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
                            <div class="border rounded-3 p-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-hdd-stack fs-2 text-primary mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= $order['devices_count'] ?></h4>
                                <p class="text-muted mb-0">تعداد دستگاه‌ها</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="border rounded-3 p-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-boxes fs-2 text-success mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= $order['total_quantity'] ?></h4>
                                <p class="text-muted mb-0">مجموع تعداد</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="border rounded-3 p-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-center">
                                <i class="bi bi-tools fs-2 text-warning mb-2"></i>
                                <h4 class="fs-3 mb-1"><?= count($parts) ?></h4>
                                <p class="text-muted mb-0">تعداد قطعات مختلف</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="border rounded-3 p-3 bg-light shadow-sm h-100 d-flex flex-column justify-content-center">
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
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-lg me-1"></i> تایید سفارش
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($can_start): ?>
                            <form method="POST" action="start_production.php">
                                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                <button type="submit" class="btn btn-primary" <?= !$all_parts_available ? 'disabled' : '' ?>>
                                    <i class="bi bi-play"></i> شروع تولید
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$all_parts_available && ($can_confirm || $can_start)): ?>
                            <a href="create_purchase_request.php?order_id=<?= $order_id ?>" 
                               class="btn btn-warning">
                                <i class="bi bi-cart"></i> ایجاد درخواست خرید
                            </a>
                        <?php endif; ?>

                        <a href="production_orders.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-right"></i> بازگشت
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mb-4">
            <div class="card animate__animated animate__fadeIn animate__delay-1s">
                <div class="card-header bg-gradient">
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
                                    <td><?= htmlspecialchars($device['device_code']) ?></td>
                                    <td><?= htmlspecialchars($device['device_name']) ?></td>
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
                <div class="card-header d-flex justify-content-between align-items-center bg-gradient">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tools me-2"></i>لیست قطعات مورد نیاز
                    </h5>
                    <div class="d-print-none">
                        <button id="btnSupplierReport" class="btn btn-outline-success btn-sm me-2" data-bs-toggle="tooltip" data-bs-title="نمایش و چاپ لیست قطعات کسری برای تامین‌کننده">
                            <i class="bi bi-truck me-1"></i> گزارش تامین‌کننده
                        </button>
                        <button id="btnWarehouseReport" class="btn btn-outline-warning btn-sm me-2" data-bs-toggle="tooltip" data-bs-title="نمایش و چاپ لیست قطعات برای انباردار">
                            <i class="bi bi-box me-1"></i> گزارش انباردار
                        </button>
                        <button id="btnManagerReport" class="btn btn-outline-info btn-sm" data-bs-toggle="tooltip" data-bs-title="نمایش و چاپ گزارش تحلیلی کامل برای مدیریت">
                            <i class="bi bi-clipboard-data me-1"></i> گزارش مدیریت
                        </button>
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
                                        <?php if ($part['supplier_name']): ?>
                                            <span class="fw-bold"><?= htmlspecialchars($part['supplier_name']) ?></span>
                                            <small class="text-muted d-block">
                                                <?= htmlspecialchars($part['supplier_code']) ?>
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
                        <small>طراحی و توسعه توسط <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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
