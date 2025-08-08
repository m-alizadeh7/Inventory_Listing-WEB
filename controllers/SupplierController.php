<?php
/**
 * کنترلر تأمین‌کنندگان
 * 
 * این کلاس مسئول مدیریت تأمین‌کنندگان است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class SupplierController {
    private $db;
    private $table_prefix;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->table_prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
    }
    
    /**
     * نمایش لیست تأمین‌کنندگان
     */
    public function list() {
        $page_title = 'مدیریت تأمین‌کنندگان';
        
        // دریافت لیست تأمین‌کنندگان
        $suppliers = [];
        if ($this->db) {
            try {
                $query = "SELECT * FROM {$this->table_prefix}suppliers ORDER BY name";
                $result = $this->db->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $suppliers[] = $row;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'خطا در دریافت اطلاعات: ' . $e->getMessage();
            }
        }
        
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/suppliers/list.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * متد پیش‌فرض
     */
    public function index() {
        $this->list();
    }
}
