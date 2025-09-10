<?php
/**
 * Professional Home template for default theme with SPA support.
 * Expects $business_info to be available.
 */

// Make database connection available
global $conn;

// Load alerts component
get_theme_part('alerts');

// Check for pending migrations
checkMigrationsPrompt();

// Set dashboard flag for header
$_GET['dashboard'] = '1';
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <?php get_theme_part('sidebar'); ?>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div id="main-content" class="dashboard-content">
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
            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM production_orders WHERE status IN ('draft', 'confirmed')")->fetch_assoc()['count'] ?? 0;

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
                    'label' => 'سفارشات آماده',
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
                    <a href="#" class="quick-action-card" data-page="new_inventory" data-url="public/new_inventory.php">
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

                    <a href="#" class="quick-action-card" data-page="new_production_order" data-url="public/new_production_order.php">
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

                    <a href="#" class="quick-action-card" data-page="settings" data-url="public/settings.php">
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

                    <a href="#" class="quick-action-card" data-page="backup" data-url="public/backup.php">
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
                        <div class="activity-list">
                            <?php
                            // Get recent activities (last 5 inventory records)
                            $recent_activities = $conn->query("
                                SELECT i.item_name, i.current_inventory as quantity, i.last_updated as created_at, 'inventory' as type
                                FROM inventory i
                                WHERE i.last_updated IS NOT NULL
                                ORDER BY i.last_updated DESC
                                LIMIT 5
                            ");
                            
                            if ($recent_activities && $recent_activities->num_rows > 0) {
                                while ($activity = $recent_activities->fetch_assoc()) {
                                    $time_ago = time() - strtotime($activity['created_at']);
                                    $time_text = $time_ago < 3600 ? ceil($time_ago / 60) . ' دقیقه پیش' : 
                                                ($time_ago < 86400 ? ceil($time_ago / 3600) . ' ساعت پیش' : 
                                                ceil($time_ago / 86400) . ' روز پیش');
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <strong><?php echo htmlspecialchars($activity['item_name']); ?></strong>
                                                موجودی به‌روزرسانی شد
                                            </div>
                                            <small class="activity-time"><?php echo $time_text; ?></small>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-1 mb-2"></i>
                                    <p>فعالیت‌های اخیر به زودی نمایش داده خواهد شد</p>
                                </div>
                                <?php
                            }
                            ?>
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

<!-- SPA Content Container -->
<div id="spa-content" class="spa-content" style="display: none;"></div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner"></div>
</div>
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
            $pending_orders = $conn->query("SELECT COUNT(*) as count FROM production_orders WHERE status IN ('draft', 'confirmed')")->fetch_assoc()['count'] ?? 0;

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
                    'label' => 'سفارشات آماده',
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
                        <div class="activity-list">
                            <?php
                            // Get recent activities (last 5 inventory records)
                            $recent_activities = $conn->query("
                                SELECT i.item_name, i.current_inventory as quantity, i.last_updated as created_at, 'inventory' as type
                                FROM inventory i
                                WHERE i.last_updated IS NOT NULL
                                ORDER BY i.last_updated DESC
                                LIMIT 5
                            ");
                            
                            if ($recent_activities && $recent_activities->num_rows > 0) {
                                while ($activity = $recent_activities->fetch_assoc()) {
                                    $time_ago = time() - strtotime($activity['created_at']);
                                    $time_text = $time_ago < 3600 ? ceil($time_ago / 60) . ' دقیقه پیش' : 
                                                ($time_ago < 86400 ? ceil($time_ago / 3600) . ' ساعت پیش' : 
                                                ceil($time_ago / 86400) . ' روز پیش');
                                    ?>
                                    <div class="activity-item">
                                        <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-text">
                                                <strong><?php echo htmlspecialchars($activity['item_name']); ?></strong>
                                                موجودی به‌روزرسانی شد
                                            </div>
                                            <small class="activity-time"><?php echo $time_text; ?></small>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-1 mb-2"></i>
                                    <p>فعالیت‌های اخیر به زودی نمایش داده خواهد شد</p>
                                </div>
                                <?php
                            }
                            ?>
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

// SPA Navigation Functions
function toggleNavSection(element) {
    const section = element.closest('.nav-section');
    section.classList.toggle('collapsed');
}

function loadPage(page, url) {
    // Show loading overlay
    const loadingOverlay = document.getElementById('loading-overlay');
    const spaContent = document.getElementById('spa-content');
    const mainContent = document.getElementById('main-content');

    loadingOverlay.style.display = 'flex';

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    event.target.classList.add('active');

    // Special handling for dashboard - no need to load via AJAX
    if (page === 'dashboard') {
        // Hide loading overlay
        loadingOverlay.style.display = 'none';

        // Show main dashboard content
        mainContent.style.display = 'block';

        // Hide SPA content container
        spaContent.style.display = 'none';

        // Clear SPA content
        spaContent.innerHTML = '';

        // Update page title
        document.title = 'سیستم مدیریت انبار';

        // Update URL without page reload
        history.pushState({page: page}, 'داشبرد', '#dashboard');

        return; // Exit early for dashboard
    }

    // Load page content via AJAX using our API
    const apiUrl = `public/spa_loader.php?page=${encodeURIComponent(page)}&url=${encodeURIComponent(url)}`;

    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(data => {
        // Hide loading overlay
        loadingOverlay.style.display = 'none';

        // Hide main dashboard content
        mainContent.style.display = 'none';

        // Show SPA content container
        spaContent.style.display = 'block';

        // Insert the loaded content
        spaContent.innerHTML = data;

        // Update page title
        const pageTitle = event.target.querySelector('span').textContent;
        document.title = pageTitle + ' - سیستم مدیریت انبار';

        // Update URL without page reload
        history.pushState({page: page}, pageTitle, '#' + page);

        // Re-initialize any JavaScript components that might be in the loaded content
        initializeLoadedContent();
    })
    .catch(error => {
        console.error('Error loading page:', error);
        loadingOverlay.style.display = 'none';
        alert('خطا در بارگذاری صفحه. لطفاً دوباره تلاش کنید.');
    });
}

// Function to initialize loaded content components
function initializeLoadedContent() {
    // Re-initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Re-initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Re-initialize any forms with validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Handle any links within loaded content that should navigate SPA-style
    document.querySelectorAll('#spa-content a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (href && !href.startsWith('http') && !href.startsWith('#') && !href.includes('logout.php')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                // You can extend this to handle internal navigation
                console.log('Internal link clicked:', href);
            });
        }
    });
}

// Handle browser back/forward buttons
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.page) {
        // Load the page from history state
        const navLink = document.querySelector(`[data-page="${event.state.page}"]`);
        if (navLink) {
            navLink.click();
        }
    } else {
        // Show dashboard
        showDashboard();
    }
});

// Function to show dashboard
function showDashboard() {
    const spaContent = document.getElementById('spa-content');
    const mainContent = document.getElementById('main-content');
    const loadingOverlay = document.getElementById('loading-overlay');

    // Hide SPA content
    spaContent.style.display = 'none';

    // Show main dashboard content
    mainContent.style.display = 'block';

    // Update active nav link
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector('[data-page="dashboard"]').classList.add('active');

    // Update URL
    history.pushState({page: 'dashboard'}, 'داشبرد', '#dashboard');

    // Update page title
    document.title = 'سیستم مدیریت انبار';
}

// Initialize SPA functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to navigation links
    document.querySelectorAll('.nav-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            const url = this.getAttribute('data-url');
            loadPage(page, url);
        });
    });

    // Add click event listeners to quick action cards
    document.querySelectorAll('.quick-action-card[data-page]').forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            const url = this.getAttribute('data-url');
            loadPage(page, url);
        });
    });

    // Handle initial page load based on URL hash
    const hash = window.location.hash.substring(1);
    if (hash && hash !== 'dashboard') {
        const navLink = document.querySelector(`[data-page="${hash}"]`);
        if (navLink) {
            // Only load page if it's not the dashboard (which is already loaded)
            navLink.click();
        }
    } else {
        // Set dashboard as active by default (content is already loaded)
        const dashboardLink = document.querySelector('[data-page="dashboard"]');
        if (dashboardLink) {
            dashboardLink.classList.add('active');
        }
    }
    }
});
</script>
