<?php
/**
 * سیستم به‌روزرسانی خودکار
 * این فایل کلاس‌های مورد نیاز برای بررسی و دریافت به‌روزرسانی‌های نرم‌افزار را فراهم می‌کند
 */

class UpdaterSystem {
    private $current_version;
    private $api_url;
    private $system_path;
    private $update_data = null;
    private $temp_dir;
    
    /**
     * مقداردهی اولیه سیستم به‌روزرسانی
     */
    public function __construct($current_version, $api_url = 'https://example.com/api/updates') {
        $this->current_version = $current_version;
        $this->api_url = $api_url;
        $this->system_path = dirname(dirname(dirname(__FILE__)));
        $this->temp_dir = $this->system_path . '/temp_updates';
    }
    
    /**
     * بررسی وجود نسخه جدید
     * @return bool آیا نسخه جدیدی موجود است
     */
    public function checkForUpdates() {
        try {
            // اتصال به سرور برای دریافت اطلاعات آخرین نسخه
            $ch = curl_init($this->api_url . '/check');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'current_version' => $this->current_version,
                'system_info' => $this->getSystemInfo()
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($status === 200 && !empty($response)) {
                $this->update_data = json_decode($response, true);
                return version_compare($this->update_data['version'], $this->current_version, '>');
            }
            
            return false;
        } catch (Exception $e) {
            $this->logError('Error checking for updates: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت اطلاعات نسخه جدید
     * @return array|null اطلاعات نسخه جدید یا null در صورت عدم وجود
     */
    public function getUpdateInfo() {
        if (!$this->update_data) {
            $this->checkForUpdates();
        }
        
        return $this->update_data;
    }
    
    /**
     * دانلود و نصب به‌روزرسانی
     * @return bool آیا به‌روزرسانی با موفقیت انجام شد
     */
    public function downloadAndInstallUpdate() {
        if (!$this->update_data) {
            if (!$this->checkForUpdates()) {
                return false; // نسخه جدیدی موجود نیست
            }
        }
        
        try {
            // ایجاد پوشه موقت
            if (!is_dir($this->temp_dir)) {
                mkdir($this->temp_dir, 0755, true);
            }
            
            $update_file = $this->temp_dir . '/update.zip';
            
            // دانلود فایل به‌روزرسانی
            $downloaded = $this->downloadFile($this->update_data['download_url'], $update_file);
            if (!$downloaded) {
                $this->logError('Failed to download update file');
                return false;
            }
            
            // بررسی hash فایل برای اطمینان از صحت دانلود
            if (!$this->verifyFileHash($update_file, $this->update_data['file_hash'])) {
                $this->logError('Update file hash verification failed');
                unlink($update_file);
                return false;
            }
            
            // ایجاد نسخه پشتیبان
            if (!$this->createBackup()) {
                $this->logError('Failed to create backup before update');
                unlink($update_file);
                return false;
            }
            
            // نصب به‌روزرسانی
            $installed = $this->installUpdate($update_file);
            unlink($update_file); // حذف فایل موقت
            
            if (!$installed) {
                $this->logError('Failed to install update');
                $this->restoreFromBackup();
                return false;
            }
            
            // بروزرسانی فایل نسخه
            $this->updateVersionFile($this->update_data['version']);
            
            // حذف پوشه موقت
            $this->removeDir($this->temp_dir);
            
            return true;
        } catch (Exception $e) {
            $this->logError('Error during update: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دانلود فایل از URL
     */
    private function downloadFile($url, $destination) {
        $file = fopen($destination, 'w+');
        if (!$file) {
            return false;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 دقیقه تایم‌اوت
        
        $result = curl_exec($ch);
        curl_close($ch);
        fclose($file);
        
        return $result !== false;
    }
    
    /**
     * بررسی hash فایل دانلود شده
     */
    private function verifyFileHash($file_path, $expected_hash) {
        $hash = hash_file('sha256', $file_path);
        return $hash === $expected_hash;
    }
    
    /**
     * ایجاد نسخه پشتیبان قبل از به‌روزرسانی
     */
    private function createBackup() {
        $backup_dir = $this->system_path . '/backups';
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
        
        // فشرده‌سازی فایل‌های اصلی
        $zip = new ZipArchive();
        if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        $this->addFolderToZip($zip, $this->system_path, ['backups', 'temp_updates', 'uploads']);
        $zip->close();
        
        return file_exists($backup_file);
    }
    
    /**
     * بازگرداندن از نسخه پشتیبان در صورت خطا
     */
    private function restoreFromBackup() {
        $backup_dir = $this->system_path . '/backups';
        $backups = glob($backup_dir . '/backup_*.zip');
        
        if (empty($backups)) {
            return false;
        }
        
        // آخرین نسخه پشتیبان
        $latest_backup = end($backups);
        
        // استخراج نسخه پشتیبان
        $zip = new ZipArchive();
        if ($zip->open($latest_backup) !== TRUE) {
            return false;
        }
        
        $zip->extractTo($this->system_path);
        $zip->close();
        
        return true;
    }
    
    /**
     * نصب به‌روزرسانی جدید
     */
    private function installUpdate($update_file) {
        $extract_dir = $this->temp_dir . '/extract';
        if (!is_dir($extract_dir)) {
            mkdir($extract_dir, 0755, true);
        }
        
        // استخراج فایل به‌روزرسانی
        $zip = new ZipArchive();
        if ($zip->open($update_file) !== TRUE) {
            return false;
        }
        
        $zip->extractTo($extract_dir);
        $zip->close();
        
        // کپی فایل‌های جدید
        $this->copyDirectory($extract_dir, $this->system_path);
        
        // اجرای اسکریپت پس از به‌روزرسانی (در صورت وجود)
        if (file_exists($extract_dir . '/post_update.php')) {
            include $extract_dir . '/post_update.php';
        }
        
        // حذف پوشه استخراج
        $this->removeDir($extract_dir);
        
        return true;
    }
    
    /**
     * بروزرسانی فایل نسخه
     */
    private function updateVersionFile($new_version) {
        $version_file = $this->system_path . '/version.php';
        $content = "<?php\n// Auto-generated version file\ndefine('SYSTEM_VERSION', '{$new_version}');\n";
        file_put_contents($version_file, $content);
    }
    
    /**
     * دریافت اطلاعات سیستم برای ارسال به سرور به‌روزرسانی
     */
    private function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'extensions' => get_loaded_extensions()
        ];
    }
    
    /**
     * افزودن پوشه به فایل ZIP
     */
    private function addFolderToZip($zip, $folder, $exclude = []) {
        $folder = rtrim($folder, '/\\') . '/';
        $base_folder = basename($folder);
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $name => $file) {
            if ($file->isDir()) {
                continue;
            }
            
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($this->system_path) + 1);
            
            // بررسی فایل‌های مستثنی
            $exclude_file = false;
            foreach ($exclude as $exc) {
                if (strpos($relativePath, $exc) === 0) {
                    $exclude_file = true;
                    break;
                }
            }
            
            if (!$exclude_file) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * کپی محتویات یک پوشه به پوشه دیگر
     */
    private function copyDirectory($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                if (is_dir($src_file)) {
                    $this->copyDirectory($src_file, $dst_file);
                } else {
                    copy($src_file, $dst_file);
                }
            }
        }
        
        closedir($dir);
    }
    
    /**
     * حذف یک پوشه و تمام محتویات آن
     */
    private function removeDir($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                $path = $dir . '/' . $object;
                
                if (is_dir($path)) {
                    $this->removeDir($path);
                } else {
                    unlink($path);
                }
            }
        }
        
        rmdir($dir);
    }
    
    /**
     * ثبت خطا در لاگ
     */
    private function logError($message) {
        $log_file = $this->system_path . '/logs/updater.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_message = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}
