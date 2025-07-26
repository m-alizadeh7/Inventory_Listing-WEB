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
    
    $jYear = $jMonth = $jDay = 0;
    convertToJalali($year, $month, $day, $jYear, $jMonth, $jDay);
    
    return sprintf('%04d/%02d/%02d', $jYear, $jMonth, $jDay);
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

$session_id = $_GET['session'] ?? '';
if (empty($session_id)) {
    die('شناسه جلسه نامعتبر است');
}

// اجرای کوئری برای دریافت تمام اطلاعات مورد نیاز
$result = $conn->query("
    SELECT 
        i.inventory_code,
        i.item_name,
        i.unit,
        i.supplier,
        i.min_inventory,
        r.current_inventory,
        r.required,
        r.notes,
        r.updated_at,
        r.completed_by,
        r.completed_at
    FROM inventory_records r
    JOIN inventory i ON r.inventory_id = i.id
    WHERE r.inventory_session = '" . $conn->real_escape_string($session_id) . "'
    ORDER BY i.row_number ASC
");

// تنظیم هدرهای فایل
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_' . $session_id . '_' . date('Y-m-d') . '.csv');

// ایجاد خروجی
$output = fopen('php://output', 'w');
// BOM برای پشتیبانی از فارسی در اکسل
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// نوشتن هدر جدول
fputcsv($output, array(
    'ردیف',
    'کد انبار',
    'نام کالا',
    'واحد',
    'تامین کننده',
    'حداقل موجودی',
    'موجودی انبار',
    'مورد نیاز',
    'توضیحات',
    'زمان ثبت',
    'مسئول',
    'تاریخ تکمیل'
));

// نوشتن داده‌ها
$index = 1;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, array(
        $index++,
        $row['inventory_code'],
        $row['item_name'],
        $row['unit'],
        $row['supplier'],
        $row['min_inventory'],
        $row['current_inventory'],
        $row['required'],
        $row['notes'],
        gregorianToJalali($row['updated_at']),
        $row['completed_by'],
        gregorianToJalali($row['completed_at'])
    ));
}

fclose($output);
$conn->close();
?>
