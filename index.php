<?php
// بررسی نصب سیستم
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}
require_once 'config.php';
require_once 'includes/functions.php';

// پردازش درخواست migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    runMigrations();
    header('Location: index.php?msg=migration_complete');
    exit;
}

$business_info = getBusinessInfo();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سیستم انبارداری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        .content-wrapper {
            flex: 1 0 auto;
            padding: 2rem 0;
        }
        .card { 
            margin-bottom: 1.5rem; 
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .main-menu {
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .main-menu .nav-link {
            font-size: 1.1rem;
            font-weight: 500;
        }
        .section-title {
            position: relative;
            padding-right: 15px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        .section-title::before {
            content: "";
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 25px;
            background-color: #0d6efd;
            border-radius: 5px;
        }
        .card-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #333;
        }
        .card-text {
            color: #6c757d;
            margin-bottom: 1.25rem;
        }
        .footer {
            flex-shrink: 0;
            background-color: #fff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem 0;
            margin-top: 2rem;
        }
        .footer-links a {
            color: #6c757d;
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.3s;
        }
        .footer-links a:hover {
            color: #0d6efd;
        }
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem 0;
            }
            .section-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<nav class="main-menu navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold">📦 <?php echo htmlspecialchars($business_info['business_name']); ?></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <!-- منوهای اصلی حذف شده -->
            </ul>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'migration_complete'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                به‌روزرسانی دیتابیس با موفقیت انجام شد!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php checkMigrationsPrompt(); ?>
        
        <h2 class="section-title">📦 مدیریت انبار</h2>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-plus-circle text-primary fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">انبارگردانی جدید</h5>
                        </div>
                        <p class="card-text">شروع یک انبارگردانی جدید برای ثبت موجودی‌ها.</p>
                        <a href="new_inventory.php" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> شروع
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-box-seam text-info fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">مدیریت موجودی انبار</h5>
                        </div>
                        <p class="card-text">جستجو، مشاهده و مدیریت موجودی کالاها.</p>
                        <a href="inventory_records.php" class="btn btn-info w-100">
                            <i class="bi bi-arrow-right-circle"></i> مشاهده
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-clipboard-check text-success fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">گزارش‌های انبارداری</h5>
                        </div>
                        <p class="card-text">مشاهده گزارش‌های انبارداری و دانلود آن‌ها.</p>
                        <a href="view_inventories.php" class="btn btn-success w-100">
                            <i class="bi bi-arrow-right-circle"></i> مشاهده
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="section-title mt-5">🏭 مدیریت تولید</h2>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-plus-square text-warning fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">ثبت سفارش تولید</h5>
                        </div>
                        <p class="card-text">ایجاد سفارش جدید برای تولید محصول.</p>
                        <a href="new_production_order.php" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-right-circle"></i> ثبت سفارش
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-list-check text-danger fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">لیست سفارشات تولید</h5>
                        </div>
                        <p class="card-text">مشاهده و مدیریت سفارشات تولید.</p>
                        <a href="production_orders.php" class="btn btn-danger w-100">
                            <i class="bi bi-arrow-right-circle"></i> مشاهده
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-secondary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-hdd-stack text-secondary fs-4"></i>
                            </div>
                            <h5 class="card-title mb-0">دستگاه‌ها و BOM</h5>
                        </div>
                        <p class="card-text">مدیریت لیست دستگاه‌ها و قطعات آن‌ها.</p>
                        <a href="devices.php" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-right-circle"></i> مدیریت
                        </a>
                    </div>
                </div>
            </div>
        </div>
</div>

<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">© <?php echo date('Y'); ?> سیستم انبارداری <?php echo htmlspecialchars($business_info['business_name']); ?></p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="footer-links">
                    <a href="settings.php">
                        <i class="bi bi-gear"></i> تنظیمات
                    </a>
                    <a href="https://github.com/m-alizadeh7" target="_blank">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                    <a href="https://t.me/alizadeh_channel" target="_blank">
                        <i class="bi bi-telegram"></i> Telegram
                    </a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>