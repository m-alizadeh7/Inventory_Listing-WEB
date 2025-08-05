<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی درخواست حذف دستگاه
if (isset($_POST['delete_device'])) {
    $device_id = clean($_POST['device_id']);
    $conn->query("DELETE FROM devices WHERE device_id = $device_id");
    header('Location: devices.php?msg=deleted');
    exit;
}

// دریافت لیست دستگاه‌ها
$result = $conn->query("SELECT * FROM devices ORDER BY device_code");
$devices = [];
while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت دستگاه‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🔧 مدیریت دستگاه‌ها</h2>
        <div>
            <a href="new_device.php" class="btn btn-primary">➕ افزودن دستگاه جدید</a>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success">دستگاه جدید با موفقیت اضافه شد.</div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning">دستگاه با موفقیت حذف شد.</div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($devices)): ?>
        <div class="alert alert-info">هیچ دستگاهی ثبت نشده است.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>کد دستگاه</th>
                        <th>نام دستگاه</th>
                        <th>توضیحات</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device): ?>
                        <tr>
                            <td><?= htmlspecialchars($device['device_code']) ?></td>
                            <td><?= htmlspecialchars($device['device_name']) ?></td>
                            <td><?= htmlspecialchars($device['description'] ?? '-') ?></td>
                            <td><?= gregorianToJalali($device['created_at']) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_device.php?id=<?= $device['device_id'] ?>" 
                                       class="btn btn-sm btn-primary">ویرایش</a>
                                    <a href="device_bom.php?id=<?= $device['device_id'] ?>" 
                                       class="btn btn-sm btn-info">لیست قطعات</a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('آیا از حذف این دستگاه اطمینان دارید؟');">
                                        <input type="hidden" name="device_id" value="<?= $device['device_id'] ?>">
                                        <button type="submit" name="delete_device" class="btn btn-sm btn-danger">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
