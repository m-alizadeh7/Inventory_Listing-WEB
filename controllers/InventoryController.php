<?php
/**
 * کنترلر موجودی انبار
 * 
 * این کلاس مسئول مدیریت موجودی انبار است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class InventoryController {
    private $db;
    private $table_prefix;
    private $current_user;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->table_prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
        $this->current_user = isset($_SESSION['user_data']) ? $_SESSION['user_data'] : false;
    }
    
    /**
     * بررسی احراز هویت
     */
    private function isAuthenticated() {
        return $this->current_user !== false;
    }
    
    /**
     * صفحه اصلی موجودی (پیش‌فرض)
     */
    public function index() {
        $this->list();
    }
    
    /**
     * نمایش لیست موجودی انبار
     */
    public function list() {
        // بررسی احراز هویت
        if (!$this->isAuthenticated()) {
            header('Location: index.php?controller=main&action=login');
            exit;
        }
        
        $page_title = 'موجودی انبار';
        
        // دریافت لیست موجودی
        $inventory = [];
        if ($this->db) {
            try {
                $query = "SELECT i.*, s.name as supplier_name 
                         FROM {$this->table_prefix}inventory i 
                         LEFT JOIN {$this->table_prefix}suppliers s ON i.supplier_id = s.id 
                         ORDER BY i.part_number";
                $result = $this->db->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $inventory[] = $row;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'خطا در دریافت اطلاعات: ' . $e->getMessage();
            }
        }
        
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/inventory/list.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
}
