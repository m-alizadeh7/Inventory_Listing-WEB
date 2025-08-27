<?php
/**
 * run_migrations.php
 *
 * اجرای ایمن و idempotent فایل‌های migration از داخل اپ (برای زمانی که دسترسی مستقیم به MySQL ندارید).
 * استفاده: از CLI اجرا کنید:
 *   php run_migrations.php
 * یا در مرورگر (فقط روی localhost) با پارامتر confirm=1:
 *   http://localhost/php1/run_migrations.php?confirm=1
 *
 * هشدار: در محیط تولید، این اسکریپت را پس از اجرا پاک یا محافظت کنید.
 */

// اجرای ایمن
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

// Safety: require CLI or explicit confirm when accessed via web
if (php_sapi_name() !== 'cli') {
    $allowedHosts = ['127.0.0.1', '::1', 'localhost'];
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, $allowedHosts)) {
        http_response_code(403);
        die('Forbidden: migration can only be run from localhost or CLI.');
    }
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
        echo "To run migrations via browser, call with ?confirm=1 on localhost.\n";
        exit;
    }
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Database connection not available. Check config.php');
}

$sqlFile = __DIR__ . '/db_users_security.sql';
if (!file_exists($sqlFile)) {
    die('Migration file not found: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) die('Could not read migration file');

// remove full-line -- comments to avoid issues
$lines = preg_split("/\r?\n/", $sql);
$filtered = [];
foreach ($lines as $line) {
    $t = trim($line);
    if ($t === '') continue;
    if (strpos($t, '--') === 0) continue;
    $filtered[] = $line;
}
$sql = implode("\n", $filtered);

// Wrap with disabling foreign key checks to avoid ordering issues, but keep idempotent
$wrapped = "SET FOREIGN_KEY_CHECKS=0;\n" . $sql . "\nSET FOREIGN_KEY_CHECKS=1;";

echo "Running migrations from: $sqlFile\n";

if ($conn->multi_query($wrapped)) {
    $count = 0;
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
        $count++;
    } while ($conn->more_results() && $conn->next_result());

    echo "Migrations executed. Statements processed (approx): $count\n";
    echo "Check tables: security_logs, users, user_roles, user_sessions, role_permissions, permissions, user_category_restrictions\n";
} else {
    echo "Migration failed: " . $conn->error . "\n";
}

// optional: show whether security_logs exists now
$res = $conn->query("SHOW TABLES LIKE 'security_logs'");
if ($res && $res->num_rows > 0) {
    echo "security_logs table exists.\n";
} else {
    echo "security_logs table still missing.\n";
}

?>
