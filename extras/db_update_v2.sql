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
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `production_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`device_id`) REFERENCES `devices`(`device_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول درخواست‌های خرید
CREATE TABLE IF NOT EXISTS `purchase_requests` (
    `request_id` INT PRIMARY KEY AUTO_INCREMENT,
    `request_code` VARCHAR(50) NOT NULL UNIQUE,
    `order_id` INT NOT NULL,
    `status` ENUM('draft', 'pending', 'approved', 'ordered', 'received', 'cancelled') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT,
    FOREIGN KEY (`order_id`) REFERENCES `production_orders`(`order_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- ایجاد جدول جزئیات درخواست خرید
CREATE TABLE IF NOT EXISTS `purchase_request_items` (
    `request_item_id` INT PRIMARY KEY AUTO_INCREMENT,
    `request_id` INT NOT NULL,
    `item_code` VARCHAR(50) NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity_needed` INT NOT NULL,
    `supplier_id` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`request_id`) REFERENCES `purchase_requests`(`request_id`) ON DELETE CASCADE,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- اضافه کردن ایندکس‌های مورد نیاز
ALTER TABLE `device_bom` ADD INDEX `idx_item_code` (`item_code`);
ALTER TABLE `purchase_request_items` ADD INDEX `idx_item_code` (`item_code`);
ALTER TABLE `device_bom` ADD FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`) ON DELETE SET NULL;
