<?php
namespace App\Controllers;

use App\Models\Inventory;

class InventoryController {
    public function getInventory() {
        $inventory = new Inventory();
        $items = $inventory->getAll();
        echo json_encode($items);
    }

    public function getInventoryItem($id) {
        $inventory = new Inventory();
        $item = $inventory->getById($id);
        echo json_encode($item);
    }

    public function addInventoryItem() {
        $data = json_decode(file_get_contents('php://input'), true);
        $inventory = new Inventory();
        if ($inventory->add($data)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false]);
        }
    }

    public function updateInventoryItem($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $inventory = new Inventory();
        if ($inventory->update($id, $data)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false]);
        }
    }

    public function deleteInventoryItem($id) {
        $inventory = new Inventory();
        if ($inventory->delete($id)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false]);
        }
    }
}
?>
