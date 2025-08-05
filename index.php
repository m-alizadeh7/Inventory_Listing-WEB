<?php
// بررسی نصب سیستم
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}

require_once 'config.php';

// بررسی نیاز به آپدیت
if (defined('SYSTEM_VERSION')) {
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'system_version'");
    if ($result && $row = $result->fetch_assoc()) {
        if (version_compare($row['setting_value'], SYSTEM_VERSION, '<')) {
            header('Location: setup.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سیستم انبارداری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .card { margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">📦 سیستم انبارداری</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">انبارداری جدید</h5>
                    <p class="card-text">شروع یک انبارداری جدید برای ثبت موجودی‌ها.</p>
                    <a href="new_inventory.php" class="btn btn-primary">شروع</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">وارد کردن لیست انبار</h5>
                    <p class="card-text">آپلود فایل CSV برای به‌روزرسانی لیست کالاها.</p>
                    <a href="import_inventory.php" class="btn btn-primary">آپلود</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">مشاهده/ویرایش انبارداری‌ها</h5>
                    <p class="card-text">مشاهده گزارش‌های انبارداری و دانلود آن‌ها.</p>
                    <a href="view_inventories.php" class="btn btn-primary">مشاهده</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>