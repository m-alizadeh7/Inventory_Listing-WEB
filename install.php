<?php
session_start();

// چک کردن وجود فایل config.php
if (file_exists('config.php')) {
    header('Location: index.php');
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch($step) {
        case 1:
            // تست اتصال به دیتابیس
            $host = $_POST['db_host'] ?? '';
            $user = $_POST['db_user'] ?? '';
            $pass = $_POST['db_pass'] ?? '';
            $name = $_POST['db_name'] ?? '';
            
            $conn = @new mysqli($host, $user, $pass, $name);
            if ($conn->connect_error) {
                $error = 'خطا در اتصال به پایگاه داده: ' . $conn->connect_error;
            } else {
                // ذخیره اطلاعات در سشن
                $_SESSION['db_info'] = [
                    'host' => $host,
                    'user' => $user,
                    'pass' => $pass,
                    'name' => $name
                ];
                header('Location: install.php?step=2');
                exit();
            }
            break;

        case 2:
            // ایجاد فایل config.php
            if (isset($_SESSION['db_info'])) {
                $config = "<?php\n";
                $config .= "// Database configuration\n";
                $config .= "define('DB_HOST', '{$_SESSION['db_info']['host']}');\n";
                $config .= "define('DB_USER', '{$_SESSION['db_info']['user']}');\n";
                $config .= "define('DB_PASS', '{$_SESSION['db_info']['pass']}');\n";
                $config .= "define('DB_NAME', '{$_SESSION['db_info']['name']}');\n";
                
                if (file_put_contents('config.php', $config)) {
                    // خواندن و اجرای فایل SQL
                    $sql = file_get_contents('db.sql');
                    $conn = new mysqli(
                        $_SESSION['db_info']['host'],
                        $_SESSION['db_info']['user'],
                        $_SESSION['db_info']['pass'],
                        $_SESSION['db_info']['name']
                    );
                    
                    if ($conn->multi_query($sql)) {
                        $success = 'نصب با موفقیت انجام شد!';
                        session_destroy();
                    } else {
                        $error = 'خطا در ایجاد جداول: ' . $conn->error;
                    }
                } else {
                    $error = 'خطا در ایجاد فایل config.php';
                }
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>نصب سیستم انبارداری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .install-box { max-width: 600px; margin: 0 auto; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .step { margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="install-box">
        <div class="logo">
            <h1>🏭</h1>
            <h2>نصب سیستم انبارداری</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
                <br>
                <a href="index.php" class="btn btn-primary mt-3">ورود به سیستم</a>
            </div>
        <?php endif; ?>

        <?php if ($step === 1 && !$success): ?>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">اطلاعات پایگاه داده</h3>
                <form method="post" action="install.php?step=1">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">آدرس هاست دیتابیس</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_name" class="form-label">نام دیتابیس</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_user" class="form-label">نام کاربری دیتابیس</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">رمز عبور دیتابیس</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                    </div>
                    <button type="submit" class="btn btn-primary">ادامه نصب</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($step === 2 && !$success): ?>
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">ایجاد جداول</h3>
                <form method="post" action="install.php?step=2">
                    <p>در حال ایجاد جداول مورد نیاز...</p>
                    <button type="submit" class="btn btn-primary">شروع نصب</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
