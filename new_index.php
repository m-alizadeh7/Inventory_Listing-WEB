<?php
/**
 * صفحه اصلی سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// بررسی نصب سیستم
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}

// لود کردن فایل‌های مورد نیاز
require_once 'config.php';
require_once 'core/includes/database.php';
require_once 'core/includes/functions.php';

// متغیرهای مورد نیاز
$page_title = 'صفحه اصلی - سیستم مدیریت انبار';
$business_info = getBusinessInfo();

// لود کردن هدر
include 'templates/default/header.php';
?>

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

<?php
// لود کردن فوتر
include 'templates/default/footer.php';
?>
