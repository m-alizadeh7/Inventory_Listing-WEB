<?php
/**
 * کنترلر دستگاه‌ها
 *
 * کنترلر مسئول مدیریت عملیات دستگاه‌ها و BOM
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');
require_once(CORE_PATH . '/models/device.php');

class DeviceController extends BaseController {
    private $deviceModel;
    
    /**
     * سازنده کنترلر دستگاه
     */
    public function __construct() {
        parent::__construct();
        $this->deviceModel = new DeviceModel();
    }
    
    /**
     * نمایش صفحه لیست دستگاه‌ها
     */
    public function index() {
        $devices = $this->deviceModel->getAllDevices();
        $this->loadView('device_list', [
            'devices' => $devices,
            'page_title' => 'لیست دستگاه‌ها'
        ]);
    }
    
    /**
     * نمایش صفحه جزئیات دستگاه و BOM
     */
    public function view($id) {
        $device = $this->deviceModel->getDeviceById($id);
        $bom = $this->deviceModel->getDeviceBOM($id);
        
        if (!$device) {
            $this->redirect('devices.php');
        }
        
        $this->loadView('device_view', [
            'device' => $device,
            'bom' => $bom,
            'page_title' => 'جزئیات دستگاه'
        ]);
    }
    
    /**
     * نمایش فرم دستگاه جدید
     */
    public function newDevice() {
        $this->loadView('new_device', [
            'page_title' => 'دستگاه جدید',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره دستگاه جدید
     */
    public function saveDevice() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'model' => $_POST['model'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        $result = $this->deviceModel->saveDevice($data);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'دستگاه با موفقیت ذخیره شد', 'id' => $result['id']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * نمایش فرم ویرایش دستگاه
     */
    public function editDevice($id) {
        $device = $this->deviceModel->getDeviceById($id);
        
        if (!$device) {
            $this->redirect('devices.php');
        }
        
        $this->loadView('edit_device', [
            'device' => $device,
            'page_title' => 'ویرایش دستگاه',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * به‌روزرسانی دستگاه
     */
    public function updateDevice() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $data = [
            'id' => $_POST['id'] ?? 0,
            'name' => $_POST['name'] ?? '',
            'model' => $_POST['model'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        $result = $this->deviceModel->updateDevice($data);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'دستگاه با موفقیت به‌روزرسانی شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در به‌روزرسانی دستگاه'], 400);
        }
    }
    
    /**
     * حذف دستگاه
     */
    public function deleteDevice($id) {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $result = $this->deviceModel->deleteDevice($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'دستگاه با موفقیت حذف شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در حذف دستگاه'], 400);
        }
    }
    
    /**
     * نمایش فرم مدیریت BOM
     */
    public function manageBOM($device_id) {
        $device = $this->deviceModel->getDeviceById($device_id);
        $bom = $this->deviceModel->getDeviceBOM($device_id);
        
        if (!$device) {
            $this->redirect('devices.php');
        }
        
        $this->loadView('manage_bom', [
            'device' => $device,
            'bom' => $bom,
            'page_title' => 'مدیریت BOM',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * ذخیره BOM
     */
    public function saveBOM() {
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        $device_id = $_POST['device_id'] ?? 0;
        $parts = json_decode($_POST['parts'] ?? '[]', true);
        
        $result = $this->deviceModel->saveBOM($device_id, $parts);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'BOM با موفقیت ذخیره شد']);
        } else {
            $this->jsonResponse(['error' => 'خطا در ذخیره BOM'], 400);
        }
    }
}
