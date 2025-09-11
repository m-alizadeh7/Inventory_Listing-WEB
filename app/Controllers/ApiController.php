<?php
namespace App\Controllers;

use App\Models\User;

class ApiController {
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $user = new User();
        if ($user->authenticate($username, $password)) {
            // Generate token or session
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    }

    public function getDashboardStats() {
        // Get stats from database
        $stats = [
            'total_inventory' => $this->getTotalInventory(),
            'total_devices' => $this->getTotalDevices(),
            'total_production_orders' => $this->getTotalProductionOrders(),
            'total_users' => $this->getTotalUsers()
        ];
        echo json_encode($stats);
    }

    private function getTotalInventory() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM inventory');
        return $stmt->fetch()['total'];
    }

    private function getTotalDevices() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM devices');
        return $stmt->fetch()['total'];
    }

    private function getTotalProductionOrders() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM production_orders');
        return $stmt->fetch()['total'];
    }

    private function getTotalUsers() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM users');
        return $stmt->fetch()['total'];
    }
}
?>
