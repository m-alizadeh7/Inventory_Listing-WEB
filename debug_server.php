<?php
/**
 * اسکریپت دیباگ برای بررسی مسیرها و فایل‌ها
 */

echo "<html><head><meta charset='utf-8'></head><body style='font-family:tahoma;direction:rtl;'>";
echo "<h2>اطلاعات دیباگ سرور</h2>";

echo "<h3>اطلاعات مسیرها:</h3>";
echo "<p><strong>__DIR__:</strong> " . htmlspecialchars(__DIR__) . "</p>";
echo "<p><strong>getcwd():</strong> " . htmlspecialchars(getcwd()) . "</p>";
echo "<p><strong>$_SERVER['DOCUMENT_ROOT']:</strong> " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'تعریف نشده') . "</p>";
echo "<p><strong>$_SERVER['SCRIPT_FILENAME']:</strong> " . htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'تعریف نشده') . "</p>";

echo "<h3>بررسی وجود فایل‌ها:</h3>";
$paths_to_check = [
    __DIR__ . '/controllers/MainController.php',
    __DIR__ . '/core/controllers/MainController.php',
    '/home/h312810/public_html/anbar2/controllers/MainController.php',
    getcwd() . '/controllers/MainController.php',
];

foreach ($paths_to_check as $path) {
    $exists = file_exists($path);
    $color = $exists ? 'green' : 'red';
    echo "<p style='color:$color;'><strong>$path:</strong> " . ($exists ? 'موجود است' : 'موجود نیست') . "</p>";
}

echo "<h3>فایل‌های موجود در پوشه فعلی:</h3>";
$files = glob(__DIR__ . '/*');
echo "<ul>";
foreach ($files as $file) {
    $type = is_dir($file) ? 'پوشه' : 'فایل';
    echo "<li>" . htmlspecialchars(basename($file)) . " ($type)</li>";
}
echo "</ul>";

if (is_dir(__DIR__ . '/controllers')) {
    echo "<h3>فایل‌های موجود در پوشه controllers:</h3>";
    $controller_files = glob(__DIR__ . '/controllers/*');
    echo "<ul>";
    foreach ($controller_files as $file) {
        $type = is_dir($file) ? 'پوشه' : 'فایل';
        echo "<li>" . htmlspecialchars(basename($file)) . " ($type)</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>پوشه controllers موجود نیست!</p>";
}

echo "</body></html>";
?>
