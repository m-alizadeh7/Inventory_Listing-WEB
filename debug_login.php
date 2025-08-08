<?php
/**
 * فایل تست برای debugging مشکل ورود
 */

session_start();

echo "<h2>تست سیستم ورود</h2>";

// بررسی متدهای دسترسی
require_once 'config.php';
require_once 'models/UserModel.php';
require_once 'controllers/MainController.php';

echo "<h3>بررسی اتصال دیتابیس:</h3>";
if ($db) {
    echo "✅ اتصال دیتابیس موفق<br>";
    
    // بررسی وجود جدول users
    $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
    $query = "SHOW TABLES LIKE '{$prefix}users'";
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        echo "✅ جدول کاربران موجود است<br>";
        
        // بررسی وجود کاربران
        $count_query = "SELECT COUNT(*) as count FROM {$prefix}users";
        $count_result = $db->query($count_query);
        $count = $count_result->fetch_assoc()['count'];
        
        echo "📊 تعداد کاربران موجود: {$count}<br>";
    } else {
        echo "❌ جدول کاربران موجود نیست<br>";
    }
} else {
    echo "❌ اتصال دیتابیس ناموفق<br>";
}

echo "<h3>بررسی کنترلر:</h3>";
$controller = new MainController();

if (method_exists($controller, 'process_login')) {
    echo "✅ متد process_login موجود است<br>";
} else {
    echo "❌ متد process_login موجود نیست<br>";
}

if (method_exists($controller, 'processLogin')) {
    echo "✅ متد processLogin موجود است<br>";
} else {
    echo "❌ متد processLogin موجود نیست<br>";
}

echo "<h3>بررسی Session:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "CSRF Token: " . (isset($_SESSION['csrf_token']) ? 'موجود' : 'موجود نیست') . "<br>";

echo "<h3>بررسی POST Data:</h3>";
if ($_POST) {
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "هیچ داده POST دریافت نشده<br>";
}

echo "<h3>تست فرم ورود:</h3>";
?>
<form method="post" action="index.php?controller=main&action=process_login">
    <div>
        <label>نام کاربری:</label>
        <input type="text" name="username" value="admin" required>
    </div>
    <div>
        <label>رمز عبور:</label>
        <input type="password" name="password" value="123456" required>
    </div>
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <div>
        <button type="submit">ورود</button>
    </div>
</form>
