<?php
// این فایل برای قفل‌گذاری روی عملیات‌های زمان‌بر استفاده می‌شود
// تا از همزمانی چندین درخواست جلوگیری شود

/**
 * ایجاد قفل با زمان انقضا
 * @param string $lockName نام قفل
 * @param int $timeout مدت زمان قفل (ثانیه)
 * @return bool
 */
function acquireLock($lockName, $timeout = 30) {
    $lockFile = sys_get_temp_dir() . '/lock_' . md5($lockName) . '.lock';
    
    // بررسی وجود قفل قبلی
    if (file_exists($lockFile)) {
        $lockData = json_decode(file_get_contents($lockFile), true);
        
        // بررسی منقضی شدن قفل قبلی
        if ($lockData && isset($lockData['expires']) && $lockData['expires'] > time()) {
            // قفل هنوز معتبر است
            return false;
        }
        
        // قفل منقضی شده، آن را حذف می‌کنیم
        @unlink($lockFile);
    }
    
    // ایجاد قفل جدید
    $lockData = [
        'created' => time(),
        'expires' => time() + $timeout,
        'name' => $lockName
    ];
    
    file_put_contents($lockFile, json_encode($lockData));
    return true;
}

/**
 * آزادسازی قفل
 * @param string $lockName نام قفل
 * @return void
 */
function releaseLock($lockName) {
    $lockFile = sys_get_temp_dir() . '/lock_' . md5($lockName) . '.lock';
    if (file_exists($lockFile)) {
        @unlink($lockFile);
    }
}
