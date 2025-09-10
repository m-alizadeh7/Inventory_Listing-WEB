<?php
require_once 'bootstrap.php';

// Fetch any data needed for the template
$business_info = getBusinessInfo();

// Load complete template using new function
get_template('devices');
?>
