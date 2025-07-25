<?php
require_once 'config.php';
session_start();

$completed_by = $_POST['completed_by'];
$completed_at = $_POST['completed_at'];
$session = $_SESSION['inventory_session'];

// به‌روزرسانی جدول inventory_records
$stmt = $conn->prepare("UPDATE `inventory_records` SET `completed_by` = ?, `completed_at` = ? WHERE `inventory_session` = ?");
$stmt->bind_param("sss", $completed_by, $completed_at, $session);
$stmt->execute();

// تولید فایل CSV
$filename = "inventory_report_$session.csv";
$output = fopen('php://temp', 'w+');
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

// ارسال ایمیل
rewind($output);
$csv_content = stream_get_contents($output);
$boundary = uniqid();
$headers = "From: " . EMAIL_FROM . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
$body = "--$boundary\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$body .= "گزارش انبارداری با شناسه $session در تاریخ $completed_at توسط $completed_by تکمیل شد.\r\n";
$body .= "--$boundary\r\n";
$body .= "Content-Type: text/csv; name=\"$filename\"\r\n";
$body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n\r\n";
$body .= chunk_split(base64_encode($csv_content)) . "\r\n";
$body .= "--$boundary--";
mail(EMAIL_TO, EMAIL_SUBJECT . " ($session)", $body, $headers);

fclose($output);
unset($_SESSION['inventory_session']); // پایان جلسه
$conn->close();

header("Location: view_inventories.php");
exit();
?>