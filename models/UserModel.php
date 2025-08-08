<?php
/**
 * مدل کاربر
 * 
 * این کلاس مسئول مدیریت کاربران و احراز هویت است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class UserModel {
    private $db;
    private $table_prefix;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $db;
        $this->db = $db;
        
        // دریافت پیشوند جداول از فایل کانفیگ
        if (defined('DB_PREFIX')) {
            $this->table_prefix = DB_PREFIX;
        } else {
            $this->table_prefix = 'inv_';
        }
    }
    
    /**
     * بررسی احراز هویت کاربر
     * 
     * @param string $username
     * @param string $password
     * @return array|bool
     */
    public function authenticate($username, $password) {
        // پاکسازی ورودی‌ها
        $username = $this->db->real_escape_string($username);
        
        // جستجوی کاربر
        $query = "SELECT * FROM {$this->table_prefix}users WHERE username = '$username' LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // بررسی رمز عبور
            if (password_verify($password, $user['password'])) {
                // بروزرسانی زمان آخرین ورود
                $update_query = "UPDATE {$this->table_prefix}users SET last_login = NOW() WHERE id = {$user['id']}";
                $this->db->query($update_query);
                
                // حذف رمز عبور از آرایه برگشتی
                unset($user['password']);
                
                return $user;
            }
        }
        
        return false;
    }
    
    /**
     * اعتبارسنجی کاربر (Alias برای authenticate)
     * 
     * @param string $username
     * @param string $password
     * @return array|bool
     */
    public function validateUser($username, $password) {
        return $this->authenticate($username, $password);
    }
    
    /**
     * دریافت کاربر بر اساس remember token
     * 
     * @param string $token
     * @return array|bool
     */
    public function getUserByRememberToken($token) {
        return $this->authenticateByToken($token);
    }
    
    /**
     * به‌روزرسانی زمان آخرین ورود
     * 
     * @param int $user_id
     * @return bool
     */
    public function updateLastLogin($user_id) {
        $update_query = "UPDATE {$this->table_prefix}users SET last_login = NOW() WHERE id = $user_id";
        return $this->db->query($update_query);
    }
    
    /**
     * احراز هویت با توکن "مرا به خاطر بسپار"
     * 
     * @param string $token
     * @return array|bool
     */
    public function authenticateByToken($token) {
        if (empty($token)) {
            return false;
        }
        
        // پاکسازی ورودی‌ها
        $token = $this->db->real_escape_string($token);
        
        // جستجوی کاربر
        $query = "SELECT * FROM {$this->table_prefix}users 
                  WHERE remember_token = '$token' 
                  AND remember_expiry > NOW() 
                  LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // بروزرسانی زمان آخرین ورود
            $update_query = "UPDATE {$this->table_prefix}users SET last_login = NOW() WHERE id = {$user['id']}";
            $this->db->query($update_query);
            
            // حذف رمز عبور از آرایه برگشتی
            unset($user['password']);
            
            return $user;
        }
        
        return false;
    }
    
    /**
     * ایجاد توکن "مرا به خاطر بسپار"
     * 
     * @param int $user_id
     * @return string|bool
     */
    public function createRememberToken($user_id) {
        // تولید توکن تصادفی
        $token = bin2hex(random_bytes(32));
        
        // تاریخ انقضا (30 روز)
        $expiry = date('Y-m-d H:i:s', time() + COOKIE_LIFETIME);
        
        // ذخیره توکن در دیتابیس
        $query = "UPDATE {$this->table_prefix}users 
                 SET remember_token = '$token', remember_expiry = '$expiry' 
                 WHERE id = $user_id";
        $result = $this->db->query($query);
        
        return $result ? $token : false;
    }
    
    /**
     * حذف توکن "مرا به خاطر بسپار"
     * 
     * @param int $user_id
     * @return bool
     */
    public function clearRememberToken($user_id) {
        $query = "UPDATE {$this->table_prefix}users 
                 SET remember_token = NULL, remember_expiry = NULL 
                 WHERE id = $user_id";
        return $this->db->query($query);
    }
    
    /**
     * دریافت اطلاعات کاربر با شناسه
     * 
     * @param int $user_id
     * @return array|bool
     */
    public function getUserById($user_id) {
        $user_id = (int)$user_id;
        $query = "SELECT * FROM {$this->table_prefix}users WHERE id = $user_id LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // حذف رمز عبور از آرایه برگشتی
            unset($user['password']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * دریافت لیست کاربران
     * 
     * @return array
     */
    public function getAllUsers() {
        $users = [];
        $query = "SELECT id, username, email, name, role, last_login, created_at 
                 FROM {$this->table_prefix}users ORDER BY id ASC";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * افزودن کاربر جدید
     * 
     * @param array $user_data
     * @return bool|int
     */
    public function addUser($user_data) {
        // بررسی وجود کاربر با نام کاربری یا ایمیل تکراری
        $username = $this->db->real_escape_string($user_data['username']);
        $email = $this->db->real_escape_string($user_data['email']);
        
        $check_query = "SELECT id FROM {$this->table_prefix}users 
                      WHERE username = '$username' OR email = '$email' LIMIT 1";
        $check_result = $this->db->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            return false; // کاربر تکراری
        }
        
        // رمزنگاری رمز عبور
        $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
        
        // درج کاربر جدید
        $query = "INSERT INTO {$this->table_prefix}users 
                 (username, password, email, name, role) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            "sssss", 
            $user_data['username'], 
            $hashed_password, 
            $user_data['email'],
            $user_data['name'],
            $user_data['role']
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            $new_id = $stmt->insert_id;
            $stmt->close();
            return $new_id;
        } else {
            $stmt->close();
            return false;
        }
    }
    
    /**
     * ویرایش کاربر
     * 
     * @param int $user_id
     * @param array $user_data
     * @return bool
     */
    public function updateUser($user_id, $user_data) {
        $user_id = (int)$user_id;
        
        // بررسی وجود کاربر
        $check_query = "SELECT id FROM {$this->table_prefix}users WHERE id = $user_id LIMIT 1";
        $check_result = $this->db->query($check_query);
        
        if (!$check_result || $check_result->num_rows === 0) {
            return false;
        }
        
        // بررسی تکراری بودن نام کاربری یا ایمیل
        if (isset($user_data['username']) || isset($user_data['email'])) {
            $username = isset($user_data['username']) ? 
                $this->db->real_escape_string($user_data['username']) : '';
            $email = isset($user_data['email']) ? 
                $this->db->real_escape_string($user_data['email']) : '';
            
            $check_duplicate = "SELECT id FROM {$this->table_prefix}users 
                              WHERE (username = '$username' OR email = '$email') 
                              AND id != $user_id LIMIT 1";
            $duplicate_result = $this->db->query($check_duplicate);
            
            if ($duplicate_result && $duplicate_result->num_rows > 0) {
                return false; // کاربر تکراری
            }
        }
        
        // ساخت پرس و جوی بروزرسانی
        $update_fields = [];
        
        if (isset($user_data['name'])) {
            $name = $this->db->real_escape_string($user_data['name']);
            $update_fields[] = "name = '$name'";
        }
        
        if (isset($user_data['email'])) {
            $email = $this->db->real_escape_string($user_data['email']);
            $update_fields[] = "email = '$email'";
        }
        
        if (isset($user_data['username'])) {
            $username = $this->db->real_escape_string($user_data['username']);
            $update_fields[] = "username = '$username'";
        }
        
        if (isset($user_data['role'])) {
            $role = $this->db->real_escape_string($user_data['role']);
            $update_fields[] = "role = '$role'";
        }
        
        if (isset($user_data['password']) && !empty($user_data['password'])) {
            $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $update_fields[] = "password = '$hashed_password'";
        }
        
        if (empty($update_fields)) {
            return true; // هیچ فیلدی برای بروزرسانی وجود ندارد
        }
        
        $update_fields[] = "updated_at = NOW()";
        
        $query = "UPDATE {$this->table_prefix}users 
                 SET " . implode(', ', $update_fields) . " 
                 WHERE id = $user_id";
                 
        return $this->db->query($query);
    }
    
    /**
     * حذف کاربر
     * 
     * @param int $user_id
     * @return bool
     */
    public function deleteUser($user_id) {
        $user_id = (int)$user_id;
        
        // بررسی حذف کاربر مدیر
        $check_query = "SELECT role FROM {$this->table_prefix}users WHERE id = $user_id LIMIT 1";
        $check_result = $this->db->query($check_query);
        
        if ($check_result && $check_result->num_rows > 0) {
            $user = $check_result->fetch_assoc();
            
            // بررسی تعداد کاربران مدیر
            if ($user['role'] === 'admin') {
                $admin_count_query = "SELECT COUNT(*) as count FROM {$this->table_prefix}users WHERE role = 'admin'";
                $admin_count_result = $this->db->query($admin_count_query);
                $admin_count = $admin_count_result->fetch_assoc()['count'];
                
                if ($admin_count <= 1) {
                    return false; // نمی‌توان آخرین کاربر مدیر را حذف کرد
                }
            }
        } else {
            return false; // کاربر یافت نشد
        }
        
        $query = "DELETE FROM {$this->table_prefix}users WHERE id = $user_id";
        return $this->db->query($query);
    }
    
    /**
     * بررسی دسترسی کاربر به عملیات
     * 
     * @param string $role نقش کاربر
     * @param string $action عملیات
     * @return bool
     */
    public function hasPermission($role, $action) {
        // تعریف دسترسی‌ها برای هر نقش
        $permissions = [
            'admin' => [
                'view_users', 'add_user', 'edit_user', 'delete_user', 
                'view_inventory', 'add_inventory', 'edit_inventory', 'delete_inventory',
                'view_suppliers', 'add_supplier', 'edit_supplier', 'delete_supplier',
                'view_devices', 'add_device', 'edit_device', 'delete_device',
                'view_production', 'add_production', 'edit_production', 'delete_production',
                'start_production', 'complete_production'
            ],
            'manager' => [
                'view_inventory', 'add_inventory', 'edit_inventory',
                'view_suppliers', 'add_supplier', 'edit_supplier',
                'view_devices', 'add_device', 'edit_device',
                'view_production', 'add_production', 'edit_production', 'delete_production',
                'start_production', 'complete_production'
            ],
            'inventory' => [
                'view_inventory', 'add_inventory', 'edit_inventory',
                'view_suppliers', 'add_supplier',
                'view_devices'
            ],
            'production' => [
                'view_inventory',
                'view_devices',
                'view_production', 
                'start_production', 'complete_production'
            ]
        ];
        
        // بررسی دسترسی
        if (!isset($permissions[$role])) {
            return false;
        }
        
        return in_array($action, $permissions[$role]);
    }
    
    /**
     * تغییر رمز عبور کاربر
     * 
     * @param int $user_id
     * @param string $current_password
     * @param string $new_password
     * @return bool
     */
    public function changePassword($user_id, $current_password, $new_password) {
        $user_id = (int)$user_id;
        
        // دریافت اطلاعات کاربر
        $query = "SELECT password FROM {$this->table_prefix}users WHERE id = $user_id LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // بررسی رمز عبور فعلی
            if (password_verify($current_password, $user['password'])) {
                // رمزنگاری رمز عبور جدید
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // بروزرسانی رمز عبور
                $update_query = "UPDATE {$this->table_prefix}users 
                               SET password = '$hashed_password', updated_at = NOW() 
                               WHERE id = $user_id";
                               
                return $this->db->query($update_query);
            }
        }
        
        return false;
    }
}
