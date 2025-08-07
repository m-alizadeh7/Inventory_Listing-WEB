<?php
require_once 'config.php';
session_start();

// بررسی و ایجاد جدول inventory اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        `row_number` INT NOT NULL,
        inventory_code VARCHAR(50) NOT NULL,
        item_name VARCHAR(255) NOT NULL,
        unit VARCHAR(50),
        min_inventory INT,
        supplier VARCHAR(255),
        current_inventory FLOAT,
        required FLOAT,
        notes TEXT,
        last_updated DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول inventory_records اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_records'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        inventory_id INT NOT NULL,
        inventory_session VARCHAR(50) NOT NULL,
        current_inventory FLOAT,
        required FLOAT,
        notes TEXT,
        updated_at DATETIME,
        completed_by VARCHAR(255),
        completed_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_records: ' . $conn->error);
    }
}

// بررسی و ایجاد جدول inventory_sessions اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'inventory_sessions'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE inventory_sessions (
        session_id VARCHAR(64) PRIMARY KEY,
        status VARCHAR(20) DEFAULT 'draft',
        completed_by VARCHAR(100) NULL,
        completed_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول inventory_sessions: ' . $conn->error);
    }
}

// اطمینان از وجود ستون‌های completed_by و completed_at
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'completed_by'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN completed_by VARCHAR(100) NULL");
}
$res = $conn->query("SHOW COLUMNS FROM inventory_sessions LIKE 'completed_at'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE inventory_sessions ADD COLUMN completed_at DATETIME NULL");
}

// ایجاد یا بازیابی جلسه انبارداری
if (!isset($_SESSION['inventory_session'])) {
    $_SESSION['inventory_session'] = uniqid('inv_');
    // ایجاد جلسه جدید در پایگاه داده
    $stmt = $conn->prepare("INSERT INTO inventory_sessions (session_id, status) VALUES (?, 'draft') ON DUPLICATE KEY UPDATE status = status");
    $stmt->bind_param("s", $_SESSION['inventory_session']);
    $stmt->execute();
    $stmt->close();
} else {
    // اطمینان از وجود جلسه در دیتابیس
    $checkStmt = $conn->prepare("SELECT session_id FROM inventory_sessions WHERE session_id = ?");
    $checkStmt->bind_param("s", $_SESSION['inventory_session']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        // اگر جلسه در دیتابیس وجود ندارد، آن را ایجاد کن
        $stmt = $conn->prepare("INSERT INTO inventory_sessions (session_id, status) VALUES (?, 'draft')");
        $stmt->bind_param("s", $_SESSION['inventory_session']);
        $stmt->execute();
        $stmt->close();
    }
    $checkStmt->close();
}

// Check and create 'notes' column in 'inventory_records' table if missing
$res = $conn->query("SHOW COLUMNS FROM inventory_records LIKE 'notes'");
if ($res && $res->num_rows === 0) {
    if (!$conn->query("ALTER TABLE inventory_records ADD COLUMN notes TEXT NULL")) {
        die('Error adding notes column to inventory_records: ' . $conn->error);
    }
}

// خواندن اقلام انبار و مقادیر ثبت شده قبلی
$sql = "SELECT i.*, r.current_inventory as recorded_inventory, r.notes as recorded_notes 
        FROM inventory i 
        LEFT JOIN inventory_records r ON i.id = r.inventory_id 
        AND r.inventory_session = ?
        ORDER BY i.row_number";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['inventory_session']);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// بررسی وضعیت جلسه
$stmt = $conn->prepare("SELECT status FROM inventory_sessions WHERE session_id = ?");
$stmt->bind_param("s", $_SESSION['inventory_session']);
$stmt->execute();
$statusRow = $stmt->get_result()->fetch_assoc();
$session_status = $statusRow ? $statusRow['status'] : 'draft';
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>انبارگردانی جدید</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f7; padding-top: 2rem; }
        .sticky-header { position: sticky; top: 0; background: #f8f9fa; z-index: 1000; }
        .table-responsive { max-height: calc(100vh - 250px); }
        @media (max-width: 768px) {
            .container { padding: 0; }
            .table-responsive { margin: 0; }
            .mobile-full { width: 100% !important; }
        }
        .modified-row { background-color: #fff3cd; }
        .saved-row { background-color: #d1e7dd; }
    </style>
</head>
<body>
<div class="container">
    <div class="sticky-header pb-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>📦 انبارگردانی (جلسه: <?= $_SESSION['inventory_session'] ?>)</h2>
            <span class="badge bg-<?= $session_status == 'draft' ? 'warning' : 'success' ?>">
                <?= $session_status == 'draft' ? 'در حال انجام' : 'تکمیل شده' ?>
            </span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" id="searchInput" class="form-control" placeholder="جستجو در نام کالا...">
            </div>
            <div class="col-md-8 text-end">
                <button type="button" class="btn btn-primary" onclick="saveAll(false)">ذخیره موقت</button>
                <button type="button" class="btn btn-success" onclick="showFinalizeModal()">پایان انبارگردانی</button>
            </div>
        </div>
    </div>

    <form id="inventoryForm" class="mb-4">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ردیف</th>
                        <th>کد انبار</th>
                        <th>نام کالا</th>
                        <th>واحد</th>
                        <th>موجودی فعلی</th>
                        <th>توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr data-item-id="<?= $item['id'] ?>" class="<?= $item['recorded_inventory'] ? 'saved-row' : '' ?>">
                        <td><?= htmlspecialchars($item['row_number']) ?></td>
                        <td><?= htmlspecialchars($item['inventory_code']) ?></td>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= htmlspecialchars($item['unit']) ?></td>
                        <td>
                            <input type="number" class="form-control inventory-input" 
                                   value="<?= htmlspecialchars($item['recorded_inventory'] ?? '') ?>" 
                                   step="0.01" onchange="markModified(this)">
                        </td>
                        <td>
                            <input type="text" class="form-control notes-input" 
                                   value="<?= htmlspecialchars($item['recorded_notes'] ?? '') ?>"
                                   onchange="markModified(this)">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- مودال نهایی کردن -->
    <div class="modal fade" id="finalizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">نهایی کردن انبارگردانی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">نام مسئول</label>
                        <input type="text" id="completedBy" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاریخ</label>
                        <input type="date" id="completedAt" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-success" onclick="finalizeInventory()">تایید و پایان</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function markModified(input) {
    const row = input.closest('tr');
    row.classList.remove('saved-row');
    row.classList.add('modified-row');
}

function saveAll(isFinalize = false) {
    const rows = document.querySelectorAll('tr[data-item-id]');
    const data = [];
    
    rows.forEach(row => {
        if (row.classList.contains('modified-row') || isFinalize) {
            data.push({
                item_id: row.dataset.itemId,
                current_inventory: row.querySelector('.inventory-input').value,
                notes: row.querySelector('.notes-input').value
            });
        }
    });

    if (data.length === 0 && !isFinalize) {
        alert('هیچ تغییری برای ذخیره وجود ندارد.');
        return;
    }

    fetch('save_inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items: data, finalize: isFinalize })
    })
    .then(response => {
        console.log('Save response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Save response data:', result);
        if (result.success) {
            if (!isFinalize) {
                document.querySelectorAll('.modified-row').forEach(row => {
                    row.classList.remove('modified-row');
                    row.classList.add('saved-row');
                });
                alert('اطلاعات با موفقیت ذخیره شد.');
            }
        } else {
            alert('خطا در ذخیره اطلاعات: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Save error details:', error);
        alert('خطا در ارتباط با سرور: ' + error.message);
    });
}

function showFinalizeModal() {
    const modal = new bootstrap.Modal(document.getElementById('finalizeModal'));
    modal.show();
}

function finalizeInventory() {
    const completedBy = document.getElementById('completedBy').value;
    const completedAt = document.getElementById('completedAt').value;
    
    if (!completedBy || !completedAt) {
        alert('لطفاً تمام فیلدها را پر کنید.');
        return;
    }

    fetch('finalize_inventory.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            completed_by: completedBy,
            completed_at: completedAt
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        console.log('Response data:', result);
        if (result.success) {
            alert('انبارگردانی با موفقیت نهایی شد.');
            window.location.href = 'index.php';
        } else {
            alert('خطا در نهایی کردن انبارگردانی: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        alert('خطا در ارتباط با سرور: ' + error.message);
    });
}

// جستجو در جدول
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const itemName = row.children[2].textContent.toLowerCase();
        row.style.display = itemName.includes(searchText) ? '' : 'none';
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>