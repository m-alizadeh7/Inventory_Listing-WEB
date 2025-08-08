<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی device_id
$device_id = clean($_GET['id'] ?? '');
if (!$device_id) {
    header('Location: devices.php');
    exit;
}

// دریافت اطلاعات دستگاه
$stmt = $conn->prepare("SELECT device_id, device_code, device_name FROM devices WHERE device_id = ? LIMIT 1");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$device = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$device) {
    header('Location: devices.php?error=device_not_found');
    exit;
}

// بررسی و ایجاد جدول device_bom اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'device_bom'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE device_bom (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        item_code VARCHAR(50) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        quantity_needed INT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_device_item (device_id, item_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($createTable);
}

// پردازش عملیات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_to_bom') {
        $inventory_id = clean($_POST['inventory_id']);
        
        // دریافت اطلاعات کالا
        $stmt = $conn->prepare("SELECT id, inventory_code, item_name FROM inv_inventory WHERE id = ?");
        $stmt->bind_param("i", $inventory_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($item) {
            // بررسی وجود قبلی
            $check = $conn->prepare("SELECT bom_id FROM device_bom WHERE device_id = ? AND item_code = ?");
            $check->bind_param("is", $device_id, $item['inventory_code']);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();
            
            if (!$exists) {
                $insert = $conn->prepare("INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed) VALUES (?, ?, ?, 1)");
                $insert->bind_param("iss", $device_id, $item['inventory_code'], $item['item_name']);
                $insert->execute();
                $insert->close();
            }
        }
        
        header("Location: device_bom.php?id=$device_id&msg=added");
        exit;
    } 
    
    elseif ($_POST['action'] === 'update_bom') {
        $quantities = $_POST['quantities'] ?? [];
        
        $stmt = $conn->prepare("UPDATE device_bom SET quantity_needed = ? WHERE bom_id = ? AND device_id = ?");
        foreach ($quantities as $bom_id => $quantity) {
            $qty = max(1, (int)$quantity);
            $stmt->bind_param("iii", $qty, $bom_id, $device_id);
            $stmt->execute();
        }
        $stmt->close();
        
        header("Location: device_bom.php?id=$device_id&msg=updated");
        exit;
    } 
    
    elseif ($_POST['action'] === 'delete_from_bom') {
        $bom_id = clean($_POST['bom_id']);
        
        $stmt = $conn->prepare("DELETE FROM device_bom WHERE bom_id = ? AND device_id = ?");
        $stmt->bind_param("ii", $bom_id, $device_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: device_bom.php?id=$device_id&msg=deleted");
        exit;
    }
}

// دریافت لیست قطعات
$stmt = $conn->prepare("
    SELECT b.bom_id, b.item_code, b.item_name, b.quantity_needed, i.current_inventory 
    FROM device_bom b 
    LEFT JOIN inv_inventory i ON b.item_code = i.inventory_code 
    WHERE b.device_id = ? 
    ORDER BY b.item_name
");
$stmt->bind_param("i", $device_id);
$stmt->execute();
$result = $stmt->get_result();
$bom_items = [];
while ($row = $result->fetch_assoc()) {
    $bom_items[] = $row;
}
$stmt->close();

// آماده‌سازی پیام‌ها
$message = '';
$message_type = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'added':
            $message = 'قطعه با موفقیت اضافه شد';
            $message_type = 'success';
            break;
        case 'updated':
            $message = 'تعدادها با موفقیت به‌روزرسانی شد';
            $message_type = 'success';
            break;
        case 'deleted':
            $message = 'قطعه با موفقیت حذف شد';
            $message_type = 'success';
            break;
        case 'error':
            $message = 'خطایی رخ داد';
            $message_type = 'danger';
            break;
    }
}

$business_info = getBusinessInfo();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قطعات دستگاه <?php echo htmlspecialchars($device['device_name']); ?> - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .main-header {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .status-badge {
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-1">
                    <i class="bi bi-tools me-2"></i>قطعات دستگاه: <?php echo htmlspecialchars($device['device_name']); ?>
                </h1>
                <p class="mb-0 opacity-75">کد دستگاه: <?php echo htmlspecialchars($device['device_code']); ?></p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="devices.php" class="btn btn-light">
                    <i class="bi bi-arrow-right me-1"></i>بازگشت
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- پیام‌ها -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- جستجو و افزودن -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-search me-2"></i>افزودن قطعه جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="searchForm">
                        <input type="hidden" name="id" value="<?php echo $device_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">جستجوی کالا (کد یا نام)</label>
                            <div class="input-group">
                                <input type="text" name="search_term" class="form-control" 
                                       value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>"
                                       placeholder="کد یا نام کالا را وارد کنید...">
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- نتایج جستجو -->
                    <?php if (isset($_GET['search_term']) && !empty($_GET['search_term'])): ?>
                        <?php
                        $search_term = clean($_GET['search_term']);
                        $search_query = "%$search_term%";
                        
                        $stmt = $conn->prepare("
                            SELECT id, inventory_code, item_name, current_inventory 
                            FROM inv_inventory 
                            WHERE (inventory_code LIKE ? OR item_name LIKE ?)
                            ORDER BY item_name 
                            LIMIT 10
                        ");
                        $stmt->bind_param("ss", $search_query, $search_query);
                        $stmt->execute();
                        $search_result = $stmt->get_result();
                        
                        if ($search_result->num_rows > 0):
                        ?>
                            <div class="mt-3">
                                <h6>نتایج جستجو:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>کد</th>
                                                <th>نام کالا</th>
                                                <th>موجودی</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $search_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['inventory_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                                    <td><?php echo (int)$row['current_inventory']; ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="add_to_bom">
                                                            <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" class="btn btn-success btn-sm">
                                                                <i class="bi bi-plus"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle me-2"></i>کالایی یافت نشد
                            </div>
                        <?php endif; ?>
                        <?php $stmt->close(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- لیست قطعات -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-check me-2"></i>لیست قطعات (<?php echo count($bom_items); ?> قطعه)
                    </h5>
                    <?php if (!empty($bom_items)): ?>
                        <button type="submit" form="updateForm" class="btn btn-primary btn-sm">
                            <i class="bi bi-save me-1"></i>ذخیره تغییرات
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($bom_items)): ?>
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">هیچ قطعه‌ای تعریف نشده</h5>
                        <p class="text-muted">از بخش جستجو، قطعات مورد نیاز را اضافه کنید</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="updateForm">
                        <input type="hidden" name="action" value="update_bom">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>کد قطعه</th>
                                        <th>نام قطعه</th>
                                        <th style="width: 100px;">تعداد</th>
                                        <th style="width: 80px;">موجودی</th>
                                        <th style="width: 80px;">وضعیت</th>
                                        <th style="width: 60px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bom_items as $item): 
                                        $current_stock = (int)($item['current_inventory'] ?? 0);
                                        $needed = (int)$item['quantity_needed'];
                                        $sufficient = $current_stock >= $needed;
                                    ?>
                                        <tr>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['item_code']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td>
                                                <input type="number" 
                                                       name="quantities[<?php echo $item['bom_id']; ?>]" 
                                                       class="form-control form-control-sm text-center" 
                                                       value="<?php echo $item['quantity_needed']; ?>" 
                                                       min="1" required>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?php echo $sufficient ? 'bg-light text-dark' : 'bg-warning text-dark'; ?>">
                                                    <?php echo $current_stock; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($sufficient): ?>
                                                    <span class="badge bg-success status-badge">
                                                        <i class="bi bi-check"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger status-badge">
                                                        <i class="bi bi-x"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteItem(<?php echo $item['bom_id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- فرم حذف مخفی -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete_from_bom">
    <input type="hidden" name="bom_id" id="bom_id_to_delete">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteItem(bomId) {
    if (confirm('آیا از حذف این قطعه مطمئن هستید؟')) {
        document.getElementById('bom_id_to_delete').value = bomId;
        document.getElementById('deleteForm').submit();
    }
}

// Auto-hide alerts after 3 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 3000);
</script>

</body>
</html>
