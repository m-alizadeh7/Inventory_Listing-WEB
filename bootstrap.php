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



// Helper: get theme file path with fallback to core
function theme_file($relative) {
    $themeFile = ACTIVE_THEME_PATH . '/' . ltrim($relative, '/');
    if (file_exists($themeFile)) return $themeFile;
    // fallback: look into /includes/theme_defaults/
    $fallback = __DIR__ . '/includes/theme_defaults/' . ltrim($relative, '/');
    return $fallback;
}

function get_template_part($slug) {
    $file = theme_file($slug . '.php');
    if (file_exists($file)) {
        include $file;
    }
}
