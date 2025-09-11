<?php
/**
 * Login page for the inventory management system
 */

define('INSTALLING', true);

// Load config if exists
if (file_exists(__DIR__ . '/config/config.php')) {
    require_once __DIR__ . '/config/config.php';
} else {
    header('Location: app/admin/setup-config.php');
    exit;
}

// Load theme system
require_once __DIR__ . '/public/bootstrap.php';

$errors = [];
$success = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember_me = isset($_POST['remember_me']);

    if (empty($username) || empty($password)) {
        $errors[] = 'لطفاً نام کاربری و رمز عبور را وارد کنید.';
    } else {
        if (isset($security)) {
            $login_result = $security->login($username, $password, $remember_me);
            if ($login_result['success']) {
                // Login successful
                $redirect_to = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect_to);
                exit;
            } else {
                $errors[] = $login_result['message'];
            }
        } else {
            $errors[] = 'خطا در سیستم امنیتی.';
        }
    }
}

// Get business info for header
$business_info = ['business_name' => 'سیستم مدیریت انبار و تولید'];

echo "<!-- Debug: Page loaded at " . date('Y-m-d H:i:s') . " -->";
echo "<script>console.log('Login page loaded successfully');</script>";
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
            min-height: 500px;
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
            flex: 1;
        }

        .login-right {
            padding: 3rem 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .form-check-input {
            margin-left: 0.5rem;
            margin-right: 0;
        }

        .welcome-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .feature-list {
            text-align: right;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .feature-item i {
            margin-left: 0.75rem;
            font-size: 1.2rem;
            opacity: 0.8;
        }

        .text-center {
            text-align: center;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .mt-4 {
            margin-top: 1.5rem;
        }

        .d-grid {
            display: block;
        }

        .d-none {
            display: none !important;
        }

        .d-md-block {
            display: block !important;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 10px;
                min-height: auto;
            }

            .login-left {
                display: none !important;
            }

            .login-right {
                padding: 2rem 1.5rem;
            }

            .welcome-text {
                font-size: 2rem;
            }
        }

        /* RTL specific adjustments */
        [dir="rtl"] .form-check-input {
            margin-left: 0.5rem;
            margin-right: 0;
        }

        [dir="rtl"] .feature-item i {
            margin-left: 0.75rem;
            margin-right: 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Welcome Message -->
        <div class="login-left d-none d-md-block">
            <div class="login-logo">
                <i class="bi bi-boxes"></i>
            </div>
            <h1 class="welcome-text">خوش آمدید</h1>
            <p class="subtitle">به سیستم مدیریت انبار و تولید</p>

            <div class="feature-list">
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    مدیریت هوشمند موجودی
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    پیگیری سفارشات تولید
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    گزارش‌گیری پیشرفته
                </div>
                <div class="feature-item">
                    <i class="bi bi-check-circle-fill"></i>
                    امنیت بالا
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="text-center mb-4">
                <h3 class="mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    ورود به سیستم
                </h3>
                <p class="text-muted">لطفاً اطلاعات ورود خود را وارد کنید</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="bi bi-person"></i> نام کاربری
                    </label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <div class="form-text">نام کاربری یا ایمیل خود را وارد کنید</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> رمز عبور
                    </label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">رمز عبور خود را وارد کنید</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                    <label class="form-check-label" for="remember_me">
                        <i class="bi bi-clock"></i> مرا به خاطر بسپار
                    </label>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        ورود به سیستم
                    </button>
                </div>

                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        برای اولین ورود از نام کاربری <strong>admin</strong> و رمز عبور <strong>admin123</strong> استفاده کنید
                    </small>
                </div>
            </form>

            <div class="text-center mt-4">
                <a href="app/admin/setup-config.php" class="text-decoration-none">
                    <small class="text-muted">
                        <i class="bi bi-gear me-1"></i>
                        تنظیمات سیستم
                    </small>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('button[type="submit"]');

            form.addEventListener('submit', function(e) {
                console.log('Login form is being submitted...');

                // Basic validation
                const requiredFields = form.querySelectorAll('input[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('لطفاً همه فیلدهای اجباری را پر کنید.');
                    return false;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass me-2"></i>در حال ورود...';

                console.log('Login form validation passed, submitting...');
            });

            // Auto-focus on username field
            const usernameField = document.getElementById('username');
            if (usernameField) {
                usernameField.focus();
            }
        });
    </script>
</body>
</html>
