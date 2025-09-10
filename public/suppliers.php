<?php
require_once '../config/config.php';
require_once '../app/includes/functions.php';
// اطمینان از وجود جداول اصلی و ستون bom_id
$conn->query("CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(50),
    supplier_name VARCHAR(255),
    address TEXT,
    contact_person VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS device_bom (
    device_id INT,
    supplier_id INT,
    item_code VARCHAR(50),
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
// اطمینان از وجود ستون supplier_id در device_bom
$res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'supplier_id'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE device_bom ADD COLUMN supplier_id INT");
}
$res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'bom_id'");
if ($res && $res->num_rows === 0) {
    // بررسی وجود ستون AUTO_INCREMENT دیگر
    $auto = $conn->query("SHOW COLUMNS FROM device_bom WHERE Extra LIKE '%auto_increment%'");
    if ($auto && $auto->num_rows === 0) {
        $conn->query("ALTER TABLE device_bom ADD COLUMN bom_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
    } else {
        $conn->query("ALTER TABLE device_bom ADD COLUMN bom_id INT NULL FIRST");
    }
}

// حذف تامین‌کننده
if (isset($_POST['delete_supplier'])) {
    $supplier_id = clean($_POST['supplier_id']);
    // بررسی استفاده از تامین‌کننده در BOM
    $check = $conn->query("SELECT COUNT(*) as used FROM device_bom WHERE supplier_id = $supplier_id")->fetch_assoc();
    
    if ($check['used'] > 0) {
        $error = 'این تامین‌کننده در لیست قطعات استفاده شده و قابل حذف نیست.';
    } else {
        $conn->query("DELETE FROM suppliers WHERE supplier_id = $supplier_id");
        header('Location: suppliers.php?msg=deleted');
        exit;
    }
}

// دریافت لیست تامین‌کنندگان با آمار استفاده
$result = $conn->query("
    SELECT s.*, 
           COUNT(DISTINCT b.device_id) as devices_count,
           COUNT(b.bom_id) as parts_count
    FROM suppliers s
    LEFT JOIN device_bom b ON s.supplier_id = b.supplier_id
    GROUP BY s.supplier_id
    ORDER BY s.supplier_name
");

$suppliers = [];
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت تامین‌کنندگان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .supplier-info:hover { background-color: #f8f9fa; }
        .badge { font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 مدیریت تامین‌کنندگان</h2>
        <div>
            <a href="new_supplier.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> افزودن تامین‌کننده جدید
            </a>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">تامین‌کننده جدید با موفقیت اضافه شد.</div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning">تامین‌کننده با موفقیت حذف شد.</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-info">اطلاعات تامین‌کننده بروزرسانی شد.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if (empty($suppliers)): ?>
        <div class="alert alert-info">هیچ تامین‌کننده‌ای ثبت نشده است.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>کد تامین‌کننده</th>
                        <th>نام شرکت</th>
                        <th>شخص رابط</th>
                        <th>اطلاعات تماس</th>
                        <th>آمار</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr class="supplier-info">
                            <td><?= htmlspecialchars($supplier['supplier_code']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($supplier['supplier_name']) ?></strong>
                                <?php if ($supplier['address']): ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($supplier['address']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($supplier['contact_person'] ?? '-') ?>
                            </td>
                            <td>
                                <?php if ($supplier['phone']): ?>
                                    <div>📞 <?= htmlspecialchars($supplier['phone']) ?></div>
                                <?php endif; ?>
                                <?php if ($supplier['email']): ?>
                                    <div>📧 <?= htmlspecialchars($supplier['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?= $supplier['devices_count'] ?> دستگاه
                                </span>
                                <span class="badge bg-primary">
                                    <?= $supplier['parts_count'] ?> قطعه
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_supplier.php?id=<?= $supplier['supplier_id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> ویرایش
                                    </a>
                                    <a href="supplier_parts.php?id=<?= $supplier['supplier_id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="bi bi-box"></i> قطعات
                                    </a>
                                    <?php if ($supplier['parts_count'] == 0): ?>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('آیا از حذف این تامین‌کننده اطمینان دارید؟');">
                                            <input type="hidden" name="supplier_id" 
                                                   value="<?= $supplier['supplier_id'] ?>">
                                            <button type="submit" name="delete_supplier" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> حذف
                                            </button>
                                        </form>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
