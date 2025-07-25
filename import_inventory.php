<?php
require_once 'config.php';
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // ریست جدول inventory
    $conn->query("TRUNCATE TABLE `inventory`");

    // خواندن فایل CSV
    $file = $_FILES['csv_file']['tmp_name'];
    $rows = array_map('str_getcsv', file($file));
    array_shift($rows); // حذف هدر

    $stmt = $conn->prepare("INSERT INTO `inventory` (`row_number`, `inventory_code`, `item_name`, `unit`, `min_inventory`, `supplier`, `current_inventory`, `required`, `notes`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisdds", $row_number, $inventory_code, $item_name, $unit, $min_inventory, $supplier, $current_inventory, $required, $notes);

    foreach ($rows as $row) {
        $row_number = intval($row[0]);
        $inventory_code = $row[1];
        $item_name = $row[2];
        $unit = $row[3];
        $min_inventory = $row[4] ? intval($row[4]) : null;
        $supplier = $row[5];
        $current_inventory = $row[7] ? floatval($row[7]) : null;
        $required = $row[8] ? floatval($row[8]) : null;
        $notes = $row[6] === 'توقف استفاده' ? 'توقف استفاده' : '';
        $stmt->execute();
    }
    $stmt->close();
    $success = true;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>وارد کردن لیست انبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">📥 وارد کردن لیست انبار</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">لیست انبار با موفقیت وارد شد!</div>
    <?php endif; ?>
    <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">فایل CSV</label>
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">آپلود و به‌روزرسانی</button>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </form>
</div>
</body>
</html>
<?php $conn->close(); ?>