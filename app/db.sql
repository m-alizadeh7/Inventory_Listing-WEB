-- ===================================
-- Complete Database Schema for Inventory Management System
-- Combined from all SQL files
-- ===================================

-- Basic inventory tables from db.sql
-- Creating table for inventory items
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `row_number` INT NULL,
    `inventory_code` VARCHAR(50) NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `unit` VARCHAR(50),
    `min_inventory` INT,
    `supplier` VARCHAR(255),
    `current_inventory` FLOAT,
    `required` FLOAT,
    `notes` TEXT,
    `last_updated` DATETIME
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Creating table for inventory records
CREATE TABLE IF NOT EXISTS `inventory_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `inventory_id` INT NOT NULL,
    `inventory_session` VARCHAR(50) NOT NULL,
    `current_inventory` FLOAT,
    `required` FLOAT,
    `notes` TEXT,
    `updated_at` DATETIME,
    `completed_by` VARCHAR(255),
    `completed_at` DATETIME,
    FOREIGN KEY (`inventory_id`) REFERENCES `inventory`(`id`) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Creating table for inventory sessions
CREATE TABLE IF NOT EXISTS `inventory_sessions` (
    `session_id` VARCHAR(50) PRIMARY KEY,
    `status` ENUM('draft', 'completed') DEFAULT 'draft',
    `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_by` VARCHAR(255),
    `completed_at` DATETIME,
    `notes` TEXT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add foreign key to inventory_records for session reference
ALTER TABLE `inventory_records`
    ADD CONSTRAINT `fk_inventory_records_session`
    FOREIGN KEY (`inventory_session`) REFERENCES `inventory_sessions`(`session_id`) ON DELETE CASCADE;

-- Production and device tables from db_update_v2.sql
-- ایجاد جدول دستگاه‌ها
CREATE TABLE IF NOT EXISTS `devices` (
    `device_id` INT PRIMARY KEY AUTO_INCREMENT,
    `device_code` VARCHAR(50) NOT NULL UNIQUE,
    `device_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول لیست قطعات مورد نیاز هر دستگاه (BOM - Bill of Materials)
CREATE TABLE IF NOT EXISTS `device_bom` (
    `bom_id` INT PRIMARY KEY AUTO_INCREMENT,
    `device_id` INT NOT NULL,
    `item_code` VARCHAR(50) NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity_needed` INT NOT NULL,
    `supplier_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`device_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول تامین‌کنندگان
CREATE TABLE IF NOT EXISTS `suppliers` (
    `supplier_id` INT PRIMARY KEY AUTO_INCREMENT,
    `supplier_code` VARCHAR(50) NOT NULL UNIQUE,
    `supplier_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255),
    `phone` VARCHAR(20),
    `email` VARCHAR(255),
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول سفارش‌های تولید
CREATE TABLE IF NOT EXISTS `production_orders` (
    `order_id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_code` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('draft', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    `notes` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول جزئیات سفارش تولید
CREATE TABLE IF NOT EXISTS `production_order_items` (
    `order_item_id` INT PRIMARY KEY AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `device_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `production_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`device_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- Enhanced inventory features from db_enhanced_inventory.sql
-- Add product categories table
CREATE TABLE IF NOT EXISTS inventory_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add default categories
INSERT IGNORE INTO inventory_categories (category_name, category_description) VALUES
('الکترونیک', 'قطعات و تجهیزات الکترونیکی'),
('مکانیک', 'قطعات مکانیکی و فلزی'),
('پلاستیک', 'قطعات پلاستیکی و پلیمری'),
('مواد شیمیایی', 'مواد شیمیایی و محلول‌ها'),
('ابزار و تجهیزات', 'ابزارآلات و تجهیزات جانبی'),
('متفرقه', 'سایر اقلام');

-- Add category_id to inventory table if not exists
ALTER TABLE inventory
ADD COLUMN IF NOT EXISTS category_id INT,
ADD COLUMN IF NOT EXISTS last_physical_count_date DATE,
ADD COLUMN IF NOT EXISTS last_physical_count_value INT DEFAULT 0,
ADD FOREIGN KEY IF NOT EXISTS (category_id) REFERENCES inventory_categories(category_id);

-- Update existing inventory items with default category
UPDATE inventory SET category_id = (SELECT category_id FROM inventory_categories WHERE category_name = 'متفرقه' LIMIT 1)
WHERE category_id IS NULL;

-- Add finalization tracking to production orders
ALTER TABLE production_orders
ADD COLUMN IF NOT EXISTS finalized_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS finalized_by VARCHAR(100),
ADD COLUMN IF NOT EXISTS inventory_deducted BOOLEAN DEFAULT FALSE;

-- Create inventory transactions table for tracking all movements
CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_code VARCHAR(50) NOT NULL,
    transaction_type ENUM('addition', 'deduction', 'withdrawal', 'physical_count', 'production_use') NOT NULL,
    quantity_change INT NOT NULL, -- positive for additions, negative for deductions
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    reference_type VARCHAR(50), -- 'production_order', 'manual_withdrawal', 'physical_count', etc.
    reference_id INT,
    notes TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User management and security from db_users_security.sql
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
    role_permission_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_by INT NULL,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(user_id),
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول لاگ فعالیت‌ها
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- درج نقش‌های پیش‌فرض
INSERT IGNORE INTO user_roles (role_name, role_name_fa, description, hierarchy_level) VALUES
('super_admin', 'مدیر کل', 'دسترسی کامل به تمام بخش‌ها', 1),
('admin', 'مدیر', 'دسترسی مدیریتی به اکثر بخش‌ها', 2),
('manager', 'مدیر بخش', 'دسترسی مدیریتی به بخش‌های مربوطه', 3),
('supervisor', 'ناظر', 'نظارت بر عملیات روزانه', 4),
('operator', 'اپراتور', 'دسترسی عملیاتی محدود', 5),
('viewer', 'ناظر ساده', 'دسترسی فقط خواندنی', 6);

-- درج مجوزهای پیش‌فرض
INSERT IGNORE INTO permissions (permission_name, permission_name_fa, module, action, description) VALUES
-- Inventory permissions
('inventory.view', 'مشاهده انبار', 'inventory', 'view', 'مشاهده موجودی و گزارش‌های انبار'),
('inventory.create', 'ایجاد آیتم انبار', 'inventory', 'create', 'افزودن آیتم جدید به انبار'),
('inventory.update', 'ویرایش انبار', 'inventory', 'update', 'ویرایش اطلاعات آیتم‌های انبار'),
('inventory.delete', 'حذف از انبار', 'inventory', 'delete', 'حذف آیتم از انبار'),
('inventory.categories', 'مدیریت گروه‌ها', 'inventory', 'manage', 'مدیریت گروه‌بندی کالاها'),
('inventory.count', 'شمارش فیزیکی', 'inventory', 'manage', 'انجام شمارش فیزیکی انبار'),
('inventory.withdraw', 'خروج موردی', 'inventory', 'manage', 'ثبت خروج موردی کالا'),
('inventory.manage', 'مدیریت کامل انبار', 'inventory', 'manage', 'دسترسی کامل به مدیریت انبار'),

-- Production permissions
('production.view', 'مشاهده تولید', 'production', 'view', 'مشاهده سفارشات و گزارش‌های تولید'),
('production.create', 'ایجاد سفارش تولید', 'production', 'create', 'ایجاد سفارش جدید تولید'),
('production.update', 'ویرایش تولید', 'production', 'update', 'ویرایش سفارشات تولید'),
('production.delete', 'حذف سفارش تولید', 'production', 'delete', 'حذف سفارش تولید'),
('production.manage', 'مدیریت تولید', 'production', 'manage', 'دسترسی کامل به مدیریت تولید'),

-- Supplier permissions
('suppliers.view', 'مشاهده تامین‌کنندگان', 'suppliers', 'view', 'مشاهده لیست تامین‌کنندگان'),
('suppliers.create', 'ایجاد تامین‌کننده', 'suppliers', 'create', 'افزودن تامین‌کننده جدید'),
('suppliers.update', 'ویرایش تامین‌کننده', 'suppliers', 'update', 'ویرایش اطلاعات تامین‌کنندگان'),
('suppliers.delete', 'حذف تامین‌کننده', 'suppliers', 'delete', 'حذف تامین‌کننده'),
('suppliers.manage', 'مدیریت تامین‌کنندگان', 'suppliers', 'manage', 'دسترسی کامل به تامین‌کنندگان'),

-- User management permissions
('users.view', 'مشاهده کاربران', 'users', 'view', 'مشاهده لیست کاربران'),
('users.create', 'ایجاد کاربر', 'users', 'create', 'افزودن کاربر جدید'),
('users.update', 'ویرایش کاربر', 'users', 'update', 'ویرایش اطلاعات کاربران'),
('users.delete', 'حذف کاربر', 'users', 'delete', 'حذف کاربر'),
('users.manage', 'مدیریت کاربران', 'users', 'manage', 'دسترسی کامل به مدیریت کاربران'),

-- System permissions
('system.admin', 'مدیریت سیستم', 'system', 'admin', 'دسترسی به تنظیمات سیستم'),
('reports.view', 'مشاهده گزارش‌ها', 'reports', 'view', 'مشاهده تمام گزارش‌ها'),
('backup.create', 'ایجاد پشتیبان', 'backup', 'create', 'ایجاد فایل پشتیبان');

-- تخصیص مجوزهای پیش‌فرض به نقش‌ها
-- Super Admin - همه مجوزها
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
CROSS JOIN permissions p
WHERE r.role_name = 'super_admin';

-- Admin - اکثر مجوزها به جز مدیریت کاربران و سیستم
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
JOIN permissions p ON p.module IN ('inventory', 'production', 'suppliers', 'reports', 'backup')
WHERE r.role_name = 'admin';

-- Manager - مجوزهای مدیریتی محدود
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
JOIN permissions p ON (
    (p.module = 'inventory' AND p.action IN ('view', 'create', 'update')) OR
    (p.module = 'production' AND p.action IN ('view', 'create', 'update')) OR
    (p.module = 'suppliers' AND p.action IN ('view', 'create', 'update')) OR
    p.permission_name = 'reports.view'
)
WHERE r.role_name = 'manager';

-- Supervisor - مجوزهای نظارتی
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
JOIN permissions p ON p.action = 'view' OR p.permission_name IN ('inventory.count', 'inventory.withdraw', 'production.create')
WHERE r.role_name = 'supervisor';

-- Operator - مجوزهای عملیاتی پایه
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
JOIN permissions p ON p.action IN ('view', 'create', 'update') AND p.module IN ('inventory', 'production')
WHERE r.role_name = 'operator';

-- Viewer - فقط مشاهده
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM user_roles r
JOIN permissions p ON p.action = 'view'
WHERE r.role_name = 'viewer';

-- ===================================
-- End of Complete Database Schema
-- ===================================

-- جدول جلسات کاربری
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
