
<?php
require_once __DIR__ . '/bootstrap.php';
// بررسی و ایجاد جدول inventory_records اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_records'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_records (
        record_id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NULL,
        part_id INT NULL,
        quantity INT NULL,
        date DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_records: ' . $conn->error);
    }
}

// افزودن ستون current_inventory اگر وجود ندارد
$res = $conn->query("SHOW COLUMNS FROM inventory_records LIKE 'current_inventory'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE inventory_records ADD COLUMN current_inventory INT NULL")) {
        die('خطا در افزودن ستون current_inventory: ' . $conn->error);
    }
}

$supplier_id = clean($_GET['id'] ?? '');
if (!$supplier_id) {
    header('Location: suppliers.php');
    exit;
}

// دریافت اطلاعات تامین‌کننده
$supplier = $conn->query("SELECT * FROM suppliers WHERE supplier_id = $supplier_id")->fetch_assoc();
if (!$supplier) {
    header('Location: suppliers.php');
    exit;
}

// دریافت لیست قطعات
$result = $conn->query("
    SELECT b.*, d.device_name, 
           (SELECT SUM(current_inventory) FROM inventory_records WHERE item_code = b.item_code) as stock
    FROM device_bom b
    JOIN devices d ON b.device_id = d.device_id
    WHERE b.supplier_id = $supplier_id
    ORDER BY b.item_code
");

$parts = [];
while ($row = $result->fetch_assoc()) {
    $parts[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قطعات تامین‌کننده</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .stock-warning { color: #dc3545; }
        .stock-ok { color: #198754; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">📦 قطعات تامین‌کننده</h2>
            <h5 class="text-muted"><?= htmlspecialchars($supplier['supplier_name']) ?></h5>
        </div>
        <a href="suppliers.php" class="btn btn-secondary">بازگشت</a>
    </div>

    <?php if (empty($parts)): ?>
        <div class="alert alert-info">هیچ قطعه‌ای برای این تامین‌کننده ثبت نشده است.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-light">
                <div class="row align-items-center">
                    <div class="col">
                        <strong>کد تامین‌کننده:</strong> <?= htmlspecialchars($supplier['supplier_code']) ?>
                    </div>
                    <div class="col">
                        <strong>تلفن:</strong> <?= htmlspecialchars($supplier['phone'] ?? '-') ?>
                    </div>
                    <div class="col">
                        <strong>ایمیل:</strong> <?= htmlspecialchars($supplier['email'] ?? '-') ?>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>کد قطعه</th>
                            <th>نام قطعه</th>
                            <th>دستگاه مرتبط</th>
                            <th>تعداد مورد نیاز</th>
                            <th>موجودی فعلی</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parts as $part): 
                            $stock = (int)($part['stock'] ?? 0);
                            $needed = (int)$part['quantity_needed'];
                            $status = $stock >= $needed ? 'ok' : 'warning';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($part['item_code']) ?></td>
                                <td><?= htmlspecialchars($part['item_name']) ?></td>
                                <td><?= htmlspecialchars($part['device_name']) ?></td>
                                <td><?= $part['quantity_needed'] ?></td>
                                <td><?= $stock ?></td>
                                <td>
                                    <?php if ($status === 'ok'): ?>
                                        <span class="stock-ok">
                                            <i class="bi bi-check-circle"></i> کافی
                                        </span>
                                    <?php else: ?>
                                        <span class="stock-warning">
                                            <i class="bi bi-exclamation-triangle"></i> نیاز به سفارش
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">📊 خلاصه وضعیت</h5>
            </div>
            <div class="card-body">
                <?php
                $total_parts = count($parts);
                $parts_needed = 0;
                foreach ($parts as $part) {
                    if (($part['stock'] ?? 0) < $part['quantity_needed']) {
                        $parts_needed++;
                    }
                }
                $percentage = $total_parts > 0 ? round(($parts_needed / $total_parts) * 100) : 0;
                ?>
                <div class="row text-center">
                    <div class="col-md-4">
                        <h3><?= $total_parts ?></h3>
                        <p class="text-muted">کل قطعات</p>
                    </div>
                    <div class="col-md-4">
                        <h3><?= $parts_needed ?></h3>
                        <p class="text-muted">نیاز به سفارش</p>
                    </div>
                    <div class="col-md-4">
                        <h3><?= $percentage ?>%</h3>
                        <p class="text-muted">درصد نیاز به تامین</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
