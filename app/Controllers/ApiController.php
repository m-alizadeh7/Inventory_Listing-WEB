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
            'pending_orders' => $this->getPendingOrders(),
            'total_suppliers' => $this->getTotalSuppliers(),
            'total_categories' => $this->getTotalCategories()
        ];
        echo json_encode($stats);
    }

    public function getRecentActivities() {
        $activities = $this->getRecentInventoryActivities();
        echo json_encode($activities);
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

    private function getPendingOrders() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM production_orders WHERE status IN ("draft", "confirmed")');
        return $stmt->fetch()['total'];
    }

    private function getTotalSuppliers() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM suppliers');
        return $stmt->fetch()['total'];
    }

    private function getTotalCategories() {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM inventory_categories');
        return $stmt->fetch()['total'];
    }

    private function getRecentInventoryActivities() {
        $stmt = $this->db->query('SELECT item_name, last_updated FROM inventory WHERE last_updated IS NOT NULL ORDER BY last_updated DESC LIMIT 5');
        $activities = [];
        while ($row = $stmt->fetch()) {
            $time_ago = time() - strtotime($row['last_updated']);
            $time_text = $time_ago < 3600 ? ceil($time_ago / 60) . ' دقیقه پیش' : 
                        ($time_ago < 86400 ? ceil($time_ago / 3600) . ' ساعت پیش' : 
                        ceil($time_ago / 86400) . ' روز پیش');
            $activities[] = [
                'id' => uniqid(),
                'item_name' => $row['item_name'],
                'time_ago' => $time_text
            ];
        }
        return $activities;
    }
}
?>
