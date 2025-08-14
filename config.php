<?php
// Load .env if present (simple loader)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
        if ($k) putenv(sprintf('%s=%s', $k, $v));
    }
}

// تنظیمات پایگاه داده (از env خوانده می‌شود یا مقادیر پیش‌فرض)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '123');
define('DB_NAME', getenv('DB_NAME') ?: 'php1');

// اتصال به پایگاه داده
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset("utf8mb4");

// بررسی اتصال
if ($conn->connect_error) {
    die("خطا در اتصال به پایگاه داده: " . $conn->connect_error);
}

// نسخه سیستم
define('SYSTEM_VERSION', '1.0.0');