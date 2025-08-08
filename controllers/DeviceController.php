<?php
/**
 * کنترلر دستگاه‌ها
 * 
 * این کلاس مسئول مدیریت دستگاه‌ها است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class DeviceController {
    private $db;
    private $table_prefix;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->table_prefix = defined('DB_PREFIX') ? DB_PREFIX : 'inv_';
    }
    
    /**
     * صفحه اصلی دستگاه‌ها (پیش‌فرض)
     */
    public function index() {
        $this->list();
    }
    
    /**
     * نمایش لیست دستگاه‌ها
     */
    public function list() {
        $page_title = 'مدیریت دستگاه‌ها';
        
        // دریافت لیست دستگاه‌ها
        $devices = [];
        if ($this->db) {
            try {
                $query = "SELECT * FROM {$this->table_prefix}devices ORDER BY name";
                $result = $this->db->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $devices[] = $row;
                    }
                }
            } catch (Exception $e) {
                $_SESSION['error'] = 'خطا در دریافت اطلاعات: ' . $e->getMessage();
            }
        }
        
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/devices/list.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * متد پیش‌فرض
     */
    public function index() {
        $this->list();
    }
}
