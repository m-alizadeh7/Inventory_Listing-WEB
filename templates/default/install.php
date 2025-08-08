<?php
/**
 * قالب صفحه نصب
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- استایل‌ها -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f8f9fa;
        }
        
        .install-container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .install-logo {
            font-size: 70px;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        
        .install-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .install-step {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background-color: #0d6efd;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
        }
        
        .install-footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
        }
        
        .form-control {
            padding: 12px;
            border-radius: 5px;
        }
        
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="install-header">
            <div class="install-logo">
                <i class="bi bi-box-seam"></i>
            </div>
            <h1 class="mb-3">نصب سیستم مدیریت انبار</h1>
            <p class="lead">لطفاً اطلاعات زیر را برای نصب سیستم وارد کنید.</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <div class="install-card">
            <h3 class="mb-4">مراحل نصب</h3>
            
            <div class="install-step">
                <div class="step-number">1</div>
                <div class="step-text">بررسی پیش‌نیازها</div>
                <div class="ms-auto">
                    <?php if ($requirements_met): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="install-step">
                <div class="step-number">2</div>
                <div class="step-text">پیکربندی دیتابیس</div>
                <div class="ms-auto">
                    <?php if ($db_config_exists): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-circle text-secondary"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="install-step">
                <div class="step-number">3</div>
                <div class="step-text">نصب دیتابیس</div>
                <div class="ms-auto">
                    <?php if ($db_installed): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-circle text-secondary"></i>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="install-step">
                <div class="step-number">4</div>
                <div class="step-text">ایجاد کاربر مدیر</div>
                <div class="ms-auto">
                    <?php if ($admin_created): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-circle text-secondary"></i>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$db_config_exists): ?>
        <div class="install-card">
            <h3 class="mb-4">پیکربندی دیتابیس</h3>
            <form method="post" action="index.php?controller=main&action=process_db_config">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="db_host" class="form-label">آدرس هاست دیتابیس</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="col-md-6">
                        <label for="db_port" class="form-label">پورت دیتابیس</label>
                        <input type="number" class="form-control" id="db_port" name="db_port" value="3306" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="db_name" class="form-label">نام دیتابیس</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="db_prefix" class="form-label">پیشوند جداول</label>
                        <input type="text" class="form-control" id="db_prefix" name="db_prefix" value="inv_">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="db_user" class="form-label">نام کاربری دیتابیس</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required>
                    </div>
                    <div class="col-md-6">
                        <label for="db_pass" class="form-label">رمز عبور دیتابیس</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">ذخیره تنظیمات دیتابیس</button>
            </form>
        </div>
        <?php elseif (!$db_installed): ?>
        <div class="install-card">
            <h3 class="mb-4">نصب دیتابیس</h3>
            <p>اکنون سیستم آماده نصب دیتابیس است. با کلیک بر روی دکمه زیر، ساختار دیتابیس ایجاد خواهد شد.</p>
            <form method="post" action="index.php?controller=main&action=install_db">
                <button type="submit" class="btn btn-primary">نصب دیتابیس</button>
            </form>
        </div>
        <?php elseif (!$admin_created): ?>
        <div class="install-card">
            <h3 class="mb-4">ایجاد کاربر مدیر</h3>
            <p>لطفاً اطلاعات مدیر سیستم را وارد کنید.</p>
            <form method="post" action="index.php?controller=main&action=create_admin">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="admin_name" class="form-label">نام و نام خانوادگی</label>
                        <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="admin_email" class="form-label">ایمیل</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="admin_username" class="form-label">نام کاربری</label>
                        <input type="text" class="form-control" id="admin_username" name="admin_username" required>
                    </div>
                    <div class="col-md-6">
                        <label for="admin_password" class="form-label">رمز عبور</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">ایجاد کاربر مدیر</button>
            </form>
        </div>
        <?php else: ?>
        <div class="install-card text-center">
            <h3 class="mb-4 text-success">نصب با موفقیت انجام شد!</h3>
            <p>سیستم مدیریت انبار با موفقیت نصب شد. اکنون می‌توانید وارد سیستم شوید.</p>
            <a href="index.php" class="btn btn-primary">ورود به سیستم</a>
        </div>
        <?php endif; ?>
        
        <div class="install-footer">
            <p>© <?php echo date('Y'); ?> سیستم مدیریت انبار</p>
            <p>توسعه‌دهنده: <a href="https://alizadehx.ir" target="_blank">Mahdi Alizadeh</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
