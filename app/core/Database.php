<?php
/**
 * Database class for managing connections and queries
 */
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        if (!defined('DB_HOST')) {
            throw new Exception('Database configuration not found.');
        }
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) $types .= 'i';
                    elseif (is_float($param)) $types .= 'd';
                    else $types .= 's';
                }
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result();
        }
        return false;
    }

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Helper function to get DB instance
function getDbConnection() {
    return Database::getInstance()->getConnection();
}
