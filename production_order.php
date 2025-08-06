<?php
require_once 'config.php';
require_once 'includes/functions.php';

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
               SELECT SUM(current_inventory)
               FROM inventory_records
               WHERE item_code = b.item_code
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

// بررسی امکان تایید سفارش
$can_confirm = $order['status'] === 'draft';
$can_start = $order['status'] === 'confirmed';
$all_parts_available = true;
$total_missing_parts = 0;

foreach ($parts as $part) {
    $stock = (int)($part['current_stock'] ?? 0);
    $needed = (int)$part['total_needed'];
    if ($stock < $needed) {
        $all_parts_available = false;
        $total_missing_parts++;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جزئیات سفارش تولید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .stock-warning { color: #dc3545; }
        .stock-ok { color: #198754; }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">🏭 سفارش شماره: <?= htmlspecialchars($order['order_code']) ?></h3>
                        <small class="text-muted">
                            تاریخ ثبت: <?= gregorianToJalali($order['created_at']) ?>
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
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h4><?= $order['devices_count'] ?></h4>
                            <p class="text-muted">تعداد دستگاه‌ها</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4><?= $order['total_quantity'] ?></h4>
                            <p class="text-muted">مجموع تعداد</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4><?= count($parts) ?></h4>
                            <p class="text-muted">تعداد قطعات مختلف</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h4><?= $total_missing_parts ?></h4>
                            <p class="text-muted">قطعات ناموجود</p>
                        </div>
                    </div>

                    <?php if ($order['notes']): ?>
                        <hr>
                        <div class="alert alert-secondary">
                            <i class="bi bi-info-circle"></i> <?= nl2br(htmlspecialchars($order['notes'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-end gap-2">
                        <?php if ($can_confirm): ?>
                            <form method="POST" action="confirm_production_order.php">
                                <input type="hidden" name="order_id" value="<?= $order_id ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-lg"></i> تایید سفارش
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">📦 لیست دستگاه‌ها</h5>
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
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">🔧 لیست قطعات مورد نیاز</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>کد قطعه</th>
                                <th>نام قطعه</th>
                                <th>تامین‌کننده</th>
                                <th>تعداد مورد نیاز</th>
                                <th>موجودی فعلی</th>
                                <th>وضعیت</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parts as $part): 
                                $stock = (int)($part['current_stock'] ?? 0);
                                $needed = (int)$part['total_needed'];
                                $status = $stock >= $needed ? 'ok' : 'warning';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($part['item_code']) ?></td>
                                    <td><?= htmlspecialchars($part['item_name']) ?></td>
                                    <td>
                                        <?php if ($part['supplier_name']): ?>
                                            <?= htmlspecialchars($part['supplier_name']) ?>
                                            <small class="text-muted d-block">
                                                <?= htmlspecialchars($part['supplier_code']) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-warning">
                                                <i class="bi bi-exclamation-triangle"></i> بدون تامین‌کننده
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $needed ?></td>
                                    <td><?= $stock ?></td>
                                    <td>
                                        <?php if ($status === 'ok'): ?>
                                            <span class="stock-ok">
                                                <i class="bi bi-check-circle"></i> موجود
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-warning">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                کسری: <?= $needed - $stock ?>
                                            </span>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
