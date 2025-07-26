<?php
namespace App\Core;

abstract class Model {
    protected $db;
    protected $table;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findAll() {
        $result = $this->db->query("SELECT * FROM {$this->table}");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findById($id) {
        $id = $this->db->escapeString($id);
        $result = $this->db->query("SELECT * FROM {$this->table} WHERE id = '{$id}'");
        return $result->fetch_assoc();
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_map([$this->db, 'escapeString'], $data)) . "'";
        
        return $this->db->query("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
    }

    public function update($id, $data) {
        $id = $this->db->escapeString($id);
        $set = array_map(function($key, $value) {
            $value = $this->db->escapeString($value);
            return "{$key} = '{$value}'";
        }, array_keys($data), $data);
        
        $set = implode(', ', $set);
        return $this->db->query("UPDATE {$this->table} SET {$set} WHERE id = '{$id}'");
    }

    public function delete($id) {
        $id = $this->db->escapeString($id);
        return $this->db->query("DELETE FROM {$this->table} WHERE id = '{$id}'");
    }
}
