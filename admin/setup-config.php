<?php
/**
 * Database configuration setup
 * Step 1: Database configuration
 */

define('INSTALLING', true);

// Load theme system
require_once __DIR__ . '/../bootstrap.php';

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Test database connection
        $dbhost = trim($_POST['dbhost'] ?? '');
        $dbname = trim($_POST['dbname'] ?? '');
        $dbuser = trim($_POST['dbuser'] ?? '');
        $dbpass = trim($_POST['dbpass'] ?? '');
        
        if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
            $errors[] = 'لطفاً همه فیلدهای اجباری را پر کنید.';
        } else {
            // Test connection
            try {
                $test_conn = new mysqli($dbhost, $dbuser, $dbpass);
                if ($test_conn->connect_error) {
                    throw new Exception('خطا در اتصال: ' . $test_conn->connect_error);
                }
                
                // Create database if not exists
                $test_conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $test_conn->select_db($dbname);
                $test_conn->close();
                
                // Save config
                $config_content = "<?php\n";
                $config_content .= "// Database configuration\n";
                $config_content .= "define('DB_HOST', '" . addslashes($dbhost) . "');\n";
                $config_content .= "define('DB_USER', '" . addslashes($dbuser) . "');\n";
                $config_content .= "define('DB_PASS', '" . addslashes($dbpass) . "');\n";
                $config_content .= "define('DB_NAME', '" . addslashes($dbname) . "');\n\n";
                $config_content .= "// System version\n";
                $config_content .= "define('SYSTEM_VERSION', '1.0.0');\n\n";
                $config_content .= "// Database connection\n";
                $config_content .= "try {\n";
                $config_content .= "    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);\n";
                $config_content .= "    \$conn->set_charset('utf8mb4');\n";
                $config_content .= "    if (\$conn->connect_error) {\n";
                $config_content .= "        throw new Exception('Database connection failed: ' . \$conn->connect_error);\n";
                $config_content .= "    }\n";
                $config_content .= "} catch (Exception \$e) {\n";
                $config_content .= "    die('Database error: ' . \$e->getMessage());\n";
                $config_content .= "}\n";
                
                if (file_put_contents(__DIR__ . '/../config.php', $config_content)) {
                    header('Location: install.php?step=2');
                    exit;
                } else {
                    $errors[] = 'خطا در ایجاد فایل پیکربندی.';
                }
                
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

// Get business info for header
$business_info = ['business_name' => 'سیستم مدیریت انبار'];

get_template_part('header');
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-gear"></i>
                        تنظیمات پایگاه داده
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p class="lead">به نصب سیستم مدیریت انبار خوش آمدید!</p>
                    <p>برای شروع، نیاز به اطلاعات پایگاه داده شما داریم.</p>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="step" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="dbhost" class="form-label">آدرس میزبان پایگاه داده</label>
                                <input type="text" class="form-control" id="dbhost" name="dbhost" 
                                       value="<?= htmlspecialchars($_POST['dbhost'] ?? 'localhost') ?>" required>
                                <div class="form-text">معمولاً localhost</div>
                            </div>
                            <div class="col-md-6">
                                <label for="dbname" class="form-label">نام پایگاه داده</label>
                                <input type="text" class="form-control" id="dbname" name="dbname" 
                                       value="<?= htmlspecialchars($_POST['dbname'] ?? 'php1') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dbuser" class="form-label">نام کاربری</label>
                                <input type="text" class="form-control" id="dbuser" name="dbuser" 
                                       value="<?= htmlspecialchars($_POST['dbuser'] ?? 'root') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dbpass" class="form-label">رمز عبور</label>
                                <input type="password" class="form-control" id="dbpass" name="dbpass" 
                                       value="<?= htmlspecialchars($_POST['dbpass'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-arrow-right"></i>
                                ادامه
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_template_part('footer'); ?>
