<?php
/**
 * کنترلر سفارشات تولید
 *
 * کنترلر مسئول مدیریت عملیات سفارشات تولید
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');
require_once(CORE_PATH . '/models/production_order.php');
require_once(CORE_PATH . '/models/device.php');

class ProductionOrderController extends BaseController {
    private $productionOrderModel;
    private $deviceModel;
    
    /**
     * سازنده کنترلر سفارش تولید
     */
    public function __construct() {
        parent::__construct();
        $this->productionOrderModel = new ProductionOrderModel();
        $this->deviceModel = new DeviceModel();
    }
    
    /**
     * نمایش صفحه لیست سفارشات تولید
     */
    public function index() {
        $orders = $this->productionOrderModel->getAllOrders();
        $this->loadView('production_order_list', [
            'orders' => $orders,
            'page_title' => 'سفارشات تولید'
        ]);
    }
    
    /**
     * نمایش صفحه جزئیات سفارش تولید
     */
    public function view($id) {
        $order = $this->productionOrderModel->getOrderById($id);
        
        if (!$order) {
            $this->redirect('production_orders.php');
        }
        
        $device = $this->deviceModel->getDeviceById($order['device_id']);
        $parts = $this->productionOrderModel->getOrderParts($id);
        
        $this->loadView('production_order_view', [
            'order' => $order,
            'device' => $device,
            'parts' => $parts,
            'page_title' => 'جزئیات سفارش تولید'
        ]);
    }
    
    /**
     * نمایش فرم سفارش تولید جدید
     */
    public function newOrder() {
        $devices = $this->deviceModel->getAllDevices();
        
        $this->loadView('new_production_order', [
            'devices' => $devices,
            'page_title' => 'سفارش تولید جدید',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره سفارش تولید جدید
     */
    public function saveOrder() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'device_id' => $_POST['device_id'] ?? 0,
            'quantity' => $_POST['quantity'] ?? 0,
            'deadline' => $_POST['deadline'] ?? '',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        $result = $this->productionOrderModel->saveOrder($data);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'سفارش تولید با موفقیت ثبت شد', 'id' => $result['id']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * تایید سفارش تولید
     */
    public function confirmOrder($id) {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $result = $this->productionOrderModel->confirmOrder($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'سفارش تولید با موفقیت تایید شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در تایید سفارش تولید'], 400);
        }
    }
    
    /**
     * شروع تولید
     */
    public function startProduction($id) {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $result = $this->productionOrderModel->startProduction($id);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'تولید با موفقیت شروع شد']);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * حذف سفارش تولید
     */
    public function deleteOrder($id) {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $result = $this->productionOrderModel->deleteOrder($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'سفارش تولید با موفقیت حذف شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در حذف سفارش تولید'], 400);
        }
    }
}
