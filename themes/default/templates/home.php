<?php
// Home template for default theme. Expects $business_info to be available.
?>
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
