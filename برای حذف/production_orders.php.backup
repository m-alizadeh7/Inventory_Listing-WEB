<?php
require_once 'config.php';
require_once 'includes/functions.php';


// حذف سفارش تولید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = clean($_POST['order_id']);
    // حذف آیتم‌های سفارش
    $stmt = $conn->prepare("DELETE FROM production_order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    // حذف سفارش
    $stmt = $conn->prepare("DELETE FROM production_orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    header("Location: production_orders.php?msg=deleted");
    exit;
}

// اگر هیچ سفارشی وجود ندارد، به صفحه ایجاد سفارش جدید هدایت شود یا پیام مناسب نمایش داده شود
$result_check = $conn->query("SELECT order_id FROM production_orders ORDER BY created_at DESC LIMIT 1");
if ($result_check && $result_check->num_rows === 0) {
    // هیچ سفارشی وجود ندارد
    header('Location: new_production_order.php');
    exit;
}

// بررسی و ایجاد جدول production_orders اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_orders'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_orders: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول production_order_items اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_order_items'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        device_id INT NOT NULL,
        quantity INT NOT NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_order_items: ' . $conn->error);
    }
}

// دریافت لیست سفارشات تولید
$result = $conn->query("
    SELECT p.*,
           COUNT(DISTINCT i.device_id) as devices_count,
           SUM(i.quantity) as total_quantity,
           (
               SELECT COUNT(DISTINCT b.item_code)
               FROM production_order_items oi
               JOIN device_bom b ON oi.device_id = b.device_id
               WHERE oi.order_id = p.order_id
           ) as unique_parts_count
    FROM production_orders p
    LEFT JOIN production_order_items i ON p.order_id = i.order_id
    GROUP BY p.order_id
    ORDER BY p.created_at DESC
");

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سفارشات تولید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { 
            background: #f7f7f7; 
            padding-top: 2rem;
            font-family: 'Vazir', sans-serif;
        }
        .status-draft { background-color: #fff3cd; }
        .status-confirmed { background-color: #cff4fc; }
        .status-in_progress { background-color: #e2e3e5; }
        .status-completed { background-color: #d1e7dd; }
        .status-cancelled { background-color: #f8d7da; }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .table th, .table td {
            vertical-align: middle;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.65rem;
                margin-bottom: 2px;
                display: inline-block;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏭 سفارشات تولید</h2>
        <div>
            <a href="new_production_order.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> سفارش جدید
            </a>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">سفارش جدید با موفقیت ثبت شد.</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
            <div class="alert alert-info">سفارش با موفقیت بروزرسانی شد.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">هیچ سفارش تولیدی ثبت نشده است.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>کد سفارش</th>
                        <th>تاریخ ثبت</th>
                        <th>وضعیت</th>
                        <th>آمار</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr class="status-<?= $order['status'] ?>">
                            <td>
                                <strong><?= htmlspecialchars($order['order_code']) ?></strong>
                            </td>
                            <td><?= gregorianToJalali($order['created_at']) ?></td>
                            <td>
                                <?php
                                $status_labels = [
                                    'draft' => ['text' => 'پیش‌نویس', 'icon' => 'file-earmark'],
                                    'confirmed' => ['text' => 'تایید شده', 'icon' => 'check-circle'],
                                    'in_progress' => ['text' => 'در حال تولید', 'icon' => 'gear'],
                                    'completed' => ['text' => 'تکمیل شده', 'icon' => 'check-all'],
                                    'cancelled' => ['text' => 'لغو شده', 'icon' => 'x-circle']
                                ];
                                $status = $status_labels[$order['status']] ?? ['text' => $order['status'], 'icon' => 'question'];
                                ?>
                                <i class="bi bi-<?= $status['icon'] ?>"></i>
                                <?= $status['text'] ?>
                            </td>
                            <td>
                                <div class="badge bg-primary">
                                    <?= $order['devices_count'] ?> دستگاه
                                </div>
                                <div class="badge bg-info">
                                    <?= $order['total_quantity'] ?> عدد
                                </div>
                                <div class="badge bg-secondary">
                                    <?= $order['unique_parts_count'] ?> قطعه متمایز
                                </div>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($order['notes'] ?? '-') ?></small>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="production_order.php?id=<?= $order['order_id'] ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> مشاهده
                                    </a>
                                    <form method="POST" class="d-inline ms-1" onsubmit="return confirm('آیا از حذف این سفارش اطمینان دارید؟ این عمل غیرقابل بازگشت است.')">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="submit" name="delete_order" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> حذف
                                        </button>
                                    </form>
                                    <?php if ($order['status'] === 'draft'): ?>
                                        <a href="edit_production_order.php?id=<?= $order['order_id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> ویرایش
                                        </a>
                                        <a href="confirm_production_order.php?id=<?= $order['order_id'] ?>" 
                                           class="btn btn-sm btn-success">
                                            <i class="bi bi-check2"></i> تایید
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($order['status'] === 'confirmed'): ?>
                                        <a href="start_production.php?id=<?= $order['order_id'] ?>" 
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-play"></i> شروع تولید
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">📊 خلاصه وضعیت</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php
                    $status_count = array_count_values(array_column($orders, 'status'));
                    $total_devices = array_sum(array_column($orders, 'devices_count'));
                    $total_quantity = array_sum(array_column($orders, 'total_quantity'));
                    ?>
                    <div class="col-md-3">
                        <h3><?= count($orders) ?></h3>
                        <p class="text-muted">کل سفارشات</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?= $status_count['in_progress'] ?? 0 ?></h3>
                        <p class="text-muted">در حال تولید</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?= $total_devices ?></h3>
                        <p class="text-muted">مجموع دستگاه‌ها</p>
                    </div>
                    <div class="col-md-3">
                        <h3><?= $total_quantity ?></h3>
                        <p class="text-muted">مجموع تعداد</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
