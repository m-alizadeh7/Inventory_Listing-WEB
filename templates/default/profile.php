<?php
/**
 * قالب صفحه پروفایل کاربر
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <i class="bi bi-person-circle text-primary" style="font-size: 60px;"></i>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted mb-3"><?php 
                        $role_titles = [
                            'admin' => 'مدیر سیستم',
                            'manager' => 'مدیر',
                            'inventory' => 'کارشناس انبار',
                            'production' => 'کارشناس تولید'
                        ];
                        echo $role_titles[$user['role']] ?? $user['role'];
                    ?></p>
                    <p class="small text-muted mb-0">
                        <i class="bi bi-clock-history"></i> آخرین ورود: 
                        <?php echo $user['last_login'] ? date('Y/m/d H:i', strtotime($user['last_login'])) : 'هیچ‌وقت'; ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">اطلاعات حساب</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="small text-muted d-block">نام کاربری</label>
                        <div class="fw-medium"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted d-block">ایمیل</label>
                        <div class="fw-medium"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="mb-0">
                        <label class="small text-muted d-block">تاریخ ایجاد حساب</label>
                        <div class="fw-medium">
                            <?php echo date('Y/m/d', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">ویرایش پروفایل</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?controller=main&action=update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">نام و نام خانوادگی</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">ایمیل</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">تغییر رمز عبور</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?controller=main&action=change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">رمز عبور فعلی</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="new_password" class="form-label">رمز عبور جدید</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">تکرار رمز عبور جدید</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">تغییر رمز عبور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
