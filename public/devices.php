<?php
require_once 'bootstrap.php';

// Handle device operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_device'])) {
        $device_name = clean($_POST['device_name']);
        $device_code = clean($_POST['device_code']);
        $description = clean($_POST['description']);
        $location = clean($_POST['location']);
        $status = clean($_POST['status']);

        $stmt = $conn->prepare("INSERT INTO devices (device_name, device_code, description, location, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->bind_param('sssss', $device_name, $device_code, $description, $location, $status) && $stmt->execute()) {
            set_flash_message('دستگاه با موفقیت اضافه شد', 'success');
        } else {
            set_flash_message('خطا در افزودن دستگاه: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['edit_device'])) {
        $id = (int)$_POST['device_id'];
        $device_name = clean($_POST['device_name']);
        $device_code = clean($_POST['device_code']);
        $description = clean($_POST['description']);
        $location = clean($_POST['location']);
        $status = clean($_POST['status']);

        $stmt = $conn->prepare("UPDATE devices SET device_name = ?, device_code = ?, description = ?, location = ?, status = ? WHERE id = ?");
        if ($stmt->bind_param('sssssi', $device_name, $device_code, $description, $location, $status, $id) && $stmt->execute()) {
            set_flash_message('دستگاه با موفقیت ویرایش شد', 'success');
        } else {
            set_flash_message('خطا در ویرایش دستگاه: ' . $conn->error, 'danger');
        }
    }

    if (isset($_POST['delete_device'])) {
        $id = (int)$_POST['device_id'];

        if ($conn->query("DELETE FROM devices WHERE id = $id")) {
            set_flash_message('دستگاه با موفقیت حذف شد', 'success');
        } else {
            set_flash_message('خطا در حذف دستگاه: ' . $conn->error, 'danger');
        }
    }

    header('Location: devices.php');
    exit;
}

// Get devices
$devices = [];
$sql = "SELECT * FROM devices ORDER BY device_name";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
}

$page_title = 'مدیریت دستگاه‌ها';
$page_description = 'مشاهده و مدیریت دستگاه‌ها و BOM آنها';

get_header();
?>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">دستگاه‌ها و BOM</h4>
                    <p class="text-muted mb-0">مدیریت دستگاه‌های تولید و لیست مواد آنها</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                    <i class="bi bi-plus-circle me-1"></i>
                    دستگاه جدید
                </button>
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
                                    <th>کد دستگاه</th>
                                    <th>نام دستگاه</th>
                                    <th>توضیحات</th>
                                    <th>مکان</th>
                                    <th>وضعیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($devices)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bi bi-cpu fs-1 text-muted mb-2"></i>
                                            <p class="text-muted mb-0">هیچ دستگاهی یافت نشد</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($devices as $device): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($device['device_code']); ?></td>
                                            <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                            <td><?php echo htmlspecialchars($device['description']); ?></td>
                                            <td><?php echo htmlspecialchars($device['location']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo $device['status'] === 'active' ? 'success' :
                                                         ($device['status'] === 'maintenance' ? 'warning' :
                                                          ($device['status'] === 'inactive' ? 'secondary' : 'danger'));
                                                ?>">
                                                    <?php
                                                    echo $device['status'] === 'active' ? 'فعال' :
                                                         ($device['status'] === 'maintenance' ? 'در تعمیر' :
                                                          ($device['status'] === 'inactive' ? 'غیرفعال' : 'خراب'));
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editDevice(<?php echo $device['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewBOM(<?php echo $device['id']; ?>)">
                                                        <i class="bi bi-list"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDevice(<?php echo $device['id']; ?>)">
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

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">افزودن دستگاه جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="device_code" class="form-label">کد دستگاه *</label>
                        <input type="text" class="form-control" id="device_code" name="device_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="device_name" class="form-label">نام دستگاه *</label>
                        <input type="text" class="form-control" id="device_name" name="device_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">مکان</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">وضعیت *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                            <option value="maintenance">در تعمیر</option>
                            <option value="broken">خراب</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_device" class="btn btn-primary">افزودن دستگاه</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editDevice(deviceId) {
    // TODO: Implement edit functionality
    alert('ویرایش دستگاه ' + deviceId);
}

function viewBOM(deviceId) {
    // TODO: Implement BOM view functionality
    alert('مشاهده BOM دستگاه ' + deviceId);
}

function deleteDevice(deviceId) {
    if (confirm('آیا از حذف این دستگاه اطمینان دارید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="device_id" value="${deviceId}">
            <input type="hidden" name="delete_device" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php get_footer(); ?>
