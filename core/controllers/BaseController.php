<?php
/**
 * کنترلر پایه
 *
 * کلاس پایه برای همه کنترلرها که توابع مشترک را فراهم می‌کند
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class BaseController {
    protected $db;
    protected $config;
    
    /**
     * سازنده کنترلر پایه
     */
    public function __construct() {
        global $conn, $config;
        $this->db = $conn;
        $this->config = $config;
    }
    
    /**
     * بارگذاری نما
     *
     * @param string $template نام قالب
     * @param array $data داده‌های مورد نیاز برای نما
     * @return void
     */
    protected function loadView($template, $data = []) {
        // استخراج داده‌ها برای دسترسی آسان‌تر در قالب
        extract($data);
        
        // بارگذاری هدر
        include(TEMPLATES_PATH . '/' . $this->config['default_theme'] . '/header.php');
        
        // بارگذاری قالب درخواستی
        include(TEMPLATES_PATH . '/' . $this->config['default_theme'] . '/' . $template . '.php');
        
        // بارگذاری فوتر
        include(TEMPLATES_PATH . '/' . $this->config['default_theme'] . '/footer.php');
    }
    
    /**
     * تغییر مسیر به URL مشخص شده
     *
     * @param string $url آدرس مقصد
     * @return void
     */
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * پاسخ JSON
     *
     * @param array $data داده‌های پاسخ
     * @param int $status_code کد وضعیت HTTP
     * @return void
     */
    protected function jsonResponse($data, $status_code = 200) {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * بررسی اعتبار درخواست CSRF
     *
     * @return bool
     */
    protected function validateCsrfToken() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }
    
    /**
     * ایجاد توکن CSRF
     *
     * @return string
     */
    protected function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}
