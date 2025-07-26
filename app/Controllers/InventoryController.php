<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Inventory;

class InventoryController extends Controller {
    private $model;

    public function __construct() {
        $this->model = new Inventory();
    }

    public function index() {
        $inventories = $this->model->findAll();
        $this->view('inventory/index', ['inventories' => $inventories]);
    }

    public function import() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            try {
                $this->model->importFromCSV($_FILES['csv_file']);
                $this->redirect('/inventory?success=1');
            } catch (\Exception $e) {
                $this->view('inventory/import', ['error' => $e->getMessage()]);
            }
        }
        $this->view('inventory/import');
    }

    public function export($sessionId) {
        $this->model->exportToCSV($sessionId);
    }

    public function view($sessionId) {
        $inventory = $this->model->getInventoryWithSession($sessionId);
        $this->view('inventory/view', ['inventory' => $inventory]);
    }
}
