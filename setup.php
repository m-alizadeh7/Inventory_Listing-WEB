<?php
require_once 'config.php';

// خواندن محتوای فایل SQL
$sql = file_get_contents('db.sql');

// تقسیم دستورات SQL
$queries = explode(';', $sql);

$success = true;
$errors = array();

// اجرای هر دستور SQL
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;
    
    if (!$conn->query($query)) {
        $success = false;
        $errors[] = $conn->error;
    }
}

// نمایش نتیجه
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نصب دیتابیس</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title">نتیجه نصب دیتابیس</h2>
            <?php if ($success && empty($errors)): ?>
                <div class="alert alert-success">
                    <h4 class="success">✅ دیتابیس با موفقیت نصب شد</h4>
                    <p>تمام جداول مورد نیاز ایجاد شدند.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4 class="error">❌ خطا در نصب دیتابیس</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <a href="index.php" class="btn btn-primary">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
