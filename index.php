<?php
// بررسی نصب سیستم
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}

require_once 'config.php';
require_once 'includes/functions.php';
// اطمینان از وجود جدول settings
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_name VARCHAR(64) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
// اجرای مایگریشن پس از تایید اپراتور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    require_once __DIR__ . '/migrate.php';
    header('Location: index.php');
    exit;
}

// دریافت اطلاعات کسب و کار
$business_info = getBusinessInfo();

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
    <title><?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .card { margin-bottom: 1rem; }
        .main-menu {
            margin-bottom: 2rem;
        }
        .main-menu .nav-link {
            font-size: 1.1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="container">
    <?php checkMigrationsPrompt(); ?>
    <nav class="main-menu navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold">📦 <?php echo htmlspecialchars($business_info['business_name']); ?></span>
        </div>
    </nav>
    <h2 class="mb-4">📦 <?php echo htmlspecialchars($business_info['business_name']); ?></h2>
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
                    <h5 class="card-title">مدیریت موجودی انبار</h5>
                    <p class="card-text">جستجو، مشاهده و مدیریت موجودی کالاها.</p>
                    <a href="inventory_records.php" class="btn btn-primary">مشاهده</a>
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
    <h2 class="mb-4">🏭 مدیریت تولید</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">ثبت سفارش تولید</h5>
                    <p class="card-text">ایجاد سفارش جدید برای تولید محصول.</p>
                    <a href="new_production_order.php" class="btn btn-primary">ثبت سفارش</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">لیست سفارشات تولید</h5>
                    <p class="card-text">مشاهده و مدیریت سفارشات تولید.</p>
                    <a href="production_orders.php" class="btn btn-primary">مشاهده</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">تامین‌کنندگان</h5>
                    <p class="card-text">مدیریت لیست تامین‌کنندگان و قطعات.</p>
                    <a href="suppliers.php" class="btn btn-primary">مدیریت</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">دستگاه‌ها و BOM</h5>
                    <p class="card-text">مدیریت لیست دستگاه‌ها و قطعات آن‌ها.</p>
                    <a href="devices.php" class="btn btn-primary">مدیریت</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<footer class="text-center py-3" style="font-size:0.9rem;color:#6c757d;border-top:1px solid #dee2e6;margin-top:2rem;">
    <div class="mb-2">
        <a href="settings.php" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-gear"></i> تنظیمات سیستم
        </a>
    </div>
    <small>
        © <?php echo date('Y'); ?> سیستم انبارداری | سازنده: <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a> | 
        <a href="https://github.com/m-alizadeh7" target="_blank">GitHub</a> | 
        <a href="https://t.me/alizadeh_channel" target="_blank">Telegram</a>
    </small>
</footer>
</body>
</html>