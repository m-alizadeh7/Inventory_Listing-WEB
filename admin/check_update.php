<?php
/**
 * صفحه بررسی به‌روزرسانی‌ها برای مدیران
 */

// بارگذاری bootstrap
require_once __DIR__ . '/../bootstrap.php';

// بررسی دسترسی
if (!$security->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// فقط مدیران اجازه دسترسی دارند
if (!$security->hasPermission('admin_access')) {
    header('Location: ../index.php');
    exit;
}

// بارگذاری سیستم به‌روزرسانی
require_once __DIR__ . '/../core/api/updater.php';

// نسخه فعلی سیستم
if (!defined('SYSTEM_VERSION')) {
    define('SYSTEM_VERSION', '1.0.0');
}

// ایجاد شیء به‌روزرسانی
$updater = new UpdaterSystem(SYSTEM_VERSION);

// بررسی وجود به‌روزرسانی
$has_update = false;
$update_info = null;
$error_message = '';
$success_message = '';

// اقدام بر اساس درخواست کاربر
if (isset($_POST['check_update'])) {
    $has_update = $updater->checkForUpdates();
    if ($has_update) {
        $update_info = $updater->getUpdateInfo();
    }
} elseif (isset($_POST['install_update'])) {
    $result = $updater->downloadAndInstallUpdate();
    if ($result) {
        $success_message = 'به‌روزرسانی با موفقیت نصب شد!';
    } else {
        $error_message = 'خطا در نصب به‌روزرسانی. لطفاً فایل لاگ را بررسی کنید.';
    }
}

// نمایش قالب
get_header();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cloud-arrow-down-fill me-2"></i>
                        بررسی به‌روزرسانی‌ها
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">نسخه فعلی</h5>
                                    <p class="card-text fs-4"><?php echo SYSTEM_VERSION; ?></p>
                                    <p class="text-muted">
                                        آخرین بررسی: 
                                        <?php 
                                            $last_check = isset($_SESSION['last_update_check']) ? $_SESSION['last_update_check'] : 'هرگز';
                                            echo $last_check !== 'هرگز' ? gregorianToJalali($last_check) : 'هرگز';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">عملیات</h5>
                                    <form method="post" class="d-grid gap-2">
                                        <button type="submit" name="check_update" class="btn btn-primary">
                                            <i class="bi bi-arrow-repeat me-2"></i>
                                            بررسی به‌روزرسانی‌ها
                                        </button>
                                        <?php if ($has_update && $update_info): ?>
                                            <button type="submit" name="install_update" class="btn btn-success">
                                                <i class="bi bi-cloud-download me-2"></i>
                                                نصب نسخه <?php echo htmlspecialchars($update_info['version']); ?>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($has_update && $update_info): ?>
                        <div class="alert alert-info">
                            <h5>
                                <i class="bi bi-info-circle me-2"></i>
                                به‌روزرسانی جدید در دسترس است!
                            </h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>نسخه جدید:</strong> <?php echo htmlspecialchars($update_info['version']); ?></p>
                                    <p><strong>تاریخ انتشار:</strong> <?php echo gregorianToJalali($update_info['release_date']); ?></p>
                                    <p><strong>حجم فایل:</strong> <?php echo htmlspecialchars($update_info['file_size']); ?></p>
                                </div>
                                <div class="col-md-8">
                                    <h6>تغییرات این نسخه:</h6>
                                    <div class="bg-light p-3 rounded">
                                        <?php echo nl2br(htmlspecialchars($update_info['changelog'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif (isset($_POST['check_update'])): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            شما از آخرین نسخه استفاده می‌کنید.
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <h5>راهنمای به‌روزرسانی</h5>
                        <ol>
                            <li>قبل از به‌روزرسانی، از اطلاعات خود نسخه پشتیبان تهیه کنید.</li>
                            <li>از اتصال اینترنت پایدار اطمینان حاصل کنید.</li>
                            <li>در صورت بروز خطا، لاگ‌های سیستم را بررسی کنید.</li>
                            <li>در صورت نیاز با پشتیبانی تماس بگیرید.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// ذخیره زمان آخرین بررسی
$_SESSION['last_update_check'] = date('Y-m-d H:i:s');

// نمایش فوتر
get_footer();
?>
