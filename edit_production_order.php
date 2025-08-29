<?php
require_once 'bootstrap.php';

if (!isset($security)) {
    header('Location: login.php'); exit;
}

if (!$security->hasPermission('production.manage')) {
    http_response_code(403); get_template_part('header');
    echo '<div class="container mt-4"><div class="alert alert-danger">شما دسترسی لازم را ندارید.</div></div>';
    get_template_part('footer'); exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];
$success = [];

// Load order data
$order = null;
if ($order_id > 0 && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT p.*, COUNT(DISTINCT i.device_id) as devices_count, SUM(i.quantity) as total_quantity
        FROM production_orders p
        LEFT JOIN production_order_items i ON p.order_id = i.order_id
        WHERE p.order_id = ?
        GROUP BY p.order_id
    ");
    if ($stmt) {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$order) {
    set_flash_message('سفارش یافت نشد', 'danger');
    header('Location: production_orders.php');
    exit;
}

// Load devices for this order
$order_devices = [];
if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT i.*, d.device_code, d.device_name
        FROM production_order_items i
        JOIN devices d ON i.device_id = d.device_id
        WHERE i.order_id = ?
        ORDER BY d.device_name
    ");
    if ($stmt) {
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $order_devices[] = $row;
        }
        $stmt->close();
    }
}

// Load all available devices for adding new ones
$available_devices = [];
if (isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("SELECT device_id, device_code, device_name FROM devices ORDER BY device_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $available_devices[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_number = trim($_POST['order_number'] ?? '');
    $status = trim($_POST['status'] ?? 'pending');
    $notes = trim($_POST['notes'] ?? '');

    // Validate input
    if (empty($order_number)) {
        $errors[] = 'شماره سفارش اجباری است.';
    }

    if (!in_array($status, ['pending', 'in_progress', 'completed'])) {
        $errors[] = 'وضعیت سفارش نامعتبر است.';
    }

    // Update order
    if (empty($errors) && isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("
            UPDATE production_orders
            SET order_number = ?, status = ?, notes = ?, updated_at = NOW()
            WHERE order_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('sssi', $order_number, $status, $notes, $order_id);
            if ($stmt->execute()) {
                $success[] = 'سفارش با موفقیت بروزرسانی شد.';

                // Reload order data
                $stmt->close();
                $stmt = $conn->prepare("SELECT * FROM production_orders WHERE order_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $order_id);
                    $stmt->execute();
                    $order = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            } else {
                $errors[] = 'خطا در بروزرسانی سفارش: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'خطا در آماده‌سازی کوئری';
        }
    }
}

// Handle removing device from order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_device'])) {
    $item_id = (int)($_POST['remove_device'] ?? 0);

    if ($item_id > 0 && isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("DELETE FROM production_order_items WHERE id = ? AND order_id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $item_id, $order_id);
            if ($stmt->execute()) {
                $success[] = 'دستگاه با موفقیت حذف شد.';
                // Reload devices
                header("Location: edit_production_order.php?id=$order_id");
                exit;
            } else {
                $errors[] = 'خطا در حذف دستگاه: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle adding new device to order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_device'])) {
    $device_id = (int)($_POST['device_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($device_id <= 0) {
        $errors[] = 'دستگاه انتخاب نشده است.';
    }

    if ($quantity <= 0) {
        $errors[] = 'تعداد باید بزرگتر از صفر باشد.';
    }

    // Check if device already exists in order
    $device_exists = false;
    foreach ($order_devices as $device) {
        if ($device['device_id'] == $device_id) {
            $device_exists = true;
            break;
        }
    }

    if ($device_exists) {
        $errors[] = 'این دستگاه قبلاً به سفارش اضافه شده است.';
    }

    if (empty($errors) && isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("INSERT INTO production_order_items (order_id, device_id, quantity) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iii', $order_id, $device_id, $quantity);
            if ($stmt->execute()) {
                $success[] = 'دستگاه با موفقیت اضافه شد.';
                // Reload devices
                $stmt->close();
                header("Location: edit_production_order.php?id=$order_id");
                exit;
            } else {
                $errors[] = 'خطا در اضافه کردن دستگاه: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

get_template_part('header');
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>ویرایش سفارش تولید: <?= htmlspecialchars($order['order_number']) ?></h4>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <ul><?php foreach ($success as $s) echo "<li>".htmlspecialchars($s)."</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <!-- Order Edit Form -->
            <form method="post" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">شماره سفارش</label>
                            <input type="text" name="order_number" class="form-control"
                                   value="<?= htmlspecialchars($order['order_number']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">وضعیت</label>
                            <select name="status" class="form-control" required>
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>در انتظار</option>
                                <option value="in_progress" <?= $order['status'] == 'in_progress' ? 'selected' : '' ?>>در حال انجام</option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>تکمیل شده</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">توضیحات</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">بروزرسانی سفارش</button>
            </form>

            <!-- Current Devices -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>دستگاه‌های سفارش (<?= count($order_devices) ?> دستگاه)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($order_devices)): ?>
                        <p class="text-muted">هیچ دستگاهی به این سفارش اضافه نشده است.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>کد دستگاه</th>
                                        <th>نام دستگاه</th>
                                        <th>تعداد</th>
                                        <th>توضیحات</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_devices as $device): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($device['device_code']) ?></code></td>
                                            <td><?= htmlspecialchars($device['device_name']) ?></td>
                                            <td><span class="badge bg-primary"><?= $device['quantity'] ?></span></td>
                                            <td><?= htmlspecialchars($device['notes'] ?? '') ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick="removeDevice(<?= $device['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add New Device -->
            <div class="card">
                <div class="card-header">
                    <h5>اضافه کردن دستگاه جدید</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="add_device" value="1">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">دستگاه</label>
                                    <select name="device_id" class="form-control" required>
                                        <option value="">انتخاب دستگاه...</option>
                                        <?php foreach ($available_devices as $device): ?>
                                            <option value="<?= $device['device_id'] ?>">
                                                <?= htmlspecialchars($device['device_code'] . ' - ' . $device['device_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">تعداد</label>
                                    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-plus"></i> اضافه
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="production_orders.php" class="btn btn-secondary">بازگشت به لیست سفارشات</a>
        <a href="production_order.php?id=<?= $order_id ?>" class="btn btn-info">مشاهده جزئیات</a>
    </div>
</div>

<script>
function removeDevice(itemId) {
    if (confirm('آیا مطمئن هستید که می‌خواهید این دستگاه را از سفارش حذف کنید؟')) {
        // Create a form to submit removal request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit_production_order.php?id=<?= $order_id ?>';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_device';
        input.value = itemId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php get_template_part('footer'); ?>
