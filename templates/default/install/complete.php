<?php
/**
 * قالب صفحه تکمیل نصب
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
            max-width: 600px;
            margin: 0 auto;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .install-logo {
            font-size: 70px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .install-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="install-header">
            <div class="install-logo">
                <i class="bi bi-box-seam"></i>
            </div>
            <h1>سیستم مدیریت انبار</h1>
            <p class="lead">نسخه 1.0.0</p>
        </div>
        
        <div class="install-card">
            <div class="success-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            
            <h2 class="mb-4">نصب با موفقیت انجام شد!</h2>
            
            <p class="mb-4">
                سیستم مدیریت انبار با موفقیت نصب شد. شما اکنون می‌توانید از تمام امکانات سیستم استفاده کنید.
            </p>
            
            <div class="row text-start mb-4">
                <div class="col-md-6">
                    <h5><i class="bi bi-check2-circle text-success me-2"></i>ویژگی‌های نصب شده:</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check text-success me-2"></i>دیتابیس آماده</li>
                        <li><i class="bi bi-check text-success me-2"></i>کاربر مدیر ایجاد شده</li>
                        <li><i class="bi bi-check text-success me-2"></i>تنظیمات پایه</li>
                        <li><i class="bi bi-check text-success me-2"></i>سیستم امنیتی</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="bi bi-gear text-primary me-2"></i>مراحل بعدی:</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-arrow-left text-primary me-2"></i>ورود به سیستم</li>
                        <li><i class="bi bi-arrow-left text-primary me-2"></i>تنظیم اطلاعات کسب‌وکار</li>
                        <li><i class="bi bi-arrow-left text-primary me-2"></i>اضافه کردن کالاها</li>
                        <li><i class="bi bi-arrow-left text-primary me-2"></i>مدیریت تأمین‌کنندگان</li>
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>نکته امنیتی:</strong> برای امنیت بیشتر، پس از ورود به سیستم، رمز عبور مدیر را تغییر دهید.
            </div>
            
            <div class="d-grid gap-2">
                <a href="index.php" class="btn btn-success btn-lg">
                    <i class="bi bi-house-door me-2"></i>ورود به سیستم
                </a>
            </div>
        </div>
        
        <div class="text-center text-muted">
            <small>
                طراحی و توسعه: <a href="https://alizadehx.ir" target="_blank">مهدی علیزاده</a>
            </small>
        </div>
    </div>
</body>
</html>
