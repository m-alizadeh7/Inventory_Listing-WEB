<?php
/**
 * Theme initialization and helper functions
 */

// Initialize theme system
if (!function_exists('init_theme')) {
function init_theme() {
    // Theme base path
    if (!defined('THEMES_PATH')) {
        define('THEMES_PATH', __DIR__ . '/../../themes');
    }
    
    // Determine active theme from settings table (fallback to default)
    global $conn;
    $active = 'default';
    
    if ($conn) {
        $result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'active_theme' LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $active = $row['setting_value'];
        }
    }
    
    if (!defined('ACTIVE_THEME')) {
        define('ACTIVE_THEME', $active);
        define('ACTIVE_THEME_PATH', THEMES_PATH . '/' . ACTIVE_THEME);
    }
    
    // Load theme functions
    $theme_functions = ACTIVE_THEME_PATH . '/functions.php';
    if (file_exists($theme_functions)) {
        require_once $theme_functions;
    }
}
}

// Helper: get theme file path with fallback to core
if (!function_exists('theme_file')) {
function theme_file($relative) {
    $themeFile = ACTIVE_THEME_PATH . '/' . ltrim($relative, '/');
    if (file_exists($themeFile)) return $themeFile;
    // fallback: look into /includes/theme_defaults/
    $fallback = __DIR__ . '/../../includes/theme_defaults/' . ltrim($relative, '/');
    return $fallback;
}
}

// Load header
if (!function_exists('get_header')) {
function get_header() {
    $header_file = theme_file('header.php');
    if (file_exists($header_file)) {
        include $header_file;
    }
}
}

// Load footer  
if (!function_exists('get_footer')) {
function get_footer() {
    $footer_file = theme_file('footer.php');
    if (file_exists($footer_file)) {
        include $footer_file;
    }
}
}

// Load complete template
if (!function_exists('get_template')) {
function get_template($template_name) {
    // Get business info for header
    global $business_info;
    if (!isset($business_info)) {
        $business_info = getBusinessInfo();
    }
    
    // Load header
    get_header();
    
    // Load template content
    $template_file = ACTIVE_THEME_PATH . '/templates/' . $template_name . '.php';
    if (file_exists($template_file)) {
        include $template_file;
    } else {
        echo '<div class="alert alert-danger">Template not found: ' . htmlspecialchars($template_name) . '</div>';
    }
    
    // Load footer
    get_footer();
}
}

// Load a template part from the active theme
if (!function_exists('get_template_part')) {
function get_template_part($slug) {
    $file = theme_file($slug . '.php');
    if (file_exists($file)) {
        include $file;
    }
}
}

// Get theme asset URL
if (!function_exists('get_theme_asset_url')) {
function get_theme_asset_url($path) {
    $theme_url = 'themes/' . ACTIVE_THEME;
    return $theme_url . '/' . ltrim($path, '/');
}
}

// Set flash message to display on next page load
if (!function_exists('set_flash_message')) {
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}
}

// Get and clear flash message
if (!function_exists('get_flash_message')) {
function get_flash_message() {
    $message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : null;
    unset($_SESSION['flash_message']);
    return $message;
}
}

// Display flash message if exists
if (!function_exists('show_flash_message')) {
function show_flash_message() {
    if (!function_exists('get_flash_message')) {
        return;
    }
    $fm = get_flash_message();
    if ($fm && !empty($fm['message'])) {
        $type = in_array($fm['type'], ['success','danger','warning','info']) ? $fm['type'] : 'info';
        $msg = htmlspecialchars($fm['message']);
        echo "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">{$msg}";
        echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>";
        echo "</div>";
    }
}
}
