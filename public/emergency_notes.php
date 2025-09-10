<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// Ensure emergency_inventory_notes table exists
$conn->query("CREATE TABLE IF NOT EXISTS emergency_inventory_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_code VARCHAR(100) NOT NULL,
    note_type VARCHAR(100) NULL,
    message TEXT NULL,
    priority VARCHAR(20) DEFAULT 'medium',
    created_by VARCHAR(255) NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolution_notes TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_emergency_note'])) {
        $inventory_code = clean($_POST['inventory_code']);
        $note_type = clean($_POST['note_type']);
        $message = clean($_POST['message']);
        $priority = clean($_POST['priority']);
        $created_by = clean($_POST['created_by']);
        
        $stmt = $conn->prepare("
            INSERT INTO emergency_inventory_notes 
            (inventory_code, note_type, message, priority, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssss', $inventory_code, $note_type, $message, $priority, $created_by);
        
        if ($stmt->execute()) {
            set_flash_message('یادداشت اضطراری ثبت شد', 'success');
        } else {
            set_flash_message('خطا در ثبت یادداشت: ' . $conn->error, 'danger');
        }
        
        header('Location: emergency_notes.php');
        exit;
    }
    
    if (isset($_POST['resolve_note'])) {
        $note_id = (int)$_POST['note_id'];
        $resolution_notes = clean($_POST['resolution_notes']);
        
        $stmt = $conn->prepare("
            UPDATE emergency_inventory_notes 
            SET status = 'resolved', resolved_at = NOW(), resolution_notes = ? 
            WHERE note_id = ?
        ");
        $stmt->bind_param('si', $resolution_notes, $note_id);
        
        if ($stmt->execute()) {
            set_flash_message('یادداشت اضطراری حل شد', 'success');
        } else {
            set_flash_message('خطا در حل یادداشت: ' . $conn->error, 'danger');
        }
        
        header('Location: emergency_notes.php');
        exit;
    }
    
    if (isset($_POST['delete_note'])) {
        $note_id = (int)$_POST['note_id'];
        
        $stmt = $conn->prepare("DELETE FROM emergency_inventory_notes WHERE note_id = ?");
        $stmt->bind_param('i', $note_id);
        
        if ($stmt->execute()) {
            set_flash_message('یادداشت اضطراری حذف شد', 'success');
        } else {
            set_flash_message('خطا در حذف یادداشت: ' . $conn->error, 'danger');
        }
        
        header('Location: emergency_notes.php');
        exit;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'active';
$priority_filter = $_GET['priority'] ?? '';
$type_filter = $_GET['type'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$types = '';

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "ein.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($priority_filter) {
    $where_conditions[] = "ein.priority = ?";
    $params[] = $priority_filter;
    $types .= 's';
}

if ($type_filter) {
    $where_conditions[] = "ein.note_type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get emergency notes
$notes = [];
$sql = "
    SELECT ein.*, i.item_name, ic.category_name,
           DATE_FORMAT(ein.created_at, '%Y/%m/%d %H:%i') as formatted_date,
           DATE_FORMAT(ein.resolved_at, '%Y/%m/%d %H:%i') as formatted_resolved_date
    FROM emergency_inventory_notes ein
    JOIN inventory i ON ein.inventory_code COLLATE utf8mb4_unicode_ci = i.inventory_code COLLATE utf8mb4_unicode_ci
    LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
    $where_clause
    ORDER BY 
        CASE ein.priority 
            WHEN 'critical' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'medium' THEN 3 
            WHEN 'low' THEN 4 
        END,
        ein.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
}

// Get statistics
$stats = [];
$stats['total_notes'] = $conn->query("SELECT COUNT(*) as count FROM emergency_inventory_notes")->fetch_assoc()['count'];
$stats['active_notes'] = $conn->query("SELECT COUNT(*) as count FROM emergency_inventory_notes WHERE status = 'active'")->fetch_assoc()['count'];
$stats['critical_notes'] = $conn->query("SELECT COUNT(*) as count FROM emergency_inventory_notes WHERE status = 'active' AND priority = 'critical'")->fetch_assoc()['count'];
$stats['resolved_notes'] = $conn->query("SELECT COUNT(*) as count FROM emergency_inventory_notes WHERE status = 'resolved'")->fetch_assoc()['count'];

// Note types and priorities
$note_types = [
    'urgent_reorder' => 'سفارش فوری',
    'quality_issue' => 'مشکل کیفی',
    'damage_report' => 'گزارش آسیب',
    'shortage_alert' => 'هشدار کمبود',
    'supplier_issue' => 'مشکل تامین‌کننده',
    'production_delay' => 'تاخیر تولید',
    'storage_problem' => 'مشکل انبارداری',
    'other' => 'سایر موارد'
];

$priorities = [
    'critical' => 'بحرانی',
    'high' => 'زیاد',
    'medium' => 'متوسط',
    'low' => 'کم'
];

$page_title = 'یادداشت‌های اضطراری انبار';
$page_description = 'مدیریت یادداشت‌های اضطراری و هشدارهای انبارداری';

get_header();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0"><?php echo $page_title; ?></h2>
            <p class="text-muted mb-0"><?php echo $page_description; ?></p>
        </div>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#emergencyNoteModal">
            <i class="fas fa-exclamation-triangle me-2"></i>ثبت یادداشت اضطراری
        </button>
    </div>

    <?php display_flash_messages(); ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_notes']); ?></h4>
                            <small>کل یادداشت‌ها</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['active_notes']); ?></h4>
                            <small>یادداشت‌های فعال</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['critical_notes']); ?></h4>
                            <small>یادداشت‌های بحرانی</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['resolved_notes']); ?></h4>
                            <small>یادداشت‌های حل شده</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">وضعیت</label>
                    <select class="form-control" id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>همه</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>فعال</option>
                        <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>حل شده</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="priority" class="form-label">اولویت</label>
                    <select class="form-control" id="priority" name="priority">
                        <option value="">همه اولویت‌ها</option>
                        <?php foreach ($priorities as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $priority_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">نوع یادداشت</label>
                    <select class="form-control" id="type" name="type">
                        <option value="">همه انواع</option>
                        <?php foreach ($note_types as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">اعمال فیلتر</button>
                        <a href="emergency_notes.php" class="btn btn-outline-secondary">حذف فیلتر</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notes Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">یادداشت‌های اضطراری</h5>
            <div>
                <button class="btn btn-outline-primary btn-sm" onclick="exportTable('notesTable', 'emergency_notes')">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="printTable('notesTable')">
                    <i class="fas fa-print me-1"></i>پرینت
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($notes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                    <h5>هیچ یادداشت اضطراری یافت نشد</h5>
                    <p class="text-muted">برای ثبت یادداشت اضطراری از دکمه بالا استفاده کنید</p>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#emergencyNoteModal">
                        ثبت یادداشت اضطراری
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="notesTable">
                        <thead class="table-dark">
                            <tr>
                                <th>اولویت</th>
                                <th>نوع</th>
                                <th>کد کالا</th>
                                <th>نام کالا</th>
                                <th>گروه</th>
                                <th>پیام</th>
                                <th>ایجادکننده</th>
                                <th>تاریخ ایجاد</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notes as $note): ?>
                                <tr class="<?php echo $note['priority'] === 'critical' ? 'table-danger' : ''; ?>">
                                    <td>
                                        <?php 
                                        $priority_classes = [
                                            'critical' => 'bg-danger',
                                            'high' => 'bg-warning',
                                            'medium' => 'bg-info',
                                            'low' => 'bg-secondary'
                                        ];
                                        $priority_class = $priority_classes[$note['priority']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $priority_class; ?>">
                                            <?php echo $priorities[$note['priority']] ?? $note['priority']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark">
                                            <?php echo $note_types[$note['note_type']] ?? $note['note_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($note['inventory_code']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($note['item_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($note['category_name']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($note['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <?php echo nl2br(htmlspecialchars($note['message'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($note['created_by']); ?>
                                    </td>
                                    <td>
                                        <small><?php echo $note['formatted_date']; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($note['status'] === 'active'): ?>
                                            <span class="badge bg-warning">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">حل شده</span>
                                            <?php if ($note['formatted_resolved_date']): ?>
                                                <br><small class="text-muted"><?php echo $note['formatted_resolved_date']; ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if ($note['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        onclick="resolveNote(<?php echo $note['note_id']; ?>)" title="حل مشکل">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="viewNote(<?php echo $note['note_id']; ?>)" title="مشاهده جزئیات">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteNote(<?php echo $note['note_id']; ?>)" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Emergency Note Modal -->
<div class="modal fade" id="emergencyNoteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">ثبت یادداشت اضطراری</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="inventory_code" class="form-label">کد کالا <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="inventory_code" name="inventory_code" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="searchItemForNote()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="itemInfoNote" class="mt-2" style="display: none;"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">اولویت <span class="text-danger">*</span></label>
                            <select class="form-control" id="priority" name="priority" required>
                                <option value="">انتخاب اولویت</option>
                                <?php foreach ($priorities as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="note_type" class="form-label">نوع یادداشت <span class="text-danger">*</span></label>
                            <select class="form-control" id="note_type" name="note_type" required>
                                <option value="">انتخاب نوع</option>
                                <?php foreach ($note_types as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="created_by" class="form-label">ایجادکننده <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="created_by" name="created_by" required
                                   placeholder="نام مسئول ایجادکننده یادداشت">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">پیام اضطراری <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="message" name="message" rows="4" required
                                  placeholder="شرح کامل مشکل یا وضعیت اضطراری..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>نکته:</strong> یادداشت‌های اضطراری برای اطلاع‌رسانی سریع مشکلات و وضعیت‌های ویژه انبار استفاده می‌شوند.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_emergency_note" class="btn btn-danger">ثبت یادداشت اضطراری</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Resolve Note Modal -->
<div class="modal fade" id="resolveNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">حل یادداشت اضطراری</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="note_id" id="resolve_note_id">
                    
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">شرح حل مشکل</label>
                        <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3"
                                  placeholder="توضیح اقدامات انجام شده برای حل مشکل..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="resolve_note" class="btn btn-success">حل مشکل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Note Modal -->
<div class="modal fade" id="deleteNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">حذف یادداشت اضطراری</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="note_id" id="delete_note_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        آیا مطمئن هستید که می‌خواهید این یادداشت اضطراری را حذف کنید؟
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="delete_note" class="btn btn-danger">حذف یادداشت</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function searchItemForNote() {
    const inventoryCode = document.getElementById('inventory_code').value.trim();
    if (!inventoryCode) {
        alert('لطفاً کد کالا را وارد کنید');
        return;
    }
    
    fetch('ajax/get_item_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'inventory_code=' + encodeURIComponent(inventoryCode)
    })
    .then(response => response.json())
    .then(data => {
        const itemInfo = document.getElementById('itemInfoNote');
        if (data.success) {
            itemInfo.innerHTML = `
                <div class="alert alert-info">
                    <strong>${data.item.item_name}</strong><br>
                    موجودی فعلی: <span class="badge ${data.item.current_inventory < 0 ? 'bg-danger' : 'bg-success'}">${data.item.current_inventory.toLocaleString()}</span>
                    ${data.item.category_name ? '<br>گروه: ' + data.item.category_name : ''}
                </div>
            `;
            itemInfo.style.display = 'block';
        } else {
            itemInfo.innerHTML = `<div class="alert alert-danger">کالا یافت نشد</div>`;
            itemInfo.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در دریافت اطلاعات کالا');
    });
}

function resolveNote(noteId) {
    document.getElementById('resolve_note_id').value = noteId;
    new bootstrap.Modal(document.getElementById('resolveNoteModal')).show();
}

function deleteNote(noteId) {
    document.getElementById('delete_note_id').value = noteId;
    new bootstrap.Modal(document.getElementById('deleteNoteModal')).show();
}

function viewNote(noteId) {
    // Implementation for viewing note details
    window.open('view_emergency_note.php?id=' + noteId, '_blank');
}

// Initialize enhanced table
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#notesTable').DataTable({
            language: {
                url: 'assets/js/dataTables.persian.json'
            },
            order: [[7, 'desc']], // Sort by creation date
            pageLength: 25,
            responsive: true
        });
    }
});
</script>

<?php get_footer(); ?>
