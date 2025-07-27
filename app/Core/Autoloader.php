<?php

// حذف نیاز به composer autoload
spl_autoload_register(function ($class) {
    // تبدیل App\Core\Database به app/Core/Database.php
    $base_dir = dirname(dirname(__DIR__));
    
    // حذف App\ از ابتدای نام کلاس
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . '/app/' . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});
