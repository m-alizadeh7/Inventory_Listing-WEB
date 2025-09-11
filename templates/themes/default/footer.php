<?php
/**
 * Default theme footer
 */

// Include theme functions if not already loaded
if (!function_exists('theme_enqueue_scripts')) {
    require_once __DIR__ . '/functions.php';
}
?>
    </div>
</div>

<?php 
// For non-dashboard pages, load footer components
$current_page = basename($_SERVER['PHP_SELF']);
$dashboard_pages = ['index.php', 'settings.php', 'inventory_categories.php'];
$install_pages = ['install.php', 'setup-config.php', 'setup.php'];

if (!in_array($current_page, $dashboard_pages) && !in_array($current_page, $install_pages) && !isset($_GET['dashboard'])) {
    // Load mobile footer from template-parts
    get_theme_part('mobile-footer'); 

    // Load desktop footer from template-parts
    get_theme_part('desktop-footer'); 
}

// Load theme scripts from functions.php
theme_enqueue_scripts(); 
?>

<!-- Developer attribution for all pages -->
<div class="text-center py-3 bg-light border-top mt-4">
    <small class="text-muted">
        <i class="bi bi-person-badge me-1"></i>
        توسعه‌دهنده: Mahdi Alizadeh • M.alizadeh7@live.com • 
        <a href="https://alizadehx.ir" target="_blank" class="text-decoration-none text-primary">Alizadehx.ir</a>
    </small>
</div>

</body>
</html>
