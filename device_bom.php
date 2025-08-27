<?php
require_once 'bootstrap.php';

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
        $quantity = max(1, (int)clean($_POST['quantity'] ?? 1));
        
        // دریافت اطلاعات کالا
        $stmt = $conn->prepare("SELECT id, inventory_code, item_name FROM inventory WHERE id = ?");
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
                $insert = $conn->prepare("INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed) VALUES (?, ?, ?, ?)");
                $insert->bind_param("issi", $device_id, $item['inventory_code'], $item['item_name'], $quantity);
                $insert->execute();
                $insert->close();
                header("Location: device_bom.php?id=$device_id&msg=added");
            } else {
                header("Location: device_bom.php?id=$device_id&msg=exists");
            }
        }
        exit;
    } 
    
    elseif ($_POST['action'] === 'update_quantity') {
        $bom_id = clean($_POST['bom_id']);
        $quantity = max(1, (int)clean($_POST['quantity']));
        
        $stmt = $conn->prepare("UPDATE device_bom SET quantity_needed = ? WHERE bom_id = ? AND device_id = ?");
        $stmt->bind_param("iii", $quantity, $bom_id, $device_id);
        $stmt->execute();
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

// دریافت لیست قطعات BOM
$stmt = $conn->prepare("
    SELECT b.bom_id, b.item_code, b.item_name, b.quantity_needed, 
           COALESCE(i.current_inventory, 0) as current_inventory,
           CASE 
               WHEN COALESCE(i.current_inventory, 0) >= b.quantity_needed THEN 'موجود'
               ELSE 'کسری'
           END as status
    FROM device_bom b 
    LEFT JOIN inventory i ON b.item_code COLLATE utf8mb4_unicode_ci = i.inventory_code COLLATE utf8mb4_unicode_ci 
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
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .search-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-available {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-insufficient {
            background-color: #f8d7da;
            color: #842029;
        }
        .quantity-input {
            max-width: 80px;
        }
    </style>
</head>
<body>

<?php get_template_part('header'); ?>
    <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-1">
                    <i class="bi bi-tools me-2"></i>BOM دستگاه: <?php echo htmlspecialchars($device['device_name']); ?>
                </h1>
                <p class="mb-0 opacity-75">کد دستگاه: <?php echo htmlspecialchars($device['device_code']); ?></p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="devices.php" class="btn btn-light">
                    <i class="bi bi-arrow-right me-1"></i>بازگشت به دستگاه‌ها
                </a>
            </div>
        </div>
        
    <!-- پیام‌ها -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- جستجو و افزودن قطعه -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-plus-circle me-2"></i>افزودن قطعه جدید
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <input type="hidden" name="id" value="<?php echo $device_id; ?>">
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="search_term" class="form-control" 
                               value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>"
                               placeholder="جستجو بر اساس کد یا نام کالا...">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-search me-1"></i>جستجو
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
                    FROM inventory 
                    WHERE (inventory_code LIKE ? OR item_name LIKE ?)
                    ORDER BY item_name 
                    LIMIT 10
                ");
                $stmt->bind_param("ss", $search_query, $search_query);
                $stmt->execute();
                $search_result = $stmt->get_result();
                
                if ($search_result->num_rows > 0):
                ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>کد کالا</th>
                                    <th>نام کالا</th>
                                    <th>موجودی فعلی</th>
                                    <th>تعداد مورد نیاز</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $search_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($row['inventory_code']); ?></code></td>
                                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo (int)$row['current_inventory']; ?></span></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="add_to_bom">
                                                <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm quantity-input d-inline-block" 
                                                       value="1" min="1" style="width: 70px;">
                                        </td>
                                        <td>
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-plus"></i> افزودن
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>کالایی با این مشخصات یافت نشد
                    </div>
                <?php endif; ?>
                <?php $stmt->close(); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- لیست قطعات BOM -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-list-check me-2"></i>لیست قطعات (<?php echo count($bom_items); ?> قطعه)
            </h5>
            <?php if (!empty($bom_items)): ?>
                <button type="submit" form="updateForm" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>ذخیره تغییرات
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (empty($bom_items)): ?>
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="text-muted mt-3">هیچ قطعه‌ای تعریف نشده</h5>
                <p class="text-muted">از بخش بالا، قطعات مورد نیاز را جستجو و اضافه کنید</p>
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
                                <th style="width: 120px;">تعداد مورد نیاز</th>
                                <th style="width: 100px;">موجودی</th>
                                <th style="width: 100px;">وضعیت</th>
                                <th style="width: 80px;">عملیات</th>
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
                                        <code><?php echo htmlspecialchars($item['item_code']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               name="quantities[<?php echo $item['bom_id']; ?>]" 
                                               class="form-control form-control-sm text-center" 
                                               value="<?php echo $item['quantity_needed']; ?>" 
                                               min="1" required>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $sufficient ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo $current_stock; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($sufficient): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i>موجود
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-circle me-1"></i>کسری
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                onclick="deleteItem(<?php echo $item['bom_id']; ?>)" 
                                                title="حذف قطعه">
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

<!-- فرم حذف مخفی -->
<form method="POST" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete_from_bom">
    <input type="hidden" name="bom_id" id="bom_id_to_delete">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteItem(bomId) {
    if (confirm('آیا از حذف این قطعه از BOM مطمئن هستید؟')) {
        document.getElementById('bom_id_to_delete').value = bomId;
        document.getElementById('deleteForm').submit();
    }
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('input[name^="quantities"]');
    quantityInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.value < 1) {
                this.value = 1;
            }
        });
    });
});
</script>

</body>
</html>
