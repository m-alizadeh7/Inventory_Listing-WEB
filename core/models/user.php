<?php
/**
 * مدل کاربران
 *
 * مدل مسئول مدیریت عملیات مربوط به کاربران سیستم
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class UserModel {
    private $db;
    
    /**
     * سازنده مدل کاربران
     */
    public function __construct() {
        global $conn;
        $this->db = $conn;
    }
    
    /**
     * دریافت لیست کاربران
     */
    public function getAllUsers() {
        $query = "SELECT id, username, fullname, email, role, status, created_at, last_login FROM users ORDER BY id";
        $result = $this->db->query($query);
        
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        
        return $users;
    }
    
    /**
     * دریافت اطلاعات یک کاربر با شناسه
     */
    public function getUserById($id) {
        $id = (int)$id;
        
        $query = "SELECT id, username, fullname, email, role, status, created_at, last_login FROM users WHERE id = $id";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * دریافت اطلاعات یک کاربر با نام کاربری
     */
    public function getUserByUsername($username) {
        $username = $this->db->real_escape_string($username);
        
        $query = "SELECT id, username, fullname, email, role, status, created_at, last_login FROM users WHERE username = '$username'";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * افزودن کاربر جدید
     */
    public function addUser($data) {
        // بررسی وجود کاربر با نام کاربری تکراری
        if ($this->getUserByUsername($data['username'])) {
            return [
                'status' => 'error',
                'message' => 'نام کاربری قبلاً ثبت شده است'
            ];
        }
        
        // آماده‌سازی داده‌ها
        $username = $this->db->real_escape_string($data['username']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $fullname = $this->db->real_escape_string($data['fullname'] ?? '');
        $email = $this->db->real_escape_string($data['email'] ?? '');
        $role = $this->db->real_escape_string($data['role'] ?? 'user');
        $status = $this->db->real_escape_string($data['status'] ?? 'active');
        $created_at = time();
        
        // افزودن کاربر
        $query = "INSERT INTO users (username, password, fullname, email, role, status, created_at)
                  VALUES ('$username', '$password', '$fullname', '$email', '$role', '$status', $created_at)";
        
        if ($this->db->query($query)) {
            return [
                'status' => 'success',
                'message' => 'کاربر با موفقیت اضافه شد',
                'id' => $this->db->insert_id
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'خطا در افزودن کاربر: ' . $this->db->error
            ];
        }
    }
    
    /**
     * به‌روزرسانی کاربر
     */
    public function updateUser($data) {
        $id = (int)$data['id'];
        
        // بررسی وجود کاربر
        if (!$this->getUserById($id)) {
            return [
                'status' => 'error',
                'message' => 'کاربر مورد نظر یافت نشد'
            ];
        }
        
        // آماده‌سازی داده‌ها
        $fullname = $this->db->real_escape_string($data['fullname'] ?? '');
        $email = $this->db->real_escape_string($data['email'] ?? '');
        $role = $this->db->real_escape_string($data['role'] ?? 'user');
        $status = $this->db->real_escape_string($data['status'] ?? 'active');
        
        // ساخت کوئری به‌روزرسانی
        $query = "UPDATE users SET
                  fullname = '$fullname',
                  email = '$email',
                  role = '$role',
                  status = '$status'
                  WHERE id = $id";
        
        // اگر رمز عبور هم به‌روزرسانی می‌شود
        if (!empty($data['password'])) {
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET
                      fullname = '$fullname',
                      email = '$email',
                      role = '$role',
                      status = '$status',
                      password = '$password'
                      WHERE id = $id";
        }
        
        if ($this->db->query($query)) {
            return [
                'status' => 'success',
                'message' => 'کاربر با موفقیت به‌روزرسانی شد'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'خطا در به‌روزرسانی کاربر: ' . $this->db->error
            ];
        }
    }
    
    /**
     * حذف کاربر
     */
    public function deleteUser($id) {
        $id = (int)$id;
        
        // بررسی وجود کاربر
        if (!$this->getUserById($id)) {
            return [
                'status' => 'error',
                'message' => 'کاربر مورد نظر یافت نشد'
            ];
        }
        
        // حذف توکن‌های کاربر
        $query1 = "DELETE FROM user_tokens WHERE user_id = $id";
        $this->db->query($query1);
        
        // حذف کاربر
        $query2 = "DELETE FROM users WHERE id = $id";
        
        if ($this->db->query($query2)) {
            return [
                'status' => 'success',
                'message' => 'کاربر با موفقیت حذف شد'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'خطا در حذف کاربر: ' . $this->db->error
            ];
        }
    }
    
    /**
     * به‌روزرسانی زمان آخرین ورود کاربر
     */
    public function updateLastLogin($id) {
        $id = (int)$id;
        $last_login = time();
        
        $query = "UPDATE users SET last_login = $last_login WHERE id = $id";
        $this->db->query($query);
    }
    
    /**
     * تعداد کل کاربران
     */
    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as count FROM users";
        $result = $this->db->query($query);
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * ذخیره توکن "مرا به خاطر بسپار"
     */
    public function saveRememberToken($user_id, $token, $expire) {
        $user_id = (int)$user_id;
        $token = $this->db->real_escape_string($token);
        $expire = (int)$expire;
        
        // حذف توکن‌های قبلی
        $this->removeRememberTokens($user_id);
        
        // ذخیره توکن جدید
        $query = "INSERT INTO user_tokens (user_id, token, expire) VALUES ($user_id, '$token', $expire)";
        return $this->db->query($query);
    }
    
    /**
     * حذف توکن‌های "مرا به خاطر بسپار"
     */
    public function removeRememberTokens($user_id) {
        $user_id = (int)$user_id;
        
        $query = "DELETE FROM user_tokens WHERE user_id = $user_id";
        return $this->db->query($query);
    }
    
    /**
     * دریافت کاربر با توکن "مرا به خاطر بسپار"
     */
    public function getUserByRememberToken($token) {
        $token = $this->db->real_escape_string($token);
        $now = time();
        
        $query = "SELECT u.* FROM users u
                  JOIN user_tokens t ON u.id = t.user_id
                  WHERE t.token = '$token' AND t.expire > $now AND u.status = 'active'";
        $result = $this->db->query($query);
        
        if (!$result || $result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    }
}
