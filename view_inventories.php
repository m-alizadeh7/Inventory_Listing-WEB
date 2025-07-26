<?php
require_once 'config.php';
$result = $conn->query("SELECT 
                        `session_id`, 
                        `status`,
                        `started_at`,
                        `completed_by`, 
                        `completed_at`,
                        `notes`
                        FROM `inventory_sessions` 
                        ORDER BY `started_at` DESC");
$sessions = [];
while ($row = $result->fetch_assoc()) {
    $sessions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مشاهده انبارداری‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">📋 گزارش‌های انبارداری</h2>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>شناسه جلسه</th>
                <th>وضعیت</th>
                <th>تاریخ شروع</th>
                <th>مسئول</th>
                <th>تاریخ تکمیل</th>
                <th>توضیحات</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?= htmlspecialchars($session['session_id']) ?></td>
                    <td><?= $session['status'] == 'completed' ? 'تکمیل شده' : 'در حال انجام' ?></td>
                    <td><?= htmlspecialchars($session['started_at']) ?></td>
                    <td><?= htmlspecialchars($session['completed_by'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($session['completed_at'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($session['notes'] ?? '-') ?></td>
                    <td>
                        <a href="export_inventory.php?session=<?= urlencode($session['session_id']) ?>" class="btn btn-success btn-sm">دانلود CSV</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">بازگشت</a>
</div>
</body>
</html>
<?php $conn->close(); ?>