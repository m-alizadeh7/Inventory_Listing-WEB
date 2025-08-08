<?php
/**
 * قالب داشبورد
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */
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
                <a href="index.php?controller=inventory&action=new" class="btn btn-primary w-100">
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
                <a href="index.php?controller=inventory&action=index" class="btn btn-info w-100">
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
                <a href="index.php?controller=inventory&action=index" class="btn btn-success w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="small-box bg-info text-white">
            <div class="inner">
                <h3><?php echo $stats['total_inventory']; ?></h3>
                <p>کل اقلام انبار</p>
            </div>
            <div class="icon">
                <i class="bi bi-box"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="small-box bg-warning text-dark">
            <div class="inner">
                <h3><?php echo $stats['low_stock']; ?></h3>
                <p>اقلام با موجودی کم</p>
            </div>
            <div class="icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="small-box bg-success text-white">
            <div class="inner">
                <h3><?php echo $stats['pending_orders']; ?></h3>
                <p>سفارشات در انتظار</p>
            </div>
            <div class="icon">
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="small-box bg-danger text-white">
            <div class="inner">
                <h3><?php echo $stats['total_devices']; ?></h3>
                <p>تعداد دستگاه‌ها</p>
            </div>
            <div class="icon">
                <i class="bi bi-gear"></i>
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
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-plus-circle text-primary fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">سفارش تولید جدید</h5>
                </div>
                <p class="card-text">ثبت سفارش تولید جدید برای دستگاه‌ها.</p>
                <a href="index.php?controller=production&action=new_order" class="btn btn-primary w-100">
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
                        <i class="bi bi-list-check text-info fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">مدیریت سفارشات تولید</h5>
                </div>
                <p class="card-text">مشاهده و مدیریت سفارشات تولید.</p>
                <a href="index.php?controller=production&action=index" class="btn btn-info w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-tools text-warning fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">مدیریت دستگاه‌ها و BOM</h5>
                </div>
                <p class="card-text">تعریف و مدیریت دستگاه‌ها و قطعات آن‌ها.</p>
                <a href="index.php?controller=device&action=index" class="btn btn-warning w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
</div>

<h2 class="section-title mt-5">👥 مدیریت تامین‌کنندگان</h2>
<div class="row">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-people text-success fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">مدیریت تامین‌کنندگان</h5>
                </div>
                <p class="card-text">ثبت، ویرایش و مدیریت اطلاعات تامین‌کنندگان.</p>
                <a href="#" class="btn btn-success w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-cart-check text-secondary fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">قطعات تامین‌کنندگان</h5>
                </div>
                <p class="card-text">مدیریت قطعات قابل تامین توسط هر تامین‌کننده.</p>
                <a href="#" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
</div>
