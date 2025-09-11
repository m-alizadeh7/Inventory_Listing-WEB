<?php
// Load application bootstrap which initializes config, DB connection and SecurityManager
require_once __DIR__ . '/bootstrap.php';

if (!isset($security) || !$security) {
    // fallback: ensure SecurityManager is available
    if (file_exists(__DIR__ . '/../app/core/includes/SecurityManager.php') && isset($conn) && $conn instanceof mysqli) {
        require_once __DIR__ . '/../app/core/includes/SecurityManager.php';
        $security = new SecurityManager($conn);
    }
}

// اگر کاربر وارد شده، به صفحه اصلی هدایت کن
if (isset($security) && $security->isLoggedIn()) {
    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        if (empty($username) || empty($password)) {
            $error_message = 'لطفاً نام کاربری و رمز عبور را وارد کنید.';
        } else {
            $result = $security->login($username, $password, $remember_me);

            if ($result['success']) {
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Get business info for header
$business_info = getBusinessInfo($conn);
echo "<!-- Debug: Business info loaded: " . htmlspecialchars($business_info['business_name']) . " -->\n";
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-right {
            padding: 3rem 2rem;
        }
        
        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-left: none;
        }
        
        .input-group .form-control {
            border-right: none;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
        }
        
        .security-features {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        .security-features ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .security-features li {
            padding: 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .security-features i {
            margin-left: 0.5rem;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <!-- بخش چپ - اطلاعات سیستم -->
                <div class="col-md-6 login-left">
                    <div class="login-logo">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h2 class="mb-3">سیستم مدیریت انبار</h2>
                    <p class="mb-4">ورود امن به سیستم مدیریت انبار و تولید</p>
                    
                    <div class="security-features">
                        <h6 class="mb-3">
                            <i class="bi bi-shield-lock me-2"></i>
                            ویژگی‌های امنیتی
                        </h6>
                        <ul>
                            <li><i class="bi bi-check2"></i> رمزگذاری پیشرفته</li>
                            <li><i class="bi bi-check2"></i> جلسات امن</li>
                            <li><i class="bi bi-check2"></i> کنترل دسترسی سطحی</li>
                            <li><i class="bi bi-check2"></i> ثبت فعالیت‌ها</li>
                            <li><i class="bi bi-check2"></i> محافظت از حمله‌های brute force</li>
                        </ul>
                    </div>
                </div>
                
                <!-- بخش راست - فرم ورود -->
                <div class="col-md-6 login-right">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold">ورود به سیستم</h3>
                        <p class="text-muted">لطفاً اطلاعات خود را وارد کنید</p>
                    </div>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">نام کاربری</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control" 
                                       placeholder="نام کاربری خود را وارد کنید"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       required>
                                <div class="invalid-feedback">
                                    لطفاً نام کاربری را وارد کنید.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">رمز عبور</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       class="form-control" 
                                       placeholder="رمز عبور خود را وارد کنید"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        onclick="togglePasswordVisibility()">
                                    <i class="bi bi-eye" id="password-toggle-icon"></i>
                                </button>
                                <div class="invalid-feedback">
                                    لطفاً رمز عبور را وارد کنید.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   name="remember_me" 
                                   class="form-check-input" 
                                   id="remember_me">
                            <label class="form-check-label" for="remember_me">
                                مرا به خاطر بسپار (30 روز)
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="login" class="btn btn-primary btn-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                ورود به سیستم
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <div class="small text-muted">
                            <i class="bi bi-shield-check me-1"></i>
                            اتصال امن SSL
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // فعال‌سازی validation فرم
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // تغییر نمایش رمز عبور
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }
        
        // فوکوس خودکار روی فیلد نام کاربری
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>
