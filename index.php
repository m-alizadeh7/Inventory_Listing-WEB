<?php
// bootstrap loads config/functions and theme helpers
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}

// Load config and functions
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// پردازش درخواست migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    runMigrations();
    header('Location: index.php?msg=migration_complete');
    exit;
}

// Get business info
$business_info = getBusinessInfo();

// Load complete template using new function
get_template('home');
?>