<?php
// تنظیم مسیر اصلی
define('ROOT_PATH', dirname(__FILE__));

global $conn;
require_once ROOT_PATH . '/config.php';
if (!isset($conn) || !$conn || !($conn instanceof mysqli)) {
    echo '<div style="color:red; font-weight:bold; margin:2rem;">خطا در اتصال به پایگاه داده. لطفاً تنظیمات دیتابیس را بررسی کنید.</div>';
    exit;
}

// حذف جلسه انبارگردانی در صورت ارسال درخواست
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session_id'])) {
    $del_id = $conn->real_escape_string($_POST['delete_session_id']);
    $conn->query("DELETE FROM inventory_records WHERE inventory_session = '$del_id'");
    $conn->query("DELETE FROM inventory_sessions WHERE session_id = '$del_id'");
    header('Location: view_inventories.php?deleted=1');
    exit;
}

// بررسی و ایجاد جدول inventory_sessions اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_sessions'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_sessions (
        session_id VARCHAR(64) PRIMARY KEY,
        status VARCHAR(20) DEFAULT 'draft',
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_by VARCHAR(100) NULL,
        completed_at DATETIME NULL,
        notes TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_sessions: ' . $conn->error);
    }
}
// اطمینان از وجود ستون started_at
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'started_at'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN started_at DATETIME DEFAULT CURRENT_TIMESTAMP");
}
// اطمینان از وجود ستون notes
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'notes'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN notes TEXT NULL");
}
require_once ROOT_PATH . '/includes/functions.php';

// دریافت لیست انبارگردانی‌ها
$result = $conn->query("SELECT 
    s.session_id,
    s.status,
    s.started_at,
    s.completed_by,
    s.completed_at,
    s.notes,
    COUNT(r.id) as total_items,
    SUM(CASE WHEN r.current_inventory IS NOT NULL THEN 1 ELSE 0 END) as counted_items
FROM inventory_sessions s
LEFT JOIN inventory_records r ON s.session_id = r.inventory_session
GROUP BY s.session_id, s.status, s.started_at, s.completed_by, s.completed_at, s.notes
ORDER BY s.started_at DESC");
    
$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>گزارش‌های انبارداری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .progress { height: 20px; margin-bottom: 0; }
        .progress-bar { 
            background-color: #28a745;
            color: white;
            font-weight: bold;
            text-align: center;
            line-height: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> جلسه انبارگردانی با موفقیت حذف شد.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 گزارش‌های انبارداری</h2>
        <a href="index.php" class="btn btn-secondary">بازگشت</a>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="alert alert-info">هیچ گزارشی یافت نشد.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>شناسه جلسه</th>
                        <th>وضعیت</th>
                        <th>تعداد کل اقلام</th>
                        <th>اقلام شمارش شده</th>
                        <th>درصد پیشرفت</th>
                        <th>تاریخ شروع</th>
                        <th>مسئول</th>
                        <th>تاریخ تکمیل</th>
                        <th>توضیحات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): 
                        $progress = $session['total_items'] > 0 ? 
                            round(($session['counted_items'] / $session['total_items']) * 100) : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($session['session_id']) ?></td>
                            <td>
                                <?php if ($session['status'] == 'completed'): ?>
                                    <span class="badge bg-success">تکمیل شده</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">در حال انجام</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $session['total_items'] ?></td>
                            <td><?= $session['counted_items'] ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                        style="width: <?= $progress ?>%;" 
                                        aria-valuenow="<?= $progress ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        <?= $progress ?>%
                                    </div>
                                </div>
                            </td>
                            <td><?= gregorianToJalali($session['started_at']) ?></td>
                            <td><?= htmlspecialchars($session['completed_by'] ?? '-') ?></td>
                            <td><?= gregorianToJalali($session['completed_at']) ?></td>
                            <td><?= htmlspecialchars($session['notes'] ?? '-') ?></td>
                            <td>
                                <a href="export_inventory.php?session=<?= urlencode($session['session_id']) ?>" 
                                   class="btn btn-success btn-sm">
                                    دانلود فایل
                                </a>
                                <form method="POST" action="" style="display:inline-block;" onsubmit="return confirm('آیا از حذف این انبارگردانی مطمئن هستید؟');">
                                    <input type="hidden" name="delete_session_id" value="<?= htmlspecialchars($session['session_id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>