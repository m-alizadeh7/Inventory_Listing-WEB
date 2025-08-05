<?php
// Database configuration

define('DB_HOST', 'localhost');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', '');

// Email configuration
define('EMAIL_TO', 'm.alizadeh7@live.com'); // ایمیل مقصد برای گزارش‌ها
define('EMAIL_FROM', 'no-reply@mecaco-service.com'); // ایمیل فرستنده
define('EMAIL_SUBJECT', 'گزارش انبارداری');

// Connect to database

// اگر اطلاعات دیتابیس ناقص بود، به صفحه نصب هدایت شود
if (DB_USER === '' || DB_PASS === '' || DB_NAME === '') {
    header('Location: setup.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>