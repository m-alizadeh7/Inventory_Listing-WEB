<?php
// تنظیم مسیر اصلی
define('ROOT_PATH', dirname(__FILE__));

require_once ROOT_PATH . '/config.php';
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