<?php
// افزودن لینک مدیریت لایسنس به منوی ادمین
function add_license_manager_to_menu() {
    // بررسی دسترسی مدیر
    if (is_admin()) {
        echo "<li";
        if (basename($_SERVER["PHP_SELF"]) == "license_manager.php") {
            echo " class=\"active\"";
        }
        echo "><a href=\"license_manager.php\"><i class=\"fa fa-key\"></i> <span>مدیریت لایسنس</span></a></li>";
    }
}

// افزودن لینک به منوی ادمین
if (function_exists("add_admin_menu_item")) {
    add_admin_menu_item("add_license_manager_to_menu");
}

