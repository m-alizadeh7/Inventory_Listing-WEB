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

if (!in_array($current_page, $dashboard_pages) && !isset($_GET['dashboard'])) {
    // Load mobile footer from template-parts
    get_theme_part('mobile-footer'); 

    // Load desktop footer from template-parts
    get_theme_part('desktop-footer'); 
}

// Load theme scripts from functions.php
theme_enqueue_scripts(); 
?>

</body>
</html>
