<?php
/**
 * قالب افزودن کاربر جدید
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// دریافت داده‌های فرم از سشن (در صورت خطا)
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">افزودن کاربر جدید</h1>
                <a href="index.php?controller=user&action=list_users" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-right"></i> بازگشت به لیست
                </a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" action="index.php?controller=user&action=add_user">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">نام کاربری <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($form_data['username']) ? htmlspecialchars($form_data['username']) : ''; ?>" required>
                                <div class="form-text">نام کاربری باید منحصر به فرد باشد.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">نام و نام خانوادگی <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo isset($form_data['name']) ? htmlspecialchars($form_data['name']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">ایمیل <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" required>
                                <div class="form-text">ایمیل باید منحصر به فرد باشد.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">نقش کاربر <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">انتخاب نقش...</option>
                                    <option value="admin" <?php echo (isset($form_data['role']) && $form_data['role'] === 'admin') ? 'selected' : ''; ?>>مدیر سیستم</option>
                                    <option value="manager" <?php echo (isset($form_data['role']) && $form_data['role'] === 'manager') ? 'selected' : ''; ?>>مدیر</option>
                                    <option value="inventory" <?php echo (isset($form_data['role']) && $form_data['role'] === 'inventory') ? 'selected' : ''; ?>>کارشناس انبار</option>
                                    <option value="production" <?php echo (isset($form_data['role']) && $form_data['role'] === 'production') ? 'selected' : ''; ?>>کارشناس تولید</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">رمز عبور <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">رمز عبور باید حداقل 8 کاراکتر باشد.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">تکرار رمز عبور <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> ثبت کاربر
                            </button>
                            <a href="index.php?controller=user&action=list_users" class="btn btn-outline-secondary ms-2">انصراف</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // بررسی یکسان بودن رمز عبور
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('confirm_password');
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        if (passwordField.value !== confirmField.value) {
            e.preventDefault();
            alert('رمز عبور و تکرار آن یکسان نیستند.');
            confirmField.focus();
        }
    });
});
</script>
