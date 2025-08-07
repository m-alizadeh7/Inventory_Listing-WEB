<?php
require_once 'config.php';
session_start();

// بررسی و ایجاد جدول inventory_records اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_records'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT NOT NULL,
        inventory_session VARCHAR(50) NOT NULL,
        current_inventory FLOAT,
        required FLOAT,
        notes TEXT,
        updated_at DATETIME,
        completed_by VARCHAR(255),
        completed_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_records: ' . $conn->error);
    }
}

header('Content-Type: application/json');

// Log برای دیباگ
error_log("save_inventory.php called");

// دریافت داده‌های ارسالی
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['inventory_session'])) {
    echo json_encode(['success' => false, 'message' => 'جلسه انبارگردانی نامعتبر است']);
    exit;
}

$session_id = $_SESSION['inventory_session'];

try {
    $conn->begin_transaction();

    foreach ($data['items'] as $item) {
        // حذف رکورد قبلی اگر وجود داشته باشد
        $stmt = $conn->prepare("DELETE FROM inventory_records WHERE inventory_id = ? AND inventory_session = ?");
        $stmt->bind_param("is", $item['item_id'], $session_id);
        $stmt->execute();
        
        // درج رکورد جدید
        $stmt = $conn->prepare("INSERT INTO inventory_records (inventory_id, inventory_session, current_inventory, notes, updated_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isds", $item['item_id'], $session_id, $item['current_inventory'], $item['notes']);
        $stmt->execute();
    }

if (isset($data['finalize']) && $data['finalize']) {
        $completed_by = $data['completed_by'];
        $completed_at = $data['completed_at'];

        // Update inventory session
        $stmt = $conn->prepare("UPDATE inventory_sessions SET status = 'completed', completed_by = ?, completed_at = ? WHERE session_id = ?");
        $stmt->bind_param("sss", $completed_by, $completed_at, $session_id);
        $stmt->execute();

        // Update main inventory table
        $updateStmt = $conn->prepare("UPDATE inventory SET current_inventory = ? WHERE id = ?");
        foreach ($data['items'] as $item) {
            $updateStmt->bind_param("di", $item['current_inventory'], $item['item_id']);
            $updateStmt->execute();
        }
        $updateStmt->close();
        
        // Clear session
        unset($_SESSION['inventory_session']);
    }

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>