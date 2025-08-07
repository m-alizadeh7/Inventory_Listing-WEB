<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی و ایجاد جدول settings
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    setting_name VARCHAR(64) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$message = '';
$error = '';

// مدیریت عملیات POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ریست دیتابیس
    if (isset($_POST['reset_db'])) {
        $pw = $_POST['reset_password'] ?? '';
        if ($pw === '2581') {
            $conn->query("SET FOREIGN_KEY_CHECKS=0");
            $res = $conn->query("SHOW TABLES");
            while ($tbl = $res->fetch_array()) {
                $conn->query("DROP TABLE `{$tbl[0]}`");
            }
            $conn->query("SET FOREIGN_KEY_CHECKS=1");
            require_once __DIR__ . '/migrate.php';
            header('Location: settings.php?reset=1');
            exit;
        } else {
            $error = 'رمز عبور اشتباه است.';
        }
    }
    
    // بک‌آپ دیتابیس بدون exec
    if (isset($_POST['backup_db'])) {
        try {
            $backupDir = 'backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            $tables = [];
            $result = $conn->query('SHOW TABLES');
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            $sqlScript = "SET NAMES utf8mb4;\n";
            foreach ($tables as $table) {
                $res = $conn->query("SHOW CREATE TABLE `$table`");
                $row2 = $res->fetch_assoc();
                $sqlScript .= "\n-- ----------------------------\n";
                $sqlScript .= "-- Table structure for `$table`\n";
                $sqlScript .= "-- ----------------------------\n";
                $sqlScript .= $row2['Create Table'] . ";\n\n";
                $sqlScript .= "-- Dumping data for table `$table`\n";
                $res = $conn->query("SELECT * FROM `$table`");
                while ($data = $res->fetch_assoc()) {
                    $cols = array_map(function($v){return '`'.$v.'`';}, array_keys($data));
                    $vals = array_map(function($v) use ($conn){return "'".$conn->real_escape_string($v)."'";}, array_values($data));
                    $sqlScript .= "INSERT INTO `$table` (".implode(",",$cols).") VALUES (".implode(",",$vals).");\n";
                }
                $sqlScript .= "\n";
            }
            file_put_contents($filepath, $sqlScript);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            unlink($filepath);
            exit;
        } catch (Exception $e) {
            $error = 'خطا در ایجاد بک‌آپ: ' . $e->getMessage();
        }
    }
    
    // ریستور دیتابیس
    if (isset($_POST['restore_db']) && isset($_FILES['backup_file'])) {
        $pw = $_POST['restore_password'] ?? '';
        if ($pw === '2581') {
            try {
                $uploadedFile = $_FILES['backup_file']['tmp_name'];
                if (is_uploaded_file($uploadedFile)) {
                    $sql = file_get_contents($uploadedFile);
                    $conn->multi_query($sql);
                    
                    // منتظر اتمام تمام کوئری‌ها
                    do {
                        if ($result = $conn->store_result()) {
                            $result->free();
                        }
                    } while ($conn->next_result());
                    
                    $message = 'بازیابی دیتابیس با موفقیت انجام شد.';
                } else {
                    $error = 'خطا در آپلود فایل.';
                }
            } catch (Exception $e) {
                $error = 'خطا در بازیابی دیتابیس: ' . $e->getMessage();
            }
        } else {
            $error = 'رمز عبور اشتباه است.';
        }
    }
    
    // ذخیره اطلاعات کسب و کار
    if (isset($_POST['save_business_info'])) {
        $business_name = clean($_POST['business_name'] ?? '');
        $business_address = clean($_POST['business_address'] ?? '');
        $business_phone = clean($_POST['business_phone'] ?? '');
        $business_email = clean($_POST['business_email'] ?? '');
        $business_website = clean($_POST['business_website'] ?? '');
        
        $settings = [
            'business_name' => $business_name,
            'business_address' => $business_address,
            'business_phone' => $business_phone,
            'business_email' => $business_email,
            'business_website' => $business_website
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        $message = 'اطلاعات کسب و کار با موفقیت ذخیره شد.';
    }
}

// نمایش پیام موفقیت ریست دیتابیس
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    $message = 'دیتابیس با موفقیت ریست شد.';
}

// دریافت اطلاعات کسب و کار فعلی
$business_info = [];
$business_fields = ['business_name', 'business_address', 'business_phone', 'business_email', 'business_website'];
foreach ($business_fields as $field) {
    $result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = '$field'");
    $business_info[$field] = ($result && $result->num_rows > 0) ? $result->fetch_assoc()['setting_value'] : '';
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات سیستم - <?php echo $business_info['business_name'] ?: 'سیستم انبارداری'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2"><i class="bi bi-gear"></i> تنظیمات سیستم</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> بازگشت به داشبورد
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- اطلاعات کسب و کار -->
            <!-- وارد کردن لیست انبار -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-upload"></i> وارد کردن لیست انبار
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">آپلود فایل CSV برای به‌روزرسانی لیست کالاها.</p>
                    <a href="import_inventory.php" class="btn btn-outline-primary">
                        <i class="bi bi-upload"></i> آپلود لیست انبار
                    </a>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building"></i> اطلاعات کسب و کار
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="business_name" class="form-label">نام شرکت/کسب و کار</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" 
                                       value="<?php echo htmlspecialchars($business_info['business_name']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="business_phone" class="form-label">تلفن</label>
                                <input type="text" class="form-control" id="business_phone" name="business_phone" 
                                       value="<?php echo htmlspecialchars($business_info['business_phone']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="business_email" class="form-label">ایمیل</label>
                                <input type="email" class="form-control" id="business_email" name="business_email" 
                                       value="<?php echo htmlspecialchars($business_info['business_email']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="business_website" class="form-label">وب‌سایت</label>
                                <input type="url" class="form-control" id="business_website" name="business_website" 
                                       value="<?php echo htmlspecialchars($business_info['business_website']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="business_address" class="form-label">آدرس</label>
                            <textarea class="form-control" id="business_address" name="business_address" rows="3"><?php echo htmlspecialchars($business_info['business_address']); ?></textarea>
                        </div>
                        <button type="submit" name="save_business_info" class="btn btn-primary">
                            <i class="bi bi-check"></i> ذخیره اطلاعات
                        </button>
                    </form>
                </div>
            </div>

            <!-- مدیریت دیتابیس -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-database"></i> مدیریت دیتابیس
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- بک‌آپ دیتابیس -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-download display-4 text-primary"></i>
                                    <h6 class="card-title mt-2">بک‌آپ دیتابیس</h6>
                                    <p class="card-text small">دانلود فایل پشتیبان از دیتابیس</p>
                                    <form method="POST" class="d-inline">
                                        <button type="submit" name="backup_db" class="btn btn-primary btn-sm">
                                            <i class="bi bi-download"></i> دانلود بک‌آپ
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- ریستور دیتابیس -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-upload display-4 text-warning"></i>
                                    <h6 class="card-title mt-2">بازیابی دیتابیس</h6>
                                    <p class="card-text small">آپلود و بازیابی از فایل پشتیبان</p>
                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#restoreModal">
                                        <i class="bi bi-upload"></i> بازیابی
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- ریست دیتابیس -->
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-arrow-clockwise display-4 text-danger"></i>
                                    <h6 class="card-title mt-2">ریست دیتابیس</h6>
                                    <p class="card-text small">حذف تمام داده‌ها و بازگشت به حالت اولیه</p>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#resetModal">
                                        <i class="bi bi-arrow-clockwise"></i> ریست
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال ریست دیتابیس -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تایید ریست دیتابیس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>هشدار:</strong> این عملیات تمام داده‌های دیتابیس را حذف خواهد کرد!
                    </div>
                    <div class="mb-3">
                        <label for="reset_password" class="form-label">رمز عبور تایید</label>
                        <input type="password" class="form-control" id="reset_password" name="reset_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="reset_db" class="btn btn-danger">ریست دیتابیس</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال بازیابی دیتابیس -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">بازیابی دیتابیس</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>توجه:</strong> این عملیات داده‌های فعلی را جایگزین خواهد کرد!
                    </div>
                    <div class="mb-3">
                        <label for="backup_file" class="form-label">فایل بک‌آپ (.sql)</label>
                        <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                    </div>
                    <div class="mb-3">
                        <label for="restore_password" class="form-label">رمز عبور تایید</label>
                        <input type="password" class="form-control" id="restore_password" name="restore_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="restore_db" class="btn btn-warning">بازیابی</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
