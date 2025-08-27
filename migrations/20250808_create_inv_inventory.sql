-- Migration: Create inv_inventory table with sample data
CREATE TABLE IF NOT EXISTS inv_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `row_number` INT NULL,
    inventory_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(255) NOT NULL,
    unit VARCHAR(50) DEFAULT 'عدد',
    min_inventory INT DEFAULT 5,
    supplier VARCHAR(100) NULL,
    current_inventory DOUBLE DEFAULT 0,
    required DOUBLE DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data if table is empty
INSERT IGNORE INTO inv_inventory (`row_number`, inventory_code, item_name, unit, min_inventory, supplier, current_inventory, required, notes) VALUES
(1, 'IC001', 'مقاومت 1K اهم', 'عدد', 100, 'الکترونیک آریا', 250, 300, 'مقاومت استاندارد'),
(2, 'IC002', 'خازن 100 میکروفاراد', 'عدد', 50, 'الکترونیک آریا', 75, 100, 'خازن الکترولیتی'),
(3, 'IC003', 'ترانزیستور BC547', 'عدد', 20, 'قطعات پارس', 15, 50, 'ترانزیستور NPN'),
(4, 'IC004', 'LED قرمز 5mm', 'عدد', 200, 'نور الکترونیک', 180, 250, 'LED استاندارد'),
(5, 'IC005', 'مقاومت 10K اهم', 'عدد', 100, 'الکترونیک آریا', 45, 150, 'مقاومت دقیق'),
(6, 'IC006', 'IC 555', 'عدد', 10, 'قطعات پارس', 8, 20, 'تایمر IC'),
(7, 'IC007', 'سیم رابط', 'متر', 50, 'کابل پارس', 25, 100, 'سیم مسی'),
(8, 'IC008', 'برد PCB خام', 'عدد', 20, 'PCB ایران', 12, 30, 'برد فیبرگلاس'),
(9, 'IC009', 'پیچ M3', 'عدد', 500, 'یراق آلات', 0, 1000, 'پیچ استیل'),
(10, 'IC010', 'باتری 9 ولت', 'عدد', 25, 'انرژی پایدار', 3, 50, 'باتری آلکالاین');
