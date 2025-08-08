<?php
/**
 * کنترلر کاربران
 *
 * کنترلر مسئول مدیریت عملیات کاربران
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

require_once(CORE_PATH . '/controllers/BaseController.php');
require_once(CORE_PATH . '/models/user.php');

class UserController extends BaseController {
    private $userModel;
    
    /**
     * سازنده کنترلر کاربران
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
    }
    
    /**
     * نمایش لیست کاربران
     */
    public function index() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->redirect('index.php?controller=main&action=access_denied');
        }
        
        $users = $this->userModel->getAllUsers();
        
        $this->loadView('user_list', [
            'users' => $users,
            'page_title' => 'مدیریت کاربران',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * نمایش صفحه افزودن کاربر
     */
    public function add() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->redirect('index.php?controller=main&action=access_denied');
        }
        
        $this->loadView('user_add', [
            'page_title' => 'افزودن کاربر جدید',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * افزودن کاربر جدید
     */
    public function save() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی توکن CSRF
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        // دریافت و بررسی داده‌ها
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        
        if (empty($username) || empty($password)) {
            $this->jsonResponse(['error' => 'نام کاربری و رمز عبور الزامی هستند'], 400);
        }
        
        // افزودن کاربر
        $result = $this->userModel->addUser([
            'username' => $username,
            'password' => $password,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role,
            'status' => $status
        ]);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => $result['message'], 'id' => $result['id']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * نمایش صفحه ویرایش کاربر
     */
    public function edit($id) {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->redirect('index.php?controller=main&action=access_denied');
        }
        
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            $this->redirect('index.php?controller=user&action=index');
        }
        
        $this->loadView('user_edit', [
            'user' => $user,
            'page_title' => 'ویرایش کاربر',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * به‌روزرسانی کاربر
     */
    public function update() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی توکن CSRF
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        // دریافت و بررسی داده‌ها
        $id = $_POST['id'] ?? 0;
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        
        if (empty($id)) {
            $this->jsonResponse(['error' => 'شناسه کاربر الزامی است'], 400);
        }
        
        // به‌روزرسانی کاربر
        $data = [
            'id' => $id,
            'fullname' => $fullname,
            'email' => $email,
            'role' => $role,
            'status' => $status
        ];
        
        // اگر رمز عبور وارد شده باشد
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        $result = $this->userModel->updateUser($data);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * حذف کاربر
     */
    public function delete($id) {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی دسترسی کاربر
        if (!$this->hasPermission('manage_users')) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی توکن CSRF
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        // حذف کاربر
        $result = $this->userModel->deleteUser($id);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => $result['message']]);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
    
    /**
     * نمایش صفحه پروفایل کاربر
     */
    public function profile() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->redirect('index.php?controller=main&action=login');
        }
        
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        if (!$user) {
            $this->redirect('index.php');
        }
        
        $this->loadView('user_profile', [
            'user' => $user,
            'page_title' => 'پروفایل کاربری',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    /**
     * به‌روزرسانی پروفایل کاربر
     */
    public function updateProfile() {
        // بررسی احراز هویت کاربر
        if (!$this->isUserLoggedIn()) {
            $this->jsonResponse(['error' => 'شما اجازه دسترسی به این بخش را ندارید'], 403);
        }
        
        // بررسی توکن CSRF
        if (!$this->validateCsrfToken()) {
            $this->jsonResponse(['error' => 'توکن CSRF نامعتبر است'], 403);
        }
        
        // دریافت و بررسی داده‌ها
        $user_id = $_SESSION['user_id'];
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        // به‌روزرسانی اطلاعات پایه
        $data = [
            'id' => $user_id,
            'fullname' => $fullname,
            'email' => $email
        ];
        
        // اگر رمز عبور هم تغییر می‌کند
        if (!empty($current_password) && !empty($new_password)) {
            // بررسی رمز عبور فعلی
            $user = $this->userModel->getUserById($user_id);
            
            if (!password_verify($current_password, $user['password'])) {
                $this->jsonResponse(['error' => 'رمز عبور فعلی اشتباه است'], 400);
            }
            
            $data['password'] = $new_password;
        }
        
        $result = $this->userModel->updateUser($data);
        
        if ($result['status'] === 'success') {
            $this->jsonResponse(['success' => true, 'message' => 'پروفایل با موفقیت به‌روزرسانی شد']);
        } else {
            $this->jsonResponse(['error' => $result['message']], 400);
        }
    }
}
