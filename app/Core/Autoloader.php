<?php

// حذف نیاز به composer autoload
spl_autoload_register(function ($class) {
    // Debug: نمایش مسیر فعلی و کلاس درخواستی
    error_log("Trying to load class: " . $class);
    error_log("Current DIR: " . __DIR__);
    
    // تبدیل App\Core\Database به app/Core/Database.php
    $base_dir = realpath(dirname(dirname(__DIR__)));
    
    // حذف App\ از ابتدای نام کلاس
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        error_log("Class does not match prefix: " . $prefix);
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 
            str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
    
    error_log("Looking for file: " . $file);
    
    if (file_exists($file)) {
        require_once $file;
        error_log("Successfully loaded: " . $file);
        return true;
    }
    
    error_log("File not found: " . $file);
    return false;
});
