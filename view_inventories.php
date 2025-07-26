<?php
require_once 'config.php';

function gregorianToJalali($date) {
    if (empty($date)) return '-';
    $datetime = new DateTime($date);
    $timestamp = $datetime->getTimestamp();
    
    $array = date::getDate($timestamp);
    $year = $array['year'];
    $month = $array['mon'];
    $day = $array['mday'];
    $hour = $array['hours'];
    $minute = $array['minutes'];
    
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
                    <td><?= gregorianToJalali($session['started_at']) ?></td>
                    <td><?= htmlspecialchars($session['completed_by'] ?? '-') ?></td>
                    <td><?= gregorianToJalali($session['completed_at']) ?></td>
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