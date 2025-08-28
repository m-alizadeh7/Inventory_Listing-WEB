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
// Load navigation from template-parts
get_theme_part('navigation'); 
?>
<div class="content-wrapper pt-5">
    <div class="container pt-4">
