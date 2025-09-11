<?php
namespace App\Models;

class Device {
    private $db;

    public function __construct() {
        $this->db = new \PDO('mysql:host=localhost;dbname=portal', 'root', '');
    }

    public function getAll() {
        $stmt = $this->db->query('SELECT * FROM devices');
        return $stmt->fetchAll();
    }

    public function add($data) {
        $stmt = $this->db->prepare('INSERT INTO devices (device_name, device_code, description, location, status) VALUES (?, ?, ?, ?, ?)');
        return $stmt->execute([$data['device_name'], $data['device_code'], $data['description'], $data['location'], $data['status']]);
    }

    // Add update and delete methods
}
?>
