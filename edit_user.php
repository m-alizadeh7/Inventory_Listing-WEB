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

// Load user if editing
$user = null;
if ($id > 0 && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT user_id, username, email, full_name, role_id, is_active FROM users WHERE user_id = ?");
    if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $stmt->close(); }
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $email === '') {
        $errors[] = 'نام کاربری و ایمیل اجباری هستند.';
    }

    if (empty($errors) && isset($conn) && $conn instanceof mysqli) {
        // Ensure role_id is valid to avoid FK constraint errors
        $valid_role_id = null;
        if ($role_id > 0) {
            $r = $conn->prepare("SELECT role_id FROM user_roles WHERE role_id = ? LIMIT 1");
            if ($r) { $r->bind_param('i', $role_id); $r->execute(); $rr = $r->get_result()->fetch_assoc(); $r->close(); if ($rr) $valid_role_id = (int)$rr['role_id']; }
        }
        if (!$valid_role_id) {
            // pick the least-privileged role (highest hierarchy_level) if any
            $resr = $conn->query("SELECT role_id FROM user_roles ORDER BY hierarchy_level DESC LIMIT 1");
            if ($resr && $rowr = $resr->fetch_assoc()) {
                $valid_role_id = (int)$rowr['role_id'];
            } else {
                // create a safe default role
                $conn->query("INSERT INTO user_roles (role_name, role_name_fa, hierarchy_level, description) VALUES ('default_user', 'کاربر عادی', 99, 'نقش پیش‌فرض ایجاد شده خودکار')");
                $valid_role_id = $conn->insert_id ?: 1;
            }
        }
        // ensure we use the validated id
        $role_id = $valid_role_id;
        if ($id > 0) {
            // update
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role_id=?, is_active=?, password_hash=? WHERE user_id = ?");
                if ($stmt) { $stmt->bind_param('sssiisi', $username, $email, $full_name, $role_id, $is_active, $hash, $id); $stmt->execute(); $stmt->close(); }
            } else {
                $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role_id=?, is_active=? WHERE user_id = ?");
                if ($stmt) { $stmt->bind_param('sssiii', $username, $email, $full_name, $role_id, $is_active, $id); $stmt->execute(); $stmt->close(); }
            }
            set_flash_message('کاربر با موفقیت بروز شد', 'success');
            header('Location: users.php'); exit;
        } else {
            // create
            if ($password === '') { $errors[] = 'رمز عبور برای ایجاد کاربر جدید لازم است.'; }
            if (empty($errors)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, role_id, is_active, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt) { $stmt->bind_param('sssiis', $username, $email, $full_name, $role_id, $is_active, $hash); $stmt->execute(); $stmt->close(); }
                set_flash_message('کاربر جدید ایجاد شد', 'success');
                header('Location: users.php'); exit;
            }
        }
    }
}

get_template_part('header');
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header"><h4><?= $id>0 ? 'ویرایش کاربر' : 'ایجاد کاربر جدید' ?></h4></div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">نام کاربری</label>
                    <input name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ایمیل</label>
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">نام کامل</label>
                    <input name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">رمز (برای بروز رسانی خالی بگذارید)</label>
                    <input name="password" type="password" class="form-control">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= (isset($user['is_active']) && $user['is_active']) ? 'checked' : '' ?> >
                    <label class="form-check-label" for="is_active">فعال</label>
                </div>
                <button class="btn btn-primary" type="submit">ذخیره</button>
                <a href="users.php" class="btn btn-secondary">انصراف</a>
            </form>
        </div>
    </div>
</div>
<?php get_template_part('footer');
