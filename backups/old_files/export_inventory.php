<?php
// تنظیم مسیر اصلی
define('ROOT_PATH', dirname(__FILE__));

global $conn;
require_once ROOT_PATH . '/config.php';
require_once ROOT_PATH . '/includes/functions.php';

// بررسی پارامتر session
if (!isset($_GET['session']) || empty($_GET['session'])) {
    die('شناسه جلسه انبارگردانی مشخص نشده است.');
}

$session_id = clean($_GET['session']);

// بررسی وجود جلسه
$checkStmt = $conn->prepare("SELECT session_id, status, completed_by, completed_at FROM inventory_sessions WHERE session_id = ?");
$checkStmt->bind_param("s", $session_id);
$checkStmt->execute();
$session = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$session) {
    die('جلسه انبارگردانی یافت نشد.');
}

// دریافت داده‌های انبارگردانی
$query = "SELECT i.inventory_code, i.item_name, i.unit, r.current_inventory as recorded_inventory, 
           r.notes, i.min_inventory, i.supplier
           FROM inventory i 
           LEFT JOIN inventory_records r ON i.id = r.inventory_id AND r.inventory_session = ?
           ORDER BY i.row_number";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();

// تنظیم هدرهای فایل CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventory_report_' . $session_id . '.csv"');

// ایجاد خروجی CSV
$output = fopen('php://output', 'w');

// مشخصات UTF-8 BOM برای پشتیبانی از حروف فارسی در Excel
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// سربرگ‌های فایل
fputcsv($output, ['کد کالا', 'نام کالا', 'واحد', 'موجودی شمارش شده', 'حداقل موجودی', 'تامین‌کننده', 'توضیحات']);

// داده‌های انبارگردانی
foreach ($items as $item) {
    fputcsv($output, [
        $item['inventory_code'],
        $item['item_name'],
        $item['unit'],
        $item['recorded_inventory'] !== null ? $item['recorded_inventory'] : '0', // صفر برای مقادیر خالی
        $item['min_inventory'],
        $item['supplier'],
        $item['notes']
    ]);
}

fclose($output);
exit;
?>
