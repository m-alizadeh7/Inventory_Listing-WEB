<?php
namespace App\Core;

// حذف نیاز به composer autoload
spl_autoload_register(function ($class) {
    // تبدیل App\Core\Database به app/Core/Database.php
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});
