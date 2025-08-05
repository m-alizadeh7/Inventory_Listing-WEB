<?php
// تبدیل تاریخ میلادی به شمسی
function gregorianToJalali($date) {
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

// تابع کمکی برای تبدیل تاریخ
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

// تابع نمایش پیغام
function showMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}

// تابع اعتبارسنجی فایل CSV
function validateCSV($file) {
    $allowed = ['text/csv', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowed)) {
        return 'فرمت فایل باید CSV باشد.';
    }
    if ($file['size'] > 5000000) { // 5MB
        return 'حجم فایل نباید بیشتر از 5 مگابایت باشد.';
    }
    return true;
}

// تابع تمیز کردن ورودی
function clean($string) {
    global $conn;
    return $conn->real_escape_string(trim($string));
}
