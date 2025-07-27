<?php
// نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// مسیر اصلی پروژه
define('BASE_PATH', realpath(dirname(__DIR__)));

// Debug: چاپ مسیر فعلی
var_dump([
    'BASE_PATH' => BASE_PATH,
    'Current Script' => __FILE__,
    'Document Root' => $_SERVER['DOCUMENT_ROOT']
]);

// لود کردن تنظیمات
require_once BASE_PATH . DIRECTORY_SEPARATOR . 'config.php';

// لود کردن Autoloader
require_once BASE_PATH . '/app/Core/Autoloader.php';

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
