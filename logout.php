<?php
require_once 'bootstrap.php';

global $security;

// خروج از سیستم
$security->logout();

// هدایت به صفحه ورود
header('Location: login.php?msg=logged_out');
exit;
?>
