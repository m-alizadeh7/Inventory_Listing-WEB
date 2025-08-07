<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Log برای دیباگ
error_log("finalize_inventory.php called");

try {
    // بررسی وجود جلسه انبارگردانی
    if (!isset($_SESSION['inventory_session'])) {
        throw new Exception('جلسه انبارگردانی یافت نشد.');
    }

    // دریافت داده‌های POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['completed_by']) || !isset($input['completed_at'])) {
        throw new Exception('اطلاعات کامل ارسال نشده است.');
    }

    $session_id = $_SESSION['inventory_session'];
    $completed_by = trim($input['completed_by']);
    $completed_at = $input['completed_at'];

    if (empty($completed_by)) {
        throw new Exception('نام مسئول الزامی است.');
    }

    // بررسی وجود جلسه در دیتابیس
    $checkStmt = $conn->prepare("SELECT status FROM inventory_sessions WHERE session_id = ?");
    $checkStmt->bind_param("s", $session_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('جلسه انبارگردانی یافت نشد.');
    }
    
    $session = $result->fetch_assoc();
    $checkStmt->close();

    if ($session['status'] === 'completed') {
        throw new Exception('این جلسه قبلاً نهایی شده است.');
    }

    // به‌روزرسانی وضعیت جلسه
    $stmt = $conn->prepare("UPDATE inventory_sessions SET status = 'completed', completed_by = ?, completed_at = ? WHERE session_id = ?");
    $stmt->bind_param("sss", $completed_by, $completed_at, $session_id);
    
    if (!$stmt->execute()) {
        throw new Exception('خطا در به‌روزرسانی وضعیت جلسه: ' . $stmt->error);
    }
    $stmt->close();

    // پاک کردن جلسه از SESSION
    unset($_SESSION['inventory_session']);

    echo json_encode(['success' => true, 'message' => 'انبارگردانی با موفقیت نهایی شد.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
