<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

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

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>