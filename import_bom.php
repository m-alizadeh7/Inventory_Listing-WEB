<?php
require_once 'bootstrap.php';

// Check if device_bom table exists and create it if not
$res = $conn->query("SHOW TABLES LIKE 'device_bom'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE device_bom (
        bom_id INT AUTO_INCREMENT PRIMARY KEY,
        device_id INT NOT NULL,
        item_code VARCHAR(100) NOT NULL,
        item_name VARCHAR(255),
        quantity_needed INT,
        supplier_id INT,
        notes TEXT,
        FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('Error creating device_bom table: ' . $conn->error);
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bom_file'])) {
    $device_id = clean($_POST['device_id']);
    $file = $_FILES['bom_file']['tmp_name'];

    if (empty($device_id)) {
        $error = "لطفاً یک دستگاه را انتخاب کنید.";
    } else {
        $handle = fopen($file, "r");
        if ($handle !== FALSE) {
            // Skip header row if it exists
            fgetcsv($handle, 1000, ",");

            $stmt = $conn->prepare("INSERT INTO device_bom (device_id, item_code, item_name, quantity_needed) VALUES (?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 3) {
                    $item_code = $data[0];
                    $item_name = $data[1];
                    $quantity_needed = (int)$data[2];

                    $stmt->bind_param("issi", $device_id, $item_code, $item_name, $quantity_needed);
                    $stmt->execute();
                }
            }
            
            $stmt->close();
            fclose($handle);
            $message = "لیست قطعات با موفقیت وارد شد.";
        } else {
            $error = "خطا در باز کردن فایل.";
        }
    }
}

// Get list of devices for the dropdown
$devices_result = $conn->query("SELECT device_id, device_name FROM devices ORDER BY device_name");
$devices = [];
if ($devices_result) {
    while ($row = $devices_result->fetch_assoc()) {
        $devices[] = $row;
    }
?>

<?php get_template_part('header'); ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-import"></i> ورود لیست قطعات (BOM) از فایل CSV</h2>
        <a href="devices.php" class="btn btn-secondary">بازگشت به لیست دستگاه‌ها</a>
    </div>
    <p>فایل CSV باید شامل ستون‌های زیر به ترتیب باشد: <strong>کد قطعه</strong>، <strong>نام قطعه</strong>، <strong>تعداد مورد نیاز</strong>. ردیف اول به عنوان سربرگ در نظر گرفته نمی‌شود.</p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form action="import_bom.php" method="post" enctype="multipart/form-data" class="card p-4">
        <div class="mb-3">
            <label for="device_id" class="form-label">انتخاب دستگاه:</label>
            <select name="device_id" id="device_id" class="form-select" required>
                <option value="">یک دستگاه را انتخاب کنید...</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?= $device['device_id'] ?>"><?= htmlspecialchars($device['device_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="bom_file" class="form-label">فایل BOM (CSV):</label>
            <input type="file" name="bom_file" id="bom_file" class="form-control" accept=".csv" required>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">آپلود و پردازش</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
