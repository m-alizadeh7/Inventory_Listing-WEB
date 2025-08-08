<?php
require_once 'config.php';
require_once 'includes/functions.php';

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

// اطمینان از معتبر بودن جهت مرتب‌سازی
if (!in_array(strtolower($order), ['asc', 'desc'])) {
    $order = 'asc';
}

// دریافت لیست دستگاه‌ها
$sql = "SELECT * FROM devices $where_sql ORDER BY $sort $order";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$devices = [];

while ($row = $result->fetch_assoc()) {
    $devices[] = $row;
}

// شمارش تعداد دستگاه‌ها بر اساس وضعیت (در صورت وجود ستون status)
$status_counts = [
    'all' => count($devices),
    'active' => 0,
    'inactive' => 0,
    'maintenance' => 0
];
if (in_array('status', $device_columns)) {
    foreach ($devices as $device) {
        $status = $device['status'] ?? 'active';
        if (isset($status_counts[$status])) $status_counts[$status]++;
    }
}

// تابع ساخت لینک مرتب‌سازی
function get_sort_link($field, $current_sort, $current_order) {
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = ($current_sort === $field && $current_order === 'asc') ? 'desc' : 'asc';
    return '?' . http_build_query($params);
}

// نمایش آیکون مرتب‌سازی
function get_sort_icon($field, $current_sort, $current_order) {
    if ($current_sort !== $field) {
        return '';
    }
    return ($current_order === 'asc') ? '▲' : '▼';
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت فنی دستگاه‌ها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f0f2f5; 
            padding-top: 1.5rem; 
            font-family: 'Tahoma', sans-serif;
        }
        .container {
            max-width: 1400px;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            font-weight: 600;
        }
        .table th {
            white-space: nowrap;
            background-color: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .device-status {
            width: 12px;
            height: 12px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 5px;
        }
        .status-active {
            background-color: #28a745;
        }
        .status-inactive {
            background-color: #dc3545;
        }
        .status-maintenance {
            background-color: #ffc107;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }
        .search-card {
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .search-card .card-body {
            padding: 15px;
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editDeviceModal<?= $device['device_id'] ?>">
                                                        <i class="bi bi-pencil-square text-success me-1"></i> ویرایش
                                                    </a>
                                                </li>
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .table-responsive {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .dropdown-menu {
            min-width: auto;
        }
        .technical-details-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .specs-row {
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .specs-row:last-child {
            border-bottom: none;
        }
        .sort-link {
            color: inherit;
            text-decoration: none;
        }
        .sort-link:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h3 mb-0"><i class="bi bi-tools"></i> مدیریت فنی دستگاه‌ها</h2>
            <p class="text-muted small mb-0">مدیریت، نگهداری و پیگیری وضعیت دستگاه‌های سیستم</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-icon" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                <i class="bi bi-plus-lg"></i>
                افزودن دستگاه جدید
            </button>
            <a href="index.php" class="btn btn-outline-secondary btn-icon">
                <i class="bi bi-house"></i>
                بازگشت
            </a>
        </div>
    <!-- مودال افزودن دستگاه جدید -->
    <div class="modal fade" id="addDeviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-lg me-1"></i> افزودن دستگاه جدید</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">کد دستگاه</label>
                                <input type="text" name="new_device_code" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام دستگاه</label>
                                <input type="text" name="new_device_name" class="form-control" required>
                            </div>
                            <?php if (in_array('model_number', $device_columns)): ?>
                            <div class="col-md-6">
                                <label class="form-label">مدل</label>
                                <input type="text" name="new_model_number" class="form-control">
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('serial_number', $device_columns)): ?>
                            <div class="col-md-6">
                                <label class="form-label">شماره سریال</label>
                                <input type="text" name="new_serial_number" class="form-control">
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('manufacturer', $device_columns)): ?>
                            <div class="col-md-6">
                                <label class="form-label">سازنده</label>
                                <input type="text" name="new_manufacturer" class="form-control">
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('status', $device_columns)): ?>
                            <div class="col-md-6">
                                <label class="form-label">وضعیت</label>
                                <select name="new_status" class="form-select">
                                    <option value="active">فعال</option>
                                    <option value="inactive">غیرفعال</option>
                                    <option value="maintenance">در حال تعمیر</option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('location', $device_columns)): ?>
                            <div class="col-md-6">
                                <label class="form-label">موقعیت</label>
                                <input type="text" name="new_location" class="form-control">
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('description', $device_columns)): ?>
                            <div class="col-12">
                                <label class="form-label">توضیحات</label>
                                <textarea name="new_description" class="form-control" rows="2"></textarea>
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('technical_specs', $device_columns)): ?>
                            <div class="col-12">
                                <label class="form-label">مشخصات فنی</label>
                                <textarea name="new_technical_specs" class="form-control" rows="2"></textarea>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="save_new_device" class="btn btn-success">
                            <i class="bi bi-plus-lg me-1"></i> افزودن
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'added'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                دستگاه جدید با موفقیت اضافه شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                دستگاه با موفقیت حذف شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- کارت آمار سریع -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-primary"><?= $status_counts['all'] ?></h5>
                    <p class="card-text text-muted mb-0">کل دستگاه‌ها</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">

                <div class="card-body">
                    <h5 class="card-title display-5 text-success"><?= $status_counts['active'] ?></h5>
                    <p class="card-text text-muted mb-0">دستگاه‌های فعال</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-danger"><?= $status_counts['inactive'] ?></h5>
                    <p class="card-text text-muted mb-0">دستگاه‌های غیرفعال</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-2">
            <div class="card stats-card text-center h-100">
                <div class="card-body">
                    <h5 class="card-title display-5 text-warning"><?= $status_counts['maintenance'] ?></h5>
                    <p class="card-text text-muted mb-0">در حال تعمیر</p>
                </div>
            </div>
        </div>
    </div>

    <!-- کارت جستجو و فیلتر -->
    <div class="card search-card">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="جستجو در کد، نام، مدل، شماره سریال یا سازنده..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>فعال</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                        <option value="maintenance" <?= $status_filter === 'maintenance' ? 'selected' : '' ?>>در حال تعمیر</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> اعمال فیلتر
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($devices)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill me-2"></i>
            هیچ دستگاهی با معیارهای جستجوی فعلی یافت نشد.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-list-ul me-1"></i>
                    لیست دستگاه‌ها
                </div>
                <div class="text-muted small">
                    <?= count($devices) ?> دستگاه یافت شد
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>کد دستگاه</th>
                                <th>نام دستگاه</th>
                                <?php if (in_array('model_number', $device_columns) || in_array('serial_number', $device_columns)): ?>
                                    <th>مدل / سریال</th>
                                <?php endif; ?>
                                <?php if (in_array('manufacturer', $device_columns)): ?><th>سازنده</th><?php endif; ?>
                                <?php if (in_array('status', $device_columns)): ?><th>وضعیت</th><?php endif; ?>
                                <?php if (in_array('next_maintenance_date', $device_columns)): ?><th>تعمیر بعدی</th><?php endif; ?>
                                <?php if (in_array('location', $device_columns)): ?><th>موقعیت</th><?php endif; ?>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devices as $device): ?>
                                <tr>
                                    <td><code><?= device_col($device, 'device_code') ?></code></td>
                                    <td class="fw-bold"><?= device_col($device, 'device_name') ?></td>
                                    <?php if (in_array('model_number', $device_columns) || in_array('serial_number', $device_columns)): ?>
                                        <td>
                                            <?php if (!empty($device['model_number'])): ?>
                                                <div><small class="text-muted">مدل:</small> <?= device_col($device, 'model_number') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($device['serial_number'])): ?>
                                                <div><small class="text-muted">سریال:</small> <code><?= device_col($device, 'serial_number') ?></code></div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if (in_array('manufacturer', $device_columns)): ?><td><?= device_col($device, 'manufacturer') ?></td><?php endif; ?>
                                    <?php if (in_array('status', $device_columns)): ?>
                                        <td>
                                            <?php 
                                            $status_class = 'secondary';
                                            $status_text = 'نامشخص';
                                            switch ($device['status'] ?? '') {
                                                case 'active': $status_class = 'success'; $status_text = 'فعال'; break;
                                                case 'inactive': $status_class = 'danger'; $status_text = 'غیرفعال'; break;
                                                case 'maintenance': $status_class = 'warning'; $status_text = 'در حال تعمیر'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?= $status_class ?> status-badge">
                                                <span class="device-status status-<?= $device['status'] ?? 'active' ?>"></span>
                                                <?= $status_text ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <?php if (in_array('next_maintenance_date', $device_columns)): ?>
                                        <td>
                                            <?php 
                                            $maintenance_class = '';
                                            $maintenance_warning = '';
                                            if (!empty($device['next_maintenance_date'])) {
                                                $now = new DateTime();
                                                $next_maintenance = new DateTime($device['next_maintenance_date']);
                                                $diff = $now->diff($next_maintenance);
                                                if ($next_maintenance < $now) {
                                                    $maintenance_class = 'text-danger fw-bold';
                                                    $maintenance_warning = '<i class="bi bi-exclamation-triangle-fill text-danger" title="تعمیر با تاخیر"></i>';
                                                } elseif ($diff->days <= 30) {
                                                    $maintenance_class = 'text-warning fw-bold';
                                                    $maintenance_warning = '<i class="bi bi-clock-history text-warning" title="تعمیر نزدیک"></i>';
                                                }
                                            }
                                            ?>
                                            <span class="<?= $maintenance_class ?>">
                                                <?= !empty($device['next_maintenance_date']) ? gregorianToJalali($device['next_maintenance_date']) : '-' ?>
                                                <?= $maintenance_warning ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <?php if (in_array('location', $device_columns)): ?><td><?= device_col($device, 'location') ?></td><?php endif; ?>
                                    <td class="action-buttons">
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editDeviceModal<?= $device['device_id'] ?>">
                                            <i class="bi bi-pencil-square"></i> ویرایش
                                        </button>
                                        <a href="device_bom.php?id=<?= $device['device_id'] ?>" class="btn btn-sm btn-outline-info me-1">
                                            <i class="bi bi-list-check"></i> لیست قطعات
                                        </a>
                                        <form id="delete-form-<?= $device['device_id'] ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="device_id" value="<?= $device['device_id'] ?>">
                                            <button type="submit" name="delete_device" class="btn btn-sm btn-outline-danger" onclick="return confirm('آیا از حذف این دستگاه اطمینان دارید؟');">
                                                <i class="bi bi-trash"></i> حذف
                                            </button>
                                        </form>

                                        <!-- مودال ویرایش دستگاه -->
                                        <div class="modal fade" id="editDeviceModal<?= $device['device_id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> ویرایش دستگاه: <?= device_col($device, 'device_name') ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="edit_device_id" value="<?= $device['device_id'] ?>">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">کد دستگاه</label>
                                                                    <input type="text" name="edit_device_code" class="form-control" value="<?= device_col($device, 'device_code') ?>" required>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">نام دستگاه</label>
                                                                    <input type="text" name="edit_device_name" class="form-control" value="<?= device_col($device, 'device_name') ?>" required>
                                                                </div>
                                                                <?php if (in_array('model_number', $device_columns)): ?>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">مدل</label>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>
