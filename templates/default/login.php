<?php
/**
 * قالب صفحه ورود به سیستم
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
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            background-color: #fff;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .login-logo img {
            max-width: 100px;
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .login-form {
            margin-bottom: 20px;
        }
        
        .form-control {
            padding: 12px;
            border-radius: 5px;
        }
        
        .login-footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
        }
        
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <div class="login-logo">
                        <i class="bi bi-box-seam text-primary" style="font-size: 60px;"></i>
                    </div>
                    
                    <h1 class="login-title">سیستم مدیریت انبار</h1>
                    
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form class="login-form" method="post" action="index.php?controller=main&action=process_login">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">نام کاربری</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">رمز عبور</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label" for="remember">مرا به خاطر بسپار</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">ورود به سیستم</button>
                    </form>
                    
                    <div class="login-footer">
                        <p>© <?php echo date('Y'); ?> سیستم مدیریت انبار</p>
                        <p>توسعه‌دهنده: <a href="https://alizadehx.ir" target="_blank">Mahdi Alizadeh</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
