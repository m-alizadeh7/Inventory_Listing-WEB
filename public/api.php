<?php
require_once '../app/Controllers/ApiController.php';
require_once '../app/Controllers/DeviceController.php';
require_once '../app/Controllers/InventoryController.php';

$apiController = new \App\Controllers\ApiController();
$deviceController = new \App\Controllers\DeviceController();
$inventoryController = new \App\Controllers\InventoryController();

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && strpos($request, '/api/login') !== false) {
    $apiController->login();
} elseif ($method === 'GET' && strpos($request, '/api/dashboard-stats') !== false) {
    $apiController->getDashboardStats();
} elseif ($method === 'GET' && strpos($request, '/api/recent-activities') !== false) {
    $apiController->getRecentActivities();
} elseif ($method === 'GET' && strpos($request, '/api/devices') !== false) {
    $deviceController->getDevices();
} elseif ($method === 'POST' && strpos($request, '/api/devices') !== false) {
    $deviceController->addDevice();
} elseif ($method === 'GET' && strpos($request, '/api/inventory') !== false) {
    $inventoryController->getInventory();
} elseif ($method === 'GET' && preg_match('/\/api\/inventory\/(\d+)/', $request, $matches)) {
    $inventoryController->getInventoryItem($matches[1]);
} elseif ($method === 'POST' && strpos($request, '/api/inventory') !== false) {
    $inventoryController->addInventoryItem();
} elseif ($method === 'PUT' && preg_match('/\/api\/inventory\/(\d+)/', $request, $matches)) {
    $inventoryController->updateInventoryItem($matches[1]);
} elseif ($method === 'DELETE' && preg_match('/\/api\/inventory\/(\d+)/', $request, $matches)) {
    $inventoryController->deleteInventoryItem($matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
}
?>
