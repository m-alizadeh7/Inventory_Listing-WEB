<?php
/**
 * Navigation template part
 * Used in header.php
 */

// Get current user info
global $security;
$current_user = $security ? $security->getCurrentUser() : null;
?>
<nav class="main-menu navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-secondary d-lg-none me-2" id="mobileMenuBtn" aria-label="باز کردن منو">
                <i class="fas fa-bars"></i>
            </button>
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo BASE_PATH; ?>">
                <span class="brand-icon">📦</span>
                <span class="ms-2 d-none d-sm-inline-block"><?php echo htmlspecialchars($business_info['business_name'] ?? 'سیستم انبارداری'); ?></span>
            </a>
        </div>

        <div class="d-none d-lg-flex align-items-center desktop-actions">
            <!-- Inventory Management Dropdown -->
            <?php if ($security && $security->hasPermission('inventory.view')): ?>
            <div class="dropdown me-2">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-warehouse me-1"></i>مدیریت انبار
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>inventory_records.php"><i class="fas fa-list me-2"></i>موجودی انبار</a></li>
                    <?php if ($security->hasPermission('inventory.categories')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>inventory_categories.php"><i class="fas fa-tags me-2"></i>گروه‌های کالا</a></li>
                    <?php endif; ?>
                    <?php if ($security->hasPermission('inventory.count')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>physical_count.php"><i class="fas fa-clipboard-list me-2"></i>شمارش فیزیکی</a></li>
                    <?php endif; ?>
                    <?php if ($security->hasPermission('inventory.withdraw')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>manual_withdrawals.php"><i class="fas fa-minus-circle me-2"></i>خروج موردی</a></li>
                    <?php endif; ?>
                    <?php if ($security->hasPermission('inventory.manage')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>emergency_notes.php"><i class="fas fa-exclamation-triangle me-2"></i>یادداشت‌های اضطراری</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>new_inventory.php"><i class="fas fa-plus me-2"></i>انبارگردانی جدید</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>view_inventories.php"><i class="fas fa-chart-line me-2"></i>گزارش‌ها</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Production Management Dropdown -->
            <?php if ($security && $security->hasPermission('production.view')): ?>
            <div class="dropdown me-2">
                <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-industry me-1"></i>تولید
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>production_orders.php"><i class="fas fa-list-alt me-2"></i>سفارشات تولید</a></li>
                    <?php if ($security->hasPermission('production.manage')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>new_production_order.php"><i class="fas fa-plus me-2"></i>سفارش جدید</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>devices.php"><i class="fas fa-cog me-2"></i>دستگاه‌ها</a></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>device_bom.php"><i class="fas fa-list me-2"></i>BOM دستگاه‌ها</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <a href="<?php echo BASE_PATH; ?>index.php" class="btn btn-sm btn-outline-secondary me-3">
                <i class="fas fa-home me-1"></i>صفحه اصلی
            </a>
            
            <!-- User Menu -->
            <?php if ($current_user): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($current_user['full_name']); ?>
                    <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($current_user['role_name_fa'] ?? $current_user['role_name']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo htmlspecialchars($current_user['full_name']); ?>
                    </h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($security->hasPermission('users.manage')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>users.php"><i class="fas fa-users me-2"></i>مدیریت کاربران</a></li>
                    <?php endif; ?>
                    <?php if ($security->hasPermission('system.admin')): ?>
                    <li><a class="dropdown-item" href="<?php echo BASE_PATH; ?>settings.php"><i class="fas fa-cog me-2"></i>تنظیمات سیستم</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?php echo BASE_PATH; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>خروج</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- mobile offcanvas menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileMenuLabel">
            <i class="fas fa-warehouse me-2"></i>
            <?php echo htmlspecialchars($business_info['business_name'] ?? 'منو'); ?>
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Inventory Management Section -->
        <div class="mb-4">
            <h6 class="text-muted mb-3">
                <i class="fas fa-warehouse me-2"></i>مدیریت انبار
            </h6>
            <ul class="list-unstyled ps-3">
                <li><a href="inventory_records.php" class="d-block py-2 text-decoration-none"><i class="fas fa-list me-2"></i>موجودی انبار</a></li>
                <li><a href="inventory_categories.php" class="d-block py-2 text-decoration-none"><i class="fas fa-tags me-2"></i>گروه‌های کالا</a></li>
                <li><a href="physical_count.php" class="d-block py-2 text-decoration-none"><i class="fas fa-clipboard-list me-2"></i>شمارش فیزیکی</a></li>
                <li><a href="manual_withdrawals.php" class="d-block py-2 text-decoration-none"><i class="fas fa-minus-circle me-2"></i>خروج موردی</a></li>
                <li><a href="emergency_notes.php" class="d-block py-2 text-decoration-none"><i class="fas fa-exclamation-triangle me-2"></i>یادداشت‌های اضطراری</a></li>
                <li><a href="new_inventory.php" class="d-block py-2 text-decoration-none"><i class="fas fa-plus me-2"></i>انبارگردانی جدید</a></li>
                <li><a href="view_inventories.php" class="d-block py-2 text-decoration-none"><i class="fas fa-chart-line me-2"></i>گزارش‌ها</a></li>
            </ul>
        </div>

        <!-- Production Management Section -->
        <div class="mb-4">
            <h6 class="text-muted mb-3">
                <i class="fas fa-industry me-2"></i>مدیریت تولید
            </h6>
            <ul class="list-unstyled ps-3">
                <li><a href="production_orders.php" class="d-block py-2 text-decoration-none"><i class="fas fa-list-alt me-2"></i>سفارشات تولید</a></li>
                <li><a href="new_production_order.php" class="d-block py-2 text-decoration-none"><i class="fas fa-plus me-2"></i>سفارش جدید</a></li>
                <li><a href="devices.php" class="d-block py-2 text-decoration-none"><i class="fas fa-cog me-2"></i>دستگاه‌ها</a></li>
                <li><a href="device_bom.php" class="d-block py-2 text-decoration-none"><i class="fas fa-list me-2"></i>BOM دستگاه‌ها</a></li>
            </ul>
        </div>

        <!-- Other Sections -->
        <div class="mb-4">
            <h6 class="text-muted mb-3">
                <i class="fas fa-cogs me-2"></i>سایر بخش‌ها
            </h6>
            <ul class="list-unstyled ps-3">
                <li><a href="suppliers.php" class="d-block py-2 text-decoration-none"><i class="fas fa-truck me-2"></i>تامین‌کنندگان</a></li>
                <li><a href="settings.php" class="d-block py-2 text-decoration-none"><i class="fas fa-cog me-2"></i>تنظیمات</a></li>
            </ul>
        </div>
    </div>
</div>
