<?php
require_once 'bootstrap.php';

// Handle production order operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $id = (int)$_POST['order_id'];
        $status = clean($_POST['status']);

        $stmt = $conn->prepare("UPDATE production_orders SET status = ? WHERE id = ?");
        if ($stmt->bind_param('si', $status, $id) && $stmt->execute()) {
            set_flash_message('وضعیت سفارش با موفقیت بروزرسانی شد', 'success');
        } else {
            set_flash_message('خطا در بروزرسانی وضعیت: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['delete_order'])) {
        $id = (int)$_POST['order_id'];

        if ($conn->query("DELETE FROM production_orders WHERE id = $id")) {
            set_flash_message('سفارش با موفقیت حذف شد', 'success');
        } else {
            set_flash_message('خطا در حذف سفارش: ' . $conn->error, 'danger');
        }
    }

    header('Location: production_orders.php');
    exit;
}

// Get production orders
$production_orders = [];
$sql = "SELECT * FROM production_orders ORDER BY created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $production_orders[] = $row;
    }
}

$page_title = 'مدیریت سفارشات تولید';
$page_description = 'مشاهده و مدیریت سفارشات تولید';

get_header();
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">سفارشات تولید</h4>
                    <p class="text-muted mb-0">مدیریت سفارشات تولید و وضعیت آنها</p>
                </div>
                <a href="new_production_order.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    سفارش جدید
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>شماره سفارش</th>
                                    <th>نام محصول</th>
                                    <th>تعداد</th>
                                    <th>مهلت تحویل</th>
                                    <th>اولویت</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($production_orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="bi bi-inbox fs-1 text-muted mb-2"></i>
                                            <p class="text-muted mb-0">هیچ سفارشی یافت نشد</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($production_orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td><?php echo number_format($order['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars($order['deadline']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $order['priority'] === 'urgent' ? 'danger' :
                                                         ($order['priority'] === 'high' ? 'warning' :
                                                          ($order['priority'] === 'medium' ? 'info' : 'secondary'));
                                                ?>">
                                                    <?php
                                                    echo $order['priority'] === 'urgent' ? 'فوری' :
                                                         ($order['priority'] === 'high' ? 'زیاد' :
                                                          ($order['priority'] === 'medium' ? 'متوسط' : 'کم'));
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $order['status'] === 'completed' ? 'success' :
                                                         ($order['status'] === 'in_progress' ? 'primary' :
                                                          ($order['status'] === 'pending' ? 'warning' : 'secondary'));
                                                ?>">
                                                    <?php
                                                    echo $order['status'] === 'completed' ? 'تکمیل شده' :
                                                         ($order['status'] === 'in_progress' ? 'در حال انجام' :
                                                          ($order['status'] === 'pending' ? 'در انتظار' : 'لغو شده'));
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editOrder(<?php echo $order['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editOrder(orderId) {
    // TODO: Implement edit functionality
    alert('ویرایش سفارش ' + orderId);
}

function deleteOrder(orderId) {
    if (confirm('آیا از حذف این سفارش اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="order_id" value="${orderId}">
            <input type="hidden" name="delete_order" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php get_footer(); ?>
