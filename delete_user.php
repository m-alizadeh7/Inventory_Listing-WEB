<?php
require_once 'bootstrap.php';

if (!isset($security)) {
    header('Location: login.php'); exit;
}

if (!$security->hasPermission('users.manage')) {
    http_response_code(403); get_template_part('header');
    echo '<div class="container mt-4"><div class="alert alert-danger">شما دسترسی لازم را ندارید.</div></div>';
    get_template_part('footer'); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

// Load user info for confirmation
$user = null;
if ($id > 0 && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT user_id, username, email, full_name FROM users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (!$user) {
        $errors[] = 'کاربر یافت نشد.';
    } else {
        // Prevent deletion of current user
        $current_user = $security->getCurrentUser();
        if ($current_user && $current_user['user_id'] == $id) {
            $errors[] = 'نمی‌توانید حساب کاربری خود را حذف کنید.';
        } else {
            // Check if user has any related data
            $has_orders = false;
            $has_inventory = false;

            if (isset($conn) && $conn instanceof mysqli) {
                // Check for production orders
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM production_orders WHERE created_by = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $has_orders = $result['count'] > 0;
                    $stmt->close();
                }

                // Check for inventory records
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_records WHERE created_by = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_assoc();
                    $has_inventory = $result['count'] > 0;
                    $stmt->close();
                }
            }

            if ($has_orders || $has_inventory) {
                $errors[] = 'این کاربر دارای داده‌های مرتبط است و نمی‌تواند حذف شود. ابتدا داده‌ها را انتقال دهید.';
            } else {
                // Safe deletion
                if (isset($conn) && $conn instanceof mysqli) {
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param('i', $id);
                        if ($stmt->execute()) {
                            // Log the deletion
                            if (function_exists('logSecurityEvent')) {
                                logSecurityEvent('user_deleted', "User {$user['username']} deleted by admin");
                            }

                            set_flash_message('کاربر با موفقیت حذف شد', 'success');
                            header('Location: users.php');
                            exit;
                        } else {
                            $errors[] = 'خطا در حذف کاربر: ' . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $errors[] = 'خطا در آماده‌سازی کوئری حذف';
                    }
                } else {
                    $errors[] = 'اتصال به دیتابیس برقرار نیست';
                }
            }
        }
    }
}

get_template_part('header');
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="text-danger">⚠️ حذف کاربر</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <div class="alert alert-warning">
                    <strong>هشدار!</strong> این عملیات قابل بازگشت نیست.
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>اطلاعات کاربر</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>نام کاربری:</strong> <?= htmlspecialchars($user['username']) ?></p>
                        <p><strong>ایمیل:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>نام کامل:</strong> <?= htmlspecialchars($user['full_name'] ?? 'نامشخص') ?></p>
                    </div>
                </div>

                <form method="post" onsubmit="return confirm('آیا مطمئن هستید که می‌خواهید این کاربر را حذف کنید؟')">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> حذف کاربر
                    </button>
                    <a href="users.php" class="btn btn-secondary">انصراف</a>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    کاربر یافت نشد یا شناسه نامعتبر است.
                </div>
                <a href="users.php" class="btn btn-primary">بازگشت به لیست کاربران</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Additional client-side confirmation
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.querySelector('form');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const confirmed = confirm('آیا واقعاً مطمئن هستید؟\nاین عملیات:\n- کاربر را کاملاً حذف می‌کند\n- قابل بازگشت نیست\n- ممکن است بر گزارش‌ها تأثیر بگذارد');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php get_template_part('footer'); ?>
