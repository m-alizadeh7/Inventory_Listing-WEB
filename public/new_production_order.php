<?php
require_once 'bootstrap.php';

// Handle new production order operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_order'])) {
        $order_number = clean($_POST['order_number']);
        $product_name = clean($_POST['product_name']);
        $description = clean($_POST['description']);
        $quantity = (int)$_POST['quantity'];
        $deadline = clean($_POST['deadline']);
        $priority = clean($_POST['priority']);
        $status = 'pending';

        $stmt = $conn->prepare("INSERT INTO production_orders (order_number, product_name, description, quantity, deadline, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->bind_param('sssisss', $order_number, $product_name, $description, $quantity, $deadline, $priority, $status) && $stmt->execute()) {
            set_flash_message('سفارش تولید با موفقیت ایجاد شد', 'success');
            header('Location: production_orders.php');
            exit;
        } else {
            set_flash_message('خطا در ایجاد سفارش: ' . $conn->error, 'danger');
        }
    }
}

$page_title = 'سفارش تولید جدید';
$page_description = 'ایجاد سفارش تولید جدید';

get_header();
?>

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-plus-square me-2"></i>
                        ایجاد سفارش تولید جدید
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="order_number" class="form-label">شماره سفارش *</label>
                                <input type="text" class="form-control" id="order_number" name="order_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="product_name" class="form-label">نام محصول *</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantity" class="form-label">تعداد *</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="deadline" class="form-label">مهلت تحویل *</label>
                                <input type="date" class="form-control" id="deadline" name="deadline" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="priority" class="form-label">اولویت *</label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low">کم</option>
                                    <option value="medium" selected>متوسط</option>
                                    <option value="high">زیاد</option>
                                    <option value="urgent">فوری</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="create_order" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>
                                ایجاد سفارش
                            </button>
                            <a href="production_orders.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>
                                بازگشت
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
