<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['inventory_session'])) {
    echo json_encode(['success' => false, 'message' => 'جلسه انبارگردانی نامعتبر است']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$session_id = $_SESSION['inventory_session'];

try {
    $conn->begin_transaction();

    // بروزرسانی وضعیت جلسه به 'completed'
    $stmt = $conn->prepare("UPDATE inventory_sessions SET 
        status = 'completed',
        completed_by = ?,
        completed_at = ?
        WHERE session_id = ?");
    $stmt->bind_param("sss", $data['completed_by'], $data['completed_at'], $session_id);
    $stmt->execute();

    // بروزرسانی موجودی فعلی در جدول inventory
    $stmt = $conn->prepare("UPDATE inventory i 
        INNER JOIN inventory_records r ON i.id = r.inventory_id 
        SET i.current_inventory = r.current_inventory,
            i.last_updated = NOW()
        WHERE r.inventory_session = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();

    $conn->commit();
    
    // پاک کردن جلسه
    unset($_SESSION['inventory_session']);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>