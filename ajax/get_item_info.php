<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_code'])) {
    $inventory_code = clean($_POST['inventory_code']);
    
    $stmt = $conn->prepare("
        SELECT i.*, ic.category_name 
        FROM inventory i 
        LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id 
        WHERE i.inventory_code = ?
    ");
    $stmt->bind_param('s', $inventory_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'item' => [
                'inventory_code' => $item['inventory_code'],
                'item_name' => $item['item_name'],
                'current_inventory' => (int)$item['current_inventory'],
                'category_name' => $item['category_name']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'کالا یافت نشد'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'درخواست نامعتبر'
    ]);
}
?>
