<?php
require_once '../app/Controllers/ApiController.php';
require_once '../app/Controllers/DeviceController.php';

$apiController = new \App\Controllers\ApiController();
$deviceController = new \App\Controllers\DeviceController();

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && strpos($request, '/api/login') !== false) {
    $apiController->login();
} elseif ($method === 'GET' && strpos($request, '/api/dashboard-stats') !== false) {
    $apiController->getDashboardStats();
} elseif ($method === 'GET' && strpos($request, '/api/devices') !== false) {
    $deviceController->getDevices();
} elseif ($method === 'POST' && strpos($request, '/api/devices') !== false) {
    $deviceController->addDevice();
} else {
    http_response_code(404);
    echo json_encode(['error' => 'API endpoint not found']);
}
?>
