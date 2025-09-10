<?php
/**
 * AJAX Page Loader for SPA
 * Loads page content without full page refresh
 */

// Start session if not already started
if (!session_id()) {
    session_start();
}

// Include bootstrap for database and theme support
require_once __DIR__ . '/bootstrap.php';

// Force SPA mode for AJAX requests
$_GET['spa_mode'] = '1';

// Check if it's an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    die('Direct access not allowed');
}

// Get the requested page
$page = $_GET['page'] ?? '';
$url = $_GET['url'] ?? '';

// Validate the request
if (empty($page) || empty($url)) {
    http_response_code(400);
    die('Invalid request parameters');
}

// Security check - only allow specific pages
$allowed_pages = [
    'inventory_records',
    'inventory_categories',
    'physical_count',
    'manual_withdrawals',
    'emergency_notes',
    'new_inventory',
    'new_production_order',
    'production_orders',
    'devices',
    'suppliers',
    'settings',
    'backup'
];

if (!in_array($page, $allowed_pages)) {
    http_response_code(403);
    die('Page not allowed');
}

// Validate URL path
$allowed_paths = [
    'inventory_records.php',
    'inventory_categories.php',
    'physical_count.php',
    'manual_withdrawals.php',
    'emergency_notes.php',
    'new_inventory.php',
    'new_production_order.php',
    'production_orders.php',
    'devices.php',
    'suppliers.php',
    'settings.php',
    'backup.php'
];

if (!in_array($url, $allowed_paths)) {
    http_response_code(403);
    die('URL not allowed');
}

// Check if file exists
$file_path = __DIR__ . '/' . $url;
if (!file_exists($file_path)) {
    http_response_code(404);
    die('Page not found');
}

// Set SPA mode flag
$_GET['spa_mode'] = '1';

// Capture the page output
ob_start();
include $file_path;
$content = ob_get_clean();

// Return the content
echo $content;
?>
