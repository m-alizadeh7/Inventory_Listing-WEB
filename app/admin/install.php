<?php
/**
 * Installation wizard - Final step
 * Step 2: Create admin user and install database
 */

define('INSTALLING', true);

// Load config if exists
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
} else {
    header('Location: setup-config.php');
    exit;
}

// Load theme system
require_once __DIR__ . '/../../public/bootstrap.php';

$step = $_GET['step'] ?? 2;
$errors = [];
$success = [];

// Check if already installed
function is_installed() {
    global $conn;
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1");
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

if (is_installed() && $step != 'complete') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $site_title = trim($_POST['weblog_title'] ?? '');
    $admin_user = trim($_POST['user_name'] ?? '');
    $admin_pass = trim($_POST['pass1'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    
    if (empty($site_title) || empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
        $errors[] = 'لطفاً همه فیلدها را پر کنید.';
    } else {
        try {
            // Run database migrations
            $migrations_run = false;
            
            // Run db.sql if exists (execute statements one-by-one and ignore harmless duplicates)
            if (file_exists(__DIR__ . '/../db.sql')) {
                $sql = file_get_contents(__DIR__ . '/../db.sql');
                $queries = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
                foreach ($queries as $query) {
                    if ($query === '') continue;
                    if (!$conn->query($query)) {
                        $err = strtolower($conn->error);
                        // ignore harmless "already exists" or duplicate constraint errors
                        if (strpos($err, 'already exists') !== false || strpos($err, 'duplicate') !== false || strpos($err, 'exists') !== false || strpos($err, 'duplicate foreign key') !== false || strpos($err, 'fk_') !== false) {
                            continue;
                        } // otherwise surface the error
                        else {
                            throw new Exception('Error executing db.sql: ' . $conn->error);
                        }
                    }
                }
            }
            
            // Run security tables migration (statement-by-statement)
            if (file_exists(__DIR__ . '/../db_users_security.sql')) {
                $sql = file_get_contents(__DIR__ . '/../db_users_security.sql');
                $queries = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
                foreach ($queries as $query) {
                    if ($query === '') continue;
                    if (!$conn->query($query)) {
                        $err = strtolower($conn->error);
                        if (strpos($err, 'already exists') !== false || strpos($err, 'duplicate') !== false || strpos($err, 'exists') !== false || strpos($err, 'duplicate foreign key') !== false) {
                            continue;
                        } else {
                            throw new Exception('Error executing db_users_security.sql: ' . $conn->error);
                        }
                    }
                }
            }
            
            // Run other migrations
            $migrations_dir = __DIR__ . '/../migrations';
            if (is_dir($migrations_dir)) {
                $conn->query("CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )");
                
                $files = glob($migrations_dir . '/*.sql');
                sort($files);
                foreach ($files as $file) {
                    $name = basename($file);
                    $check = $conn->query("SELECT id FROM migrations WHERE migration = '$name'");
                    if (!$check || $check->num_rows == 0) {
                        $sql = file_get_contents($file);
                        $queries2 = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
                        foreach ($queries2 as $q) {
                            if ($q === '') continue;
                            if (!$conn->query($q)) {
                                $err = strtolower($conn->error);
                                if (strpos($err, 'already exists') !== false || strpos($err, 'duplicate') !== false || strpos($err, 'exists') !== false || strpos($err, 'duplicate foreign key') !== false) {
                                    continue;
                                } else {
                                    throw new Exception('Error executing migration ' . $name . ': ' . $conn->error);
                                }
                            }
                        }
                        $safeName = $conn->real_escape_string($name);
                        $conn->query("INSERT INTO migrations (migration) VALUES ('$safeName')");
                    }
                }
            }
            
            // Create default user roles if they don't exist
            $conn->query("INSERT IGNORE INTO user_roles (role_id, role_name, role_name_fa, description, hierarchy_level) VALUES 
                (1, 'admin', 'مدیر سیستم', 'دسترسی کامل به تمام بخش‌های سیستم', 0),
                (2, 'manager', 'مدیر', 'دسترسی مدیریتی به بیشتر بخش‌ها', 1),
                (3, 'user', 'کاربر', 'دسترسی محدود به بخش‌های خاص', 2)");
            
            // Create admin user
            $password_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
            $stmt->bind_param('s', $admin_user);
            $stmt->execute();
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, role_id, is_active) VALUES (?, ?, ?, ?, 1, 1)");
            if ($stmt) {
                $full_name = $site_title . ' Administrator';
                $stmt->bind_param('ssss', $admin_user, $admin_email, $password_hash, $full_name);
                $stmt->execute();
                $stmt->close();
            } else {
                throw new Exception('خطا در آماده‌سازی دستور ایجاد کاربر');
            }
            
            // Save site settings
            $conn->query("CREATE TABLE IF NOT EXISTS settings (
                setting_name VARCHAR(64) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            
            $settings = [
                'business_name' => $site_title,
                'admin_email' => $admin_email,
                'system_version' => SYSTEM_VERSION,
                'install_date' => date('Y-m-d H:i:s')
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                if ($stmt) {
                    $v1 = $value; $v2 = $value; $k = $key;
                    $stmt->bind_param('sss', $k, $v1, $v2);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            header('Location: install.php?step=complete');
            exit;
            
        } catch (Exception $e) {
            $errors[] = 'خطا در نصب: ' . $e->getMessage();
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
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-person-plus"></i>
                        <?php if ($step == 'complete'): ?>
                            نصب کامل شد!
                        <?php else: ?>
                            ایجاد حساب مدیر
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($step == 'complete'): ?>
                        <div class="alert alert-success">
                            <h5>تبریک! نصب با موفقیت انجام شد.</h5>
                            <p>سیستم مدیریت انبار آماده استفاده است.</p>
                        </div>
                        
                        <div class="text-center">
                            <a href="../login.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i>
                                ورود به سیستم
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <p class="lead">اطلاعات سایت و حساب مدیر را وارد کنید:</p>

                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="step" value="2">
                            
                            <div class="mb-3">
                                <label for="weblog_title" class="form-label">عنوان سایت</label>
                                <input type="text" class="form-control" id="weblog_title" name="weblog_title" 
                                       value="<?= htmlspecialchars($_POST['weblog_title'] ?? 'سیستم مدیریت انبار') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="user_name" class="form-label">نام کاربری مدیر</label>
                                <input type="text" class="form-control" id="user_name" name="user_name" 
                                       value="<?= htmlspecialchars($_POST['user_name'] ?? 'admin') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pass1" class="form-label">رمز عبور</label>
                                <input type="password" class="form-control" id="pass1" name="pass1" required>
                                <div class="form-text">حداقل 8 کاراکتر توصیه می‌شود.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">ایمیل مدیر</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-lg"></i>
                                    نصب سیستم
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_template_part('footer'); ?>
