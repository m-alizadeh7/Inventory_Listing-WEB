<?php
/**
 * Sidebar Navigation Template
 * WordPress-like sidebar for dashboard navigation with SPA support
 */

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];
?>

<!-- Sidebar -->
<div class="dashboard-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-boxes fs-3 text-primary"></i>
            <span class="sidebar-title">مدیریت انبار</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <div class="nav-section">
            <div class="nav-section-title" onclick="toggleNavSection(this)">
                <i class="bi bi-house-door"></i>
                <span>داشبرد</span>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>" data-page="dashboard" data-url="index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>داشبرد اصلی</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Inventory Management -->
        <div class="nav-section">
            <div class="nav-section-title" onclick="toggleNavSection(this)">
                <i class="bi bi-box-seam"></i>
                <span>مدیریت انبار</span>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'inventory_records.php') !== false) ? 'active' : ''; ?>" data-page="inventory_records" data-url="public/inventory_records.php">
                        <i class="bi bi-list-ul"></i>
                        <span>موجودی انبار</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'inventory_categories.php') !== false) ? 'active' : ''; ?>" data-page="inventory_categories" data-url="public/inventory_categories.php">
                        <i class="bi bi-tags"></i>
                        <span>گروه‌های کالا</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'physical_count.php') !== false) ? 'active' : ''; ?>" data-page="physical_count" data-url="public/physical_count.php">
                        <i class="bi bi-clipboard-check"></i>
                        <span>شمارش فیزیکی</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'manual_withdrawals.php') !== false) ? 'active' : ''; ?>" data-page="manual_withdrawals" data-url="public/manual_withdrawals.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>خروج موردی</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'emergency_notes.php') !== false) ? 'active' : ''; ?>" data-page="emergency_notes" data-url="public/emergency_notes.php">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>یادداشت‌های اضطراری</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'new_inventory.php') !== false) ? 'active' : ''; ?>" data-page="new_inventory" data-url="public/new_inventory.php">
                        <i class="bi bi-plus-circle"></i>
                        <span>انبارگردانی جدید</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Production Management -->
        <div class="nav-section">
            <div class="nav-section-title" onclick="toggleNavSection(this)">
                <i class="bi bi-gear-fill"></i>
                <span>مدیریت تولید</span>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'new_production_order.php') !== false) ? 'active' : ''; ?>" data-page="new_production_order" data-url="public/new_production_order.php">
                        <i class="bi bi-plus-square"></i>
                        <span>سفارش جدید</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'production_orders.php') !== false) ? 'active' : ''; ?>" data-page="production_orders" data-url="public/production_orders.php">
                        <i class="bi bi-list-check"></i>
                        <span>مدیریت سفارشات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'devices.php') !== false) ? 'active' : ''; ?>" data-page="devices" data-url="public/devices.php">
                        <i class="bi bi-hdd-stack"></i>
                        <span>دستگاه‌ها و BOM</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- System Management -->
        <div class="nav-section">
            <div class="nav-section-title" onclick="toggleNavSection(this)">
                <i class="bi bi-gear-fill"></i>
                <span>مدیریت سیستم</span>
                <i class="bi bi-chevron-down toggle-icon"></i>
            </div>
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'suppliers.php') !== false) ? 'active' : ''; ?>" data-page="suppliers" data-url="public/suppliers.php">
                        <i class="bi bi-truck"></i>
                        <span>تامین‌کنندگان</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'settings.php') !== false) ? 'active' : ''; ?>" data-page="settings" data-url="public/settings.php">
                        <i class="bi bi-gear"></i>
                        <span>تنظیمات سیستم</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link <?php echo (strpos($current_path, 'backup.php') !== false) ? 'active' : ''; ?>" data-page="backup" data-url="public/backup.php">
                        <i class="bi bi-download"></i>
                        <span>پشتیبان‌گیری</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'کاربر'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            <span>خروج</span>
        </a>
    </div>
</div>
