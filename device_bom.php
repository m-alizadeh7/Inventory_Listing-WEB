<?php
require_once 'config.php';
require_once 'includes/functions.php';

// اطمینان از وجود ستون‌های مورد نیاز در جدول device_bom
$conn->query("ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NULL");
$conn->query("ALTER TABLE device_bom ADD COLUMN quantity_needed INT NULL");
$conn->query("ALTER TABLE device_bom ADD COLUMN supplier_id INT NULL");

$device_id = clean($_GET['id'] ?? '');
if (!$device_id) {
    header('Location: devices.php');
    exit;
}

// دریافت اطلاعات دستگاه
$device = $conn->query("SELECT * FROM devices WHERE device_id = $device_id")->fetch_assoc();
if (!$device) {
    header('Location: devices.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_bom'])) {
        // حذف قطعات قبلی
        $conn->query("DELETE FROM device_bom WHERE device_id = $device_id");
        
        // افزودن قطعات جدید
        $items = $_POST['items'] ?? [];
        foreach ($items as $item) {
            $item_code = clean($item['code']);
            $item_name = clean($item['name']);
            $quantity = (int)$item['quantity'];
            $supplier_id = (int)($item['supplier_id'] ?? 0);
            
            if ($item_code && $item_name && $quantity > 0) {
                $sql = "INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed, supplier_id) 
                        VALUES ($device_id, '$item_code', '$item_name', $quantity, " . 
                        ($supplier_id > 0 ? $supplier_id : "NULL") . ")";
                $conn->query($sql);
            }
        }
        
        header("Location: device_bom.php?id=$device_id&msg=saved");
        exit;
    }
}

// دریافت لیست قطعات دستگاه
$bom_items = [];
$result = $conn->query("
    SELECT b.*, s.supplier_name 
    FROM device_bom b 
    LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id 
    WHERE b.device_id = $device_id 
    ORDER BY b.item_code
");
while ($row = $result->fetch_assoc()) {
    $bom_items[] = $row;
}

// دریافت لیست تامین‌کنندگان
$suppliers = [];
$result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name");
while ($row = $result->fetch_assoc()) {
    $suppliers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لیست قطعات دستگاه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 لیست قطعات دستگاه <?= htmlspecialchars($device['device_name']) ?></h2>
        <div>
            <button type="button" class="btn btn-success" onclick="addRow()">➕ افزودن قطعه</button>
            <a href="devices.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
        <div class="alert alert-success">لیست قطعات با موفقیت ذخیره شد.</div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>کد قطعه</th>
                        <th>نام قطعه</th>
                        <th>تعداد مورد نیاز</th>
                        <th>تامین‌کننده</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="bomTableBody">
                    <?php foreach ($bom_items as $item): ?>
                        <tr>
                            <td>
                                <input type="text" name="items[<?= $item['bom_id'] ?>][code]" 
                                       class="form-control" value="<?= htmlspecialchars($item['item_code']) ?>" required>
                            </td>
                            <td>
                                <input type="text" name="items[<?= $item['bom_id'] ?>][name]" 
                                       class="form-control" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                            </td>
                            <td>
                                <input type="number" name="items[<?= $item['bom_id'] ?>][quantity]" 
                                       class="form-control" value="<?= $item['quantity_needed'] ?>" 
                                       min="1" required>
                            </td>
                            <td>
                                <select name="items[<?= $item['bom_id'] ?>][supplier_id]" class="form-select">
                                    <option value="">انتخاب تامین‌کننده</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>" 
                                            <?= $supplier['supplier_id'] == $item['supplier_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($supplier['supplier_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-grid gap-2 col-md-6 mx-auto mt-4">
            <button type="submit" name="save_bom" class="btn btn-primary">ذخیره لیست قطعات</button>
        </div>
    </form>
</div>

<script>
let rowCounter = <?= count($bom_items) ?>;

function addRow() {
    const tbody = document.getElementById('bomTableBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="text" name="items[new_${rowCounter}][code]" class="form-control" required>
        </td>
        <td>
            <input type="text" name="items[new_${rowCounter}][name]" class="form-control" required>
        </td>
        <td>
            <input type="number" name="items[new_${rowCounter}][quantity]" class="form-control" min="1" required>
        </td>
        <td>
            <select name="items[new_${rowCounter}][supplier_id]" class="form-select">
                <option value="">انتخاب تامین‌کننده</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['supplier_id'] ?>">
                        <?= htmlspecialchars($supplier['supplier_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">حذف</button>
        </td>
    `;
    tbody.appendChild(tr);
    rowCounter++;
}

function removeRow(button) {
    button.closest('tr').remove();
}

function validateForm() {
    const tbody = document.getElementById('bomTableBody');
    if (tbody.children.length === 0) {
        alert('لطفاً حداقل یک قطعه را وارد کنید.');
        return false;
    }
    return true;
}

// اگر جدول خالی است، یک ردیف اضافه کن
if (document.getElementById('bomTableBody').children.length === 0) {
    addRow();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
