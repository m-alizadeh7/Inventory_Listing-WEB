<?php
require_once 'config.php';
require_once 'includes/functions.php';

// دریافت ID دستگاه
$device_id = intval($_GET['id'] ?? 0);
if ($device_id <= 0) {
    die('شناسه دستگاه نامعتبر است.');
}

// دریافت اطلاعات دستگاه
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT device_name, description FROM devices WHERE device_id = ?");
    $stmt->bind_param("i", $device_id);
    $stmt->execute();
    $device = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$device) {
        die('دستگاه مورد نظر یافت نشد.');
    }
}

// ذخیره تغییرات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $device_name = clean($_POST['device_name'] ?? '');
    $description = clean($_POST['description'] ?? '');

    if (empty($device_name)) {
        die('نام دستگاه نمی‌تواند خالی باشد.');
    }

    $stmt = $conn->prepare("UPDATE devices SET device_name = ?, description = ? WHERE device_id = ?");
    $stmt->bind_param("ssi", $device_name, $description, $device_id);
    if ($stmt->execute()) {
        header("Location: devices.php?msg=updated");
        exit;
    } else {
        die('خطا در به‌روزرسانی اطلاعات دستگاه: ' . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ویرایش دستگاه</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>ویرایش دستگاه</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="device_name" class="form-label">نام دستگاه</label>
            <input type="text" class="form-control" id="device_name" name="device_name" value="<?= htmlspecialchars($device['device_name'] ?? '') ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">توضیحات</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($device['description'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
        <a href="devices.php" class="btn btn-secondary">بازگشت</a>
    </form>
</div>
</body>
</html>
