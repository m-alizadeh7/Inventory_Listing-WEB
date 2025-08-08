<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-person-circle"></i> پروفایل کاربری
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
                
                <form method="POST" action="index.php?controller=main&action=update_profile">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">نام کاربری</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                <div class="form-text">نام کاربری قابل تغییر نیست.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="full_name" class="form-label">نام و نام خانوادگی</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">ایمیل</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">نقش کاربری</label>
                                <input type="text" class="form-control" id="role" 
                                       value="<?php 
                                       $role_labels = [
                                           'admin' => 'مدیر کل',
                                           'manager' => 'مدیر',
                                           'user' => 'کاربر عادی'
                                       ];
                                       echo $role_labels[$user['role']] ?? 'نامشخص';
                                       ?>" readonly>
                                <div class="form-text">نقش کاربری توسط مدیر تعیین می‌شود.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_login" class="form-label">آخرین ورود</label>
                                <input type="text" class="form-control" id="last_login" 
                                       value="<?php echo $user['last_login'] ? date('Y/m/d H:i', strtotime($user['last_login'])) : 'هرگز'; ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="created_at" class="form-label">تاریخ عضویت</label>
                                <input type="text" class="form-control" id="created_at" 
                                       value="<?php echo date('Y/m/d', strtotime($user['created_at'])); ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6>تغییر رمز عبور</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">رمز عبور فعلی</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">رمز عبور جدید</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تکرار رمز عبور جدید</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="index.php" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
