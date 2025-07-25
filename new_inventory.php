<?php
require_once 'config.php';
session_start();

// ایجاد شناسه یکتا برای جلسه انبارداری
if (!isset($_SESSION['inventory_session'])) {
    $_SESSION['inventory_session'] = uniqid('inv_');
}

// خواندن اقلام انبار
$result = $conn->query("SELECT * FROM inventory ORDER BY `row_number`");
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>انبارداری جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .table-responsive { max-height: 500px; }
    </style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">📦 انبارداری جدید (جلسه: <?= $_SESSION['inventory_session'] ?>)</h2>
    <form action="save_inventory.php" method="POST" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">نام کالا</label>
            <select name="item_id" class="form-control" required>
                <option value="">انتخاب کالا</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['item_name']) ?> (کد: <?= $item['inventory_code'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">موجودی فعلی</label>
            <input type="number" name="current_inventory" class="form-control" step="0.01" required>
        </div>
        <div class="col-md-5">
            <label class="form-label">توضیحات</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">ذخیره</button>
        </div>
    </form>

    <hr class="my-4">
    <h4>📋 اقلام ثبت‌شده در این انبارداری</h4>
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="جستجو در نام کالا...">

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>کد انبار</th>
                    <th>نام کالا</th>
                    <th>واحد</th>
                    <th>موجودی فعلی</th>
                    <th>مورد نیاز</th>
                    <th>توضیحات</th>
                    <th>زمان ثبت</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php
                $result = $conn->query("SELECT i.`row_number`, i.`inventory_code`, i.`item_name`, i.`unit`, r.`current_inventory`, r.`required`, r.`notes`, r.`updated_at`
                                        FROM `inventory_records` r
                                        JOIN `inventory` i ON r.`inventory_id` = i.`id`
                                        WHERE r.`inventory_session` = '" . $conn->real_escape_string($_SESSION['inventory_session']) . "'
                                        ORDER BY i.`row_number`");
                while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['row_number']) ?></td>
                        <td><?= htmlspecialchars($row['inventory_code']) ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars($row['unit']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['current_inventory']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['required']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['notes']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['updated_at']) ?: '-' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <hr class="my-4">
    <h4>🏁 نهایی کردن انبارداری</h4>
    <form action="finalize_inventory.php" method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">نام مسئول</label>
            <input type="text" name="completed_by" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">تاریخ</label>
            <input type="date" name="completed_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success">پایان انبارداری و ارسال گزارش</button>
        </div>
    </form>
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('tableBody');
    searchInput.addEventListener('input', () => {
        const value = searchInput.value.toLowerCase();
        [...tableBody.rows].forEach(row => {
            row.style.display = row.cells[2].textContent.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>
</body>
</html>
<?php $conn->close(); ?>