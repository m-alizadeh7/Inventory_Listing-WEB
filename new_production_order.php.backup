<?php
require_once 'config.php';
require_once 'includes/functions.php';

// حذف سفارش
if (isset($_POST['delete_order_id'])) {
    $delete_id = (int)$_POST['delete_order_id'];
    $conn->query("DELETE FROM production_order_items WHERE order_id = $delete_id");
    $conn->query("DELETE FROM production_orders WHERE order_id = $delete_id");
    header('Location: new_production_order.php?msg=deleted');
    exit;
}

// دریافت لیست سفارشات قبلی
$orders = [];
$result_orders = $conn->query("SELECT order_id, order_code, created_at, status FROM production_orders ORDER BY created_at DESC");
if ($result_orders) {
    while ($row = $result_orders->fetch_assoc()) {
        $orders[] = $row;
    }
}

// افزودن سفارش جدید
if (isset($_POST['save_new_order'])) {
    try {
        $conn->begin_transaction();
        
    // ایجاد کد سفارش جدید فقط به صورت ORD + شماره سه‌رقمی پشت سر هم
    $prefix = 'ORD';
    $sql = "SELECT MAX(CAST(SUBSTRING(order_code, 4) AS UNSIGNED)) as max_num FROM production_orders WHERE order_code LIKE 'ORD%'";
    $result = $conn->query($sql)->fetch_assoc();
    $next_num = ($result['max_num'] ?? 0) + 1;
    $order_code = $prefix . sprintf('%03d', $next_num);
        
        $notes = clean($_POST['notes'] ?? '');
        
        // ایجاد سفارش جدید
        $sql = "INSERT INTO production_orders (order_code, status, notes) VALUES (?, 'pending', ?)";
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
    SELECT d.*, COUNT(DISTINCT b.item_code) as parts_count
    FROM devices d
    LEFT JOIN device_bom b ON d.device_id = b.device_id
    GROUP BY d.device_id
    ORDER BY d.device_name
");
$devices = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

// شمارش آمار مربوط به سفارشات
$orders_count = [
    'all' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0
];

$stats_query = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM production_orders 
    GROUP BY status
");

if ($stats_query) {
    while ($row = $stats_query->fetch_assoc()) {
        $status = $row['status'] ?? 'pending';
        if (isset($orders_count[$status])) {
            $orders_count[$status] = (int)$row['count'];
        }
        $orders_count['all'] += (int)$row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت سفارشات تولید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .table thead th { background: #e9ecef; }
        .stats-card { transition: all 0.3s; }
        .stats-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .sort-link { color: inherit; text-decoration: none; }
        .sort-link:hover { color: #0d6efd; }
        .modal-header { background: #e9ecef; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h3 mb-0"><i class="bi bi-boxes"></i> مدیریت سفارشات تولید</h2>
            <p class="text-muted small mb-0">ایجاد و مدیریت سفارشات تولید برای دستگاه‌های مختلف</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-icon" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                <i class="bi bi-plus-lg"></i>
                سفارش تولید جدید
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-icon">
                <i class="bi bi-house"></i>
                بازگشت
            </a>
        </div>
    </div>
    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                سفارش جدید با موفقیت ثبت شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'started'): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="bi bi-info-circle-fill me-2"></i>
                سفارش با موفقیت شروع شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'completed'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                سفارش با موفقیت تکمیل شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <!-- کارت آمار سریع -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-primary"><?= $orders_count['all'] ?></h5>
                    <p class="card-text text-muted mb-0">کل سفارشات</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-warning"><?= $orders_count['pending'] ?></h5>
                    <p class="card-text text-muted mb-0">در انتظار</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-info"><?= $orders_count['in_progress'] ?></h5>
                    <p class="card-text text-muted mb-0">در حال انجام</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-success"><?= $orders_count['completed'] ?></h5>
                    <p class="card-text text-muted mb-0">تکمیل شده</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal افزودن سفارش جدید -->
    <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-lg me-1"></i> ایجاد سفارش تولید جدید</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="notes" class="form-label">توضیحات سفارش</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <h5 class="mt-4 mb-3">دستگاه‌های سفارش</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>دستگاه</th>
                                        <th>کد دستگاه</th>
                                        <th>تعداد قطعات</th>
                                        <th>تعداد سفارش</th>
                                        <th width="80">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="deviceTableBody">
                                    <!-- ردیف‌ها با جاوااسکریپت اضافه می‌شوند -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-info" id="addDeviceRow">
                            <i class="bi bi-plus-lg"></i> افزودن دستگاه
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="save_new_order" class="btn btn-primary" id="submitOrder">
                            <i class="bi bi-check-lg me-1"></i> ثبت سفارش
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- جدول سفارشات یا لیست سفارشات می‌تواند اینجا اضافه شود -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="bi bi-list-ul me-1"></i>
                لیست سفارشات قبلی
            </div>
            <div class="text-muted small">
                <?= count($orders) ?> سفارش یافت شد
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>کد سفارش</th>
                            <th>تاریخ ثبت</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    هیچ سفارشی ثبت نشده است.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_code']) ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td><?= htmlspecialchars($order['status']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('آیا از حذف این سفارش اطمینان دارید؟');">
                                        <input type="hidden" name="delete_order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> حذف</button>
                                    </form>
                                    <a href="production_order.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> مشاهده</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer class="text-center py-3" style="font-size:0.9rem;color:#6c757d;border-top:1px solid #dee2e6;margin-top:2rem;">
    <small>© <?php echo date('Y'); ?> سیستم انبارداری | سازنده: <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a> | <a href="https://github.com/m-alizadeh7" target="_blank">GitHub</a> | <a href="https://t.me/alizadeh_channel" target="_blank">Telegram</a></small>
</footer>
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
                    <i class="bi bi-trash"></i>
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
        const tbody = document.getElementById('deviceTableBody');
        if (tbody.children.length > 1) {
            button.closest('tr').remove();
        } else {
            alert('حداقل یک دستگاه باید در سفارش وجود داشته باشد.');
        }
    }

    document.getElementById('addDeviceRow').addEventListener('click', addDeviceRow);
    
    document.getElementById('submitOrder').addEventListener('click', function(e) {
        const tbody = document.getElementById('deviceTableBody');
        if (tbody.children.length === 0) {
            e.preventDefault();
            alert('لطفاً حداقل یک دستگاه را برای سفارش انتخاب کنید.');
            return false;
        }
        
        // بررسی معتبر بودن فرم
        const form = this.closest('form');
        const selects = form.querySelectorAll('select[required]');
        const inputs = form.querySelectorAll('input[required]');
        
        let isValid = true;
        selects.forEach(select => {
            if (!select.value) {
                select.classList.add('is-invalid');
                isValid = false;
            } else {
                select.classList.remove('is-invalid');
            }
        });
        
        inputs.forEach(input => {
            if (!input.value || input.value < 1) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('لطفاً همه فیلدهای ضروری را پر کنید.');
            return false;
        }
    });

    // اضافه کردن یک ردیف در شروع
    addDeviceRow();
</script>
</body>
</html>
