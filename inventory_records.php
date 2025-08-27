<?php
// تابع ساخت لینک مرتب‌سازی (باید قبل از خروجی HTML باشد)
function sort_link($col, $label, $sort, $order) {
    $next_order = ($sort === $col && $order === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($sort === $col) {
        $icon = $order === 'asc' ? '▲' : '▼';
    }
    $params = $_GET;
    $params['sort'] = $col;
    $params['order'] = $next_order;
    $url = '?' . http_build_query($params);
    return "<a href='$url' class='text-decoration-none'>$label $icon</a>";
}
?>
<?php

require_once 'bootstrap.php';

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


// پیدا کردن آخرین جلسه انبارگردانی تایید شده
$lastSession = null;
$lastSessionInfo = null;
$sql = "SELECT s.session_id, s.completed_at
    FROM inventory_sessions s
    WHERE s.status = 'completed'
    ORDER BY s.completed_at DESC LIMIT 1";
$res = $conn->query($sql);
if ($res && $row = $res->fetch_assoc()) {
    $lastSession = $row['session_id'];
    $lastSessionInfo = $row;
}

// دریافت همه کالاهای انبار، حتی اگر در انبارگردانی نباشند
$items = [];
$total = 0;
// پارامترهای جستجو
$search_code = clean($_GET['search_code'] ?? '');
$search_name = clean($_GET['search_name'] ?? '');
$filter = clean($_GET['filter'] ?? '');

$where = [];
$params = [];
$types = '';
if ($search_code) {
    $where[] = "i.inventory_code LIKE ?";
    $params[] = "%$search_code%";
    $types .= 's';
}
if ($search_name) {
    $where[] = "i.item_name LIKE ?";
    $params[] = "%$search_name%";
    $types .= 's';
}
$where_clause = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : '';



// مرتب‌سازی
$sortable_columns = [
    'row_number' => 'ردیف',
    'inventory_code' => 'کد کالا',
    'item_name' => 'نام کالا',
    'unit' => 'واحد',
    'min_inventory' => 'حداقل موجودی',
    'current_inventory' => 'موجودی فعلی',
    'stock_status' => 'وضعیت',
    'supplier' => 'تامین‌کننده',
    'notes' => 'توضیحات',
];
$sort = $_GET['sort'] ?? 'row_number';
$order = strtolower($_GET['order'] ?? 'asc');
if (!array_key_exists($sort, $sortable_columns)) $sort = 'row_number';
if (!in_array($order, ['asc','desc'])) $order = 'asc';
// اگر مرتب‌سازی بر اساس stock_status باشد، بعد از واکشی داده‌ها مرتب‌سازی می‌شود
$order_by_sql = ($sort !== 'stock_status') ? "ORDER BY i.$sort $order" : "ORDER BY i.row_number";

$query = "SELECT i.*, IFNULL(r.current_inventory, i.current_inventory) as session_inventory,
         i.current_inventory as system_inventory
         FROM inventory i
         LEFT JOIN inventory_records r ON r.inventory_id = i.id AND r.inventory_session = ?
         $where_clause
         $order_by_sql";
$params_query = array_merge([$lastSession ?? ''], $params);
$types_query = 's' . $types;
$stmt = $conn->prepare($query);
$stmt->bind_param($types_query, ...$params_query);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$out_of_stock = 0;
$equal_min = 0;
$low_stock = 0;
$sufficient_stock = 0;

while ($row = $result->fetch_assoc()) {
    // انتخاب موجودی فعلی بر اساس انبارگردانی تایید شده یا سیستم
    $row['current_inventory'] = $lastSession ? $row['session_inventory'] : $row['system_inventory'];
    
    // تعیین وضعیت موجودی
    $min_inventory = intval($row['min_inventory'] ?? 0);
    $current = floatval($row['current_inventory'] ?? 0);
    
    if ($current <= 0) {
        $row['stock_status'] = 'out_of_stock';
        $out_of_stock++;
    } elseif ($current == $min_inventory) {
        $row['stock_status'] = 'equal_min';
        $equal_min++;
    } elseif ($current > $min_inventory && $current <= ($min_inventory + 3)) {
        $row['stock_status'] = 'low_stock';
        $low_stock++;
    } else {
        $row['stock_status'] = 'sufficient';
        $sufficient_stock++;
    }
    
    // فیلتر بر اساس وضعیت موجودی
    if ($filter === 'out' && $row['stock_status'] !== 'out_of_stock') continue;
    if ($filter === 'equal' && $row['stock_status'] !== 'equal_min') continue;
    if ($filter === 'low' && $row['stock_status'] !== 'low_stock') continue;
    if ($filter === 'sufficient' && $row['stock_status'] !== 'sufficient') continue;
    
    $items[] = $row;
}
$stmt->close();

$total = count($items);
?>

<?php get_template_part('header'); ?>
    <!-- Debug Info -->
    <?php
    // تعداد کل کالاها در جدول inv_inventory
    $debug_total_items = 0;
    $debug_total_records = 0;
    $debug_last_session = $lastSession ?? '';
    $res_debug = $conn->query("SELECT COUNT(*) as cnt FROM inv_inventory");
    if ($res_debug && $row_debug = $res_debug->fetch_assoc()) {
        $debug_total_items = $row_debug['cnt'];
    }
    if ($debug_last_session) {
        $res_debug2 = $conn->query("SELECT COUNT(*) as cnt FROM inventory_records WHERE inventory_session = '".$conn->real_escape_string($debug_last_session)."'");
        if ($res_debug2 && $row_debug2 = $res_debug2->fetch_assoc()) {
            $debug_total_records = $row_debug2['cnt'];
        }
    }
    ?>
    <div class="alert alert-secondary mb-2">
        <b>🛠️ دیباگ:</b> تعداد کل کالاها در inv_inventory: <b><?= $debug_total_items ?></b> | تعداد رکوردهای inventory_records برای آخرین انبارگردانی: <b><?= $debug_total_records ?></b> | شناسه آخرین انبارگردانی: <b><?= htmlspecialchars($debug_last_session) ?></b>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">📦 مدیریت موجودی انبار</h2>
            <p class="text-muted">مدیریت و جستجوی کالاهای انبار</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-success me-2">
                <i class="bi bi-printer"></i> چاپ لیست
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-right"></i> بازگشت
            </a>
        </div>
    </div>
    
    <?php if ($lastSessionInfo): ?>
    <div class="session-info">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">آخرین انبارگردانی تایید شده:</h5>
                <p class="mb-0">
                    <small>تاریخ تایید: 
                        <?php if (!empty($lastSessionInfo['completed_at'])): ?>
                            <?= date('Y/m/d H:i', strtotime($lastSessionInfo['completed_at'])) ?>
                        <?php else: ?>
                            <span class="text-danger">ثبت نشده</span>
                        <?php endif; ?>
                    </small>
                </p>
            </div>
            <div>
                <span class="badge bg-info">شناسه: <?= $lastSessionInfo['session_id'] ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="print-header" style="border-bottom:2px solid #333;padding-bottom:10px;margin-bottom:20px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="font-weight:bold;letter-spacing:1px;">گزارش مدیریتی موجودی انبار</h2>
                <p style="margin-bottom:0;">تهیه شده برای مدیریت سازمان</p>
            </div>
            <div style="text-align:left;direction:ltr;">
                <span style="font-size:13px;">alizadehx.ir</span>
            </div>
        </div>
        <p style="margin-top:10px;">تاریخ گزارش: <?= date('Y/m/d') ?></p>
        <?php if ($lastSessionInfo): ?>
        <p>بر اساس انبارگردانی تایید شده در تاریخ: 
            <?php if (!empty($lastSessionInfo['completed_at'])): ?>
                <?= date('Y/m/d', strtotime($lastSessionInfo['completed_at'])) ?>
            <?php else: ?>
                <span class="text-danger">ثبت نشده</span>
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success no-print">موجودی با موفقیت به‌روزرسانی شد.</div>
    <?php endif; ?>

    <div class="inventory-status-legend no-print">
        <div class="legend-item out-of-stock">
            <span class="status-indicator">⚠️</span>
            <span>ناموجود</span>
        </div>
        <div class="legend-item equal-min">
            <span class="status-indicator">⚡</span>
            <span>موجود مساوی حداقل موجودی</span>
        </div>
        <div class="legend-item low-stock">
            <span class="status-indicator">🔶</span>
            <span>موجود 1 تا 3 واحد بیشتر از حداقل</span>
        </div>
        <div class="legend-item sufficient">
            <span class="status-indicator">✅</span>
            <span>موجود بیشتر از 3 واحد از حداقل</span>
        </div>
    </div>

    <!-- فرم جستجو -->
    <div class="card mb-4 no-print">
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
                    <label class="form-label">فیلتر وضعیت</label>
                    <select name="filter" class="form-select">
                        <option value="">همه کالاها</option>
                        <option value="out" <?= $filter === 'out' ? 'selected' : '' ?>>ناموجود</option>
                        <option value="equal" <?= $filter === 'equal' ? 'selected' : '' ?>>موجود مساوی حداقل</option>
                        <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>1 تا 3 واحد بیشتر از حداقل</option>
                        <option value="sufficient" <?= $filter === 'sufficient' ? 'selected' : '' ?>>بیشتر از 3 واحد</option>
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
                    <span class="badge bg-danger"><?= $out_of_stock ?> کالا ناموجود</span>
                    <span class="badge bg-warning"><?= $equal_min ?> کالا مساوی حداقل موجودی</span>
                    <span class="badge bg-info"><?= $low_stock ?> کالا 1 تا 3 واحد بیشتر از حداقل</span>
                    <span class="badge bg-success"><?= $sufficient_stock ?> کالا با موجودی کافی</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th><?= sort_link('row_number','ردیف',$sort,$order) ?></th>
                            <th><?= sort_link('inventory_code','کد کالا',$sort,$order) ?></th>
                            <th><?= sort_link('item_name','نام کالا',$sort,$order) ?></th>
                            <th><?= sort_link('unit','واحد',$sort,$order) ?></th>
                            <th><?= sort_link('min_inventory','حداقل موجودی',$sort,$order) ?></th>
                            <th><?= sort_link('current_inventory','موجودی فعلی',$sort,$order) ?></th>
                            <th><?= sort_link('stock_status','وضعیت',$sort,$order) ?></th>
                            <th><?= sort_link('supplier','تامین‌کننده',$sort,$order) ?></th>
                            <th><?= sort_link('notes','توضیحات',$sort,$order) ?></th>
                            <th class="no-print">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $row_class = '';
                            switch ($item['stock_status']) {
                                case 'out_of_stock':
                                    $row_class = 'out-of-stock';
                                    $status_icon = '<span class="status-indicator" title="ناموجود">⚠️</span>';
                                    $status_text = 'ناموجود';
                                    break;
                                case 'equal_min':
                                    $row_class = 'equal-min';
                                    $status_icon = '<span class="status-indicator" title="موجود مساوی حداقل موجودی">⚡</span>';
                                    $status_text = 'مساوی حداقل';
                                    break;
                                case 'low_stock':
                                    $row_class = 'low-stock';
                                    $status_icon = '<span class="status-indicator" title="موجود 1 تا 3 واحد بیشتر از حداقل">🔶</span>';
                                    $status_text = '1 تا 3 واحد بیشتر';
                                    break;
                                case 'sufficient':
                                    $row_class = 'sufficient';
                                    $status_icon = '<span class="status-indicator" title="موجود بیشتر از 3 واحد از حداقل">✅</span>';
                                    $status_text = 'موجودی کافی';
                                    break;
                                default:
                                    $status_icon = '';
                                    $status_text = '';
                            }
                        ?>
                            <tr class="<?= $row_class ?>">
                                <td><?= $item['row_number'] ?></td>
                                <td><?= htmlspecialchars($item['inventory_code']) ?></td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                <td><?= $item['min_inventory'] ?? 0 ?></td>
                                <td>
                                    <form method="POST" class="d-inline no-print" onsubmit="return confirm('آیا از به‌روزرسانی موجودی اطمینان دارید؟')">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <input type="number" name="current_inventory" value="<?= $item['current_inventory'] ?? 0 ?>" class="form-control form-control-sm d-inline-block" style="width: 80px;">
                                        <button type="submit" name="update_inventory" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                    <span class="d-none d-print-inline"><?= $item['current_inventory'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <?= $status_icon ?> <?= $status_text ?>
                                </td>
                                <td><?= htmlspecialchars($item['supplier'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['notes'] ?? '') ?></td>
                                <td class="no-print">
                                    <button class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($item['inventory_code']) ?>');this.innerHTML='کپی شد!';setTimeout(()=>{this.innerHTML='<i class=\'bi bi-clipboard\'></i>'},1200)" title="کپی کد کالا">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
    

    <div class="print-footer" style="border-top:2px solid #333;margin-top:30px;padding-top:10px;">
        <p style="font-size:13px;">این گزارش مدیریتی توسط سیستم انبارداری <?= htmlspecialchars(getBusinessInfo()['business_name']) ?> تهیه شده است.</p>
        <p style="font-size:13px;direction:ltr;text-align:left;">alizadehx.ir</p>
    </div>
<?php endif; ?>
</div>
