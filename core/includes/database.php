<?php
/**
 * اتصال به پایگاه داده
 * 
 * این فایل مدیریت اتصال به پایگاه داده را انجام می‌دهد
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

/**
 * کلاس اتصال به پایگاه داده
 */
class Database {
    private static $instance = null;
    private $conn;
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        global $db_config;
        
        $this->conn = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );
        
        if ($this->conn->connect_error) {
            die("خطا در اتصال به پایگاه داده: " . $this->conn->connect_error);
        }
        
        // تنظیم کدبندی اتصال
        $this->conn->set_charset($db_config['charset']);
        
        // تنظیم collation
        $this->conn->query("SET NAMES {$db_config['charset']} COLLATE {$db_config['collate']}");
    }
    
    /**
     * دریافت نمونه کلاس
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * دریافت اتصال
     * 
     * @return mysqli
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * اجرای کوئری
     * 
     * @param string $query کوئری
     * @return mysqli_result|bool
     */
    public function query($query) {
        return $this->conn->query($query);
    }
    
    /**
     * آماده‌سازی کوئری
     * 
     * @param string $query کوئری
     * @return mysqli_stmt
     */
    public function prepare($query) {
        return $this->conn->prepare($query);
    }
    
    /**
     * دریافت آخرین شناسه درج شده
     * 
     * @return int
     */
    public function getLastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * دریافت پیام خطا
     * 
     * @return string
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * دریافت کد خطا
     * 
     * @return int
     */
    public function getErrorCode() {
        return $this->conn->errno;
    }
    
    /**
     * فرار از کاراکترهای خاص
     * 
     * @param string $string رشته
     * @return string
     */
    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * بستن اتصال
     */
    public function close() {
        $this->conn->close();
    }
}

// ایجاد اتصال به پایگاه داده
if (isset($db_config) && !empty($db_config['database'])) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} else {
    $conn = null;
}
