<?php
/**
 * Theme functions file for Default theme
 * Contains all theme-specific functions and customizations
 */

// Theme setup function - runs when theme is initialized
function theme_default_setup() {
    // Theme version for cache busting
    define('THEME_VERSION', '1.0.0');
    
    // Theme directory URI (for assets) - corrected path
    define('THEME_URI', 'templates/themes/default');
    
    // Asset paths
    define('THEME_CSS_URI', THEME_URI . '/assets/css');
    define('THEME_JS_URI', THEME_URI . '/assets/js');
    define('THEME_IMG_URI', THEME_URI . '/assets/images');
}
theme_default_setup();

/**
 * Enqueue theme stylesheets in proper order
 */
function theme_enqueue_styles() {
    // Core CSS files
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">' . PHP_EOL;
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">' . PHP_EOL;
    
    // Theme CSS files in order
    echo '<link href="' . THEME_CSS_URI . '/base.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/header.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/footer.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/cards.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/forms.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/animations.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
    echo '<link href="' . THEME_CSS_URI . '/responsive.css?v=' . THEME_VERSION . '" rel="stylesheet">' . PHP_EOL;
}

/**
 * Enqueue theme scripts in proper order
 */
function theme_enqueue_scripts() {
    // Core JS files
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>' . PHP_EOL;
    
    // Excel export library
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>' . PHP_EOL;
    
    // Theme JS files
    echo '<script src="' . THEME_JS_URI . '/navigation.js?v=' . THEME_VERSION . '"></script>' . PHP_EOL;
    echo '<script src="' . THEME_JS_URI . '/enhanced-tables.js?v=' . THEME_VERSION . '"></script>' . PHP_EOL;
    echo '<script src="' . THEME_JS_URI . '/theme.js?v=' . THEME_VERSION . '"></script>' . PHP_EOL;
}

/**
 * Get template part (similar to WordPress get_template_part)
 * 
 * @param string $slug The slug name for the template part
 * @param string $name Optional. The name of the specialized template
 */
function get_theme_part($slug, $args = null) {
    $template = '';
    
    // Extract args for template parts that need them
    if (is_array($args)) {
        extract($args);
    }
    
    // Look for template-parts/$slug.php
    if (file_exists(ACTIVE_THEME_PATH . "/template-parts/{$slug}.php")) {
        $template = ACTIVE_THEME_PATH . "/template-parts/{$slug}.php";
    }
    
    // Include the template if found
    if ($template) {
        include $template;
    }
}

/**
 * Display page title
 * 
 * @param string $title The page title
 * @param string $subtitle Optional subtitle
 */
function the_page_title($title, $subtitle = '') {
    echo '<div class="page-header mb-4">';
    echo '<h1 class="h3 mb-0">' . htmlspecialchars($title) . '</h1>';
    
    if ($subtitle) {
        echo '<p class="text-muted">' . htmlspecialchars($subtitle) . '</p>';
    }
    
    echo '</div>';
}

/**
 * Generate a dashboard card
 * 
 * @param array $args Card arguments
 */
function the_dashboard_card($args) {
    $defaults = array(
        'title' => '',
        'icon' => 'bi-box',
        'text' => '',
        'link' => '',
        'link_text' => 'مدیریت',
        'icon_bg' => 'bg-secondary bg-opacity-10',
        'icon_color' => 'text-secondary',
        'button_class' => 'btn-secondary'
    );
    
    $card = array_merge($defaults, $args);
    
    echo '<div class="col-md-4 col-sm-6">';
    echo '<div class="card h-100">';
    echo '<div class="card-body">';
    
    echo '<div class="d-flex align-items-center mb-3">';
    echo '<div class="' . $card['icon_bg'] . ' p-3 rounded-circle me-3">';
    echo '<i class="bi ' . $card['icon'] . ' ' . $card['icon_color'] . ' fs-4"></i>';
    echo '</div>';
    echo '<h5 class="card-title mb-0">' . htmlspecialchars($card['title']) . '</h5>';
    echo '</div>';
    
    echo '<p class="card-text">' . htmlspecialchars($card['text']) . '</p>';
    
    if ($card['link']) {
        echo '<a href="' . $card['link'] . '" class="btn ' . $card['button_class'] . ' w-100">';
        echo '<i class="bi bi-arrow-right-circle"></i> ' . htmlspecialchars($card['link_text']);
        echo '</a>';
    }
    
    echo '</div>'; // card-body
    echo '</div>'; // card
    echo '</div>'; // col
}

/**
 * Create enhanced table with export/print functionality
 * 
 * @param string $id Table ID
 * @param string $title Print title
 * @param string $company Company name for print header
 * @param bool $search Enable search
 * @param bool $export Enable Excel export
 * @param bool $print Enable print
 */
function enhanced_table_start($id, $title = 'گزارش سیستم انبارداری', $company = 'شرکت نمونه', $search = true, $export = true, $print = true) {
    echo '<table id="' . $id . '" class="enhanced-table table table-hover table-bordered" ';
    echo 'data-print-title="' . htmlspecialchars($title) . '" ';
    echo 'data-company-name="' . htmlspecialchars($company) . '" ';
    echo 'data-show-search="' . ($search ? 'true' : 'false') . '" ';
    echo 'data-show-export="' . ($export ? 'true' : 'false') . '" ';
    echo 'data-show-print="' . ($print ? 'true' : 'false') . '">';
}

function enhanced_table_end() {
    echo '</table>';
}

/**
 * Excel import/export helper functions
 */
function generate_excel_template($headers, $filename) {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // For now, we'll use CSV as fallback
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('.xlsx', '.csv', $filename) . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fwrite($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, $headers);
    
    return $output;
}

function process_excel_import($file_path, $expected_headers = []) {
    // Basic CSV processing for now
    $data = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ",");
        
        // Validate headers if provided
        if (!empty($expected_headers)) {
            foreach ($expected_headers as $expected) {
                if (!in_array($expected, $headers)) {
                    return ['error' => 'فایل دارای ساختار نامعتبر است. ستون مورد نیاز یافت نشد: ' . $expected];
                }
            }
        }
        
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    
    return ['success' => true, 'data' => $data, 'count' => count($data)];
}
