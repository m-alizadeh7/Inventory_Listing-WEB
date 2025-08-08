<?php
/**
 * مدل مدیریت سفارشات تولید
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class ProductionOrderModel {
    private $conn;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * ایجاد جداول سفارشات تولید
     * 
     * @return bool
     */
    public function createTables() {
        $success = true;
        
        // بررسی و ایجاد جدول production_orders اگر وجود ندارد
        $res = $this->conn->query("SHOW TABLES LIKE 'production_orders'");
        if ($res && $res->num_rows === 0) {
            $createTable = "CREATE TABLE production_orders (
                order_id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(100) NOT NULL,
                status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME NULL,
                notes TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $success = $success && $this->conn->query($createTable);
        }

        // بررسی و ایجاد جدول production_order_items اگر وجود ندارد
        $res = $this->conn->query("SHOW TABLES LIKE 'production_order_items'");
        if ($res && $res->num_rows === 0) {
            $createTable = "CREATE TABLE production_order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                device_id INT NOT NULL,
                quantity INT NOT NULL,
                notes TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $success = $success && $this->conn->query($createTable);
        }
        
        return $success;
    }
    
    /**
     * دریافت لیست سفارشات تولید
     * 
     * @return array
     */
    public function getOrders() {
        $orders = [];
        
        $result = $this->conn->query("
            SELECT p.*,
                   COUNT(DISTINCT i.device_id) as devices_count,
                   SUM(i.quantity) as total_quantity,
                   (
                       SELECT COUNT(DISTINCT b.item_code)
                       FROM production_order_items oi
                       JOIN device_bom b ON oi.device_id = b.device_id
                       WHERE oi.order_id = p.order_id
                   ) as unique_parts_count
            FROM production_orders p
            LEFT JOIN production_order_items i ON p.order_id = i.order_id
            GROUP BY p.order_id
            ORDER BY p.created_at DESC
        ");
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        return $orders;
    }
    
    /**
     * دریافت اطلاعات سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @return array
     */
    public function getOrder($order_id) {
        $stmt = $this->conn->prepare("
            SELECT p.*, 
                   COUNT(DISTINCT i.device_id) as devices_count,
                   SUM(i.quantity) as total_quantity
            FROM production_orders p
            LEFT JOIN production_order_items i ON p.order_id = i.order_id
            WHERE p.order_id = ?
            GROUP BY p.order_id
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $order;
    }
    
    /**
     * دریافت لیست دستگاه‌های سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @return array
     */
    public function getOrderDevices($order_id) {
        $devices = [];
        
        $stmt = $this->conn->prepare("
            SELECT i.*, d.device_code, d.device_name,
                   (
                       SELECT COUNT(DISTINCT b.item_code)
                       FROM device_bom b
                       WHERE b.device_id = i.device_id
                   ) as parts_count
            FROM production_order_items i
            JOIN devices d ON i.device_id = d.device_id
            WHERE i.order_id = ?
            ORDER BY d.device_name
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
        $stmt->close();
        
        return $devices;
    }
    
    /**
     * دریافت لیست قطعات مورد نیاز سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @return array
     */
    public function getOrderParts($order_id) {
        $parts = [];
        
        $stmt = $this->conn->prepare("
            SELECT b.item_code, 
                   (SELECT i.item_name FROM inventory i WHERE i.inventory_code = b.item_code COLLATE utf8mb4_general_ci LIMIT 1) as item_name,
                   b.supplier_id,
                   s.supplier_name, s.supplier_code,
                   SUM(b.quantity_needed * i.quantity) as total_needed,
                   (
                       SELECT SUM(inv.current_inventory)
                       FROM inventory inv
                       WHERE inv.inventory_code COLLATE utf8mb4_general_ci = b.item_code COLLATE utf8mb4_general_ci
                   ) as current_stock
            FROM production_order_items i
            JOIN device_bom b ON i.device_id = b.device_id
            LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id
            WHERE i.order_id = ?
            GROUP BY b.item_code, b.supplier_id, s.supplier_name, s.supplier_code
            ORDER BY b.item_code
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $parts[] = $row;
        }
        $stmt->close();
        
        return $parts;
    }
    
    /**
     * افزودن سفارش جدید
     * 
     * @param string $order_number شماره سفارش
     * @param string $notes توضیحات
     * @return int
     */
    public function addOrder($order_number, $notes = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO production_orders (order_number, notes) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("ss", $order_number, $notes);
        $stmt->execute();
        $order_id = $this->conn->insert_id;
        $stmt->close();
        
        return $order_id;
    }
    
    /**
     * افزودن دستگاه به سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @param int $device_id شناسه دستگاه
     * @param int $quantity تعداد
     * @param string $notes توضیحات
     * @return bool
     */
    public function addOrderDevice($order_id, $device_id, $quantity, $notes = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO production_order_items (order_id, device_id, quantity, notes) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $order_id, $device_id, $quantity, $notes);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * تغییر وضعیت سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @param string $status وضعیت
     * @return bool
     */
    public function updateOrderStatus($order_id, $status) {
        $completed_at = ($status === 'completed') ? 'NOW()' : 'NULL';
        
        $stmt = $this->conn->prepare("
            UPDATE production_orders 
            SET status = ?, 
                completed_at = " . $completed_at . " 
            WHERE order_id = ?
        ");
        $stmt->bind_param("si", $status, $order_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * حذف سفارش
     * 
     * @param int $order_id شناسه سفارش
     * @return bool
     */
    public function deleteOrder($order_id) {
        // حذف آیتم‌های سفارش
        $stmt = $this->conn->prepare("DELETE FROM production_order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        
        // حذف سفارش
        $stmt = $this->conn->prepare("DELETE FROM production_orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
