<?php
/**
 * مدل مدیریت دستگاه‌ها و BOM
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class DeviceModel {
    private $conn;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * ایجاد جداول دستگاه‌ها
     * 
     * @return bool
     */
    public function createTables() {
        $success = true;
        
        // بررسی و ایجاد جدول devices اگر وجود ندارد
        $res = $this->conn->query("SHOW TABLES LIKE 'devices'");
        if ($res && $res->num_rows === 0) {
            $createTable = "CREATE TABLE devices (
                device_id INT AUTO_INCREMENT PRIMARY KEY,
                device_code VARCHAR(50) NOT NULL,
                device_name VARCHAR(255) NOT NULL,
                device_type VARCHAR(100) NULL,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $success = $success && $this->conn->query($createTable);
        }

        // بررسی و ایجاد جدول device_bom اگر وجود ندارد
        $res = $this->conn->query("SHOW TABLES LIKE 'device_bom'");
        if ($res && $res->num_rows === 0) {
            $createTable = "CREATE TABLE device_bom (
                id INT AUTO_INCREMENT PRIMARY KEY,
                device_id INT NOT NULL,
                item_code VARCHAR(50) NOT NULL,
                quantity_needed DOUBLE NOT NULL,
                supplier_id INT NULL,
                notes TEXT,
                FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $success = $success && $this->conn->query($createTable);
        }
        
        return $success;
    }
    
    /**
     * دریافت لیست دستگاه‌ها
     * 
     * @return array
     */
    public function getDevices() {
        $devices = [];
        
        $result = $this->conn->query("
            SELECT d.*, 
                   (SELECT COUNT(*) FROM device_bom WHERE device_id = d.device_id) as parts_count
            FROM devices d
            ORDER BY d.device_name
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $devices[] = $row;
            }
        }
        
        return $devices;
    }
    
    /**
     * دریافت اطلاعات دستگاه
     * 
     * @param int $device_id شناسه دستگاه
     * @return array
     */
    public function getDevice($device_id) {
        $stmt = $this->conn->prepare("
            SELECT d.*, 
                   (SELECT COUNT(*) FROM device_bom WHERE device_id = d.device_id) as parts_count
            FROM devices d
            WHERE d.device_id = ?
        ");
        $stmt->bind_param("i", $device_id);
        $stmt->execute();
        $device = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $device;
    }
    
    /**
     * دریافت لیست قطعات دستگاه
     * 
     * @param int $device_id شناسه دستگاه
     * @return array
     */
    public function getDeviceParts($device_id) {
        $parts = [];
        
        $stmt = $this->conn->prepare("
            SELECT b.*, 
                   (SELECT i.item_name FROM inventory i WHERE i.inventory_code = b.item_code COLLATE utf8mb4_general_ci LIMIT 1) as item_name,
                   (SELECT i.unit FROM inventory i WHERE i.inventory_code = b.item_code COLLATE utf8mb4_general_ci LIMIT 1) as unit,
                   s.supplier_name
            FROM device_bom b
            LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id
            WHERE b.device_id = ?
            ORDER BY b.item_code
        ");
        $stmt->bind_param("i", $device_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $parts[] = $row;
        }
        $stmt->close();
        
        return $parts;
    }
    
    /**
     * افزودن دستگاه جدید
     * 
     * @param array $data داده‌های دستگاه
     * @return int
     */
    public function addDevice($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO devices (device_code, device_name, device_type, notes) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", 
            $data['device_code'], 
            $data['device_name'], 
            $data['device_type'], 
            $data['notes']
        );
        $stmt->execute();
        $device_id = $this->conn->insert_id;
        $stmt->close();
        
        return $device_id;
    }
    
    /**
     * ویرایش دستگاه
     * 
     * @param int $device_id شناسه دستگاه
     * @param array $data داده‌های دستگاه
     * @return bool
     */
    public function updateDevice($device_id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE devices SET 
                device_code = ?, 
                device_name = ?, 
                device_type = ?, 
                notes = ?
            WHERE device_id = ?
        ");
        $stmt->bind_param("ssssi", 
            $data['device_code'], 
            $data['device_name'], 
            $data['device_type'], 
            $data['notes'], 
            $device_id
        );
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * حذف دستگاه
     * 
     * @param int $device_id شناسه دستگاه
     * @return bool
     */
    public function deleteDevice($device_id) {
        $stmt = $this->conn->prepare("DELETE FROM devices WHERE device_id = ?");
        $stmt->bind_param("i", $device_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * افزودن قطعه به دستگاه
     * 
     * @param int $device_id شناسه دستگاه
     * @param array $data داده‌های قطعه
     * @return bool
     */
    public function addDevicePart($device_id, $data) {
        $stmt = $this->conn->prepare("
            INSERT INTO device_bom (device_id, item_code, quantity_needed, supplier_id, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdis", 
            $device_id, 
            $data['item_code'], 
            $data['quantity_needed'], 
            $data['supplier_id'], 
            $data['notes']
        );
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * ویرایش قطعه دستگاه
     * 
     * @param int $id شناسه قطعه
     * @param array $data داده‌های قطعه
     * @return bool
     */
    public function updateDevicePart($id, $data) {
        $stmt = $this->conn->prepare("
            UPDATE device_bom SET 
                item_code = ?, 
                quantity_needed = ?, 
                supplier_id = ?, 
                notes = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sdisi", 
            $data['item_code'], 
            $data['quantity_needed'], 
            $data['supplier_id'], 
            $data['notes'], 
            $id
        );
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * حذف قطعه دستگاه
     * 
     * @param int $id شناسه قطعه
     * @return bool
     */
    public function deleteDevicePart($id) {
        $stmt = $this->conn->prepare("DELETE FROM device_bom WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
