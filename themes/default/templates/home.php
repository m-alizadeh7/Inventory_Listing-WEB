<?php
/**
 * Professional Home template for default theme. 
 * Expects $business_info to be available.
 */

// Make database connection available
global $conn;

// Load alerts component
get_theme_part('alerts');

// Check for pending migrations
checkMigrationsPrompt(); 

// Page header data
$header_args = array(
    'title' => 'سیستم مدیریت انبار ' . ($business_info['business_name'] ?? ''),
    'subtitle' => 'مدیریت هوشمند موجودی، انبارگردانی و تولید',
    'icon' => 'bi bi-boxes'
);

get_theme_part('page-header', $header_args);
?>

<div class="home-dashboard">
    <!-- Dashboard Statistics -->
    <?php
    // Get dashboard statistics
    $inventory_count = $conn->query("SELECT COUNT(*) as count FROM inventory")->fetch_assoc()['count'] ?? 0;
    $devices_count = $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'] ?? 0;
    $orders_count = $conn->query("SELECT COUNT(*) as count FROM production_orders")->fetch_assoc()['count'] ?? 0;
    $pending_orders = $conn->query("SELECT COUNT(*) as count FROM production_orders WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
    
    $stats = array(
        array(
            'icon' => 'bi bi-box-seam',
            'value' => number_format($inventory_count),
            'label' => 'کالاهای موجود',
            'icon_color' => 'text-primary'
        ),
        array(
            'icon' => 'bi bi-hdd-stack',
            'value' => number_format($devices_count),
            'label' => 'دستگاه‌ها',
            'icon_color' => 'text-info'
        ),
        array(
            'icon' => 'bi bi-list-check',
            'value' => number_format($orders_count),
            'label' => 'سفارشات تولید',
            'icon_color' => 'text-success'
        ),
        array(
            'icon' => 'bi bi-clock-history',
            'value' => number_format($pending_orders),
            'label' => 'سفارشات در انتظار',
            'icon_color' => 'text-warning'
        )
    );
    
    include ACTIVE_THEME_PATH . '/template-parts/stats-cards.php';
    ?>

    <!-- Inventory Management Section -->
    <section class="mb-5 fade-in-delay-1">
        <h2 class="section-title">
            <i class="bi bi-box-seam me-2"></i>
            مدیریت انبار
        </h2>
        <p class="text-muted mb-4">مدیریت موجودی و انبارگردانی‌های سیستم</p>
        
        <div class="row g-4">
            <?php 
            // Inventory management cards
            $inventory_cards = array(
                array(
                    'title' => 'مدیریت موجودی انبار',
                    'icon' => 'bi-box-seam',
                    'text' => 'جستجو، مشاهده و مدیریت موجودی کالاها با امکانات پیشرفته.',
                    'link' => 'inventory_records.php',
                    'link_text' => 'مشاهده موجودی',
                    'icon_bg' => 'bg-primary bg-opacity-10',
                    'icon_color' => 'text-primary',
                    'button_class' => 'btn-primary'
                ),
                array(
                    'title' => 'گروه‌های کالا',
                    'icon' => 'bi-tags',
                    'text' => 'مدیریت گروه‌بندی کالاها برای سازماندهی بهتر انبار.',
                    'link' => 'inventory_categories.php',
                    'link_text' => 'مدیریت گروه‌ها',
                    'icon_bg' => 'bg-info bg-opacity-10',
                    'icon_color' => 'text-info',
                    'button_class' => 'btn-info'
                ),
                array(
                    'title' => 'شمارش فیزیکی',
                    'icon' => 'bi-clipboard-check',
                    'text' => 'انجام شمارش فیزیکی انبار با امکان شمارش گروهی یا کامل.',
                    'link' => 'physical_count.php',
                    'link_text' => 'شمارش فیزیکی',
                    'icon_bg' => 'bg-success bg-opacity-10',
                    'icon_color' => 'text-success',
                    'button_class' => 'btn-success'
                ),
                array(
                    'title' => 'خروج موردی کالا',
                    'icon' => 'bi-box-arrow-right',
                    'text' => 'ثبت خروج موردی کالاها از انبار با ذکر دلیل و مسئول.',
                    'link' => 'manual_withdrawals.php',
                    'link_text' => 'خروج موردی',
                    'icon_bg' => 'bg-warning bg-opacity-10',
                    'icon_color' => 'text-warning',
                    'button_class' => 'btn-warning'
                ),
                array(
                    'title' => 'یادداشت‌های اضطراری',
                    'icon' => 'bi-exclamation-triangle',
                    'text' => 'ثبت و مدیریت یادداشت‌های اضطراری و هشدارهای انبار.',
                    'link' => 'emergency_notes.php',
                    'link_text' => 'یادداشت‌ها',
                    'icon_bg' => 'bg-danger bg-opacity-10',
                    'icon_color' => 'text-danger',
                    'button_class' => 'btn-danger'
                ),
                array(
                    'title' => 'انبارگردانی جدید',
                    'icon' => 'bi-plus-circle',
                    'text' => 'شروع یک انبارگردانی جدید برای ثبت موجودی‌ها و کنترل دقیق انبار.',
                    'link' => 'new_inventory.php',
                    'link_text' => 'انبارگردانی جدید',
                    'icon_bg' => 'bg-secondary bg-opacity-10',
                    'icon_color' => 'text-secondary',
                    'button_class' => 'btn-secondary'
                )
            );
            
            // Render inventory cards
            foreach ($inventory_cards as $card) {
                the_dashboard_card($card);
            }
            ?>
        </div>
    </section>

    <!-- Production Management Section -->
    <section class="mb-5 fade-in-delay-2">
        <h2 class="section-title">
            <i class="bi bi-gear-fill me-2"></i>
            مدیریت تولید
        </h2>
        <p class="text-muted mb-4">ثبت و پیگیری سفارشات تولید و مدیریت دستگاه‌ها</p>
        
        <div class="row g-4">
            <?php 
            // Production management cards
            $production_cards = array(
                array(
                    'title' => 'ثبت سفارش تولید',
                    'icon' => 'bi-plus-square',
                    'text' => 'ایجاد سفارش جدید برای تولید محصولات با مدیریت قطعات.',
                    'link' => 'new_production_order.php',
                    'link_text' => 'ثبت سفارش جدید',
                    'icon_bg' => 'bg-warning bg-opacity-10',
                    'icon_color' => 'text-warning',
                    'button_class' => 'btn-warning'
                ),
                array(
                    'title' => 'مدیریت سفارشات تولید',
                    'icon' => 'bi-list-check',
                    'text' => 'مشاهده، ویرایش و پیگیری وضعیت سفارشات تولید.',
                    'link' => 'production_orders.php',
                    'link_text' => 'مدیریت سفارشات',
                    'icon_bg' => 'bg-danger bg-opacity-10',
                    'icon_color' => 'text-danger',
                    'button_class' => 'btn-danger'
                ),
                array(
                    'title' => 'دستگاه‌ها و BOM',
                    'icon' => 'bi-hdd-stack',
                    'text' => 'مدیریت لیست دستگاه‌ها، قطعات و ساختار محصولات.',
                    'link' => 'devices.php',
                    'link_text' => 'مدیریت دستگاه‌ها',
                    'icon_bg' => 'bg-secondary bg-opacity-10',
                    'icon_color' => 'text-secondary',
                    'button_class' => 'btn-secondary'
                )
            );
            
            // Render production cards
            foreach ($production_cards as $card) {
                the_dashboard_card($card);
            }
            ?>
        </div>
    </section>

    <!-- Quick Actions Section -->
    <section class="fade-in-delay-3">
        <h2 class="section-title">
            <i class="bi bi-lightning-fill me-2"></i>
            دسترسی سریع
        </h2>
        <p class="text-muted mb-4">ابزارها و تنظیمات سیستم</p>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card hover-lift">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-gear-fill text-info fs-4"></i>
                            </div>
                        </div>
                        <h5 class="card-title">تنظیمات سیستم</h5>
                        <p class="card-text text-muted">تنظیمات کلی سیستم و پیکربندی</p>
                        <a href="settings.php" class="btn btn-info">
                            <i class="bi bi-arrow-left-circle"></i> تنظیمات
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card hover-lift">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-truck text-primary fs-4"></i>
                            </div>
                        </div>
                        <h5 class="card-title">مدیریت تامین‌کنندگان</h5>
                        <p class="card-text text-muted">مدیریت لیست تامین‌کنندگان</p>
                        <a href="suppliers.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left-circle"></i> تامین‌کنندگان
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card hover-lift">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="bi bi-download text-success fs-4"></i>
                            </div>
                        </div>
                        <h5 class="card-title">پشتیبان‌گیری</h5>
                        <p class="card-text text-muted">پشتیبان‌گیری از اطلاعات سیستم</p>
                        <a href="backup.php" class="btn btn-success">
                            <i class="bi bi-arrow-left-circle"></i> پشتیبان‌گیری
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
