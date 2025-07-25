<?php
require_once 'config.php';

$session = $_GET['session'];
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventory_report_' . $session . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM برای UTF-8
fputcsv($output, ['ردیف', 'کد انبار', 'نام کالا', 'واحد', 'موجودی فعلی', 'مورد نیاز', 'توضیحات', 'زمان ثبت', 'مسئول', 'تاریخ تکمیل']);

$result = $conn->query("SELECT i.`row_number`, i.`inventory_code`, i.`item_name`, i.`unit`, r.`current_inventory`, r.`required`, r.`notes`, r.`updated_at`, r.`completed_by`, r.`completed_at`
                        FROM `inventory_records` r
                        JOIN `inventory` i ON r.`inventory_id` = i.`id`
                        WHERE r.`inventory_session` = '" . $conn->real_escape_string($session) . "'
                        ORDER BY i.`row_number`");
while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

$conn->close();
fclose($output);
exit();
?>