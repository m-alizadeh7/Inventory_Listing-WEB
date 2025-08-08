<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-gear"></i> تنظیمات سیستم
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="index.php?controller=main&action=update_settings">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="business_name" class="form-label">نام شرکت/کسب‌وکار</label>
                                <input type="text" class="form-control" id="business_name" name="business_name" 
                                       value="<?php echo htmlspecialchars($business_info['business_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_phone" class="form-label">تلفن شرکت</label>
                                <input type="text" class="form-control" id="business_phone" name="business_phone" 
                                       value="<?php echo htmlspecialchars($business_info['business_phone']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_email" class="form-label">ایمیل شرکت</label>
                                <input type="email" class="form-control" id="business_email" name="business_email" 
                                       value="<?php echo htmlspecialchars($business_info['business_email']); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="business_address" class="form-label">آدرس شرکت</label>
                                <textarea class="form-control" id="business_address" name="business_address" rows="3"><?php echo htmlspecialchars($business_info['business_address']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="business_website" class="form-label">وب‌سایت شرکت</label>
                                <input type="url" class="form-control" id="business_website" name="business_website" 
                                       value="<?php echo htmlspecialchars($business_info['business_website']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> ذخیره تغییرات
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>اطلاعات سیستم</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>نسخه PHP:</td>
                                <td><?php echo phpversion(); ?></td>
                            </tr>
                            <tr>
                                <td>نسخه MySQL:</td>
                                <td>
                                    <?php
                                    try {
                                        global $db;
                                        $version = $db->query("SELECT VERSION()")->fetchColumn();
                                        echo $version;
                                    } catch (Exception $e) {
                                        echo 'نامشخص';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>فضای استفاده شده:</td>
                                <td>
                                    <?php
                                    $bytes = disk_total_space(ROOT_PATH) - disk_free_space(ROOT_PATH);
                                    echo formatBytes($bytes);
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>عملیات سیستم</h6>
                        <div class="d-grid gap-2">
                            <a href="index.php?controller=main&action=backup" class="btn btn-outline-info">
                                <i class="bi bi-download"></i> پشتیبان‌گیری
                            </a>
                            <a href="index.php?controller=main&action=clear_cache" class="btn btn-outline-warning">
                                <i class="bi bi-trash"></i> پاک کردن کش
                            </a>
                            <a href="index.php?controller=main&action=check_updates" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-repeat"></i> بررسی به‌روزرسانی
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * تابع فرمت کردن اندازه فایل
 */
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}
?>
