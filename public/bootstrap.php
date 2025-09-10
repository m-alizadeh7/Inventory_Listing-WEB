<?php
// Start session
if (!session_id()) {
    session_start();
}

// Bootstrap for theme support
// Theme base path
define('THEMES_PATH', __DIR__ . '/../templates/themes');

// Load core config and functions
// Installation detection
if (!file_exists(__DIR__ . '/../config/config.php') && !defined('INSTALLING')) {
    // CLI: instruct user
    if (php_sapi_name() === 'cli') {
        fwrite(STDOUT, "Configuration file missing: run setup via web interface.\n");
        // continue without exiting so scripts can handle missing config if needed
    } else {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $allowed = ['127.0.0.1', '::1', 'localhost'];
        if (!in_array($remote, $allowed)) {
            http_response_code(403);
            die('Configuration missing. Contact administrator.');
        }

        // safe redirect to installer on localhost
        if (file_exists(__DIR__ . '/../app/admin/setup-config.php')) {
            // Don't redirect if we're already on setup pages
            $current_path = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($current_path, 'setup-config.php') === false && 
                strpos($current_path, 'install.php') === false) {
                header('Location: /portal/app/admin/setup-config.php');
                exit;
            }
        } elseif (file_exists(__DIR__ . '/../app/setup.php')) {
            header('Location: /portal/app/setup.php');
            exit;
        } else {
            die('Configuration missing and installer not found. Please restore config.php from config.example.php.');
        }
    }
}

// Load config if available
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
}

// Load functions only if file exists
if (file_exists(__DIR__ . '/../app/includes/functions.php')) {
    require_once __DIR__ . '/../app/includes/functions.php';
}

// تلاش برای اتصال به دیتابیس اگر موجود باشد
if (!isset($conn) || !$conn) {
    try {
        if (function_exists('getDbConnection') && defined('DB_HOST')) {
            $conn = getDbConnection(false);
        } else {
            $conn = null;
        }
    } catch (Exception $e) {
        // اتصال برقرار نشد - ممکن است در حالت نصب باشیم
        $conn = null;
    }
}

// Load security manager
require_once __DIR__ . '/../app/core/includes/SecurityManager.php';

// Load theme functions
require_once __DIR__ . '/../app/core/includes/theme.php';

// Initialize global security manager
global $security;
if (isset($conn) && $conn instanceof mysqli) {
    $security = new SecurityManager($conn);
}

// Check database version and if DB needs update, redirect to setup (only on web/local)
if (isset($conn) && $conn instanceof mysqli) {
    // ensure settings table exists to query system_version safely
    $res = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_name = 'system_version' LIMIT 1");
        if ($stmt) {
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $row = $r->fetch_assoc()) {
                $dbVersion = $row['setting_value'];
                if (defined('SYSTEM_VERSION') && version_compare($dbVersion, SYSTEM_VERSION, '<')) {
                    // only do web redirect for non-CLI and localhost
                    if (php_sapi_name() !== 'cli') {
                        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
                        $allowed = ['127.0.0.1', '::1', 'localhost'];
                        if (in_array($remote, $allowed) && file_exists(__DIR__ . '/../app/setup.php')) {
                            // set a session message and redirect
                            if (session_status() === PHP_SESSION_NONE) session_start();
                            $_SESSION['setup_required'] = 'نسخه دیتابیس قدیمی است؛ لطفاً ابتدا به‌روزرسانی را انجام دهید.';
                            header('Location: /portal/app/setup.php');
                            exit;
                        }
                    }
                }
            }
            $stmt->close();
        }
    }
}

// Determine active theme from settings table (fallback to default)
$active = getSetting('active_theme', 'default');
define('ACTIVE_THEME', $active);
define('ACTIVE_THEME_PATH', THEMES_PATH . '/' . ACTIVE_THEME);

// Load theme functions
$theme_functions = ACTIVE_THEME_PATH . '/functions.php';
if (file_exists($theme_functions)) {
    require_once $theme_functions;
}

// --- Safe runtime schema fixes (minimal, idempotent) ---
// Ensure inventory_categories table exists and inventory has category_id column
if (isset($conn) || function_exists('get_db_connection')) {
    // try to access global $conn if available
    if (!isset($conn) && function_exists('get_db_connection')) {
        $conn = get_db_connection();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        // create categories table if missing
        $conn->query("CREATE TABLE IF NOT EXISTS inventory_categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(255) NOT NULL,
            category_description TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // ensure inventory table has category_id column
        $resInv = $conn->query("SHOW TABLES LIKE 'inventory'");
        if ($resInv && $resInv->num_rows > 0) {
            $colCheck = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory' AND COLUMN_NAME = 'category_id'");
            if ($colCheck) {
                $colRow = $colCheck->fetch_assoc();
                if ((int)$colRow['cnt'] === 0) {
                    // add the column quietly
                    @$conn->query("ALTER TABLE inventory ADD COLUMN category_id INT NULL AFTER id");
                    @$conn->query("ALTER TABLE inventory ADD INDEX idx_inventory_category (category_id)");
                }
            }
        }
    }
}

// بارگذاری سیستم بررسی لایسنس
if (file_exists(dirname(__FILE__) . "/../app/core/license/license_check.php")) {
    require_once dirname(__FILE__) . "/../app/core/license/license_check.php";
    
    // اجرای بررسی لایسنس
    if (function_exists("enforce_license")) {
        enforce_license();
    }
}

// SPA Mode Detection and Setup
if (isset($_GET['spa_mode']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Override theme functions for SPA mode
    function get_header() {
        // Skip header in SPA mode
    }

    function get_footer() {
        // Skip footer in SPA mode
    }

    function get_theme_part($part) {
        // Skip navigation and footer components in SPA mode
        if (in_array($part, ['navigation', 'mobile-footer', 'desktop-footer'])) {
            return;
        }

        // For other parts, load normally
        $theme_part_path = ACTIVE_THEME_PATH . '/template-parts/' . $part . '.php';
        if (file_exists($theme_part_path)) {
            include $theme_part_path;
        }
    }
}
