<?php
require_once 'config.php';
require_once 'includes/functions.php';
// افزودن ستون description اگر وجود ندارد
$descExists = false;
$res = $conn->query("SHOW COLUMNS FROM devices LIKE 'description'");
if ($res && $res->num_rows === 1) {
    $descExists = true;
}
if (!$descExists) {
    $conn->query("ALTER TABLE devices ADD COLUMN description TEXT NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_code = clean($_POST['device_code']);
    $device_name = clean($_POST['device_name']);
    $description = clean($_POST['description']);

    // بررسی تکراری نبودن کد دستگاه
    $check = $conn->query("SELECT device_id FROM devices WHERE device_code = '$device_code'");
    if ($check->num_rows > 0) {
        $error = 'کد دستگاه تکراری است.';
    } else {
        // افزودن دستگاه جدید
        $sql = "INSERT INTO devices (device_code, device_name, description) 
                VALUES ('$device_code', '$device_name', '$description')";
        
        if ($conn->query($sql)) {
            header('Location: devices.php?msg=added');
            exit;
        } else {
            $error = 'خطا در ثبت دستگاه.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>افزودن دستگاه جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">➕ افزودن دستگاه جدید</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="device_code" class="form-label">کد دستگاه</label>
                            <input type="text" class="form-control" id="device_code" name="device_code" 
                                   required pattern="[A-Za-z0-9-_]+" 
                                   title="فقط حروف انگلیسی، اعداد، خط تیره و زیرخط مجاز است">
                        </div>

                        <div class="mb-3">
                            <label for="device_name" class="form-label">نام دستگاه</label>
                            <input type="text" class="form-control" id="device_name" name="device_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">ثبت دستگاه</button>
                            <a href="devices.php" class="btn btn-secondary">انصراف</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
