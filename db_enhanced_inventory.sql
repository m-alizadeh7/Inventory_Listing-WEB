-- Enhanced inventory management database structure
-- Adding product categories, finalization tracking, and emergency notes

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_code (inventory_code),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create manual withdrawals table
CREATE TABLE IF NOT EXISTS manual_withdrawals (
    withdrawal_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_code VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    reason TEXT NOT NULL,
    requested_by VARCHAR(100),
    approved_by VARCHAR(100),
    withdrawal_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    INDEX idx_inventory_code (inventory_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create emergency notes table for warehouse manager alerts
CREATE TABLE IF NOT EXISTS emergency_inventory_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_code VARCHAR(50) NOT NULL,
    required_quantity INT NOT NULL,
    current_stock INT NOT NULL,
    urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    note_text TEXT NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    resolved_by VARCHAR(100),
    status ENUM('pending', 'acknowledged', 'resolved') DEFAULT 'pending',
    INDEX idx_inventory_code (inventory_code),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create physical count sessions table
CREATE TABLE IF NOT EXISTS physical_count_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL,
    category_id INT,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    counted_by VARCHAR(100),
    notes TEXT,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create physical count details table
CREATE TABLE IF NOT EXISTS physical_count_details (
    count_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    inventory_code VARCHAR(50) NOT NULL,
    system_quantity INT NOT NULL,
    counted_quantity INT,
    difference_quantity INT,
    notes TEXT,
    counted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES physical_count_sessions(session_id),
    INDEX idx_session_id (session_id),
    INDEX idx_inventory_code (inventory_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
