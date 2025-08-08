<?php
require_once 'config.php';
$res = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory (
        id INT AUTO_INCREMENT,
        `row_number` INT NULL,
        inventory_code VARCHAR(50) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        unit VARCHAR(50) NULL,
        min_inventory INT NULL,
        supplier VARCHAR(100) NULL,
        current_inventory DOUBLE NULL,
        required DOUBLE NULL,
        notes VARCHAR(255) NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory: ' . $conn->error);
    }
}
$success = false;
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        // حذف رکوردهای جدول inventory به جای truncate برای جلوگیری از خطای کلید خارجی
        $conn->query("DELETE FROM `inventory`");

        // خواندن فایل CSV
        $file = $_FILES['csv_file']['tmp_name'];
        $rows = array_map('str_getcsv', file($file));
        array_shift($rows); // حذف هدر

        $stmt = $conn->prepare("INSERT INTO `inventory` (`row_number`, `inventory_code`, `item_name`, `unit`, `min_inventory`, `supplier`, `current_inventory`, `required`, `notes`) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisdds", $row_number, $inventory_code, $item_name, $unit, $min_inventory, $supplier, $current_inventory, $required, $notes);

        foreach ($rows as $row) {
            // اعتبارسنجی داده‌ها
            if (count($row) < 9 || empty($row[1]) || empty($row[2])) {
                continue; // رد ردیف‌های ناقص یا بدون کد/نام کالا
            }
            $row_number = intval($row[0]);
            $inventory_code = $row[1];
            $item_name = $row[2];
            $unit = $row[3];
            $supplier = isset($row[5]) ? trim($row[5]) : '';
            // min_inventory واقعی ستون 6 است (در برخی ردیف‌ها ممکن است خالی یا "توقف استفاده" باشد)
            $min_inventory = (isset($row[6]) && is_numeric($row[6])) ? intval($row[6]) : null;
            $current_inventory = isset($row[7]) && is_numeric($row[7]) ? floatval($row[7]) : null;
            $required = isset($row[8]) && is_numeric($row[8]) ? floatval($row[8]) : null;
            // اگر هر یک از ستون‌های 6، 7 یا 8 مقدار "توقف استفاده" داشت، یادداشت ثبت شود
            $notes = (isset($row[6]) && trim($row[6]) === 'توقف استفاده') || (isset($row[7]) && trim($row[7]) === 'توقف استفاده') || (isset($row[8]) && trim($row[8]) === 'توقف استفاده') ? 'توقف استفاده' : '';
            $stmt->execute();
        }
        $stmt->close();
        $success = true;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
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
    <?php elseif (!empty($error_message)): ?>
        <div class="alert alert-danger">خطا در وارد کردن فایل: <?= htmlspecialchars($error_message) ?></div>
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