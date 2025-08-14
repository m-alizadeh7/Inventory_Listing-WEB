<?php
// Bootstrap for theme support
// Theme base path
define('THEMES_PATH', __DIR__ . '/themes');

// Load core config and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Determine active theme from settings table (fallback to default)
$active = getSetting('active_theme', 'default');
define('ACTIVE_THEME', $active);
define('ACTIVE_THEME_PATH', THEMES_PATH . '/' . ACTIVE_THEME);

// Load theme functions
$theme_functions = ACTIVE_THEME_PATH . '/functions.php';
if (file_exists($theme_functions)) {
    require_once $theme_functions;
}

// Helper: get theme file path with fallback to core
function theme_file($relative) {
    $themeFile = ACTIVE_THEME_PATH . '/' . ltrim($relative, '/');
    if (file_exists($themeFile)) return $themeFile;
    // fallback: look into /includes/theme_defaults/
    $fallback = __DIR__ . '/includes/theme_defaults/' . ltrim($relative, '/');
    return $fallback;
}

// Load a template part from the active theme
function get_template_part($slug) {
    $file = theme_file($slug . '.php');
    if (file_exists($file)) {
        include $file;
    }
}

// Get theme asset URL
function get_theme_asset_url($path) {
    $theme_url = 'themes/' . ACTIVE_THEME;
    return $theme_url . '/' . ltrim($path, '/');
}

// Load full page template with header and footer
function get_template($template_name) {
    // Get business info for header
    global $business_info;
    if (!isset($business_info)) {
        $business_info = getBusinessInfo();
    }
    
    // Load header
    get_template_part('header');
    
    // Load template content
    get_template_part('templates/' . $template_name);
    
    // Load footer
    get_template_part('footer');
}
