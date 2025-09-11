<?php
/**
 * Template for devices page
 * Professional design for device management
 */

// Make database connection available
global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (function_exists('getDbConnection')) {
        try {
            $conn = getDbConnection();
        } catch (Exception $e) {
            $conn = null;
        }
    } else {
        $conn = null;
    }
}

// Page header data
$header_args = array(
    'title' => 'مدیریت دستگاه‌ها',
    'subtitle' => 'مدیریت و ثبت اطلاعات دستگاه‌های موجود در سیستم',
    'icon' => 'bi bi-cpu',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => '../index.php'),
        array('text' => 'مدیریت دستگاه‌ها')
    ),
    'actions' => array(
        array(
            'text' => 'BOM دستگاه‌ها',
            'url' => 'device_bom.php',
            'class' => 'btn-info',
            'icon' => 'bi bi-diagram-3'
        ),
        array(
            'text' => 'بازگشت',
            'url' => '../index.php',
            'class' => 'btn-secondary',
            'icon' => 'bi bi-house'
        )
    )
);

get_theme_part('page-header', $header_args);

// Load alerts
get_theme_part('alerts');

// ذخیره ویرایش دستگاه
if (isset($_POST['save_edit_device']) && isset($_POST['edit_device_id'])) {
    $id = intval($_POST['edit_device_id']);
    $fields = [];
    $params = [];
    $types = '';
    if (isset($_POST['edit_device_code'])) {
        $fields[] = 'device_code = ?';
        $params[] = clean($_POST['edit_device_code']);
        $types .= 's';
    }
    if (isset($_POST['edit_device_name'])) {
        $fields[] = 'device_name = ?';
        $params[] = clean($_POST['edit_device_name']);
        $types .= 's';
    }
    if (isset($_POST['edit_model_number'])) {
        $fields[] = 'model_number = ?';
        $params[] = clean($_POST['edit_model_number']);
        $types .= 's';
    }
    if (isset($_POST['edit_serial_number'])) {
        $fields[] = 'serial_number = ?';
        $params[] = clean($_POST['edit_serial_number']);
        $types .= 's';
    }
    if (isset($_POST['edit_manufacturer'])) {
        $fields[] = 'manufacturer = ?';
        $params[] = clean($_POST['edit_manufacturer']);
        $types .= 's';
    }
    if (isset($_POST['edit_status'])) {
        $fields[] = 'status = ?';
        $params[] = clean($_POST['edit_status']);
        $types .= 's';
    }
    if (isset($_POST['edit_location'])) {
        $fields[] = 'location = ?';
        $params[] = clean($_POST['edit_location']);
        $types .= 's';
    }
    if (isset($_POST['edit_description'])) {
        $fields[] = 'description = ?';
        $params[] = clean($_POST['edit_description']);
        $types .= 's';
    }
    if (isset($_POST['edit_technical_specs'])) {
        $fields[] = 'technical_specs = ?';
        $params[] = clean($_POST['edit_technical_specs']);
        $types .= 's';
    }
    if (!empty($fields)) {
        $params[] = $id;
        $types .= 'i';
        $sql = 'UPDATE devices SET ' . implode(', ', $fields) . ' WHERE device_id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        header('Location: devices.php?msg=edited');
        exit;
    }
}

// افزودن دستگاه جدید
if (isset($_POST['save_new_device'])) {
    $fields = [];
    $placeholders = [];
    $params = [];
    $types = '';
    if (isset($_POST['new_device_code'])) {
        $fields[] = 'device_code';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_device_code']);
        $types .= 's';
    }
    if (isset($_POST['new_device_name'])) {
        $fields[] = 'device_name';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_device_name']);
        $types .= 's';
    }
    if (isset($_POST['new_model_number'])) {
        $fields[] = 'model_number';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_model_number']);
        $types .= 's';
    }
    if (isset($_POST['new_serial_number'])) {
        $fields[] = 'serial_number';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_serial_number']);
        $types .= 's';
    }
    if (isset($_POST['new_manufacturer'])) {
        $fields[] = 'manufacturer';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_manufacturer']);
        $types .= 's';
    }
    if (isset($_POST['new_status'])) {
        $fields[] = 'status';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_status']);
        $types .= 's';
    }
    if (isset($_POST['new_location'])) {
        $fields[] = 'location';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_location']);
        $types .= 's';
    }
    if (isset($_POST['new_description'])) {
        $fields[] = 'description';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_description']);
        $types .= 's';
    }
    if (isset($_POST['new_technical_specs'])) {
        $fields[] = 'technical_specs';
        $placeholders[] = '?';
        $params[] = clean($_POST['new_technical_specs']);
        $types .= 's';
    }
    if (!empty($fields)) {
        $sql = 'INSERT INTO devices (' . implode(',', $fields) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        header('Location: devices.php?msg=added');
        exit;
    }
}

// دریافت لیست ستون‌های جدول devices برای سازگاری با دیتابیس‌های قدیمی
$result_cols = $conn->query("SHOW COLUMNS FROM devices");
$device_columns = [];
if ($result_cols) {
    while ($col = $result_cols->fetch_assoc()) {
        $device_columns[] = $col['Field'];
    }
}

function device_col($row, $col, $default = '-') {
    return isset($row[$col]) ? htmlspecialchars($row[$col]) : $default;
}

// بررسی درخواست حذف دستگاه
if (isset($_POST['delete_device'])) {
    $device_id = clean($_POST['device_id']);
    // بررسی اینکه آیا دستگاه در سیستم استفاده شده است
    $check = $conn->query("SELECT COUNT(*) as count FROM device_bom WHERE device_id = $device_id");
    $row = $check->fetch_assoc();
    if ($row['count'] > 0) {
        $error_message = "این دستگاه دارای قطعات مرتبط است و نمی‌توان آن را حذف کرد.";
    } else {
        $conn->query("DELETE FROM devices WHERE device_id = $device_id");
        header('Location: devices.php?msg=deleted');
        exit;
    }
}

// جستجو و فیلتر
$search = clean($_GET['search'] ?? '');
$status_filter = clean($_GET['status'] ?? '');

$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(device_code LIKE ? OR device_name LIKE ? OR model_number LIKE ? OR serial_number LIKE ? OR manufacturer LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $types .= 'sssss';
}

if (!empty($status_filter)) {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// ترتیب‌بندی
$sort = clean($_GET['sort'] ?? 'device_code');
$order = clean($_GET['order'] ?? 'asc');

// اطمینان از معتبر بودن فیلد مرتب‌سازی
$valid_sort_fields = ['device_code', 'device_name', 'status', 'manufacturer', 'created_at', 'last_maintenance_date', 'next_maintenance_date'];
if (!in_array($sort, $valid_sort_fields)) {
    $sort = 'device_code';
}

// جلوگیری از injection در order
$order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';

// آماده‌سازی و اجرای query
$sql = "SELECT * FROM devices $where_sql ORDER BY $sort $order";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$devices = [];
$total_devices = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $devices[] = $row;
    }
    $total_devices = count($devices);
}

// نمایش پیام‌های سیستم
$message = '';
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    switch ($msg) {
        case 'added':
            $message = 'دستگاه جدید با موفقیت اضافه شد.';
            break;
        case 'edited':
            $message = 'اطلاعات دستگاه با موفقیت بروزرسانی شد.';
            break;
        case 'deleted':
            $message = 'دستگاه با موفقیت حذف شد.';
            break;
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="bi bi-hdd-rack"></i> مدیریت دستگاه‌ها</h1>
        <a href="../index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> بازگشت به داشبورد
        </a>
    </div>

    <?php if (isset($message) && !empty($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message) && !empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-4">
            <!-- افزودن دستگاه جدید -->
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i> افزودن دستگاه جدید</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_device_code" class="form-label">کد دستگاه <span class="text-danger">*</span></label>
                                <input type="text" id="new_device_code" name="new_device_code" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_device_name" class="form-label">نام دستگاه <span class="text-danger">*</span></label>
                                <input type="text" id="new_device_name" name="new_device_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_model_number" class="form-label">شماره مدل</label>
                                <input type="text" id="new_model_number" name="new_model_number" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_serial_number" class="form-label">شماره سریال</label>
                                <input type="text" id="new_serial_number" name="new_serial_number" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_manufacturer" class="form-label">سازنده</label>
                                <input type="text" id="new_manufacturer" name="new_manufacturer" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_status" class="form-label">وضعیت</label>
                                <select id="new_status" name="new_status" class="form-select">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                    <option value="maintenance">در حال تعمیر</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="new_location" class="form-label">موقعیت</label>
                                <input type="text" id="new_location" name="new_location" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label for="new_description" class="form-label">توضیحات</label>
                                <textarea id="new_description" name="new_description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="new_technical_specs" class="form-label">مشخصات فنی</label>
                                <textarea id="new_technical_specs" name="new_technical_specs" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button type="submit" name="save_new_device" class="btn btn-primary w-100">
                            <i class="bi bi-plus-lg me-1"></i> افزودن دستگاه
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- جستجو و فیلتر -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="bi bi-search me-2"></i> جستجو و فیلتر</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-5 mb-3">
                            <label for="search" class="form-label">جستجو:</label>
                            <input type="text" id="search" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="جستجو در کد، نام، مدل...">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">وضعیت:</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>فعال</option>
                                <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                                <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>در حال تعمیر</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="sort" class="form-label">ترتیب براساس:</label>
                            <select id="sort" name="sort" class="form-select">
                                <option value="device_code" <?= $sort === 'device_code' ? 'selected' : '' ?>>کد دستگاه</option>
                                <option value="device_name" <?= $sort === 'device_name' ? 'selected' : '' ?>>نام دستگاه</option>
                                <option value="manufacturer" <?= $sort === 'manufacturer' ? 'selected' : '' ?>>سازنده</option>
                                <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>وضعیت</option>
                                <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>تاریخ ثبت</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i> جستجو
                                    </button>
                                    <a href="devices.php" class="btn btn-outline-secondary ms-2">
                                        <i class="bi bi-arrow-repeat me-1"></i> پاک کردن فیلترها
                                    </a>
                                </div>
                                <div>
                                    <select name="order" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                        <option value="asc" <?= $order === 'ASC' ? 'selected' : '' ?>>صعودی</option>
                                        <option value="desc" <?= $order === 'DESC' ? 'selected' : '' ?>>نزولی</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">اعمال</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- لیست دستگاه‌ها -->
            <?php if (empty($devices)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> هیچ دستگاهی یافت نشد.
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-list me-2"></i> لیست دستگاه‌ها
                            <span class="badge bg-secondary ms-2"><?= $total_devices ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 100px;">کد</th>
                                        <th>نام دستگاه</th>
                                        <th>مدل</th>
                                        <th>سازنده</th>
                                        <th>وضعیت</th>
                                        <th style="width: 150px;">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($devices as $device): 
                                        // تعیین کلاس و متن وضعیت
                                        $status_class = 'secondary';
                                        $status_text = 'نامشخص';
                                        
                                        switch ($device['status'] ?? '') {
                                            case 'active':
                                                $status_class = 'success';
                                                $status_text = 'فعال';
                                                break;
                                            case 'inactive':
                                                $status_class = 'danger';
                                                $status_text = 'غیرفعال';
                                                break;
                                            case 'maintenance':
                                                $status_class = 'warning';
                                                $status_text = 'در حال تعمیر';
                                                break;
                                        }
                                    ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($device['device_code']) ?></code></td>
                                            <td><?= htmlspecialchars($device['device_name']) ?></td>
                                            <td><?= device_col($device, 'model_number') ?></td>
                                            <td><?= device_col($device, 'manufacturer') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $device['device_id'] ?>" title="جزئیات">
                                                        <i class="bi bi-info-circle"></i>
                                                    </button>
                                                    <a href="device_bom.php?id=<?= $device['device_id'] ?>" class="btn btn-primary" title="لیست قطعات">
                                                        <i class="bi bi-list-check"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editDeviceModal<?= $device['device_id'] ?>" title="ویرایش">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteDeviceModal<?= $device['device_id'] ?>" title="حذف">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Modal حذف دستگاه -->
                                                <div class="modal fade" id="deleteDeviceModal<?= $device['device_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">تایید حذف دستگاه</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>آیا از حذف دستگاه "<?= htmlspecialchars($device['device_name']) ?>" اطمینان دارید؟</p>
                                                                <div class="alert alert-warning">
                                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                                    توجه: در صورتی که این دستگاه دارای قطعات مرتبط باشد، امکان حذف وجود ندارد.
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form method="POST">
                                                                    <input type="hidden" name="device_id" value="<?= $device['device_id'] ?>">
                                                                    <button type="submit" name="delete_device" class="btn btn-danger">حذف</button>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Modal ویرایش دستگاه -->
                                                <div class="modal fade" id="editDeviceModal<?= $device['device_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">ویرایش دستگاه</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <input type="hidden" name="edit_device_id" value="<?= $device['device_id'] ?>">
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">کد دستگاه</label>
                                                                            <input type="text" name="edit_device_code" class="form-control" value="<?= htmlspecialchars($device['device_code']) ?>" required>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">نام دستگاه</label>
                                                                            <input type="text" name="edit_device_name" class="form-control" value="<?= htmlspecialchars($device['device_name']) ?>" required>
                                                                        </div>
                                                                        <?php if (in_array('model_number', $device_columns)): ?>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">شماره مدل</label>
                                                                            <input type="text" name="edit_model_number" class="form-control" value="<?= device_col($device, 'model_number') ?>">
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('serial_number', $device_columns)): ?>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">شماره سریال</label>
                                                                            <input type="text" name="edit_serial_number" class="form-control" value="<?= device_col($device, 'serial_number') ?>">
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('manufacturer', $device_columns)): ?>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">سازنده</label>
                                                                            <input type="text" name="edit_manufacturer" class="form-control" value="<?= device_col($device, 'manufacturer') ?>">
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('status', $device_columns)): ?>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">وضعیت</label>
                                                                            <select name="edit_status" class="form-select">
                                                                                <option value="active" <?= ($device['status'] ?? '') === 'active' ? 'selected' : '' ?>>فعال</option>
                                                                                <option value="inactive" <?= ($device['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                                                                                <option value="maintenance" <?= ($device['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>در حال تعمیر</option>
                                                                            </select>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('location', $device_columns)): ?>
                                                                        <div class="col-md-6">
                                                                            <label class="form-label">موقعیت</label>
                                                                            <input type="text" name="edit_location" class="form-control" value="<?= device_col($device, 'location') ?>">
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('description', $device_columns)): ?>
                                                                        <div class="col-12">
                                                                            <label class="form-label">توضیحات</label>
                                                                            <textarea name="edit_description" class="form-control" rows="2"><?= device_col($device, 'description') ?></textarea>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('technical_specs', $device_columns)): ?>
                                                                        <div class="col-12">
                                                                            <label class="form-label">مشخصات فنی</label>
                                                                            <textarea name="edit_technical_specs" class="form-control" rows="2"><?= device_col($device, 'technical_specs') ?></textarea>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" name="save_edit_device" class="btn btn-primary">
                                                                        <i class="bi bi-save me-1"></i> ذخیره تغییرات
                                                                    </button>
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- مودال‌های جزئیات فنی -->
    <?php foreach ($devices as $device): ?>
        <div class="modal fade" id="detailsModal<?= $device['device_id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-tools me-1"></i>
                            جزئیات فنی: <?= htmlspecialchars($device['device_name']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body technical-details-container">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <i class="bi bi-info-circle me-1"></i> اطلاعات اصلی
                                    </div>
                                    <div class="card-body">
                                        <div class="specs-row">
                                            <strong>کد دستگاه:</strong> <code><?= htmlspecialchars($device['device_code']) ?></code>
                                        </div>
                                        <div class="specs-row">
                                            <strong>نام دستگاه:</strong> <?= htmlspecialchars($device['device_name']) ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>مدل:</strong> <?= htmlspecialchars($device['model_number'] ?? '-') ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>شماره سریال:</strong> <code><?= htmlspecialchars($device['serial_number'] ?? '-') ?></code>
                                        </div>
                                        <div class="specs-row">
                                            <strong>سازنده:</strong> <?= htmlspecialchars($device['manufacturer'] ?? '-') ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>موقعیت:</strong> <?= htmlspecialchars($device['location'] ?? '-') ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>وضعیت:</strong> 
                                            <span class="badge bg-<?= $status_class ?>">
                                                <?= $status_text ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <i class="bi bi-calendar-check me-1"></i> تاریخ‌های کلیدی
                                    </div>
                                    <div class="card-body">
                                        <div class="specs-row">
                                            <strong>تاریخ خرید:</strong> <?= !empty($device['purchase_date']) ? gregorianToJalali($device['purchase_date']) : '-' ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>تاریخ پایان گارانتی:</strong> <?= !empty($device['warranty_expiry']) ? gregorianToJalali($device['warranty_expiry']) : '-' ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>آخرین تعمیر:</strong> <?= !empty($device['last_maintenance_date']) ? gregorianToJalali($device['last_maintenance_date']) : '-' ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>تعمیر بعدی:</strong> <?= !empty($device['next_maintenance_date']) ? gregorianToJalali($device['next_maintenance_date']) : '-' ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>تاریخ ثبت:</strong> <?= !empty($device['created_at']) ? gregorianToJalali($device['created_at']) : '-' ?>
                                        </div>
                                        <div class="specs-row">
                                            <strong>آخرین به‌روزرسانی:</strong> <?= !empty($device['updated_at']) ? gregorianToJalali($device['updated_at']) : '-' ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <i class="bi bi-card-text me-1"></i> توضیحات
                            </div>
                            <div class="card-body">
                                <?= !empty($device['description']) ? nl2br(htmlspecialchars($device['description'])) : '<em class="text-muted">بدون توضیحات</em>' ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="bi bi-file-earmark-code me-1"></i> مشخصات فنی
                            </div>
                            <div class="card-body">
                                <?php if (!empty($device['technical_specs'])): ?>
                                    <pre class="technical-specs"><?= htmlspecialchars($device['technical_specs']) ?></pre>
                                <?php else: ?>
                                    <em class="text-muted">بدون مشخصات فنی</em>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="device_bom.php?id=<?= $device['device_id'] ?>" class="btn btn-info">
                            <i class="bi bi-list-check me-1"></i> لیست قطعات
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editDeviceModal<?= $device['device_id'] ?>">
                            <i class="bi bi-pencil-square me-1"></i> ویرایش دستگاه
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// اسکریپت برای بستن خودکار اعلان‌ها پس از ۵ ثانیه
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
