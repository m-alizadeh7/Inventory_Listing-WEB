<?php
require_once '../config/config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once __DIR__ . '/core/includes/theme.php';
init_theme();

// Fetch any data needed for the template
$business_info = getBusinessInfo();

// Load complete template using new function
get_template('devices');
?>
