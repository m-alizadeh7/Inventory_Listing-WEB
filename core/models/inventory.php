<?php
/**
 * مدل مدیریت موجودی انبار
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

class InventoryModel {
    private $db;
    private $conn;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * ایجاد جدول موجودی
     * 
     * @return bool
     */
    public function createTable() {
        $res = $this->conn->query("SHOW TABLES LIKE 'inventory'");
        if ($res && $res->num_rows === 0) {
            $createTable = "CREATE TABLE inventory (
                id INT AUTO_INCREMENT,
                `row_number` INT NULL,
                inventory_code VARCHAR(50) NOT NULL,
                item_name VARCHAR(255) NOT NULL,
                unit VARCHAR(50) NULL,
                min_inventory INT NULL,
                supplier VARCHAR(100) NULL,
                current_inventory DOUBLE NULL,
                required DOUBLE NULL,
                notes VARCHAR(255) NULL,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            return $this->conn->query($createTable);
        }
        return true;
    }
    
    /**
     * دریافت کل موجودی
     * 
     * @param string $search_code کد کالا
     * @param string $search_name نام کالا
     * @param string $filter فیلتر
     * @param int $page شماره صفحه
     * @param int $per_page تعداد در هر صفحه
     * @return array
     */
    public function getItems($search_code = '', $search_name = '', $filter = '', $page = 1, $per_page = 20) {
        // ساخت شرط جستجو
        $where = [];
        $params = [];
        $types = '';

        if ($search_code) {
            $where[] = "inventory_code LIKE ?";
            $params[] = "%$search_code%";
            $types .= 's';
        }

        if ($search_name) {
            $where[] = "item_name LIKE ?";
            $params[] = "%$search_name%";
            $types .= 's';
        }

        if ($filter === 'low') {
            $where[] = "current_inventory < min_inventory";
        }

        if ($filter === 'out') {
            $where[] = "(current_inventory = 0 OR current_inventory IS NULL)";
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // تعداد کل رکوردها
        $total_query = "SELECT COUNT(*) as total FROM inventory $where_clause";
        if (!empty($params)) {
            $stmt = $this->conn->prepare($total_query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
        } else {
            $total = $this->conn->query($total_query)->fetch_assoc()['total'];
        }

        // پارامترهای صفحه‌بندی
        $offset = ($page - 1) * $per_page;
        $total_pages = ceil($total / $per_page);

        // دریافت رکوردها
        $query = "SELECT * FROM inventory $where_clause ORDER BY `row_number` LIMIT ? OFFSET ?";
        $params[] = $per_page;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();

        return [
            'items' => $items,
            'total' => $total,
            'total_pages' => $total_pages,
            'page' => $page
        ];
    }
    
    /**
     * به‌روزرسانی موجودی
     * 
     * @param int $id شناسه کالا
     * @param float $current_inventory موجودی فعلی
     * @return bool
     */
    public function updateInventory($id, $current_inventory) {
        $stmt = $this->conn->prepare("UPDATE inventory SET current_inventory = ? WHERE id = ?");
        $stmt->bind_param("di", $current_inventory, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * دریافت جزئیات کالا
     * 
     * @param int $id شناسه کالا
     * @return array
     */
    public function getItem($id) {
        $stmt = $this->conn->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item;
    }
    
    /**
     * افزودن کالا
     * 
     * @param array $data داده‌های کالا
     * @return bool
     */
    public function addItem($data) {
        $stmt = $this->conn->prepare("INSERT INTO inventory 
            (row_number, inventory_code, item_name, unit, min_inventory, supplier, current_inventory, required, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isssisss", 
            $data['row_number'],
            $data['inventory_code'],
            $data['item_name'],
            $data['unit'],
            $data['min_inventory'],
            $data['supplier'],
            $data['current_inventory'],
            $data['required'],
            $data['notes']
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * ویرایش کالا
     * 
     * @param int $id شناسه کالا
     * @param array $data داده‌های کالا
     * @return bool
     */
    public function updateItem($id, $data) {
        $stmt = $this->conn->prepare("UPDATE inventory SET 
            row_number = ?, 
            inventory_code = ?, 
            item_name = ?, 
            unit = ?, 
            min_inventory = ?, 
            supplier = ?, 
            current_inventory = ?, 
            required = ?, 
            notes = ? 
            WHERE id = ?");
        $stmt->bind_param(
            "isssisssi", 
            $data['row_number'],
            $data['inventory_code'],
            $data['item_name'],
            $data['unit'],
            $data['min_inventory'],
            $data['supplier'],
            $data['current_inventory'],
            $data['required'],
            $data['notes'],
            $id
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * حذف کالا
     * 
     * @param int $id شناسه کالا
     * @return bool
     */
    public function deleteItem($id) {
        $stmt = $this->conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * وارد کردن لیست موجودی
     * 
     * @param array $items لیست کالاها
     * @return int
     */
    public function importItems($items) {
        $count = 0;
        
        foreach ($items as $item) {
            $stmt = $this->conn->prepare("INSERT INTO inventory 
                (row_number, inventory_code, item_name, unit, min_inventory, supplier, current_inventory, required, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                item_name = VALUES(item_name),
                unit = VALUES(unit),
                min_inventory = VALUES(min_inventory),
                supplier = VALUES(supplier),
                current_inventory = VALUES(current_inventory),
                required = VALUES(required),
                notes = VALUES(notes)");
            $stmt->bind_param(
                "isssisss", 
                $item['row_number'],
                $item['inventory_code'],
                $item['item_name'],
                $item['unit'],
                $item['min_inventory'],
                $item['supplier'],
                $item['current_inventory'],
                $item['required'],
                $item['notes']
            );
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
        
        return $count;
    }
}
