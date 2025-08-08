<?php
/**
 * کنترلر کاربران
 * 
 * این کلاس مسئول مدیریت کاربران سیستم است
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class UserController {
    private $db;
    private $user_model;
    private $main_controller;
    private $current_user;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->user_model = new UserModel();
        $this->main_controller = new MainController();
        
        // بررسی نصب سیستم
        if (!$this->main_controller->checkInstallation()) {
            return;
        }
    }
    
    /**
     * صفحه اصلی کاربران (پیش‌فرض)
     */
    public function index() {
        $this->listUsers();
    }
    
    /**
     * نمایش لیست کاربران
     */
    public function listUsers() {
        // بررسی احراز هویت
        if (!$this->main_controller->checkAuth()) {
            header('Location: index.php?controller=user&action=login');
            exit;
        }
        
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('view_users')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php');
            exit;
        }
        
        $page_title = 'مدیریت کاربران';
        $users = $this->user_model->getAllUsers();
        
        // نمایش صفحه لیست کاربران
        include ROOT_PATH . '/templates/default/header.php';
        include ROOT_PATH . '/templates/default/user/list.php';
        include ROOT_PATH . '/templates/default/footer.php';
    }
    
    /**
     * نمایش فرم افزودن کاربر
     */
    public function showAddUser() {
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('add_user')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        $page_title = 'افزودن کاربر جدید';
        $csrf_token = $_SESSION['csrf_token'];
        
        // نمایش فرم افزودن کاربر
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/users/add.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * پردازش فرم افزودن کاربر
     */
    public function addUser() {
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('add_user')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // بررسی CSRF توکن
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'خطای امنیتی رخ داده است. لطفاً دوباره تلاش کنید.';
            header('Location: index.php?controller=user&action=show_add_user');
            exit;
        }
        
        // دریافت اطلاعات فرم
        $user_data = [
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'role' => isset($_POST['role']) ? $_POST['role'] : 'inventory'
        ];
        
        // بررسی خالی نبودن فیلدها
        if (empty($user_data['username']) || empty($user_data['password']) || 
            empty($user_data['email']) || empty($user_data['name'])) {
            $_SESSION['error'] = 'لطفاً تمام فیلدها را پر کنید.';
            $_SESSION['form_data'] = $user_data;
            header('Location: index.php?controller=user&action=show_add_user');
            exit;
        }
        
        // بررسی معتبر بودن نقش
        $valid_roles = ['admin', 'manager', 'inventory', 'production'];
        if (!in_array($user_data['role'], $valid_roles)) {
            $_SESSION['error'] = 'نقش انتخاب شده معتبر نیست.';
            $_SESSION['form_data'] = $user_data;
            header('Location: index.php?controller=user&action=show_add_user');
            exit;
        }
        
        // افزودن کاربر
        $result = $this->user_model->addUser($user_data);
        
        if ($result) {
            $_SESSION['success'] = 'کاربر جدید با موفقیت ایجاد شد.';
            header('Location: index.php?controller=user&action=list_users');
        } else {
            $_SESSION['error'] = 'خطا در ایجاد کاربر. نام کاربری یا ایمیل تکراری است.';
            $_SESSION['form_data'] = $user_data;
            header('Location: index.php?controller=user&action=show_add_user');
        }
        exit;
    }
    
    /**
     * نمایش فرم ویرایش کاربر
     */
    public function showEditUser() {
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('edit_user')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // دریافت شناسه کاربر
        $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($user_id <= 0) {
            $_SESSION['error'] = 'شناسه کاربر نامعتبر است.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // دریافت اطلاعات کاربر
        $user = $this->user_model->getUserById($user_id);
        
        if (!$user) {
            $_SESSION['error'] = 'کاربر مورد نظر یافت نشد.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        $page_title = 'ویرایش کاربر';
        $csrf_token = $_SESSION['csrf_token'];
        
        // نمایش فرم ویرایش کاربر
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/header.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/users/edit.php');
        include(TEMPLATES_PATH . '/' . DEFAULT_TEMPLATE . '/footer.php');
    }
    
    /**
     * پردازش فرم ویرایش کاربر
     */
    public function editUser() {
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('edit_user')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // بررسی CSRF توکن
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'خطای امنیتی رخ داده است. لطفاً دوباره تلاش کنید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // دریافت شناسه کاربر
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($user_id <= 0) {
            $_SESSION['error'] = 'شناسه کاربر نامعتبر است.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // دریافت اطلاعات فرم
        $user_data = [
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'role' => isset($_POST['role']) ? $_POST['role'] : 'inventory'
        ];
        
        // اگر رمز عبور وارد شده باشد، آن را اضافه می‌کنیم
        if (isset($_POST['password']) && !empty($_POST['password'])) {
            $user_data['password'] = $_POST['password'];
        }
        
        // بررسی خالی نبودن فیلدها
        if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['name'])) {
            $_SESSION['error'] = 'لطفاً تمام فیلدهای ضروری را پر کنید.';
            header('Location: index.php?controller=user&action=show_edit_user&id=' . $user_id);
            exit;
        }
        
        // بررسی معتبر بودن نقش
        $valid_roles = ['admin', 'manager', 'inventory', 'production'];
        if (!in_array($user_data['role'], $valid_roles)) {
            $_SESSION['error'] = 'نقش انتخاب شده معتبر نیست.';
            header('Location: index.php?controller=user&action=show_edit_user&id=' . $user_id);
            exit;
        }
        
        // ویرایش کاربر
        $result = $this->user_model->updateUser($user_id, $user_data);
        
        if ($result) {
            $_SESSION['success'] = 'اطلاعات کاربر با موفقیت بروزرسانی شد.';
            header('Location: index.php?controller=user&action=list_users');
        } else {
            $_SESSION['error'] = 'خطا در بروزرسانی اطلاعات کاربر. نام کاربری یا ایمیل تکراری است.';
            header('Location: index.php?controller=user&action=show_edit_user&id=' . $user_id);
        }
        exit;
    }
    
    /**
     * حذف کاربر
     */
    public function deleteUser() {
        // بررسی دسترسی
        if (!$this->main_controller->hasPermission('delete_user')) {
            $_SESSION['error'] = 'شما دسترسی به این بخش را ندارید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // دریافت شناسه کاربر
        $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($user_id <= 0) {
            $_SESSION['error'] = 'شناسه کاربر نامعتبر است.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // بررسی حذف خود کاربر
        if ($user_id === $this->current_user['id']) {
            $_SESSION['error'] = 'شما نمی‌توانید حساب کاربری خود را حذف کنید.';
            header('Location: index.php?controller=user&action=list_users');
            exit;
        }
        
        // حذف کاربر
        $result = $this->user_model->deleteUser($user_id);
        
        if ($result) {
            $_SESSION['success'] = 'کاربر با موفقیت حذف شد.';
        } else {
            $_SESSION['error'] = 'خطا در حذف کاربر. نمی‌توان آخرین کاربر مدیر را حذف کرد.';
        }
        
        header('Location: index.php?controller=user&action=list_users');
        exit;
    }
    
    /**
     * نمایش صفحه ورود
     */
    public function login() {
        // اگر کاربر قبلاً وارد شده، به داشبورد هدایت شود
        if (isset($_SESSION['user_data'])) {
            header('Location: index.php');
            exit;
        }
        
        $page_title = 'ورود به سیستم';
        include ROOT_PATH . '/templates/default/login.php';
    }
    
    /**
     * پردازش ورود کاربر
     */
    public function process_login() {
        return $this->main_controller->processLogin();
    }
    
    /**
     * خروج از سیستم
     */
    public function logout() {
        return $this->main_controller->logout();
    }
}
