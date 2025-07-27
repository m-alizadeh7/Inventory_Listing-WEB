<?php

// اتولودر با قابلیت نمایش خطاها
spl_autoload_register(function ($class) {
    // تبدیل App\Core\Database به app/Core/Database.php
    $base_dir = dirname(dirname(__DIR__));
    
    // حذف App\ از ابتدای نام کلاس
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        echo "<!-- Class $class does not match prefix $prefix -->\n";
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . '/app/' . str_replace('\\', '/', $relative_class) . '.php';
    
    echo "<!-- Looking for file: $file -->\n";
    
    if (!file_exists($file)) {
        throw new Exception("Class file not found: $file for class $class");
    }
    
    require_once $file;
    echo "<!-- Successfully loaded: $file -->\n";
});
