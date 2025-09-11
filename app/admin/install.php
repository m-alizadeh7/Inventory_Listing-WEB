<?php
/**
 * Installation wizard - Final step
 * Step 2: Create admin user and install database
 */

define('INSTALLING', true);

// Load config if exists
if (file_exists(__DIR__ . '/../../config/config.php')) {
    require_once __DIR__ . '/../../config/config.php';
} elseif ($step != 1) {
    // Only redirect to setup-config if we're not on step 1
    header('Location: setup-config.php');
    exit;
}

// Load theme system
require_once __DIR__ . '/../../public/bootstrap.php';

$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// Check if already installed
function is_installed() {
    // Only check if config exists and we have database connection
    if (!file_exists(__DIR__ . '/../../config/config.php') || !function_exists('getDbConnection')) {
        return false;
    }

    try {
        $conn = getDbConnection();
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

if (is_installed() && $step != 'complete' && $step != 1) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($step == 1 || $step == 2)) {
    if ($step == 2) {
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
}

// Get business info for header
$business_info = ['business_name' => 'سیستم مدیریت انبار'];

// Get server information
function getServerInfo() {
    $info = [];

    // PHP Version
    $info['php_version'] = PHP_VERSION;
    $info['php_version_status'] = version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'warning';

    // MySQL Version - will be checked after DB connection
    $info['mysql_version'] = 'Not connected yet';
    $info['mysql_version_status'] = 'warning';

    // Server Software
    $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';

    // Operating System
    $info['os'] = PHP_OS;

    // Memory Limit
    $info['memory_limit'] = ini_get('memory_limit');

    // Max Execution Time
    $info['max_execution_time'] = ini_get('max_execution_time') . ' seconds';

    // Upload Max Filesize
    $info['upload_max_filesize'] = ini_get('upload_max_filesize');

    // Post Max Size
    $info['post_max_size'] = ini_get('post_max_size');

    return $info;
}

$server_info = getServerInfo();

// Handle database configuration step
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $dbhost = trim($_POST['dbhost'] ?? 'localhost');
    $dbname = trim($_POST['dbname'] ?? 'portal');
    $dbuser = trim($_POST['dbuser'] ?? 'root');
    $dbpass = trim($_POST['dbpass'] ?? '');

    if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
        $errors[] = 'لطفاً همه فیلدهای اجباری را پر کنید.';
    } else {
        try {
            // Test database connection
            $test_conn = new mysqli($dbhost, $dbuser, $dbpass);
            if ($test_conn->connect_error) {
                throw new Exception('خطا در اتصال به پایگاه داده: ' . $test_conn->connect_error);
            }

            // Create database if it doesn't exist
            $test_conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            if ($test_conn->error) {
                throw new Exception('خطا در ایجاد پایگاه داده: ' . $test_conn->error);
            }
            $test_conn->close();

            // Save configuration
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
            $config_content .= "        die('Database connection failed: ' . \$conn->connect_error);\n";
            $config_content .= "    }\n";
            $config_content .= "} catch (Exception \$e) {\n";
            $config_content .= "    die('Database connection error: ' . \$e->getMessage());\n";
            $config_content .= "}\n";
            $config_content .= "?>";

            // Write config file
            if (file_put_contents(__DIR__ . '/../../config/config.php', $config_content) === false) {
                throw new Exception('خطا در ذخیره فایل پیکربندی. لطفاً دسترسی نوشتن را بررسی کنید.');
            }

            // Load the new config
            require_once __DIR__ . '/../../config/config.php';

            // Update server info with MySQL version
            try {
                $result = $conn->query("SELECT VERSION() as version");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $server_info['mysql_version'] = $row['version'];
                    $server_info['mysql_version_status'] = version_compare($row['version'], '5.7.0', '>=') ? 'success' : 'warning';
                }
            } catch (Exception $e) {
                $server_info['mysql_version'] = 'Error: ' . $e->getMessage();
                $server_info['mysql_version_status'] = 'danger';
            }

            header('Location: install.php?step=2');
            exit;

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

get_template_part('header');
?>

<style>
/* Professional Installation Wizard Styles */
.installation-wizard {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;
    position: relative;
    overflow-x: hidden;
}

.installation-wizard::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
}

.wizard-container {
    position: relative;
    z-index: 2;
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem;
}

.wizard-header {
    text-align: center;
    margin-bottom: 2rem;
    color: white;
}

.wizard-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.wizard-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 0;
}

.wizard-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 992px) {
    .wizard-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.system-info-panel, .installation-panel {
    background: white;
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    overflow: hidden;
}

.panel-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.25rem 1.5rem;
    border: none;
}

.panel-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.panel-body {
    padding: 1.5rem;
}

.system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1rem;
    border-left: 3px solid #667eea;
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: #212529;
    word-break: break-all;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.installation-steps {
    margin-bottom: 1.5rem;
}

.step-indicator {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    position: relative;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: 10px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    flex: 1;
}

.step.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
}

.step.completed {
    background: #d4edda;
    border-color: #28a745;
    color: #155724;
}

.step-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    background: rgba(255,255,255,0.2);
    color: currentColor;
}

.step.completed .step-icon {
    background: #28a745;
    color: white;
}

.step-text h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.step-text p {
    margin: 0.25rem 0 0 0;
    font-size: 0.8rem;
    opacity: 0.8;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-label {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
    display: block;
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background: white;
}

.form-text {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.btn-install {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 0.875rem 1.75rem;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    width: 100%;
    justify-content: center;
}

.btn-install:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.25);
}

.btn-install:active {
    transform: translateY(0);
}

.alert {
    padding: 1rem 1.25rem;
    border-radius: 10px;
    border: none;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-icon {
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.completion-message {
    text-align: center;
    padding: 2.5rem 1.5rem;
}

.completion-icon {
    font-size: 3.5rem;
    color: #28a745;
    margin-bottom: 1.25rem;
}

.completion-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.75rem;
}

.completion-text {
    font-size: 1rem;
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin: 1rem 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 3px;
    transition: width 0.3s ease;
}

@media (max-width: 768px) {
    .wizard-container {
        padding: 1rem;
    }

    .wizard-title {
        font-size: 1.75rem;
    }

    .wizard-content {
        gap: 1rem;
    }

    .panel-body {
        padding: 1.25rem;
    }

    .system-info-grid {
        grid-template-columns: 1fr;
    }

    .completion-icon {
        font-size: 3rem;
    }

    .completion-title {
        font-size: 1.5rem;
    }
}
</style>

<div class="installation-wizard">
    <div class="wizard-container">
        <!-- Header -->
        <div class="wizard-header">
            <h1 class="wizard-title">
                <i class="bi bi-gear-fill me-3"></i>
                <?php if ($step == 'complete'): ?>
                    نصب کامل شد!
                <?php else: ?>
                    نصب سیستم مدیریت انبار
                <?php endif; ?>
            </h1>
            <p class="wizard-subtitle">
                <?php if ($step == 'complete'): ?>
                    سیستم شما آماده استفاده است
                <?php else: ?>
                    راه‌اندازی سریع و آسان سیستم مدیریت انبار و تولید
                <?php endif; ?>
            </p>
        </div>

        <?php if ($step != 'complete'): ?>
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $step == 1 ? '25%' : ($step == 2 ? '75%' : '100%'); ?>"></div>
        </div>

        <!-- Main Content -->
        <div class="wizard-content">
            <!-- System Information Panel -->
            <div class="system-info-panel">
                <div class="panel-header">
                    <h3>
                        <i class="bi bi-info-circle-fill"></i>
                        اطلاعات سیستم
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="system-info-grid">
                        <div class="info-card">
                            <div class="info-label">نسخه PHP</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($server_info['php_version']); ?>
                                <span class="status-indicator status-<?php echo $server_info['php_version_status']; ?>">
                                    <i class="bi bi-<?php echo $server_info['php_version_status'] == 'success' ? 'check-circle' : ($server_info['php_version_status'] == 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                                    <?php echo $server_info['php_version_status'] == 'success' ? 'مناسب' : ($server_info['php_version_status'] == 'warning' ? 'قدیمی' : 'نا مناسب'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">نسخه MySQL</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($server_info['mysql_version']); ?>
                                <span class="status-indicator status-<?php echo $server_info['mysql_version_status']; ?>">
                                    <i class="bi bi-<?php echo $server_info['mysql_version_status'] == 'success' ? 'check-circle' : ($server_info['mysql_version_status'] == 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                                    <?php echo $server_info['mysql_version_status'] == 'success' ? 'مناسب' : ($server_info['mysql_version_status'] == 'warning' ? 'قدیمی' : 'نا مناسب'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">سیستم عامل</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['os']); ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">وب سرور</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['server_software']); ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">حداکثر حافظه</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['memory_limit']); ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">حداکثر زمان اجرا</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['max_execution_time']); ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">حداکثر حجم آپلود</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['upload_max_filesize']); ?></div>
                        </div>

                        <div class="info-card">
                            <div class="info-label">حداکثر حجم POST</div>
                            <div class="info-value"><?php echo htmlspecialchars($server_info['post_max_size']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Installation Panel -->
            <div class="installation-panel">
                <div class="panel-header">
                    <h3>
                        <i class="bi bi-<?php echo $step == 1 ? 'database' : 'wrench-adjustable'; ?>-circle-fill"></i>
                        <?php echo $step == 1 ? 'پیکربندی پایگاه داده' : 'تنظیمات سیستم'; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <!-- Installation Steps -->
                    <div class="installation-steps">
                        <div class="step-indicator">
                            <div class="step <?php echo $step == 1 ? 'active' : 'completed'; ?>">
                                <div class="step-icon">
                                    <?php if ($step > 1): ?>
                                        <i class="bi bi-check-lg"></i>
                                    <?php else: ?>
                                        <i class="bi bi-1-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-text">
                                    <h4>پیکربندی پایگاه داده</h4>
                                    <p>تنظیم اتصال به MySQL</p>
                                </div>
                            </div>
                        </div>

                        <div class="step-indicator">
                            <div class="step <?php echo $step == 2 ? 'active' : ($step == 'complete' ? 'completed' : ''); ?>">
                                <div class="step-icon">
                                    <?php if ($step == 'complete'): ?>
                                        <i class="bi bi-check-lg"></i>
                                    <?php elseif ($step > 1): ?>
                                        <i class="bi bi-2-circle"></i>
                                    <?php else: ?>
                                        <i class="bi bi-2-circle" style="opacity: 0.3;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-text">
                                    <h4>ایجاد حساب مدیر</h4>
                                    <p>تنظیمات اولیه و ایجاد کاربر مدیر</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <div class="alert-icon">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                            <div>
                                <strong>خطا در نصب:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($step == 1): ?>
                        <!-- Database Configuration Form -->
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="step" value="1">

                            <div class="form-group">
                                <label for="dbhost" class="form-label">
                                    <i class="bi bi-server me-2"></i>
                                    آدرس میزبان پایگاه داده
                                </label>
                                <input type="text" class="form-control" id="dbhost" name="dbhost"
                                       value="<?php echo htmlspecialchars($_POST['dbhost'] ?? 'localhost'); ?>" required>
                                <div class="form-text">معمولاً localhost یا 127.0.0.1</div>
                                <div class="invalid-feedback">
                                    لطفاً آدرس میزبان را وارد کنید.
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="dbname" class="form-label">
                                    <i class="bi bi-folder me-2"></i>
                                    نام پایگاه داده
                                </label>
                                <input type="text" class="form-control" id="dbname" name="dbname"
                                       value="<?php echo htmlspecialchars($_POST['dbname'] ?? 'portal'); ?>" required>
                                <div class="form-text">نام دیتابیسی که می‌خواهید ایجاد شود</div>
                                <div class="invalid-feedback">
                                    لطفاً نام پایگاه داده را وارد کنید.
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dbuser" class="form-label">
                                            <i class="bi bi-person me-2"></i>
                                            نام کاربری
                                        </label>
                                        <input type="text" class="form-control" id="dbuser" name="dbuser"
                                               value="<?php echo htmlspecialchars($_POST['dbuser'] ?? 'root'); ?>" required>
                                        <div class="form-text">نام کاربری MySQL</div>
                                        <div class="invalid-feedback">
                                            لطفاً نام کاربری را وارد کنید.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dbpass" class="form-label">
                                            <i class="bi bi-lock me-2"></i>
                                            رمز عبور
                                        </label>
                                        <input type="password" class="form-control" id="dbpass" name="dbpass"
                                               value="<?php echo htmlspecialchars($_POST['dbpass'] ?? ''); ?>">
                                        <div class="form-text">رمز عبور MySQL (اختیاری)</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn-install">
                                <i class="bi bi-arrow-left"></i>
                                مرحله بعدی: ایجاد حساب مدیر
                            </button>
                        </form>

                    <?php elseif ($step == 2): ?>
                        <!-- Admin User Creation Form -->
                        <form method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="step" value="2">

                        <div class="form-group">
                            <label for="weblog_title" class="form-label">
                                <i class="bi bi-building me-2"></i>
                                عنوان سازمان
                            </label>
                            <input type="text" class="form-control" id="weblog_title" name="weblog_title"
                                   value="<?php echo htmlspecialchars($_POST['weblog_title'] ?? 'سیستم مدیریت انبار'); ?>" required>
                            <div class="form-text">نام سازمان یا شرکت شما</div>
                            <div class="invalid-feedback">
                                لطفاً عنوان سازمان را وارد کنید.
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="user_name" class="form-label">
                                <i class="bi bi-person-circle me-2"></i>
                                نام کاربری مدیر سیستم
                            </label>
                            <input type="text" class="form-control" id="user_name" name="user_name"
                                   value="<?php echo htmlspecialchars($_POST['user_name'] ?? 'admin'); ?>" required>
                            <div class="form-text">نام کاربری برای ورود به پنل مدیریت</div>
                            <div class="invalid-feedback">
                                لطفاً نام کاربری را وارد کنید.
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pass1" class="form-label">
                                <i class="bi bi-shield-lock me-2"></i>
                                رمز عبور مدیر سیستم
                            </label>
                            <input type="password" class="form-control" id="pass1" name="pass1" required>
                            <div class="form-text">رمز عبور باید حداقل 8 کاراکتر باشد</div>
                            <div class="invalid-feedback">
                                لطفاً رمز عبور را وارد کنید.
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="admin_email" class="form-label">
                                <i class="bi bi-envelope me-2"></i>
                                ایمیل مدیر سیستم
                            </label>
                            <input type="email" class="form-control" id="admin_email" name="admin_email"
                                   value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
                            <div class="form-text">ایمیل برای دریافت اعلان‌های سیستم</div>
                            <div class="invalid-feedback">
                                لطفاً ایمیل معتبر وارد کنید.
                            </div>
                        </div>

                        <button type="submit" class="btn-install">
                            <i class="bi bi-rocket-takeoff"></i>
                            شروع نصب سیستم
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Completion Screen -->
        <div class="completion-message">
            <div class="completion-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2 class="completion-title">تبریک! نصب با موفقیت انجام شد</h2>
            <p class="completion-text">
                سیستم مدیریت انبار شما آماده استفاده است. اکنون می‌توانید وارد سیستم شده و از امکانات کامل آن استفاده کنید.
            </p>

            <div class="alert alert-success">
                <div class="alert-icon">
                    <i class="bi bi-info-circle-fill"></i>
                </div>
                <div>
                    <strong>اطلاعات مهم:</strong>
                    <ul class="mb-0 mt-2">
                        <li>نام کاربری مدیر سیستم: <strong><?php echo htmlspecialchars($_POST['user_name'] ?? 'admin'); ?></strong></li>
                        <li>رمز عبور شما ذخیره شده است</li>
                        <li>برای امنیت بیشتر، رمز عبور خود را پس از اولین ورود تغییر دهید</li>
                    </ul>
                </div>
            </div>

            <a href="../login.php" class="btn-install">
                <i class="bi bi-box-arrow-in-right"></i>
                ورود به سیستم مدیریت انبار
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
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

// Password strength indicator
document.getElementById('pass1').addEventListener('input', function() {
    var password = this.value;
    var strength = 0;

    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/\d/)) strength++;
    if (password.match(/[^a-zA-Z\d]/)) strength++;

    // Remove existing classes
    this.classList.remove('border-success', 'border-warning', 'border-danger');

    if (strength >= 3) {
        this.classList.add('border-success');
    } else if (strength >= 2) {
        this.classList.add('border-warning');
    } else {
        this.classList.add('border-danger');
    }
});
</script>

<?php get_template_part('footer'); ?>
