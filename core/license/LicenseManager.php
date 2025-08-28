<?php
/**
 * کلاس مدیریت لایسنس نرم‌افزار
 * این کلاس برای بررسی اعتبار لایسنس و فعال‌سازی سیستم استفاده می‌شود
 */

class LicenseManager {
    private $license_key;
    private $system_info;
    private $api_url;
    private $license_file;
    private $cache_time = 86400; // یک روز به ثانیه
    
    /**
     * مقداردهی اولیه
     */
    public function __construct($api_url = 'https://example.com/api/license') {
        $this->api_url = $api_url;
        $this->license_file = dirname(dirname(dirname(__FILE__))) . '/config/license.json';
        $this->system_info = $this->getSystemInfo();
        $this->loadLicenseData();
    }
    
    /**
     * بارگذاری اطلاعات لایسنس از فایل
     */
    private function loadLicenseData() {
        if (file_exists($this->license_file)) {
            $data = json_decode(file_get_contents($this->license_file), true);
            if ($data && isset($data['license_key'])) {
                $this->license_key = $data['license_key'];
            }
        }
    }
    
    /**
     * ذخیره اطلاعات لایسنس در فایل
     */
    private function saveLicenseData($data) {
        $dir = dirname($this->license_file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->license_file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * فعال‌سازی لایسنس
     * @param string $license_key کلید لایسنس
     * @param string $email ایمیل خریدار
     * @return array نتیجه فعال‌سازی
     */
    public function activateLicense($license_key, $email) {
        try {
            // بررسی اعتبار ورودی‌ها
            if (empty($license_key) || empty($email)) {
                return ['success' => false, 'message' => 'لطفاً کلید لایسنس و ایمیل را وارد کنید.'];
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'ایمیل وارد شده معتبر نیست.'];
            }
            
            // درخواست به سرور لایسنس
            $response = $this->sendRequest('activate', [
                'license_key' => $license_key,
                'email' => $email,
                'system_info' => $this->system_info
            ]);
            
            if ($response['success']) {
                // ذخیره اطلاعات لایسنس
                $license_data = [
                    'license_key' => $license_key,
                    'email' => $email,
                    'activated_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $response['data']['expires_at'],
                    'license_type' => $response['data']['license_type'],
                    'status' => 'active',
                    'last_check' => date('Y-m-d H:i:s')
                ];
                
                $this->saveLicenseData($license_data);
                $this->license_key = $license_key;
            }
            
            return $response;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در فعال‌سازی لایسنس: ' . $e->getMessage()];
        }
    }
    
    /**
     * بررسی اعتبار لایسنس
     * @return bool آیا لایسنس معتبر است
     */
    public function isLicenseValid() {
        if (empty($this->license_key)) {
            return false;
        }
        
        // بررسی از کش
        if (file_exists($this->license_file)) {
            $data = json_decode(file_get_contents($this->license_file), true);
            
            // اگر به تازگی بررسی شده باشد، از کش استفاده می‌کنیم
            if (isset($data['last_check']) && isset($data['status'])) {
                $last_check_time = strtotime($data['last_check']);
                $current_time = time();
                
                if (($current_time - $last_check_time) < $this->cache_time) {
                    return $data['status'] === 'active';
                }
            }
        }
        
        // بررسی آنلاین
        $response = $this->verifyLicense();
        return $response['success'] && isset($response['data']['status']) && $response['data']['status'] === 'active';
    }
    
    /**
     * بررسی آنلاین اعتبار لایسنس
     * @return array نتیجه بررسی
     */
    public function verifyLicense() {
        try {
            if (empty($this->license_key)) {
                return ['success' => false, 'message' => 'لایسنس نصب نشده است.'];
            }
            
            // درخواست به سرور لایسنس
            $response = $this->sendRequest('verify', [
                'license_key' => $this->license_key,
                'system_info' => $this->system_info
            ]);
            
            if ($response['success']) {
                // بروزرسانی اطلاعات لایسنس در فایل
                if (file_exists($this->license_file)) {
                    $data = json_decode(file_get_contents($this->license_file), true);
                    $data['status'] = $response['data']['status'];
                    $data['last_check'] = date('Y-m-d H:i:s');
                    $data['expires_at'] = $response['data']['expires_at'];
                    $this->saveLicenseData($data);
                }
            }
            
            return $response;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در بررسی اعتبار لایسنس: ' . $e->getMessage()];
        }
    }
    
    /**
     * غیرفعال‌سازی لایسنس
     * @return array نتیجه غیرفعال‌سازی
     */
    public function deactivateLicense() {
        try {
            if (empty($this->license_key)) {
                return ['success' => false, 'message' => 'لایسنس نصب نشده است.'];
            }
            
            // درخواست به سرور لایسنس
            $response = $this->sendRequest('deactivate', [
                'license_key' => $this->license_key,
                'system_info' => $this->system_info
            ]);
            
            if ($response['success']) {
                // حذف فایل لایسنس
                if (file_exists($this->license_file)) {
                    unlink($this->license_file);
                }
                
                $this->license_key = null;
            }
            
            return $response;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'خطا در غیرفعال‌سازی لایسنس: ' . $e->getMessage()];
        }
    }
    
    /**
     * دریافت اطلاعات لایسنس
     * @return array اطلاعات لایسنس
     */
    public function getLicenseInfo() {
        if (file_exists($this->license_file)) {
            return json_decode(file_get_contents($this->license_file), true);
        }
        
        return null;
    }
    
    /**
     * ارسال درخواست به سرور لایسنس
     */
    private function sendRequest($action, $data) {
        // در حالت توسعه، شبیه‌سازی پاسخ سرور
        if ($this->api_url === 'https://example.com/api/license') {
            return $this->mockResponse($action, $data);
        }
        
        $url = $this->api_url . '/' . $action;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($status !== 200 || empty($response)) {
            return ['success' => false, 'message' => 'خطا در ارتباط با سرور لایسنس.'];
        }
        
        return json_decode($response, true);
    }
    
    /**
     * شبیه‌سازی پاسخ سرور در محیط توسعه
     */
    private function mockResponse($action, $data) {
        switch ($action) {
            case 'activate':
                if ($data['license_key'] === 'DEMO-LICENSE-KEY') {
                    return [
                        'success' => true,
                        'message' => 'لایسنس با موفقیت فعال شد.',
                        'data' => [
                            'license_key' => $data['license_key'],
                            'email' => $data['email'],
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                            'license_type' => 'professional',
                            'status' => 'active'
                        ]
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'کلید لایسنس نامعتبر است.'
                    ];
                }
                
            case 'verify':
                if ($data['license_key'] === 'DEMO-LICENSE-KEY') {
                    return [
                        'success' => true,
                        'message' => 'لایسنس معتبر است.',
                        'data' => [
                            'license_key' => $data['license_key'],
                            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                            'license_type' => 'professional',
                            'status' => 'active'
                        ]
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'کلید لایسنس نامعتبر است.'
                    ];
                }
                
            case 'deactivate':
                return [
                    'success' => true,
                    'message' => 'لایسنس با موفقیت غیرفعال شد.'
                ];
                
            default:
                return [
                    'success' => false,
                    'message' => 'عملیات نامعتبر.'
                ];
        }
    }
    
    /**
     * دریافت اطلاعات سیستم
     */
    private function getSystemInfo() {
        return [
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'ip' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'directory' => dirname(dirname(dirname(__FILE__))),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
    }
}
