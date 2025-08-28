-- ===================================
-- سیستم امنیتی و مدیریت کاربران
-- طراحی: سطوح دسترسی سلسله‌مراتبی
-- ===================================

-- جدول نقش‌ها (roles)
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_name_fa VARCHAR(100) NOT NULL,
    description TEXT NULL,
    hierarchy_level INT NOT NULL DEFAULT 0, -- سطح سلسله‌مراتب (کمتر = بالاتر)
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول کاربران
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    role_id INT NOT NULL,
    department VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    password_reset_token VARCHAR(100) NULL,
    password_reset_expires DATETIME NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول مجوزها (permissions)
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_name_fa VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL, -- مثل inventory, production, reports
    action VARCHAR(50) NOT NULL, -- مثل create, read, update, delete, approve
    description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ارتباط نقش به مجوز (role_permissions)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_by INT NULL,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول محدودیت‌های گروه کالا (user_category_restrictions)
CREATE TABLE IF NOT EXISTS user_category_restrictions (
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    access_type ENUM('read', 'write', 'full') DEFAULT 'read',
    granted_by INT NULL,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, category_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول جلسات کاربری (user_sessions)
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول لاگ فعالیت‌های امنیتی
CREATE TABLE IF NOT EXISTS security_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    success TINYINT(1) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- داده‌های پایه نقش‌ها
-- ===================================

INSERT INTO user_roles (role_name, role_name_fa, hierarchy_level, description) VALUES
('system_admin', 'ادمین سیستم', 1, 'دسترسی کامل به همه قسمت‌ها'),
('company_manager', 'مدیر شرکت', 2, 'دسترسی مدیریتی کامل به عملیات شرکت'),
('middle_manager', 'مدیر میانی', 3, 'دسترسی مدیریتی به بخش‌های مختلف'),
('production_manager', 'مدیر تولید', 4, 'مدیریت کامل واحد تولید'),
('production_supervisor', 'سرپرست تولید', 5, 'نظارت بر عملیات تولید'),
('warehouse_manager', 'مدیر انبار', 4, 'مدیریت کامل انبار'),
('senior_warehouse_clerk', 'انباردار ارشد', 5, 'عملیات پیشرفته انبار'),
('warehouse_assistant', 'کمک انباردار', 6, 'عملیات پایه انبار'),
('sales_manager', 'مدیر فروش', 4, 'مدیریت واحد فروش'),
('accountant', 'حسابداری', 5, 'مدیریت مالی و حسابداری'),
('quality_manager', 'مدیر کنترل کیفیت', 4, 'مدیریت کنترل کیفیت'),
('machine_quality_approver', 'تایید کیفیت ماشین‌آلات', 5, 'تایید کیفیت تجهیزات'),
('technical_unit_limited', 'واحد فنی محدود', 6, 'دسترسی محدود به گروه‌های کالای خاص');

-- ===================================
-- مجوزهای سیستم
-- ===================================

INSERT INTO permissions (permission_name, permission_name_fa, module, action, description) VALUES
-- مجوزهای کلی سیستم
('system.admin', 'مدیریت سیستم', 'system', 'admin', 'دسترسی کامل مدیریت سیستم'),
('users.manage', 'مدیریت کاربران', 'users', 'manage', 'ایجاد، ویرایش و حذف کاربران'),
('users.view', 'مشاهده کاربران', 'users', 'read', 'مشاهده لیست کاربران'),

-- مجوزهای انبار
('inventory.admin', 'مدیریت کامل انبار', 'inventory', 'admin', 'دسترسی کامل به انبار'),
('inventory.manage', 'مدیریت انبار', 'inventory', 'manage', 'ایجاد و ویرایش اقلام انبار'),
('inventory.count', 'انبارگردانی', 'inventory', 'count', 'انجام انبارگردانی'),
('inventory.view', 'مشاهده انبار', 'inventory', 'read', 'مشاهده اقلام انبار'),
('inventory.withdraw', 'خروج موردی', 'inventory', 'withdraw', 'ثبت خروج موردی'),
('inventory.categories', 'مدیریت گروه‌های کالا', 'inventory', 'categories', 'مدیریت دسته‌بندی کالاها'),

-- مجوزهای تولید
('production.admin', 'مدیریت کامل تولید', 'production', 'admin', 'دسترسی کامل به تولید'),
('production.manage', 'مدیریت تولید', 'production', 'manage', 'ایجاد و مدیریت سفارش‌های تولید'),
('production.supervise', 'نظارت بر تولید', 'production', 'supervise', 'نظارت بر فرآیند تولید'),
('production.view', 'مشاهده تولید', 'production', 'read', 'مشاهده اطلاعات تولید'),
('production.finalize', 'نهایی‌سازی تولید', 'production', 'finalize', 'نهایی‌سازی سفارش‌های تولید'),

-- مجوزهای فروش
('sales.manage', 'مدیریت فروش', 'sales', 'manage', 'مدیریت فروش و سفارش‌ها'),
('sales.view', 'مشاهده فروش', 'sales', 'read', 'مشاهده اطلاعات فروش'),

-- مجوزهای حسابداری
('accounting.manage', 'مدیریت حسابداری', 'accounting', 'manage', 'مدیریت مالی و حسابداری'),
('accounting.view', 'مشاهده حسابداری', 'accounting', 'read', 'مشاهده گزارش‌های مالی'),

-- مجوزهای کنترل کیفیت
('quality.admin', 'مدیریت کنترل کیفیت', 'quality', 'admin', 'مدیریت کامل کنترل کیفیت'),
('quality.approve', 'تایید کیفیت', 'quality', 'approve', 'تایید کیفیت محصولات'),
('quality.test', 'تست کیفیت', 'quality', 'test', 'انجام تست‌های کیفیت'),
('quality.view', 'مشاهده کیفیت', 'quality', 'read', 'مشاهده اطلاعات کیفیت'),

-- مجوزهای گزارش‌گیری
('reports.admin', 'مدیریت گزارش‌ها', 'reports', 'admin', 'دسترسی کامل به گزارش‌ها'),
('reports.view', 'مشاهده گزارش‌ها', 'reports', 'read', 'مشاهده گزارش‌ها'),
('reports.export', 'خروجی گزارش‌ها', 'reports', 'export', 'خروجی گرفتن از گزارش‌ها');

-- ===================================
-- تخصیص مجوزها به نقش‌ها
-- ===================================

-- ادمین سیستم: همه مجوزها
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions;

-- مدیر شرکت: همه مجوزها به جز مدیریت سیستم
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, permission_id FROM permissions WHERE permission_name != 'system.admin';

-- مدیر میانی: مجوزهای مدیریتی کلیدی
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, permission_id FROM permissions WHERE permission_name IN (
    'users.view', 'inventory.view', 'production.view', 'sales.view', 
    'accounting.view', 'quality.view', 'reports.view'
);

-- مدیر تولید: مجوزهای تولید + مشاهده انبار
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, permission_id FROM permissions WHERE permission_name IN (
    'production.admin', 'inventory.view', 'quality.view', 'reports.view'
);

-- سرپرست تولید: نظارت تولید
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, permission_id FROM permissions WHERE permission_name IN (
    'production.supervise', 'production.view', 'inventory.view', 'quality.test'
);

-- مدیر انبار: مجوزهای انبار کامل
INSERT INTO role_permissions (role_id, permission_id)
SELECT 6, permission_id FROM permissions WHERE permission_name IN (
    'inventory.admin', 'reports.view', 'production.view'
);

-- انباردار ارشد: عملیات انبار پیشرفته
INSERT INTO role_permissions (role_id, permission_id)
SELECT 7, permission_id FROM permissions WHERE permission_name IN (
    'inventory.manage', 'inventory.count', 'inventory.withdraw', 'inventory.view'
);

-- کمک انباردار: عملیات پایه انبار
INSERT INTO role_permissions (role_id, permission_id)
SELECT 8, permission_id FROM permissions WHERE permission_name IN (
    'inventory.view', 'inventory.withdraw'
);

-- مدیر فروش: مجوزهای فروش
INSERT INTO role_permissions (role_id, permission_id)
SELECT 9, permission_id FROM permissions WHERE permission_name IN (
    'sales.manage', 'inventory.view', 'reports.view'
);

-- حسابداری: مجوزهای مالی
INSERT INTO role_permissions (role_id, permission_id)
SELECT 10, permission_id FROM permissions WHERE permission_name IN (
    'accounting.manage', 'reports.view', 'inventory.view'
);

-- مدیر کنترل کیفیت: مجوزهای کیفیت
INSERT INTO role_permissions (role_id, permission_id)
SELECT 11, permission_id FROM permissions WHERE permission_name IN (
    'quality.admin', 'production.view', 'inventory.view', 'reports.view'
);

-- تایید کیفیت ماشین‌آلات: تایید کیفیت
INSERT INTO role_permissions (role_id, permission_id)
SELECT 12, permission_id FROM permissions WHERE permission_name IN (
    'quality.approve', 'quality.test', 'quality.view'
);

-- واحد فنی محدود: دسترسی پایه (محدود به گروه‌های خاص)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 13, permission_id FROM permissions WHERE permission_name IN (
    'inventory.view', 'inventory.withdraw'
);

-- ایجاد کاربر ادمین پیش‌فرض
INSERT INTO users (username, email, password_hash, full_name, role_id, is_active) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدیر سیستم', 1, 1);
-- رمز عبور: password

-- ایندکس‌های بهینه‌سازی
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_security_logs_user ON security_logs(user_id);
CREATE INDEX idx_security_logs_date ON security_logs(created_at);
CREATE INDEX idx_category_restrictions_user ON user_category_restrictions(user_id);
