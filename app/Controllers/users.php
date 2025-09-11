<?php
// صفحه مدیریت کاربران
require_once __DIR__ . '/bootstrap.php';

// اگر security بارگذاری نشده، به صفحه ورود فرستاده شود
if (!isset($security)) {
    header('Location: login.php');
    exit;
}

// اگر کاربر لاگین نکرده، به ورود هدایت شود
if (!$security->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// فقط کاربران با دسترسی مناسب می‌توانند این صفحه را ببینند
if (!$security->hasPermission('users.manage')) {
    http_response_code(403);
    get_template_part('header');
    echo '<div class="container mt-4"><div class="alert alert-danger">شما دسترسی لازم برای مشاهده این صفحه را ندارید.</div></div>';
    get_template_part('footer');
    exit;
}

$users = [];
if (isset($conn) && $conn instanceof mysqli) {
    $res = $conn->query("SELECT u.user_id, u.username, u.email, u.full_name, ur.role_name, u.is_active FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.role_id ORDER BY u.user_id");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

// header (theme)
get_template_part('header');
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">مدیریت کاربران</h4>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="alert alert-info">هیچ کاربری یافت نشد.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>نام کاربری</th>
                                <th>نام کامل</th>
                                <th>ایمیل</th>
                                <th>نقش</th>
                                <th>وضعیت</th>
                                <th>اقدامات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['user_id']) ?></td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['role_name'] ?? '') ?></td>
                                    <td><?= $u['is_active'] ? '<span class="badge bg-success">فعال</span>' : '<span class="badge bg-secondary">غیرفعال</span>' ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?= urlencode($u['user_id']) ?>" class="btn btn-sm btn-outline-primary">ویرایش</a>
                                        <a href="delete_user.php?id=<?= urlencode($u['user_id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('آیا از حذف کاربر مطمئن هستید؟')">حذف</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_template_part('footer');
