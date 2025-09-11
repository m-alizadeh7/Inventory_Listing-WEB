<?php
namespace App\Models;

class Inventory {
    private $db;

    public function __construct() {
        $this->db = new \PDO('mysql:host=localhost;dbname=portal', 'root', '');
    }

    public function getAll() {
        $stmt = $this->db->query('SELECT * FROM inventory ORDER BY item_name');
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare('SELECT * FROM inventory WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function add($data) {
        $stmt = $this->db->prepare('INSERT INTO inventory (inventory_code, item_name, unit, min_inventory, supplier, current_inventory, required, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$data['inventory_code'], $data['item_name'], $data['unit'], $data['min_inventory'], $data['supplier'], $data['current_inventory'], $data['required'], $data['notes']]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare('UPDATE inventory SET inventory_code = ?, item_name = ?, unit = ?, min_inventory = ?, supplier = ?, current_inventory = ?, required = ?, notes = ? WHERE id = ?');
        return $stmt->execute([$data['inventory_code'], $data['item_name'], $data['unit'], $data['min_inventory'], $data['supplier'], $data['current_inventory'], $data['required'], $data['notes'], $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare('DELETE FROM inventory WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
?>
