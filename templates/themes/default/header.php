<?php
/**
 * Default theme header
 */

// Include theme functions if not already loaded
if (!function_exists('theme_enqueue_styles')) {
    require_once __DIR__ . '/functions.php';
}

// Get current user info
global $security;
$current_user = $security ? $security->getCurrentUser() : null;
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business_info['business_name'] ?? 'سیستم انبارداری'); ?></title>
    
    <?php 
    // Load theme styles from functions.php
    theme_enqueue_styles(); 
    ?>
</head>
<body>
<?php 
// For dashboard pages, don't load navigation as it's included in the sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$dashboard_pages = ['index.php', 'settings.php', 'inventory_categories.php'];

if (!in_array($current_page, $dashboard_pages) && !isset($_GET['dashboard'])) {
    get_theme_part('navigation'); 
}
?>
<div class="content-wrapper pt-5">
    <div class="container-fluid pt-4">
