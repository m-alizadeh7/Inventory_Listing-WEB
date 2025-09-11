<?php
namespace App\Controllers;

use App\Models\Device;

class DeviceController {
    public function getDevices() {
        $device = new Device();
        $devices = $device->getAll();
        echo json_encode($devices);
    }

    public function addDevice() {
        $data = json_decode(file_get_contents('php://input'), true);
        $device = new Device();
        if ($device->add($data)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false]);
        }
    }

    // Add edit and delete methods
}
?>
