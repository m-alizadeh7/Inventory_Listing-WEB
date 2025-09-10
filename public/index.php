<?php
/**
 * Main entry point with installation check
 */

// Check if system is installed
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header('Location: app/admin/setup-config.php');
    exit;
}

// Load bootstrap (config, functions, theme)
require_once __DIR__ . '/bootstrap.php';

// Check if installation is complete
if (!defined('INSTALLING')) {
    // Check if admin user exists
    try {
        if (isset($conn)) {
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
            if (!$result || $result->fetch_assoc()['count'] == 0) {
                header('Location: app/admin/install.php');
                exit;
            }
        }
    } catch (Exception $e) {
        header('Location: app/admin/install.php');
        exit;
    }
}

// بررسی ورود کاربر
global $security;
if (isset($security)) {
    $security->requireLogin();
}

// پردازش درخواست migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    // فقط ادمین‌ها می‌توانند migration اجرا کنند
    if (isset($security)) {
        $security->requirePermission('system.admin');
    }
    runMigrations();
    header('Location: index.php?msg=migration_complete');
    exit;
}

// Get business info
$business_info = getBusinessInfo();

// Load complete template using new function
get_template('home');