<?php
require_once 'config.php';
require_once 'includes/functions.php';

// بررسی وجود جدول inventory
$res = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory (
        id INT AUTO_INCREMENT,
        row_number INT NULL,
        inventory_code VARCHAR(50) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        unit VARCHAR(50) NULL,
        min_inventory INT NULL,
        supplier VARCHAR(100) NULL,
        current_inventory DOUBLE NULL,
        required DOUBLE NULL,
        notes VARCHAR(255) NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory: ' . $conn->error);
    }
}

// ویرایش موجودی کالا
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $id = clean($_POST['id']);
    $current_inventory = clean($_POST['current_inventory']);
    
    $stmt = $conn->prepare("UPDATE inventory SET current_inventory = ? WHERE id = ?");
    $stmt->bind_param("di", $current_inventory, $id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: inventory_records.php?msg=updated");
    exit;
}

// پارامترهای جستجو
$search_code = clean($_GET['search_code'] ?? '');
$search_name = clean($_GET['search_name'] ?? '');
$filter = clean($_GET['filter'] ?? '');

// ساخت شرط جستجو
$where = [];
$params = [];
$types = '';

if ($search_code) {
    $where[] = "inventory_code LIKE ?";
    $params[] = "%$search_code%";
    $types .= 's';
}

if ($search_name) {
    $where[] = "item_name LIKE ?";
    $params[] = "%$search_name%";
    $types .= 's';
}

if ($filter === 'low') {
    $where[] = "current_inventory < min_inventory";
}

if ($filter === 'out') {
    $where[] = "(current_inventory = 0 OR current_inventory IS NULL)";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// تعداد کل رکوردها
$total_query = "SELECT COUNT(*) as total FROM inventory $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
} else {
    $total = $conn->query($total_query)->fetch_assoc()['total'];
}

// پارامترهای صفحه‌بندی
$records_per_page = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;
$total_pages = ceil($total / $records_per_page);

// دریافت رکوردها
$query = "SELECT * FROM inventory $where_clause ORDER BY row_number LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مدیریت موجودی انبار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .low-stock { background-color: #fff3cd; }
        .out-of-stock { background-color: #f8d7da; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">📦 مدیریت موجودی انبار</h2>
            <p class="text-muted">مدیریت و جستجوی کالاهای انبار</p>
        </div>
        <div>
            <a href="import_inventory.php" class="btn btn-primary">
                <i class="bi bi-upload"></i> وارد کردن لیست
            </a>
            <a href="index.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">موجودی با موفقیت به‌روزرسانی شد.</div>
    <?php endif; ?>

    <!-- فرم جستجو -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">🔍 جستجو و فیلتر</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">کد کالا</label>
                    <input type="text" name="search_code" class="form-control" value="<?= htmlspecialchars($search_code) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">نام کالا</label>
                    <input type="text" name="search_name" class="form-control" value="<?= htmlspecialchars($search_name) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">فیلتر</label>
                    <select name="filter" class="form-select">
                        <option value="">همه کالاها</option>
                        <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>موجودی کم</option>
                        <option value="out" <?= $filter === 'out' ? 'selected' : '' ?>>اتمام موجودی</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول کالاها -->
    <?php if (empty($items)): ?>
        <div class="alert alert-info">هیچ کالایی یافت نشد.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <strong>تعداد کالاها:</strong> <?= $total ?>
                </div>
                <div>
                    <?php
                    $low_stock = 0;
                    $out_of_stock = 0;
                    foreach ($items as $item) {
                        if ($item['current_inventory'] == 0) {
                            $out_of_stock++;
                        } elseif ($item['current_inventory'] < $item['min_inventory']) {
                            $low_stock++;
                        }
                    }
                    ?>
                    <span class="badge bg-warning"><?= $low_stock ?> کالا با موجودی کم</span>
                    <span class="badge bg-danger"><?= $out_of_stock ?> کالا بدون موجودی</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>ردیف</th>
                            <th>کد کالا</th>
                            <th>نام کالا</th>
                            <th>واحد</th>
                            <th>حداقل موجودی</th>
                            <th>موجودی فعلی</th>
                            <th>تامین‌کننده</th>
                            <th>توضیحات</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $row_class = '';
                            if ($item['current_inventory'] == 0) {
                                $row_class = 'out-of-stock';
                            } elseif ($item['min_inventory'] && $item['current_inventory'] < $item['min_inventory']) {
                                $row_class = 'low-stock';
                            }
                        ?>
                            <tr class="<?= $row_class ?>">
                                <td><?= $item['row_number'] ?></td>
                                <td><?= htmlspecialchars($item['inventory_code']) ?></td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                <td><?= $item['min_inventory'] ?? 0 ?></td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('آیا از به‌روزرسانی موجودی اطمینان دارید؟')">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <input type="number" name="current_inventory" value="<?= $item['current_inventory'] ?? 0 ?>" class="form-control form-control-sm d-inline-block" style="width: 80px;">
                                        <button type="submit" name="update_inventory" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($item['supplier'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['notes'] ?? '') ?></td>
                                <td>
                                    <a href="save_inventory.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- صفحه‌بندی -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="صفحه‌بندی" class="mt-3">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search_code=<?= urlencode($search_code) ?>&search_name=<?= urlencode($search_name) ?>&filter=<?= urlencode($filter) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
