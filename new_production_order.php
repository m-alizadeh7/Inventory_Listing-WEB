<?php
require_once 'config.php';
require_once 'includes/functions.php';

// ایجاد کد سفارش جدید
function generateOrderCode() {
    global $conn;
    $prefix = 'ORD';
    $date = date('ymd');
    $sql = "SELECT MAX(CAST(SUBSTRING(order_code, 10) AS UNSIGNED)) as max_num 
            FROM production_orders 
            WHERE order_code LIKE 'ORD{$date}%'";
    $result = $conn->query($sql)->fetch_assoc();
    $next_num = ($result['max_num'] ?? 0) + 1;
    return $prefix . $date . sprintf('%03d', $next_num);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $order_code = generateOrderCode();
        $notes = clean($_POST['notes']);
        
        // ایجاد سفارش جدید
        $sql = "INSERT INTO production_orders (order_code, status, notes) VALUES (?, 'draft', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $order_code, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // افزودن آیتم‌های سفارش
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $sql = "INSERT INTO production_order_items (order_id, device_id, quantity) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($_POST['items'] as $item) {
                $device_id = (int)$item['device_id'];
                $quantity = (int)$item['quantity'];
                if ($device_id > 0 && $quantity > 0) {
                    $stmt->bind_param('iii', $order_id, $device_id, $quantity);
                    $stmt->execute();
                }
            }
        }

        $conn->commit();
        header('Location: production_order.php?id=' . $order_id . '&msg=added');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = 'خطا در ثبت سفارش: ' . $e->getMessage();
    }
}

// دریافت لیست دستگاه‌ها
$result = $conn->query("
    SELECT d.*, 
           COUNT(DISTINCT b.item_code) as parts_count
    FROM devices d
    LEFT JOIN device_bom b ON d.device_id = b.device_id
    GROUP BY d.device_id
    ORDER BY d.device_name
");

$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سفارش تولید جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .device-row:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">🏭 سفارش تولید جدید</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" id="productionForm" class="needs-validation" novalidate>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>دستگاه</th>
                                        <th>کد دستگاه</th>
                                        <th>تعداد قطعات</th>
                                        <th>تعداد سفارش</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="deviceTableBody">
                                    <!-- ردیف‌ها با جاوااسکریپت اضافه می‌شوند -->
                                </tbody>
                            </table>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-info" onclick="addDeviceRow()">
                                <i class="bi bi-plus-lg"></i> افزودن دستگاه
                            </button>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">توضیحات سفارش</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" onclick="return validateForm()">
                                <i class="bi bi-check-lg"></i> ثبت سفارش
                            </button>
                            <a href="production_orders.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> انصراف
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const devices = <?= json_encode($devices) ?>;
let rowCounter = 0;

function addDeviceRow() {
    const tbody = document.getElementById('deviceTableBody');
    const tr = document.createElement('tr');
    tr.className = 'device-row';
    tr.innerHTML = `
        <td>
            <select name="items[${rowCounter}][device_id]" class="form-select" required onchange="updateDeviceInfo(this)">
                <option value="">انتخاب دستگاه</option>
                ${devices.map(d => `
                    <option value="${d.device_id}" 
                            data-code="${d.device_code}"
                            data-parts="${d.parts_count}">
                        ${d.device_name}
                    </option>
                `).join('')}
            </select>
        </td>
        <td class="device-code">-</td>
        <td class="parts-count">-</td>
        <td>
            <input type="number" name="items[${rowCounter}][quantity]" 
                   class="form-control" min="1" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeDeviceRow(this)">
                <i class="bi bi-trash"></i> حذف
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    rowCounter++;
}

function updateDeviceInfo(select) {
    const row = select.closest('tr');
    const option = select.selectedOptions[0];
    if (option.value) {
        row.querySelector('.device-code').textContent = option.dataset.code;
        row.querySelector('.parts-count').textContent = option.dataset.parts;
    } else {
        row.querySelector('.device-code').textContent = '-';
        row.querySelector('.parts-count').textContent = '-';
    }
}

function removeDeviceRow(button) {
    button.closest('tr').remove();
}

function validateForm() {
    const tbody = document.getElementById('deviceTableBody');
    if (tbody.children.length === 0) {
        alert('لطفاً حداقل یک دستگاه را انتخاب کنید.');
        return false;
    }
    return true;
}

// اضافه کردن یک ردیف در شروع
addDeviceRow();
</script>
<footer class="text-center py-3" style="font-size:0.9rem;color:#6c757d;border-top:1px solid #dee2e6;margin-top:2rem;">
    <small>© <?php echo date('Y'); ?> سیستم انبارداری | سازنده: <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a> | <a href="https://github.com/m-alizadeh7" target="_blank">GitHub</a> | <a href="https://t.me/alizadeh_channel" target="_blank">Telegram</a></small>
</footer>
</body>
</html>
