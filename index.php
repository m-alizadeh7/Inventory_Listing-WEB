<?php
// bootstrap loads config/functions and theme helpers
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}
require_once 'bootstrap.php';

// پردازش درخواست migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migrations'])) {
    runMigrations();
    header('Location: index.php?msg=migration_complete');
    exit;
}

$business_info = getBusinessInfo();

// load theme header and main template
get_template_part('header');
get_template_part('templates/home');

// load theme footer
get_template_part('footer');

?>