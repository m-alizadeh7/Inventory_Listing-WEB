<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Router;
use App\Controllers\InventoryController;

$router = new Router();

// تعریف مسیرها
$router->get('/', [InventoryController::class, 'index']);
$router->get('/inventory', [InventoryController::class, 'index']);
$router->get('/inventory/import', [InventoryController::class, 'import']);
$router->post('/inventory/import', [InventoryController::class, 'import']);
$router->get('/inventory/export/{id}', [InventoryController::class, 'export']);
$router->get('/inventory/view/{id}', [InventoryController::class, 'view']);

try {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    // نمایش صفحه خطا
    http_response_code(404);
    include __DIR__ . '/app/Views/error/404.php';
}
