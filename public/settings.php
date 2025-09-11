<?php
require_once 'bootstrap.php';

// Handle settings operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        $business_name = clean($_POST['business_name']);
        $business_address = clean($_POST['business_address']);
        $business_phone = clean($_POST['business_phone']);
        $business_email = clean($_POST['business_email']);
        $default_language = clean($_POST['default_language']);
        $timezone = clean($_POST['timezone']);

        // Update business info
        $stmt = $conn->prepare("UPDATE business_info SET
            business_name = ?,
            business_address = ?,
            business_phone = ?,
            business_email = ?,
            default_language = ?,
            timezone = ?
            WHERE id = 1");
        if ($stmt->bind_param('ssssss', $business_name, $business_address, $business_phone, $business_email, $default_language, $timezone) && $stmt->execute()) {
            set_flash_message('تنظیمات با موفقیت بروزرسانی شد', 'success');
        } else {
            set_flash_message('خطا در بروزرسانی تنظیمات: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['update_security'])) {
        $session_timeout = (int)$_POST['session_timeout'];
        $password_min_length = (int)$_POST['password_min_length'];
        $login_attempts = (int)$_POST['login_attempts'];
        $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;

        // Update security settings
        $stmt = $conn->prepare("UPDATE system_settings SET
            session_timeout = ?,
            password_min_length = ?,
            login_attempts = ?,
            enable_2fa = ?
            WHERE id = 1");
        if ($stmt->bind_param('iiii', $session_timeout, $password_min_length, $login_attempts, $enable_2fa) && $stmt->execute()) {
            set_flash_message('تنظیمات امنیتی با موفقیت بروزرسانی شد', 'success');
        } else {
            set_flash_message('خطا در بروزرسانی تنظیمات امنیتی: ' . $conn->error, 'danger');
        }
    }

    header('Location: settings.php');
    exit;
}

// Get current settings
$business_info = getBusinessInfo($conn);
$system_settings = [];
$settings_result = $conn->query("SELECT * FROM system_settings WHERE id = 1");
if ($settings_result && $settings_result->num_rows > 0) {
    $system_settings = $settings_result->fetch_assoc();
}

$page_title = 'تنظیمات سیستم';
$page_description = 'مدیریت تنظیمات سیستم و اطلاعات کسب‌وکار';

get_header();
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">تنظیمات سیستم</h4>
                    <p class="text-muted mb-0">مدیریت تنظیمات سیستم و اطلاعات کسب‌وکار</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Business Information -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>
                        اطلاعات کسب‌وکار
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="business_name" class="form-label">نام کسب‌وکار *</label>
                                <input type="text" class="form-control" id="business_name" name="business_name"
                                       value="<?php echo htmlspecialchars($business_info['business_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="business_phone" class="form-label">تلفن</label>
                                <input type="tel" class="form-control" id="business_phone" name="business_phone"
                                       value="<?php echo htmlspecialchars($business_info['business_phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="business_email" class="form-label">ایمیل</label>
                            <input type="email" class="form-control" id="business_email" name="business_email"
                                   value="<?php echo htmlspecialchars($business_info['business_email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="business_address" class="form-label">آدرس</label>
                            <textarea class="form-control" id="business_address" name="business_address" rows="3"><?php
                                echo htmlspecialchars($business_info['business_address'] ?? '');
                            ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="default_language" class="form-label">زبان پیش‌فرض</label>
                                <select class="form-select" id="default_language" name="default_language">
                                    <option value="fa" <?php echo ($business_info['default_language'] ?? '') === 'fa' ? 'selected' : ''; ?>>فارسی</option>
                                    <option value="en" <?php echo ($business_info['default_language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="timezone" class="form-label">منطقه زمانی</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="Asia/Tehran" <?php echo ($business_info['timezone'] ?? '') === 'Asia/Tehran' ? 'selected' : ''; ?>>تهران (UTC+3:30)</option>
                                    <option value="UTC" <?php echo ($business_info['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>
                            بروزرسانی تنظیمات
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>
                        تنظیمات امنیتی
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="session_timeout" class="form-label">زمان انقضا جلسه (دقیقه)</label>
                            <input type="number" class="form-control" id="session_timeout" name="session_timeout"
                                   value="<?php echo $system_settings['session_timeout'] ?? 60; ?>" min="5" max="480">
                        </div>

                        <div class="mb-3">
                            <label for="password_min_length" class="form-label">حداقل طول رمز عبور</label>
                            <input type="number" class="form-control" id="password_min_length" name="password_min_length"
                                   value="<?php echo $system_settings['password_min_length'] ?? 8; ?>" min="6" max="32">
                        </div>

                        <div class="mb-3">
                            <label for="login_attempts" class="form-label">حداکثر تلاش ورود</label>
                            <input type="number" class="form-control" id="login_attempts" name="login_attempts"
                                   value="<?php echo $system_settings['login_attempts'] ?? 5; ?>" min="3" max="10">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa"
                                       <?php echo ($system_settings['enable_2fa'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_2fa">
                                    فعال‌سازی احراز هویت دو مرحله‌ای
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="update_security" class="btn btn-warning">
                            <i class="bi bi-shield-check me-1"></i>
                            بروزرسانی امنیت
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        اطلاعات سیستم
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>MySQL Version:</strong> <?php echo $conn->server_info; ?>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                        </div>
                        <div class="col-12 mb-0">
                            <strong>Database:</strong> <?php echo $conn->query("SELECT DATABASE()")->fetch_row()[0]; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
