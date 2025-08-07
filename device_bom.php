<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 30);
ini_set('memory_limit', '128M');
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی و ایجاد جدول device_bom اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'device_bom'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE device_bom (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        item_code VARCHAR(100) NOT NULL,
        item_name VARCHAR(255),
        quantity_needed INT DEFAULT 1,
        supplier_id INT,
        notes TEXT,
        INDEX idx_device_id (device_id),
        INDEX idx_item_code (item_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول device_bom: ' . $conn->error);
    }
}

// اطمینان از وجود ستون‌های مورد نیاز
$columns_check = [
    'item_name' => "ALTER TABLE device_bom ADD COLUMN item_name VARCHAR(255) NULL",
    'quantity_needed' => "ALTER TABLE device_bom ADD COLUMN quantity_needed INT DEFAULT 1",
    'supplier_id' => "ALTER TABLE device_bom ADD COLUMN supplier_id INT NULL"
];

foreach ($columns_check as $column => $sql) {
    $res = $conn->query("SHOW COLUMNS FROM device_bom LIKE '$column'");
    if ($res && $res->num_rows === 0) {
        $conn->query($sql);
    }
}

$device_id = clean($_GET['id'] ?? '');
if (!$device_id) {
    header('Location: devices.php');
    exit;
}

// دریافت اطلاعات دستگاه
$stmt = $conn->prepare("SELECT device_name FROM devices WHERE device_id = ? LIMIT 1");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$device) {
    header('Location: devices.php');
    exit;
}

// افزودن کالا به BOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_bom') {
        $inventory_id = intval(clean($_POST['inventory_id']));
        $stmt = $conn->prepare("SELECT inventory_code, item_name FROM inventory WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $inventory_id);
        $stmt->execute();
        $inventory_item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($inventory_item) {
            $check = $conn->prepare("SELECT 1 FROM device_bom WHERE device_id = ? AND CONVERT(item_code USING utf8mb4) = CONVERT(? USING utf8mb4) LIMIT 1");
            $check->bind_param("is", $device_id, $inventory_item['inventory_code']);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) {
                $insert = $conn->prepare("INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed) VALUES (?, ?, ?, 1)");
                $insert->bind_param("iss", $device_id, $inventory_item['inventory_code'], $inventory_item['item_name']);
                if (!$insert->execute()) {
                    die('خطا در افزودن به BOM: ' . $insert->error);
                }
                $insert->close();
            }
            $check->close();
        }
        header("Location: device_bom.php?id=$device_id&msg=added");
        exit;
    } elseif ($_POST['action'] === 'update_bom') {
        $quantities = $_POST['quantities'] ?? [];
        $stmt = $conn->prepare("UPDATE device_bom SET quantity_needed = ? WHERE bom_id = ? AND device_id = ?");
        foreach ($quantities as $bom_id => $quantity) {
            $qty = (int)$quantity;
            $stmt->bind_param("iii", $qty, $bom_id, $device_id);
            $stmt->execute();
        }
        $stmt->close();
        header("Location: device_bom.php?id=$device_id&msg=updated");
        exit;
    } elseif ($_POST['action'] === 'delete_from_bom') {
        $bom_id = clean($_POST['bom_id']);
        $stmt = $conn->prepare("DELETE FROM device_bom WHERE bom_id = ? AND device_id = ?");
        $stmt->bind_param("ii", $bom_id, $device_id);
        $stmt->execute();
        $stmt->close();
        header("Location: device_bom.php?id=$device_id&msg=deleted");
        exit;
    }
}

// دریافت لیست قطعات دستگاه
$bom_items = [];
$stmt = $conn->prepare("
    SELECT b.bom_id, b.item_code, b.item_name, b.quantity_needed, s.supplier_name 
    FROM device_bom b 
    LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id 
    WHERE b.device_id = ? 
    ORDER BY b.item_code
");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bom_items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لیست قطعات دستگاه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-card-list"></i> لیست قطعات دستگاه: <?= htmlspecialchars($device['device_name']) ?></h2>
        <a href="devices.php" class="btn btn-secondary">
            <i class="bi bi-arrow-right"></i> بازگشت به لیست دستگاه‌ها
        </a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php 
        $messages = [
            'updated' => 'لیست قطعات با موفقیت به‌روزرسانی شد.',
            'added' => 'کالا با موفقیت به BOM اضافه شد.',
            'deleted' => 'کالا با موفقیت از BOM حذف شد.'
        ];
        $msg_type = 'success';
        ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $messages[$_GET['msg']] ?? 'عملیات موفق' ?></div>
    <?php endif; ?>

    <!-- Search and Add from Inventory -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-search"></i> افزودن کالا از انبار</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="id" value="<?= $device_id ?>">
                <div class="col-md-5">
                    <label class="form-label">کد یا نام کالا</label>
                    <input type="text" name="search_term" class="form-control" value="<?= htmlspecialchars($_GET['search_term'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">جستجو</button>
                </div>
            </form>

            <?php
            if (isset($_GET['search_term']) && !empty($_GET['search_term'])) {
                $search_term = clean($_GET['search_term']);
                $search_query = "%$search_term%";
                
                $stmt = $conn->prepare("
                    SELECT * FROM inventory 
                    WHERE inventory_code LIKE ? OR item_name LIKE ?
                    ORDER BY item_name 
                    LIMIT 10
                ");
                $stmt->bind_param("ss", $search_query, $search_query);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="table-responsive mt-3">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>کد کالا</th>
                                    <th>نام کالا</th>
                                    <th>موجودی</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>
                            <td>' . htmlspecialchars($row['inventory_code']) . '</td>
                            <td>' . htmlspecialchars($row['item_name']) . '</td>
                            <td>' . ($row['current_inventory'] ?? 0) . '</td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="inventory_id" value="' . $row['id'] . '">
                                    <input type="hidden" name="action" value="add_to_bom">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-plus-lg"></i> افزودن
                                    </button>
                                </form>
                            </td>
                        </tr>';
                    }
                    
                    echo '</tbody></table></div>';
                } else {
                    echo '<div class="alert alert-info mt-3">هیچ کالایی یافت نشد.</div>';
                }
                $stmt->close();
            }
            ?>
        </div>
    </div>

    <!-- BOM Items List -->
    <form method="POST">
        <input type="hidden" name="action" value="update_bom">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-list-check"></i> قطعات تعریف شده</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>کد قطعه</th>
                            <th>نام قطعه</th>
                            <th style="width: 150px;">تعداد مورد نیاز</th>
                            <th>تامین‌کننده</th>
                            <th style="width: 100px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bom_items)): ?>
                            <tr>
                                <td colspan="5" class="text-center">هیچ قطعه‌ای برای این دستگاه تعریف نشده است.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bom_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_code']) ?></td>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td>
                                        <input type="number" name="quantities[<?= $item['bom_id'] ?>]" 
                                               class="form-control form-control-sm" 
                                               value="<?= htmlspecialchars($item['quantity_needed']) ?>" 
                                               min="1" required>
                                    </td>
                                    <td><?= htmlspecialchars($item['supplier_name'] ?? 'تعیین نشده') ?></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $item['bom_id'] ?>)">
                                            <i class="bi bi-trash"></i> حذف
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($bom_items)): ?>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> ذخیره تغییرات تعداد
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Hidden Delete Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete_from_bom">
        <input type="hidden" name="bom_id" id="bom_id_to_delete">
    </form>
</div>

<script>
function confirmDelete(bomId) {
    if (confirm('آیا از حذف این قطعه از لیست مطمئن هستید؟')) {
        document.getElementById('bom_id_to_delete').value = bomId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
