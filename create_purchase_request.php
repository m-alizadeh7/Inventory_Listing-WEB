<?php
require_once 'bootstrap.php';

if (!isset($security)) {
    header('Location: login.php'); exit;
}

if (!$security->hasPermission('purchase.manage')) {
    http_response_code(403); get_template_part('header');
    echo '<div class="container mt-4"><div class="alert alert-danger">شما دسترسی لازم را ندارید.</div></div>';
    get_template_part('footer'); exit;
}

// Ensure purchase_requests table exists
if (isset($conn) && $conn instanceof mysqli) {
    $conn->query("CREATE TABLE IF NOT EXISTS purchase_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        request_number VARCHAR(50) NOT NULL UNIQUE,
        supplier_id INT,
        device_id INT,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2),
        total_price DECIMAL(10,2),
        status ENUM('pending', 'approved', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        notes TEXT,
        requested_by INT,
        approved_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE SET NULL,
        FOREIGN KEY (requested_by) REFERENCES users(user_id) ON DELETE SET NULL,
        FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

$errors = [];
$success = [];

// Load suppliers for dropdown
$suppliers = [];
if (isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("SELECT supplier_id, supplier_code, supplier_name FROM suppliers ORDER BY supplier_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
}

// Load devices for dropdown
$devices = [];
if (isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("SELECT device_id, device_code, device_name FROM devices ORDER BY device_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_number = trim($_POST['request_number'] ?? '');
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $device_id = !empty($_POST['device_id']) ? (int)$_POST['device_id'] : null;
    $quantity = (int)($_POST['quantity'] ?? 0);
    $unit_price = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
    $status = trim($_POST['status'] ?? 'pending');
    $priority = trim($_POST['priority'] ?? 'medium');
    $notes = trim($_POST['notes'] ?? '');

    // Validate input
    if (empty($request_number)) {
        $errors[] = 'شماره درخواست اجباری است.';
    }

    if (empty($device_id)) {
        $errors[] = 'انتخاب دستگاه اجباری است.';
    }

    if ($quantity <= 0) {
        $errors[] = 'تعداد باید بزرگتر از صفر باشد.';
    }

    if ($unit_price !== null && $unit_price <= 0) {
        $errors[] = 'قیمت واحد باید بزرگتر از صفر باشد.';
    }

    if (!in_array($status, ['pending', 'approved', 'ordered', 'received', 'cancelled'])) {
        $errors[] = 'وضعیت درخواست نامعتبر است.';
    }

    if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $errors[] = 'اولویت نامعتبر است.';
    }

    // Check if request number already exists
    if (empty($errors) && isset($conn) && $conn instanceof mysqli) {
        $stmt = $conn->prepare("SELECT request_id FROM purchase_requests WHERE request_number = ?");
        if ($stmt) {
            $stmt->bind_param('s', $request_number);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = 'شماره درخواست تکراری است.';
            }
            $stmt->close();
        }
    }

    // Insert new purchase request
    if (empty($errors) && isset($conn) && $conn instanceof mysqli) {
        $total_price = $unit_price !== null ? $unit_price * $quantity : null;
        $requested_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $stmt = $conn->prepare("
            INSERT INTO purchase_requests
            (request_number, supplier_id, device_id, quantity, unit_price, total_price, status, priority, notes, requested_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('siidsssssi',
                $request_number, $supplier_id, $device_id, $quantity, $unit_price,
                $total_price, $status, $priority, $notes, $requested_by
            );
            if ($stmt->execute()) {
                $success[] = 'درخواست خرید با موفقیت ثبت شد.';
                // Reset form
                $request_number = $supplier_id = $device_id = $quantity = $unit_price = $notes = '';
                $status = 'pending';
                $priority = 'medium';
            } else {
                $errors[] = 'خطا در ثبت درخواست خرید: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'خطا در آماده‌سازی کوئری';
        }
    }
}

// Generate request number if not provided
if (empty($request_number) && isset($conn) && $conn instanceof mysqli) {
    $result = $conn->query("SELECT COUNT(*) as count FROM purchase_requests WHERE DATE(created_at) = CURDATE()");
    $count = $result ? $result->fetch_assoc()['count'] + 1 : 1;
    $request_number = 'PR-' . date('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
}

get_template_part('header');
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-plus-circle"></i> ایجاد درخواست خرید جدید</h4>
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

            <form method="post" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">شماره درخواست</label>
                            <input type="text" name="request_number" class="form-control"
                                   value="<?= htmlspecialchars($request_number) ?>" required
                                   pattern="PR-[0-9]{8}-[0-9]{3}"
                                   title="فرمت: PR-YYYYMMDD-XXX">
                            <div class="form-text">فرمت خودکار: PR-YYYYMMDD-XXX</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">تامین‌کننده</label>
                            <select name="supplier_id" class="form-control">
                                <option value="">انتخاب تامین‌کننده (اختیاری)...</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= $supplier['supplier_id'] ?>"
                                            <?= (isset($supplier_id) && $supplier_id == $supplier['supplier_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supplier['supplier_code'] . ' - ' . $supplier['supplier_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">دستگاه</label>
                            <select name="device_id" class="form-control" required>
                                <option value="">انتخاب دستگاه...</option>
                                <?php foreach ($devices as $device): ?>
                                    <option value="<?= $device['device_id'] ?>"
                                            <?= (isset($device_id) && $device_id == $device['device_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($device['device_code'] . ' - ' . $device['device_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">تعداد</label>
                            <input type="number" name="quantity" class="form-control" min="1"
                                   value="<?= htmlspecialchars($quantity ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label class="form-label">قیمت واحد (تومان)</label>
                            <input type="number" name="unit_price" class="form-control" min="0" step="0.01"
                                   value="<?= htmlspecialchars($unit_price ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">وضعیت</label>
                            <select name="status" class="form-control" required>
                                <option value="pending" <?= ($status ?? 'pending') == 'pending' ? 'selected' : '' ?>>در انتظار</option>
                                <option value="approved" <?= ($status ?? '') == 'approved' ? 'selected' : '' ?>>تایید شده</option>
                                <option value="ordered" <?= ($status ?? '') == 'ordered' ? 'selected' : '' ?>>سفارش داده شده</option>
                                <option value="received" <?= ($status ?? '') == 'received' ? 'selected' : '' ?>>دریافت شده</option>
                                <option value="cancelled" <?= ($status ?? '') == 'cancelled' ? 'selected' : '' ?>>لغو شده</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">اولویت</label>
                            <select name="priority" class="form-control" required>
                                <option value="low" <?= ($priority ?? 'medium') == 'low' ? 'selected' : '' ?>>کم</option>
                                <option value="medium" <?= ($priority ?? 'medium') == 'medium' ? 'selected' : '' ?>>متوسط</option>
                                <option value="high" <?= ($priority ?? '') == 'high' ? 'selected' : '' ?>>زیاد</option>
                                <option value="urgent" <?= ($priority ?? '') == 'urgent' ? 'selected' : '' ?>>فوری</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">توضیحات</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="توضیحات اضافی درباره درخواست خرید..."><?= htmlspecialchars($notes ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> ثبت درخواست خرید
                    </button>
                    <a href="purchase_requests.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> بازگشت به لیست درخواست‌ها
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Help Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-info-circle"></i> راهنما</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>وضعیت‌های درخواست:</h6>
                    <ul>
                        <li><strong>در انتظار:</strong> درخواست جدید ثبت شده</li>
                        <li><strong>تایید شده:</strong> درخواست توسط مدیر تایید شده</li>
                        <li><strong>سفارش داده شده:</strong> سفارش به تامین‌کننده ارسال شده</li>
                        <li><strong>دریافت شده:</strong> کالا دریافت و انبار شده</li>
                        <li><strong>لغو شده:</strong> درخواست لغو شده</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6>نکات مهم:</h6>
                    <ul>
                        <li>شماره درخواست به صورت خودکار تولید می‌شود</li>
                        <li>انتخاب تامین‌کننده اختیاری است</li>
                        <li>قیمت واحد برای محاسبه کل هزینه استفاده می‌شود</li>
                        <li>اولویت فوری برای موارد ضروری استفاده شود</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate request number
function generateRequestNumber() {
    const now = new Date();
    const dateStr = now.getFullYear().toString() +
                   (now.getMonth() + 1).toString().padStart(2, '0') +
                   now.getDate().toString().padStart(2, '0');
    return 'PR-' + dateStr + '-001';
}

// Set default request number if empty
document.addEventListener('DOMContentLoaded', function() {
    const requestNumberInput = document.querySelector('input[name="request_number"]');
    if (!requestNumberInput.value) {
        requestNumberInput.value = generateRequestNumber();
    }
});
</script>

<?php get_template_part('footer'); ?>
