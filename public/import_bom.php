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
}
?>

<?php get_template_part('header'); ?>
<style>
    body {
        font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin: 2rem auto;
        max-width: 800px;
    }
    
    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .btn-primary {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .btn-secondary {
        border-radius: 10px;
        padding: 0.75rem 2rem;
        font-weight: 600;
    }
    
    .alert {
        border-radius: 10px;
        border: none;
    }
    
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    h2 {
        color: #333;
        font-weight: 700;
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 0.5rem;
    }
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-wrapper input[type=file] {
        position: absolute;
        left: -9999px;
    }
    
    .file-input-label {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border: 2px dashed #667eea;
        border-radius: 10px;
        background: #f8f9ff;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .file-input-label:hover {
        background: #eef0ff;
        border-color: #5a67d8;
    }
    
    .file-input-label i {
        font-size: 2rem;
        color: #667eea;
        margin-left: 1rem;
    }
</style>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-import"></i> ورود لیست قطعات (BOM) از فایل CSV</h2>
        <a href="devices.php" class="btn btn-secondary">بازگشت به لیست دستگاه‌ها</a>
    </div>
    <p class="text-muted mb-4">فایل CSV باید شامل ستون‌های زیر به ترتیب باشد: <strong>کد قطعه</strong>، <strong>نام قطعه</strong>، <strong>تعداد مورد نیاز</strong>. ردیف اول به عنوان سربرگ در نظر گرفته نمی‌شود.</p>

    <?php if ($message): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $error ?></div>
    <?php endif; ?>

    <form action="import_bom.php" method="post" enctype="multipart/form-data" class="card p-4">
        <div class="mb-4">
            <label for="device_id" class="form-label">انتخاب دستگاه:</label>
            <select name="device_id" id="device_id" class="form-select" required>
                <option value="">یک دستگاه را انتخاب کنید...</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?= $device['device_id'] ?>"><?= htmlspecialchars($device['device_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
            <label for="bom_file" class="form-label">فایل BOM (CSV):</label>
            <div class="file-input-wrapper">
                <input type="file" name="bom_file" id="bom_file" class="form-control" accept=".csv" required>
                <label for="bom_file" class="file-input-label">
                    <i class="bi bi-cloud-upload"></i>
                    <div>
                        <strong>انتخاب فایل CSV</strong><br>
                        <small class="text-muted">فایل BOM دستگاه را اینجا رها کنید یا کلیک کنید</small>
                    </div>
                </label>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="devices.php" class="btn btn-secondary">انصراف</a>
            <button type="submit" class="btn btn-primary">آپلود و پردازش</button>
        </div>
    </form>
</div>
<?php get_template_part('footer'); ?>
