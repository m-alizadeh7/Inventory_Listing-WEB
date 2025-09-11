<?php
namespace App\Models;

class User {
    private $db;

    public function __construct() {
        // Initialize database connection
        $this->db = new \PDO('mysql:host=localhost;dbname=portal', 'root', '');
    }

    public function authenticate($username, $password) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return true;
        }
        return false;
    }

    // Add more methods
}
?>
