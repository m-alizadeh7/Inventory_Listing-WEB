<?php
/**
 * کنترلر انبار
 *
 * کنترلر مسئول مدیریت عملیات انبار
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');
require_once(CORE_PATH . '/models/inventory.php');

class InventoryController extends BaseController {
    private $inventoryModel;
    
    /**
     * سازنده کنترلر انبار
     */
    public function __construct() {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
    }
    
    /**
     * نمایش صفحه لیست موجودی
     */
    public function index() {
        $inventories = $this->inventoryModel->getAllInventories();
        $this->loadView('inventory_list', [
            'inventories' => $inventories,
            'page_title' => 'موجودی انبار'
        ]);
    }
    
    /**
     * نمایش صفحه جزئیات موجودی
     */
    public function view($id) {
        $inventory = $this->inventoryModel->getInventoryById($id);
        $items = $this->inventoryModel->getInventoryItems($id);
        
        if (!$inventory) {
            $this->redirect('index.php');
        }
        
        $this->loadView('inventory_view', [
            'inventory' => $inventory,
            'items' => $items,
            'page_title' => 'جزئیات موجودی'
        ]);
    }
    
    /**
     * نمایش فرم انبارگردانی جدید
     */
    public function newInventory() {
        $this->loadView('new_inventory', [
            'page_title' => 'انبارگردانی جدید',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره انبارگردانی جدید
     */
    public function saveInventory() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'session_name' => $_POST['session_name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'items' => json_decode($_POST['items'] ?? '[]', true)
        ];
        
        $result = $this->inventoryModel->saveInventory($data);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'انبارگردانی با موفقیت ذخیره شد', 'id' => $result['id']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * حذف موجودی
     */
    public function deleteInventory($id) {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $result = $this->inventoryModel->deleteInventory($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'موجودی با موفقیت حذف شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در حذف موجودی'], 400);
        }
    }
    
    /**
     * نمایش صفحه ورود اطلاعات از CSV
     */
    public function importCSV() {
        $this->loadView('import_csv', [
            'page_title' => 'ورود اطلاعات از CSV',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * پردازش فایل CSV
     */
    public function processCSV() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'خطا در آپلود فایل'], 400);
        }
        
        $result = $this->inventoryModel->importFromCSV($_FILES['csv_file']['tmp_name']);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'اطلاعات با موفقیت وارد شدند', 'data' => $result['data']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * خروجی CSV
     */
    public function exportCSV($id) {
        $inventory = $this->inventoryModel->getInventoryById($id);
        $items = $this->inventoryModel->getInventoryItems($id);
        
        if (!$inventory) {
            $this->redirect('index.php');
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_' . $id . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM برای UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // هدر
        fputcsv($output, ['شناسه', 'نام کالا', 'تعداد', 'واحد', 'محل نگهداری', 'توضیحات']);
        
        // داده‌ها
        foreach ($items as $item) {
            fputcsv($output, [
                $item['id'],
                $item['name'],
                $item['quantity'],
                $item['unit'],
                $item['location'],
                $item['notes']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
