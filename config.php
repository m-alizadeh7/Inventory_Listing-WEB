<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'h312810_anbarus');
define('DB_PASS', '-XZ!)MwBW.ae');
define('DB_NAME', 'h312810_anbar');

// Email configuration
define('EMAIL_TO', 'm.alizadeh7@live.com'); // ایمیل مقصد برای گزارش‌ها
define('EMAIL_FROM', 'no-reply@mecaco-service.com'); // ایمیل فرستنده
define('EMAIL_SUBJECT', 'گزارش انبارداری');

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>