<?php
namespace App\Models;

use App\Core\Model;

class Inventory extends Model {
    protected $table = 'inventory';

    public function getInventoryWithSession($sessionId) {
        $sessionId = $this->db->escapeString($sessionId);
        $query = "
            SELECT 
                i.*,
                r.current_inventory as counted_inventory,
                r.required as needed_quantity,
                r.notes as session_notes,
                r.updated_at,
                r.completed_by
            FROM {$this->table} i
            LEFT JOIN inventory_records r ON i.id = r.inventory_id
            WHERE r.inventory_session = '{$sessionId}'
            ORDER BY i.row_number ASC
        ";
        
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function importFromCSV($file) {
        // پیاده‌سازی وارد کردن از CSV
    }

    public function exportToCSV($sessionId) {
        // پیاده‌سازی خروجی CSV
    }
}
