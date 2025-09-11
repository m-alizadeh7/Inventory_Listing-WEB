<?php
/**
 * صفحه مدیریت لایسنس
 * امکان فعال‌سازی، بررسی وضعیت و غیرفعال‌سازی لایسنس را فراهم می‌کند
 */

require_once '../../public/bootstrap.php';
require_once __DIR__ . '/../core/license/LicenseManager.php';

// بررسی دسترسی مدیر
if (!is_admin()) {
    redirect_to('login.php');
    exit();
}

$licenseManager = new LicenseManager();
$license_info = $licenseManager->getLicenseInfo();
$message = '';
$message_type = '';

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // فعال‌سازی لایسنس
    if (isset($_POST['activate_license'])) {
        $license_key = trim($_POST['license_key']);
        $email = trim($_POST['email']);
        
        $result = $licenseManager->activateLicense($license_key, $email);
        
        if ($result['success']) {
            $message = 'لایسنس با موفقیت فعال شد.';
            $message_type = 'success';
            $license_info = $licenseManager->getLicenseInfo();
        } else {
            $message = 'خطا در فعال‌سازی لایسنس: ' . $result['message'];
            $message_type = 'danger';
        }
    }
    
    // بررسی وضعیت لایسنس
    if (isset($_POST['verify_license'])) {
        $result = $licenseManager->verifyLicense();
        
        if ($result['success']) {
            $message = 'لایسنس معتبر است و تا تاریخ ' . (isset($result['data']['expires_at']) ? $result['data']['expires_at'] : 'نامشخص') . ' اعتبار دارد.';
            $message_type = 'success';
            $license_info = $licenseManager->getLicenseInfo();
        } else {
            $message = 'خطا در بررسی لایسنس: ' . $result['message'];
            $message_type = 'danger';
        }
    }
    
    // غیرفعال‌سازی لایسنس
    if (isset($_POST['deactivate_license'])) {
        $result = $licenseManager->deactivateLicense();
        
        if ($result['success']) {
            $message = 'لایسنس با موفقیت غیرفعال شد.';
            $message_type = 'success';
            $license_info = null;
        } else {
            $message = 'خطا در غیرفعال‌سازی لایسنس: ' . $result['message'];
            $message_type = 'danger';
        }
    }
}

// بررسی وضعیت فعلی لایسنس
$is_license_valid = $licenseManager->isLicenseValid();

// نمایش صفحه
get_header();
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            مدیریت لایسنس
            <small>فعال‌سازی و بررسی وضعیت لایسنس نرم‌افزار</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="../../public/index.php"><i class="fa fa-dashboard"></i> داشبورد</a></li>
            <li class="active">مدیریت لایسنس</li>
        </ol>
    </section>

    <section class="content">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">وضعیت لایسنس</h3>
                    </div>
                    <div class="box-body">
                        <?php if ($is_license_valid): ?>
                            <div class="callout callout-success">
                                <h4><i class="fa fa-check"></i> لایسنس فعال است</h4>
                                <p>سیستم شما دارای لایسنس معتبر است و می‌توانید از تمامی امکانات آن استفاده کنید.</p>
                            </div>
                            
                            <?php if ($license_info): ?>
                                <table class="table table-striped">
                                    <tr>
                                        <th>کلید لایسنس:</th>
                                        <td><?php echo substr($license_info['license_key'], 0, 5) . '...' . substr($license_info['license_key'], -5); ?></td>
                                    </tr>
                                    <tr>
                                        <th>ایمیل:</th>
                                        <td><?php echo isset($license_info['email']) ? $license_info['email'] : 'نامشخص'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>نوع لایسنس:</th>
                                        <td><?php echo isset($license_info['license_type']) ? $license_info['license_type'] : 'نامشخص'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>تاریخ فعال‌سازی:</th>
                                        <td><?php echo isset($license_info['activated_at']) ? $license_info['activated_at'] : 'نامشخص'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>تاریخ انقضا:</th>
                                        <td><?php echo isset($license_info['expires_at']) ? $license_info['expires_at'] : 'نامشخص'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>آخرین بررسی:</th>
                                        <td><?php echo isset($license_info['last_check']) ? $license_info['last_check'] : 'نامشخص'; ?></td>
                                    </tr>
                                </table>
                                
                                <form method="post" class="mt-3">
                                    <button type="submit" name="verify_license" class="btn btn-info">
                                        <i class="fa fa-refresh"></i> بررسی وضعیت لایسنس
                                    </button>
                                    <button type="submit" name="deactivate_license" class="btn btn-danger" onclick="return confirm('آیا از غیرفعال‌سازی لایسنس اطمینان دارید؟');">
                                        <i class="fa fa-times"></i> غیرفعال‌سازی لایسنس
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="callout callout-warning">
                                <h4><i class="fa fa-warning"></i> لایسنس فعال نیست</h4>
                                <p>برای استفاده از تمامی امکانات سیستم، لطفاً لایسنس خود را فعال کنید.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$is_license_valid): ?>
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">فعال‌سازی لایسنس</h3>
                    </div>
                    <div class="box-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="license_key">کلید لایسنس</label>
                                <input type="text" class="form-control" id="license_key" name="license_key" placeholder="کلید لایسنس خود را وارد کنید" required>
                                <p class="help-block">کلید لایسنس خود را که در هنگام خرید دریافت کرده‌اید وارد کنید.</p>
                            </div>
                            <div class="form-group">
                                <label for="email">ایمیل</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="ایمیل خود را وارد کنید" required>
                                <p class="help-block">ایمیلی که با آن خرید انجام داده‌اید را وارد کنید.</p>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="activate_license" class="btn btn-primary">
                                    <i class="fa fa-key"></i> فعال‌سازی لایسنس
                                </button>
                            </div>
                        </form>
                        
                        <div class="callout callout-info">
                            <h4>نیاز به خرید لایسنس دارید؟</h4>
                            <p>برای خرید لایسنس جدید، لطفاً به <a href="https://example.com/buy" target="_blank">فروشگاه ما</a> مراجعه کنید.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php get_footer(); ?>
