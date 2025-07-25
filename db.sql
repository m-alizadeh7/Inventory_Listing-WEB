-- Creating table for inventory items
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `row_number` INT NOT NULL,
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