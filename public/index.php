<?php
/**
 * Main entry point with routing
 */

use App\Core\Database;
use App\Core\SecurityManager;

// Check if system is installed
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header('Location: app/admin/setup-config.php');
    exit;
}

// Load bootstrap (config, functions, theme)
require_once __DIR__ . '/bootstrap.php';

// Load Database class
require_once __DIR__ . '/../app/core/Database.php';

// Initialize dependencies
$db = Database::getInstance();
$security = new \App\Core\SecurityManager($db->getConnection());

// Check if installation is complete
if (!defined('INSTALLING')) {
    // Check if admin user exists
    try {
        $conn = $db->getConnection();
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        if (!$result || $result->fetch_assoc()['count'] == 0) {
            header('Location: app/admin/install.php');
            exit;
        }
    } catch (Exception $e) {
        header('Location: app/admin/install.php');
        exit;
    }
}

// بررسی ورود کاربر
$security->requireLogin();

// پردازش درخواست migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    // فقط ادمین‌ها می‌توانند migration اجرا کنند
    $security->requirePermission('system.admin');
    runMigrations($db->getConnection());
    header('Location: index.php?msg=migration_complete');
    exit;
}

// Simple routing
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/portal', '', $path); // Adjust for subfolder

if ($path === '/' || $path === '/index.php') {
    // Load home
    $business_info = getBusinessInfo($db->getConnection());
    get_template('home');
} elseif (strpos($path, '/inventory') === 0) {
    // Route to inventory controller
    require_once __DIR__ . '/../app/Controllers/inventory_records.php';
} elseif (strpos($path, '/production') === 0) {
    // Route to production controller
    require_once __DIR__ . '/../app/Controllers/production_orders.php';
} elseif (strpos($path, '/users') === 0) {
    // Route to users controller
    require_once __DIR__ . '/../app/Controllers/users.php';
} elseif (strpos($path, '/suppliers') === 0) {
    // Route to suppliers controller
    require_once __DIR__ . '/../app/Controllers/suppliers.php';
} elseif (strpos($path, '/devices') === 0) {
    // Route to devices controller
    require_once __DIR__ . '/../app/Controllers/devices.php';
} elseif (strpos($path, '/settings') === 0) {
    // Route to settings controller
    require_once __DIR__ . '/../app/Controllers/settings.php';
} else {
    // Default to home
    $business_info = getBusinessInfo($db->getConnection());
    get_template('home');
}

// Load complete template using new function
get_template('home');