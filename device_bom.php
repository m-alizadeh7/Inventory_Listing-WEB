<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی و ایجاد جدول inventory اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        row_number INT NOT NULL,
        inventory_code VARCHAR(50) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        unit VARCHAR(50),
        min_inventory INT,
        supplier VARCHAR(255),
        current_inventory FLOAT,
        required FLOAT,
        notes TEXT,
        last_updated DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول devices اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'devices'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE devices (
        device_id INT AUTO_INCREMENT PRIMARY KEY,
        device_name VARCHAR(255) NOT NULL,
        device_code VARCHAR(100),
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول devices: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول device_bom اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'device_bom'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE device_bom (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        item_code VARCHAR(100) NOT NULL,
        item_name VARCHAR(255),
        quantity_needed INT,
        supplier_id INT,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول device_bom: ' . $conn->error);
    }
}

// اطمینان از وجود ستون‌های مورد نیاز در جدول device_bom با بررسی وجود قبلی
$columns_to_add = ['item_name', 'quantity_needed', 'supplier_id'];
foreach ($columns_to_add as $column) {
    $res = $conn->query("SHOW COLUMNS FROM device_bom LIKE '$column'");
    if ($res && $res->num_rows === 0) {
        if ($column === 'item_name') {
            $conn->query("ALTER TABLE device_bom ADD COLUMN $column VARCHAR(255) NULL");
        } elseif ($column === 'quantity_needed') {
            $conn->query("ALTER TABLE device_bom ADD COLUMN $column INT NULL");
        } elseif ($column === 'supplier_id') {
            $conn->query("ALTER TABLE device_bom ADD COLUMN $column INT NULL");
        }
    }
}

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

// افزودن کالا به BOM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_bom') {
    $inventory_id = clean($_POST['inventory_id']);
    
    // دریافت اطلاعات کالا
    $inventory_item = $conn->query("SELECT * FROM inventory WHERE id = $inventory_id")->fetch_assoc();
    if ($inventory_item) {
        // بررسی تکراری نبودن کالا در BOM
        $check = $conn->query("SELECT bom_id FROM device_bom WHERE device_id = $device_id AND item_code = '" . $inventory_item['inventory_code'] . "'");
        if ($check->num_rows === 0) {
            // افزودن به BOM
            $stmt = $conn->prepare("INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("iss", $device_id, $inventory_item['inventory_code'], $inventory_item['item_name']);
            $stmt->execute();
            $stmt->close();
            
            header("Location: device_bom.php?id=$device_id&msg=added");
            exit;
        }
    }
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
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'added'): ?>
        <div class="alert alert-success">کالا با موفقیت به BOM اضافه شد.</div>
    <?php endif; ?>

    <!-- جستجو در کالاهای انبار -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">🔍 افزودن کالا از انبار</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="id" value="<?= $device_id ?>">
                <div class="col-md-4">
                    <label class="form-label">کد کالا</label>
                    <input type="text" name="search_code" class="form-control" value="<?= htmlspecialchars($_GET['search_code'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">نام کالا</label>
                    <input type="text" name="search_name" class="form-control" value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">جستجو</button>
                </div>
            </form>

            <?php
            // نمایش نتایج جستجو
            if (isset($_GET['search_code']) || isset($_GET['search_name'])) {
                $search_code = clean($_GET['search_code'] ?? '');
                $search_name = clean($_GET['search_name'] ?? '');
                
                $where = [];
                $params = [];
                $types = '';
                
                if ($search_code) {
                    $where[] = "inventory_code LIKE ?";
                    $params[] = "%$search_code%";
                    $types .= 's';
                }
                if ($search_name) {
                    $where[] = "item_name LIKE ?";
                    $params[] = "%$search_name%";
                    $types .= 's';
                }
                
                $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $conn->prepare("
                    SELECT * FROM inventory 
                    $where_clause 
                    ORDER BY item_name 
                    LIMIT 10
                ");
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="table-responsive mt-3">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>کد کالا</th>
                                    <th>نام کالا</th>
                                    <th>واحد</th>
                                    <th>موجودی</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>';
                    
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>
                            <td>' . htmlspecialchars($row['inventory_code']) . '</td>
                            <td>' . htmlspecialchars($row['item_name']) . '</td>
                            <td>' . htmlspecialchars($row['unit'] ?? '') . '</td>
                            <td>' . ($row['current_inventory'] ?? 0) . '</td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="inventory_id" value="' . $row['id'] . '">
                                    <input type="hidden" name="action" value="add_to_bom">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="bi bi-plus-lg"></i> افزودن به BOM
                                    </button>
                                </form>
                            </td>
                        </tr>';
                    }
                    
                    echo '</tbody></table></div>';
                    
                    if ($result->num_rows === 10) {
                        echo '<div class="text-muted mt-2">نمایش 10 نتیجه اول. لطفاً جستجو را دقیق‌تر کنید.</div>';
                    }
                } else {
                    echo '<div class="alert alert-info mt-3">هیچ کالایی یافت نشد.</div>';
                }
                
                $stmt->close();
            }
            ?>
        </div>
    </div>

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
                        <?php
                        $bom_id = isset($item['bom_id']) ? $item['bom_id'] : uniqid('bom_');
                        $item_code = isset($item['item_code']) ? htmlspecialchars($item['item_code']) : '';
                        $item_name = isset($item['item_name']) ? htmlspecialchars($item['item_name']) : '';
                        $quantity_needed = isset($item['quantity_needed']) ? $item['quantity_needed'] : '';
                        $supplier_id = isset($item['supplier_id']) ? $item['supplier_id'] : '';
                        ?>
                        <tr>
                            <td>
                                <input type="text" name="items[<?= $bom_id ?>][code]" 
                                       class="form-control" value="<?= $item_code ?>" required>
                            </td>
                            <td>
                                <input type="text" name="items[<?= $bom_id ?>][name]" 
                                       class="form-control" value="<?= $item_name ?>" required>
                            </td>
                            <td>
                                <input type="number" name="items[<?= $bom_id ?>][quantity]" 
                                       class="form-control" value="<?= $quantity_needed ?>" 
                                       min="1" required>
                            </td>
                            <td>
                                <select name="items[<?= $bom_id ?>][supplier_id]" class="form-select">
                                    <option value="">انتخاب تامین‌کننده</option>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['supplier_id'] ?>" 
                                            <?= $supplier['supplier_id'] == $supplier_id ? 'selected' : '' ?>>
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
