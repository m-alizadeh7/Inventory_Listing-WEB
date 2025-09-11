<?php
/**
 * Project Destroy Script
 * Resets the project for testing purposes
 * WARNING: This will delete all data and reset configuration!
 */

// Start session for messages
session_start();

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_destroy'])) {
    try {
        // Include current config to get DB settings
        $configPath = __DIR__ . '/../config/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        } else {
            throw new Exception('Config file not found');
        }

        // Connect to database
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');

        // Get all tables
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }

            // Drop all tables
            foreach ($tables as $table) {
                $conn->query("DROP TABLE IF EXISTS `$table`");
                if ($conn->error) {
                    throw new Exception("Error dropping table $table: " . $conn->error);
                }
            }
        }

        $conn->close();

        // Reset config file
        $exampleConfig = __DIR__ . '/../config/config.example.php';
        $currentConfig = __DIR__ . '/../config/config.php';

        if (file_exists($exampleConfig)) {
            if (copy($exampleConfig, $currentConfig)) {
                $_SESSION['destroy_success'] = 'پروژه با موفقیت ریست شد. تمام جداول حذف و تنظیمات به پیش‌فرض تغییر یافت.';
            } else {
                throw new Exception('Failed to reset config file');
            }
        } else {
            throw new Exception('Config example file not found');
        }

        // Redirect to avoid form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        $_SESSION['destroy_error'] = 'خطا در ریست پروژه: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ریست پروژه - Destroy Project</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 600px; margin-top: 50px; }
        .alert { border-radius: 10px; }
        .btn-danger { border-radius: 10px; padding: 12px 30px; font-size: 18px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h2 class="mb-0">⚠️ ریست پروژه - Destroy Project</h2>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['destroy_success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['destroy_success']; unset($_SESSION['destroy_success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['destroy_error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['destroy_error']; unset($_SESSION['destroy_error']); ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-warning">
                    <h5>هشدار!</h5>
                    <p>این عملیات تمام داده‌ها را حذف خواهد کرد:</p>
                    <ul>
                        <li>تمام جداول دیتابیس</li>
                        <li>تنظیمات کانفیگ به پیش‌فرض</li>
                    </ul>
                    <p><strong>این عملیات قابل بازگشت نیست!</strong></p>
                </div>

                <form method="post" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید پروژه را ریست کنید؟ تمام داده‌ها حذف خواهند شد!')">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm" required>
                        <label class="form-check-label" for="confirm">
                            من متوجه هستم که تمام داده‌ها حذف خواهند شد
                        </label>
                    </div>

                    <button type="submit" name="confirm_destroy" value="1" class="btn btn-danger btn-lg w-100">
                        🚨 ریست پروژه - Destroy Project 🚨
                    </button>
                </form>

                <div class="mt-3">
                    <a href="index.php" class="btn btn-secondary">بازگشت به صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
