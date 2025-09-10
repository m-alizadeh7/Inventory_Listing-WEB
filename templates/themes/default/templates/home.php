<?php
/**
 * Professional Home template for default theme with sidebar layout.
 * Expects $business_info to be available.
 */

// Make database connection available
global $conn;

// Load alerts component
get_theme_part('alerts');

// Check for pending migrations
checkMigrationsPrompt();
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <?php get_theme_part('sidebar'); ?>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1 class="welcome-title">
                    <i class="bi bi-house-door"></i>
                    <?php echo htmlspecialchars($business_info['business_name'] ?? 'سیستم مدیریت انبار'); ?>
                </h1>
                <p class="welcome-subtitle">مدیریت هوشمند موجودی، انبارگردانی و تولید</p>
            </div>

            <!-- Dashboard Statistics -->
            <?php
            // Get dashboard statistics
            $inventory_count = $conn->query("SELECT COUNT(*) as count FROM inventory")->fetch_assoc()['count'] ?? 0;
            $devices_count = $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'] ?? 0;
            $orders_count = $conn->query("SELECT COUNT(*) as count FROM production_orders")->fetch_assoc()['count'] ?? 0;
            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM production_orders WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;

            $stats = array(
                array(
                    'icon' => 'bi-box-seam',
                    'value' => number_format($inventory_count),
                    'label' => 'کالاهای موجود',
                    'bg_class' => 'bg-primary',
                    'text_class' => 'text-white'
                ),
                array(
                    'icon' => 'bi-hdd-stack',
                    'value' => number_format($devices_count),
                    'label' => 'دستگاه‌ها',
                    'bg_class' => 'bg-info',
                    'text_class' => 'text-white'
                ),
                array(
                    'icon' => 'bi-list-check',
                    'value' => number_format($orders_count),
                    'label' => 'سفارشات تولید',
                    'bg_class' => 'bg-success',
                    'text_class' => 'text-white'
                ),
                array(
                    'icon' => 'bi-clock-history',
                    'value' => number_format($pending_orders),
                    'label' => 'سفارشات در انتظار',
                    'bg_class' => 'bg-warning',
                    'text_class' => 'text-dark'
                )
            );
            ?>

            <div class="dashboard-cards">
                <?php foreach ($stats as $stat): ?>
                <div class="dashboard-card">
                    <div class="card-icon <?php echo $stat['bg_class']; ?> <?php echo $stat['text_class']; ?>">
                        <i class="<?php echo $stat['icon']; ?>"></i>
                    </div>
                    <div class="card-value"><?php echo $stat['value']; ?></div>
                    <div class="card-label"><?php echo $stat['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick Actions -->
            <div class="mb-4">
                <h2 class="section-title">
                    <i class="bi bi-lightning-fill"></i>
                    دسترسی سریع
                </h2>

                <div class="quick-actions">
                    <a href="public/new_inventory.php" class="quick-action-card">
                        <div class="quick-action-icon bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <h5 class="quick-action-title">انبارگردانی جدید</h5>
                        <p class="quick-action-text">شروع یک انبارگردانی جدید</p>
                        <span class="quick-action-btn btn-primary">
                            <i class="bi bi-arrow-left"></i>
                            شروع
                        </span>
                    </a>

                    <a href="public/new_production_order.php" class="quick-action-card">
                        <div class="quick-action-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-plus-square"></i>
                        </div>
                        <h5 class="quick-action-title">سفارش تولید جدید</h5>
                        <p class="quick-action-text">ایجاد سفارش تولید جدید</p>
                        <span class="quick-action-btn btn-warning">
                            <i class="bi bi-arrow-left"></i>
                            ایجاد
                        </span>
                    </a>

                    <a href="public/settings.php" class="quick-action-card">
                        <div class="quick-action-icon bg-info bg-opacity-10 text-info">
                            <i class="bi bi-gear"></i>
                        </div>
                        <h5 class="quick-action-title">تنظیمات سیستم</h5>
                        <p class="quick-action-text">مدیریت تنظیمات سیستم</p>
                        <span class="quick-action-btn btn-info">
                            <i class="bi bi-arrow-left"></i>
                            تنظیمات
                        </span>
                    </a>

                    <a href="public/backup.php" class="quick-action-card">
                        <div class="quick-action-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-download"></i>
                        </div>
                        <h5 class="quick-action-title">پشتیبان‌گیری</h5>
                        <p class="quick-action-text">ایجاد پشتیبان از داده‌ها</p>
                        <span class="quick-action-btn btn-success">
                            <i class="bi bi-arrow-left"></i>
                            پشتیبان‌گیری
                        </span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <h5 class="section-title mb-3">
                            <i class="bi bi-activity"></i>
                            فعالیت‌های اخیر
                        </h5>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-clock-history fs-1 mb-2"></i>
                            <p>فعالیت‌های اخیر به زودی نمایش داده خواهد شد</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <h5 class="section-title mb-3">
                            <i class="bi bi-bar-chart"></i>
                            آمار سریع
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-primary">
                                        <?php echo $conn->query("SELECT COUNT(*) as count FROM suppliers")->fetch_assoc()['count'] ?? 0; ?>
                                    </div>
                                    <small class="text-muted">تامین‌کننده</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-info">
                                        <?php echo $conn->query("SELECT COUNT(*) as count FROM inventory_categories")->fetch_assoc()['count'] ?? 0; ?>
                                    </div>
                                    <small class="text-muted">گروه کالا</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Toggle -->
<button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-lg-none" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close sidebar when clicking on a link (mobile)
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            toggleSidebar();
        }
    });
});
</script>
