<?php
// finish_install.php
// Secure cleanup endpoint to remove installer files after successful setup.
// Accessible only from localhost and requires POST with confirm=1.

if (php_sapi_name() !== 'cli') {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1', 'localhost'])) {
        http_response_code(403);
        die('Forbidden.');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== '1') {
    http_response_code(400);
    die('Bad request.');
}

$base = __DIR__;
$files = [
    'setup.php',
    'install.php',
    'run_migrations.php'
];
$deleted = [];
$errors = [];

foreach ($files as $f) {
    $path = $base . DIRECTORY_SEPARATOR . $f;
    if (file_exists($path)) {
        if (@unlink($path)) {
            $deleted[] = $f;
        } else {
            $errors[] = "Failed to delete: $f";
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>پاکسازی فایل‌های نصب</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h4>پاکسازی فایل‌های نصب</h4>

    <?php if (!empty($deleted)): ?>
        <div class="alert alert-success">فایل‌های زیر حذف شدند:<ul>
            <?php foreach ($deleted as $d): ?><li><?php echo htmlspecialchars($d); ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">خطاها:<ul>
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <a href="/php1/index.php" class="btn btn-primary">رفتن به صفحه اصلی</a>
</div>
</body>
</html>
