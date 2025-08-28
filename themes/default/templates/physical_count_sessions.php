<?php
// Get statistics for dashboard
$total_sessions = $conn->query("SELECT COUNT(*) as count FROM physical_count_sessions")->fetch_assoc()['count'];
$active_sessions = $conn->query("SELECT COUNT(*) as count FROM physical_count_sessions WHERE status = 'active'")->fetch_assoc()['count'];
$completed_sessions = $conn->query("SELECT COUNT(*) as count FROM physical_count_sessions WHERE status = 'completed'")->fetch_assoc()['count'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0"><?php echo $page_title; ?></h2>
        <p class="text-muted mb-0"><?php echo $page_description; ?></p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSessionModal">
        <i class="fas fa-plus me-2"></i>شروع جلسه شمارش جدید
    </button>
</div>

<?php display_flash_messages(); ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($total_sessions); ?></h4>
                        <small>کل جلسات شمارش</small>
                    </div>
                    <div class="fs-2">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($active_sessions); ?></h4>
                        <small>جلسات فعال</small>
                    </div>
                    <div class="fs-2">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?php echo number_format($completed_sessions); ?></h4>
                        <small>جلسات تکمیل شده</small>
                    </div>
                    <div class="fs-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sessions Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">جلسات شمارش فیزیکی</h5>
        <div>
            <button class="btn btn-outline-primary btn-sm" onclick="exportTable('sessionsTable', 'physical_count_sessions')">
                <i class="fas fa-file-excel me-1"></i>Excel
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="printTable('sessionsTable')">
                <i class="fas fa-print me-1"></i>پرینت
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($sessions)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5>هیچ جلسه شمارشی یافت نشد</h5>
                <p class="text-muted">برای شروع شمارش فیزیکی انبار، جلسه جدید ایجاد کنید</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSessionModal">
                    شروع جلسه شمارش جدید
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="sessionsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>نام جلسه</th>
                            <th>گروه کالا</th>
                            <th>شمارشگر</th>
                            <th>تاریخ شروع</th>
                            <th>تاریخ پایان</th>
                            <th>وضعیت</th>
                            <th>تعداد اقلام</th>
                            <th>پیشرفت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($session['session_name']); ?></strong>
                                    <?php if ($session['notes']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($session['notes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['category_name']): ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($session['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">همه گروه‌ها</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($session['counted_by']); ?></td>
                                <td>
                                    <small><?php echo jdate('Y/m/d - H:i', strtotime($session['start_date'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($session['end_date']): ?>
                                        <small><?php echo jdate('Y/m/d - H:i', strtotime($session['end_date'])); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($session['status'] == 'active'): ?>
                                        <span class="badge bg-warning">در حال انجام</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">تکمیل شده</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo number_format($session['total_items']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $progress = $session['total_items'] > 0 ? round(($session['counted_items'] / $session['total_items']) * 100) : 0;
                                    $progress_class = $progress < 30 ? 'bg-danger' : ($progress < 70 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo $progress_class; ?>" style="width: <?php echo $progress; ?>%">
                                            <?php echo $progress; ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo number_format($session['counted_items']); ?> از <?php echo number_format($session['total_items']); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="physical_count.php?session=<?php echo $session['session_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="ادامه شمارش">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($session['status'] == 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="finalizeSession(<?php echo $session['session_id']; ?>)" title="تکمیل جلسه">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                onclick="viewSessionReport(<?php echo $session['session_id']; ?>)" title="گزارش">
                                            <i class="fas fa-chart-bar"></i>
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

<!-- New Session Modal -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">شروع جلسه شمارش جدید</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="session_name" class="form-label">نام جلسه <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="session_name" name="session_name" required
                                   placeholder="مثال: شمارش انبار مهر ۱۴۰۳">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="counted_by" class="form-label">شمارشگر <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="counted_by" name="counted_by" required
                                   placeholder="نام شخص انجام‌دهنده شمارش">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">گروه کالا</label>
                        <select class="form-control" id="category_id" name="category_id">
                            <option value="">همه گروه‌ها</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">در صورت انتخاب گروه خاص، فقط اقلام آن گروه شمارش خواهند شد</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">یادداشت</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="توضیحات اضافی در مورد این جلسه شمارش"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>نکته:</strong> پس از شروع جلسه شمارش، می‌توانید شمارش را به صورت مرحله‌ای انجام دهید. 
                        در پایان می‌توانید انتخاب کنید که موجودی انبار به‌روزرسانی شود یا خیر.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="start_count_session" class="btn btn-primary">شروع جلسه شمارش</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Finalize Session Modal -->
<div class="modal fade" id="finalizeSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="finalizeSessionForm">
                <div class="modal-header">
                    <h5 class="modal-title">تکمیل جلسه شمارش</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="session_id" id="finalize_session_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        آیا مطمئن هستید که می‌خواهید این جلسه شمارش را تکمیل کنید؟
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="update_inventory" name="update_inventory" checked>
                        <label class="form-check-label" for="update_inventory">
                            <strong>به‌روزرسانی موجودی انبار بر اساس شمارش</strong>
                        </label>
                        <div class="form-text">
                            در صورت فعال بودن این گزینه، موجودی انبار بر اساس مقادیر شمارش شده به‌روزرسانی می‌شود
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="finalize_count_session" class="btn btn-success">تکمیل جلسه</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function finalizeSession(sessionId) {
    document.getElementById('finalize_session_id').value = sessionId;
    new bootstrap.Modal(document.getElementById('finalizeSessionModal')).show();
}

function viewSessionReport(sessionId) {
    window.open('reports/physical_count_report.php?session=' + sessionId, '_blank');
}

// Initialize enhanced table
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#sessionsTable').DataTable({
            language: {
                url: 'assets/js/dataTables.persian.json'
            },
            order: [[3, 'desc']], // Sort by start date
            pageLength: 25,
            responsive: true
        });
    }
});
</script>
