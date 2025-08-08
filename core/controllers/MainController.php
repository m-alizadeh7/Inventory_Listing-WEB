<?php
/**
 * کنترلر اصلی سیستم
 *
 * کنترلر اصلی برای مدیریت صفحات عمومی سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');

class MainController extends BaseController {
    /**
     * سازنده کنترلر اصلی
     */
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * صفحه اصلی
     */
    public function index() {
        // اطلاعات آماری
        $stats = [
            'total_inventory' => $this->getInventoryCount(),
            'low_stock' => $this->getLowStockCount(),
            'pending_orders' => $this->getPendingOrdersCount(),
            'total_devices' => $this->getDevicesCount()
        ];
        
        $this->loadView('dashboard', [
            'stats' => $stats,
            'page_title' => 'صفحه اصلی - سیستم مدیریت انبار'
        ]);
    }
    
    /**
     * صفحه تنظیمات
     */
    public function settings() {
        $business_info = getBusinessInfo();
        
        $this->loadView('settings', [
            'business_info' => $business_info,
            'page_title' => 'تنظیمات سیستم',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره تنظیمات
     */
    public function saveSettings() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'business_name' => $_POST['business_name'] ?? '',
            'business_phone' => $_POST['business_phone'] ?? '',
            'business_email' => $_POST['business_email'] ?? '',
            'business_address' => $_POST['business_address'] ?? ''
        ];
        
        $result = saveBusinessInfo($data);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'تنظیمات با موفقیت ذخیره شدند']);
        } else {
            $this->jsonResponse(['error' => 'خطا در ذخیره تنظیمات'], 400);
        }
    }
    
    /**
     * تعداد کل اقلام انبار
     */
    private function getInventoryCount() {
        $sql = "SELECT COUNT(*) as count FROM inventory_records";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * تعداد اقلام با موجودی کم
     */
    private function getLowStockCount() {
        $sql = "SELECT COUNT(*) as count FROM inventory_records WHERE quantity <= min_quantity";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * تعداد سفارشات در انتظار
     */
    private function getPendingOrdersCount() {
        $sql = "SELECT COUNT(*) as count FROM production_orders WHERE status IN ('pending', 'confirmed')";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * تعداد کل دستگاه‌ها
     */
    private function getDevicesCount() {
        $sql = "SELECT COUNT(*) as count FROM devices";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
