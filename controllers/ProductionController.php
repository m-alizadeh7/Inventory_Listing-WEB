<?php
/**
 * کنترلر سفارشات تولید
 * 
 * این کلاس مسئول مدیریت سفارشات تولید است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class ProductionController {
    private $db;
    private $table_prefix;
    private $main_controller;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->table_prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
        $this->main_controller = new MainController();
    }
    
    /**
     * صفحه اصلی سفارشات تولید (پیش‌فرض)
     */
    public function index() {
        $this->list();
    }
    
    /**
     * نمایش لیست سفارشات تولید
     */
    public function list_orders() {
        $page_title = 'سفارشات تولید';
        
        // دریافت لیست سفارشات
        $orders = [];
        if ($this->db) {
            try {
                $query = "SELECT po.*, d.name as device_name 
                         FROM {$this->table_prefix}production_orders po 
                         LEFT JOIN {$this->table_prefix}devices d ON po.device_id = d.id 
                         ORDER BY po.created_at DESC";
                $result = $this->db->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $orders[] = $row;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'خطا در دریافت اطلاعات: ' . $e->getMessage();
            }
        }
        
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/production/list.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * متد پیش‌فرض
     */
    public function index() {
        $this->list_orders();
    }
}
