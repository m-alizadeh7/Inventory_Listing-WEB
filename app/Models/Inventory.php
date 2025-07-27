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
        if (!file_exists($file)) {
            throw new \Exception('File not found');
        }

        $handle = fopen($file, "r");
        if (!$handle) {
            throw new \Exception('Could not open file');
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \Exception('Empty CSV file');
        }

        $requiredColumns = ['row_number', 'name', 'quantity', 'unit'];
        foreach ($requiredColumns as $column) {
            if (!in_array($column, $header)) {
                fclose($handle);
                throw new \Exception("Missing required column: {$column}");
            }
        }

        $this->db->query("START TRANSACTION");
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $row = array_combine($header, $data);
                
                $rowNumber = $this->db->escapeString($row['row_number']);
                $name = $this->db->escapeString($row['name']);
                $quantity = (int)$row['quantity'];
                $unit = $this->db->escapeString($row['unit']);
                
                $query = "INSERT INTO {$this->table} (row_number, name, quantity, unit) 
                         VALUES ('{$rowNumber}', '{$name}', {$quantity}, '{$unit}')
                         ON DUPLICATE KEY UPDATE 
                         name = VALUES(name),
                         quantity = VALUES(quantity),
                         unit = VALUES(unit)";
                
                $this->db->query($query);
            }
            $this->db->query("COMMIT");
        } catch (\Exception $e) {
            $this->db->query("ROLLBACK");
            fclose($handle);
            throw $e;
        }
        
        fclose($handle);
        return true;
    }

    public function exportToCSV($sessionId) {
        $data = $this->getInventoryWithSession($sessionId);
        if (empty($data)) {
            throw new \Exception('No data found for this session');
        }

        $filename = 'inventory_export_' . date('Y-m-d_His') . '.csv';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new \Exception('Could not create export file');
        }

        // Write headers
        $headers = [
            'row_number',
            'name',
            'quantity',
            'unit',
            'counted_inventory',
            'needed_quantity',
            'session_notes',
            'updated_at',
            'completed_by'
        ];
        fputcsv($handle, $headers);

        // Write data
        foreach ($data as $row) {
            $exportRow = array_intersect_key($row, array_flip($headers));
            fputcsv($handle, $exportRow);
        }

        fclose($handle);
        return $filepath;
    }
}
