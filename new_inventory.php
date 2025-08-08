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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f7f7f7; 
            padding-top: 1rem; 
            font-family: Tahoma, Arial, sans-serif;
        }
        .sticky-header { 
            position: sticky; 
            top: 0; 
            background: #f7f7f7; 
            z-index: 1000; 
            padding: 10px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .table-responsive { 
            max-height: calc(100vh - 270px);
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.125);
            font-weight: bold;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn {
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-icon {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .table th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 10;
        }
        .modified-row { background-color: #fff3cd; }
        .saved-row { background-color: #d1e7dd; }
        
        /* تغییرات برای موبایل */
        @media (max-width: 768px) {
            .container { 
                padding: 0 10px; 
                max-width: 100%; 
            }
            .action-buttons {
                justify-content: space-between;
                width: 100%;
            }
            .action-buttons .btn {
                flex: 1;
                padding: 8px 5px;
                font-size: 0.9rem;
            }
            .btn-text {
                display: none;
            }
            .sticky-header h2 {
                font-size: 1.3rem;
            }
            .search-box {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sticky-header mb-3 p-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">
                <i class="bi bi-box-seam"></i> انبارگردانی جدید
            </h2>
            <span class="badge bg-<?= $session_status == 'draft' ? 'warning' : 'success' ?> px-3 py-2">
                <i class="bi bi-<?= $session_status == 'draft' ? 'pencil-square' : 'check-circle' ?>"></i>
                <?= $session_status == 'draft' ? 'در حال انجام' : 'تکمیل شده' ?>
            </span>
        </div>
        
        <div class="small text-muted mb-3">شماره جلسه: <?= $_SESSION['inventory_session'] ?></div>

        <div class="row g-3">
            <div class="col-md-5 col-12 search-box">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="جستجو در نام کالا...">
                </div>
            </div>
            <div class="col-md-7 col-12">
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-outline-secondary btn-icon">
                        <i class="bi bi-house"></i>
                        <span class="btn-text">بازگشت به منو</span>
                    </a>
                    <button type="button" class="btn btn-primary btn-icon" onclick="saveAll(false)">
                        <i class="bi bi-save"></i>
                        <span class="btn-text">ذخیره موقت</span>
                    </button>
                    <button type="button" class="btn btn-success btn-icon" onclick="showFinalizeModal()">
                        <i class="bi bi-check2-circle"></i>
                        <span class="btn-text">پایان انبارگردانی</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center p-3">
            <div>
                <i class="bi bi-list-check"></i> لیست کالاها
            </div>
            <div class="small text-muted"><?= count($items) ?> کالا</div>
        </div>
        <div class="card-body p-0">
            <form id="inventoryForm">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th width="60">ردیف</th>
                                <th width="100">کد انبار</th>
                                <th>نام کالا</th>
                                <th width="80">واحد</th>
                                <th width="120">موجودی</th>
                                <th width="200">توضیحات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr data-item-id="<?= $item['id'] ?>" class="<?= $item['recorded_inventory'] ? 'saved-row' : '' ?>">
                                <td class="text-muted"><?= htmlspecialchars($item['row_number']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($item['inventory_code']) ?></td>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($item['unit']) ?></td>
                                <td>
                                    <input type="number" class="form-control form-control-sm inventory-input" 
                                           value="<?= htmlspecialchars($item['recorded_inventory'] ?? '-') ?>" 
                                           step="0.01" onchange="markModified(this)">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm notes-input" 
                                           value="<?= htmlspecialchars($item['recorded_notes'] ?? '') ?>"
                                           onchange="markModified(this)">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- راهنمای رنگ‌ها -->
    <div class="d-flex gap-3 justify-content-end mb-4 flex-wrap">
        <div class="d-flex align-items-center">
            <span class="d-inline-block me-2" style="width:20px;height:20px;background-color:#d1e7dd;border-radius:3px;"></span>
            <small>ذخیره شده</small>
        </div>
        <div class="d-flex align-items-center">
            <span class="d-inline-block me-2" style="width:20px;height:20px;background-color:#fff3cd;border-radius:3px;"></span>
            <small>تغییر یافته (ذخیره نشده)</small>
        </div>
    </div>

    <!-- مودال نهایی کردن -->
    <div class="modal fade" id="finalizeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-check2-circle"></i> نهایی کردن انبارگردانی</h5>
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
        showToast('هیچ تغییری برای ذخیره وجود ندارد.', 'warning');
        return;
    }

    // نمایش وضعیت درحال بارگذاری
    const saveBtn = document.querySelector('.btn-primary');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال ذخیره...';

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
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        
        if (result.success) {
            if (!isFinalize) {
                document.querySelectorAll('.modified-row').forEach(row => {
                    row.classList.remove('modified-row');
                    row.classList.add('saved-row');
                });
                showToast('اطلاعات با موفقیت ذخیره شد.', 'success');
            }
        } else {
            showToast('خطا در ذخیره اطلاعات: ' + result.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Save error details:', error);
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
        showToast('خطا در ارتباط با سرور: ' + error.message, 'danger');
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
        showToast('لطفاً تمام فیلدها را پر کنید.', 'warning');
        return;
    }

    // نمایش وضعیت درحال بارگذاری
    const finalizeBtn = document.querySelector('#finalizeModal .btn-success');
    const originalText = finalizeBtn.innerHTML;
    finalizeBtn.disabled = true;
    finalizeBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال نهایی‌سازی...';

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
        finalizeBtn.disabled = false;
        finalizeBtn.innerHTML = originalText;
        
        if (result.success) {
            showToast('انبارگردانی با موفقیت نهایی شد.', 'success');
            setTimeout(() => window.location.href = 'index.php', 1500);
        } else {
            showToast('خطا در نهایی کردن انبارگردانی: ' + result.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error details:', error);
        finalizeBtn.disabled = false;
        finalizeBtn.innerHTML = originalText;
        showToast('خطا در ارتباط با سرور: ' + error.message, 'danger');
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

// نمایش پیام toast
function showToast(message, type = 'info') {
    // ایجاد toast container اگر وجود ندارد
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // ایجاد toast
    const toastId = 'toast-' + Date.now();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('id', toastId);
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();
    
    // حذف toast پس از مخفی شدن
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// اضافه کردن قابلیت پیمایش خودکار بعد از ویرایش
document.querySelectorAll('.inventory-input, .notes-input').forEach(input => {
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // پیدا کردن ردیف بعدی
            const currentRow = this.closest('tr');
            const nextRow = currentRow.nextElementSibling;
            if (nextRow) {
                // تمرکز روی فیلد مشابه در ردیف بعدی
                const isInventoryInput = this.classList.contains('inventory-input');
                const nextInput = isInventoryInput 
                    ? nextRow.querySelector('.inventory-input')
                    : nextRow.querySelector('.notes-input');
                nextInput.focus();
            }
        }
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>