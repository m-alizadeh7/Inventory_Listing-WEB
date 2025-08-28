<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Log برای دیباگ
error_log("finalize_inventory.php called");

// اطمینان از وجود جدول و ستون‌های لازم
$conn->query("CREATE TABLE IF NOT EXISTS inventory_sessions (
    session_id VARCHAR(64) PRIMARY KEY,
    status VARCHAR(20) DEFAULT 'draft',
    completed_by VARCHAR(100) NULL,
    completed_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// اطمینان از وجود ستون‌های completed_by و completed_at
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'completed_by'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN completed_by VARCHAR(100) NULL");
}
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'completed_at'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN completed_at DATETIME NULL");
}

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
        // اگر جلسه وجود ندارد، آن را ایجاد کنیم
        $createStmt = $conn->prepare("INSERT INTO inventory_sessions (session_id, status) VALUES (?, 'draft')");
        $createStmt->bind_param("s", $session_id);
        if (!$createStmt->execute()) {
            throw new Exception('خطا در ایجاد جلسه انبارگردانی: ' . $createStmt->error);
        }
        $createStmt->close();
        $session_status = 'draft';
    } else {
        $session = $result->fetch_assoc();
        $session_status = $session['status'];
    }
    $checkStmt->close();

    if ($session_status === 'completed') {
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
