<?php
/**
 * صفحه اصلی سیستم
 * 
 * فایل ورودی اصلی برنامه که درخواست‌ها را به کنترلرهای مناسب هدایت می‌کند
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// بررسی نصب سیستم
if (!file_exists('config.php')) {
    header('Location: setup.php');
    exit;
}

// لود کردن فایل‌های مورد نیاز
require_once 'config.php';

// شروع session
session_start();

// تنظیم نوع درخواست و پارامترهای آن
$controller = $_GET['controller'] ?? 'main';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// لود کردن کنترلر مناسب
switch ($controller) {
    case 'inventory':
        require_once(CORE_PATH . '/controllers/InventoryController.php');
        $controller = new InventoryController();
        break;
    
    case 'device':
        require_once(CORE_PATH . '/controllers/DeviceController.php');
        $controller = new DeviceController();
        break;
    
    case 'production':
        require_once(CORE_PATH . '/controllers/ProductionOrderController.php');
        $controller = new ProductionOrderController();
        break;
    
    case 'main':
    default:
        require_once(CORE_PATH . '/controllers/MainController.php');
        $controller = new MainController();
        break;
}

// اجرای عمل مناسب
switch ($action) {
    // برای کنترلر اصلی
    case 'settings':
        $controller->settings();
        break;
    
    case 'save_settings':
        $controller->saveSettings();
        break;
        
    // برای کنترلر انبار
    case 'view':
        $controller->view($id);
        break;
        
    case 'new':
        $controller->newInventory();
        break;
        
    case 'save':
        $controller->saveInventory();
        break;
        
    case 'delete':
        $controller->deleteInventory($id);
        break;
        
    case 'import_csv':
        $controller->importCSV();
        break;
        
    case 'process_csv':
        $controller->processCSV();
        break;
        
    case 'export_csv':
        $controller->exportCSV($id);
        break;
        
    // برای کنترلر دستگاه
    case 'new_device':
        $controller->newDevice();
        break;
        
    case 'save_device':
        $controller->saveDevice();
        break;
        
    case 'edit_device':
        $controller->editDevice($id);
        break;
        
    case 'update_device':
        $controller->updateDevice();
        break;
        
    case 'delete_device':
        $controller->deleteDevice($id);
        break;
        
    case 'manage_bom':
        $controller->manageBOM($id);
        break;
        
    case 'save_bom':
        $controller->saveBOM();
        break;
        
    // برای کنترلر سفارشات تولید
    case 'new_order':
        $controller->newOrder();
        break;
        
    case 'save_order':
        $controller->saveOrder();
        break;
        
    case 'confirm_order':
        $controller->confirmOrder($id);
        break;
        
    case 'start_production':
        $controller->startProduction($id);
        break;
        
    case 'delete_order':
        $controller->deleteOrder($id);
        break;
        
    // حالت پیش‌فرض
    case 'index':
    default:
        $controller->index();
        break;
}
<div class="row">
    <div class="col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-plus-square text-warning fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">ثبت سفارش تولید</h5>
                </div>
                <p class="card-text">ایجاد سفارش جدید برای تولید محصول.</p>
                <a href="new_production_order.php" class="btn btn-warning w-100">
                    <i class="bi bi-arrow-right-circle"></i> ثبت سفارش
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-list-check text-danger fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">لیست سفارشات تولید</h5>
                </div>
                <p class="card-text">مشاهده و مدیریت سفارشات تولید.</p>
                <a href="production_orders.php" class="btn btn-danger w-100">
                    <i class="bi bi-arrow-right-circle"></i> مشاهده
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-secondary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-hdd-stack text-secondary fs-4"></i>
                    </div>
                    <h5 class="card-title mb-0">دستگاه‌ها و BOM</h5>
                </div>
                <p class="card-text">مدیریت لیست دستگاه‌ها و قطعات آن‌ها.</p>
                <a href="devices.php" class="btn btn-secondary w-100">
                    <i class="bi bi-arrow-right-circle"></i> مدیریت
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// لود کردن فوتر
include 'templates/default/footer.php';
?>
