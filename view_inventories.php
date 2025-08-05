<?php
require_once 'config.php';
require_once 'includes/functions.php';($date) {
    if (empty($date)) return '-';
    $datetime = new DateTime($date);
    $timestamp = $datetime->getTimestamp();
    
    $year = date('Y', $timestamp);
    $month = date('n', $timestamp);
    $day = date('j', $timestamp);
    $hour = date('H', $timestamp);
    $minute = date('i', $timestamp);
    
    $jYear = $jMonth = $jDay = 0;
    convertToJalali($year, $month, $day, $jYear, $jMonth, $jDay);
    
    return sprintf('%04d/%02d/%02d %02d:%02d', $jYear, $jMonth, $jDay, $hour, $minute);
}

function convertToJalali($g_y, $g_m, $g_d, &$j_y, &$j_m, &$j_d) {
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    $gy = $g_y-1600;
    $gm = $g_m-1;
    $gd = $g_d-1;
    
    $g_day_no = 365*$gy+div($gy+3,4)-div($gy+99,100)+div($gy+399,400);
    
    for ($i=0; $i < $gm; ++$i)
        $g_day_no += $g_days_in_month[$i];
    if ($gm>1 && (($gy%4==0 && $gy%100!=0) || ($gy%400==0)))
        $g_day_no++;
    $g_day_no += $gd;
    
    $j_day_no = $g_day_no-79;
    
    $j_np = div($j_day_no, 12053);
    $j_day_no = $j_day_no % 12053;
    
    $jy = 979+33*$j_np+4*div($j_day_no,1461);
    
    $j_day_no %= 1461;
    
    if ($j_day_no >= 366) {
        $jy += div($j_day_no-1, 365);
        $j_day_no = ($j_day_no-1)%365;
    }
    
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
        $j_day_no -= $j_days_in_month[$i];
    $jm = $i+1;
    $jd = $j_day_no+1;
    
    $j_y = $jy;
    $j_m = $jm;
    $j_d = $jd;
}

function div($a, $b) {
    return (int)($a / $b);
}

$result = $conn->query("
    SELECT 
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
    ORDER BY s.started_at DESC
");
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
    <h2 class="mb-4">📋 گزارش‌های انبارداری</h2>
    <table class="table table-bordered table-hover">
        <thead>
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
                    <td><?= $session['status'] == 'completed' ? 'تکمیل شده' : 'در حال انجام' ?></td>
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
                        <a href="export_csv.php?session=<?= urlencode($session['session_id']) ?>" class="btn btn-success btn-sm">دانلود فایل</a>
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