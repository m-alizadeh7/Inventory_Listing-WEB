<?php

// حذف نیاز به composer autoload
spl_autoload_register(function ($class) {
    // تبدیل App\Core\Database به app/Core/Database.php
    $base_dir = dirname(dirname(__DIR__));
    $class = ltrim($class, '\\');
    $file = $base_dir . '/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
