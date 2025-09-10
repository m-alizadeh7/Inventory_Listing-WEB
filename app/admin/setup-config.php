<?php
/**
 * Database configuration setup
 * Step 1: Database configuration
 */

define('INSTALLING', true);

// Load theme system
require_once __DIR__ . '/../../public/bootstrap.php';

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple debug
    file_put_contents(__DIR__ . '/debug.log', 'POST received: ' . print_r($_POST, true) . "\n", FILE_APPEND);
    
    if ($step == 1) {
        $dbhost = trim($_POST['dbhost'] ?? 'localhost');
        $dbname = trim($_POST['dbname'] ?? 'portal');
        $dbuser = trim($_POST['dbuser'] ?? 'root');
        $dbpass = trim($_POST['dbpass'] ?? '');
        
        if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
            $errors[] = 'لطفاً همه فیلدهای اجباری را پر کنید.';
        } else {
            try {
                // Simple connection test
                $test_conn = new mysqli($dbhost, $dbuser, $dbpass);
                if ($test_conn->connect_error) {
                    throw new Exception('خطا در اتصال: ' . $test_conn->connect_error);
                }
                
                // Create database
                $test_conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $test_conn->close();
                
                // Save config
                $config_content = "<?php\n";
                $config_content .= "define('DB_HOST', '" . addslashes($dbhost) . "');\n";
                $config_content .= "define('DB_USER', '" . addslashes($dbuser) . "');\n";
                $config_content .= "define('DB_PASS', '" . addslashes($dbpass) . "');\n";
                $config_content .= "define('DB_NAME', '" . addslashes($dbname) . "');\n";
                $config_content .= "define('SYSTEM_VERSION', '1.0.0');\n";
                $config_content .= "try {\n";
                $config_content .= "    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n";
                $config_content .= "    \$conn->set_charset('utf8mb4');\n";
                $config_content .= "    if (\$conn->connect_error) {\n";
                $config_content .= "        throw new Exception('Database connection failed: ' . \$conn->connect_error);\n";
                $config_content .= "    }\n";
                $config_content .= "} catch (Exception \$e) {\n";
                $config_content .= "    die('Database error: ' . \$e->getMessage());\n";
                $config_content .= "}\n";
                
                $config_path = __DIR__ . '/../../config/config.php';
                if (file_put_contents($config_path, $config_content)) {
                    file_put_contents(__DIR__ . '/debug.log', 'Config saved successfully, redirecting...' . "\n", FILE_APPEND);
                    echo "<script>window.location.href = '/portal/app/admin/install.php?step=2';</script>";
                    exit;
                } else {
                    $errors[] = 'خطا در ایجاد فایل پیکربندی.';
                    file_put_contents(__DIR__ . '/debug.log', 'Failed to save config file' . "\n", FILE_APPEND);
                }
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
                file_put_contents(__DIR__ . '/debug.log', 'Exception: ' . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
    }
}

$business_info = ['business_name' => 'سیستم مدیریت انبار و تولید'];

// Debug message
echo "<!-- Debug: Page loaded at " . date('Y-m-d H:i:s') . " -->";
echo "<script>console.log('PHP executed successfully');</script>";
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنظیمات پایگاه داده - <?php echo htmlspecialchars($business_info['business_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            margin: 20px;
        }
        
        .setup-left {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .setup-right {
            padding: 3rem 2rem;
        }
        
        .setup-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .setup-container {
                margin: 10px;
            }
            
            .setup-left {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="row g-0">
            <!-- Left Side - Branding -->
            <div class="col-md-5 setup-left d-none d-md-block">
                <div class="setup-logo">
                    <i class="bi bi-gear-fill"></i>
                </div>
                <h2 class="mb-3">تنظیمات سیستم</h2>
                <p class="mb-4">به نصب سیستم مدیریت انبار و تولید خوش آمدید</p>
                <div class="text-center">
                    <i class="bi bi-database-fill" style="font-size: 4rem; opacity: 0.8;"></i>
                </div>
            </div>
            
            <!-- Right Side - Form -->
            <div class="col-md-7 setup-right">
                <div class="text-center mb-4">
                    <h3 class="mb-3">پیکربندی پایگاه داده</h3>
                    <p class="text-muted">لطفاً اطلاعات اتصال به پایگاه داده خود را وارد کنید</p>
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
                
                <form method="post" action="">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="mb-3">
                        <label for="dbhost" class="form-label">
                            <i class="bi bi-server"></i> آدرس میزبان پایگاه داده
                        </label>
                        <input type="text" class="form-control" id="dbhost" name="dbhost" 
                               value="<?php echo htmlspecialchars($_POST['dbhost'] ?? 'localhost'); ?>" required>
                        <div class="form-text">معمولاً localhost یا 127.0.0.1</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="dbname" class="form-label">
                            <i class="bi bi-folder"></i> نام پایگاه داده
                        </label>
                        <input type="text" class="form-control" id="dbname" name="dbname" 
                               value="<?php echo htmlspecialchars($_POST['dbname'] ?? 'portal'); ?>" required>
                        <div class="form-text">نام دیتابیسی که می‌خواهید ایجاد شود</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dbuser" class="form-label">
                                <i class="bi bi-person"></i> نام کاربری
                            </label>
                            <input type="text" class="form-control" id="dbuser" name="dbuser" 
                                   value="<?php echo htmlspecialchars($_POST['dbuser'] ?? 'root'); ?>" required>
                            <div class="form-text">نام کاربری MySQL</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="dbpass" class="form-label">
                                <i class="bi bi-lock"></i> رمز عبور
                            </label>
                            <input type="password" class="form-control" id="dbpass" name="dbpass" 
                                   value="<?php echo htmlspecialchars($_POST['dbpass'] ?? ''); ?>">
                            <div class="form-text">رمز عبور MySQL (اختیاری)</div>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>
                            ادامه و نصب سیستم
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            سیستم به صورت خودکار پایگاه داده را ایجاد خواهد کرد
                        </small>
                    </div>
                </form>
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
                console.log('Form is being submitted...');
                
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
                submitBtn.innerHTML = '<i class="bi bi-hourglass me-2"></i>در حال پردازش...';
                
                console.log('Form validation passed, submitting...');
            });
        });
    </script>
</body>
</html>
