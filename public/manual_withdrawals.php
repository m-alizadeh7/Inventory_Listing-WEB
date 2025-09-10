<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize theme
require_once 'core/includes/theme.php';
init_theme();

// Ensure manual_withdrawals table exists
$conn->query("CREATE TABLE IF NOT EXISTS manual_withdrawals (
    withdrawal_id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_code VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(255) NULL,
    notes TEXT NULL,
    withdrawn_by VARCHAR(255) NULL,
    previous_quantity INT NULL,
    remaining_quantity INT NULL,
    status VARCHAR(20) DEFAULT 'active',
    withdrawal_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    cancelled_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Handle withdrawal operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['withdraw_item'])) {
        $inventory_code = clean($_POST['inventory_code']);
        $quantity = (int)$_POST['quantity'];
        $reason = clean($_POST['reason']);
        $notes = clean($_POST['notes']);
        $withdrawn_by = clean($_POST['withdrawn_by']);
        
        try {
            $conn->begin_transaction();
            
            // Check current inventory
            $inventory_check = $conn->prepare("SELECT current_inventory, item_name FROM inventory WHERE inventory_code = ?");
            $inventory_check->bind_param('s', $inventory_code);
            $inventory_check->execute();
            $inventory_result = $inventory_check->get_result();
            
            if ($inventory_result->num_rows === 0) {
                throw new Exception('کد کالای وارد شده یافت نشد');
            }
            
            $inventory_data = $inventory_result->fetch_assoc();
            $current_stock = $inventory_data['current_inventory'];
            $item_name = $inventory_data['item_name'];
            
            if ($quantity <= 0) {
                throw new Exception('مقدار خروج باید بیشتر از صفر باشد');
            }
            
            // Record withdrawal (even if it makes inventory negative)
            $stmt = $conn->prepare("
                INSERT INTO manual_withdrawals 
                (inventory_code, quantity, reason, notes, withdrawn_by, previous_quantity, remaining_quantity) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $remaining_quantity = $current_stock - $quantity;
            $stmt->bind_param('sissiii', $inventory_code, $quantity, $reason, $notes, $withdrawn_by, $current_stock, $remaining_quantity);
            $stmt->execute();
            
            $withdrawal_id = $conn->insert_id;
            
            // Update inventory
            $update_stmt = $conn->prepare("UPDATE inventory SET current_inventory = current_inventory - ? WHERE inventory_code = ?");
            $update_stmt->bind_param('is', $quantity, $inventory_code);
            $update_stmt->execute();
            
            // Record transaction
            $transaction_stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (inventory_code, transaction_type, quantity_change, previous_quantity, new_quantity, reference_type, reference_id, notes, created_by) 
                VALUES (?, 'manual_withdrawal', ?, ?, ?, 'manual_withdrawal', ?, ?, ?)
            ");
            
            $quantity_change = -$quantity; // Negative for withdrawal
            $transaction_notes = "خروج موردی - $reason" . ($notes ? " - $notes" : "");
            
            $transaction_stmt->bind_param('siiiiss', 
                $inventory_code, 
                $quantity_change, 
                $current_stock, 
                $remaining_quantity, 
                $withdrawal_id, 
                $transaction_notes, 
                $withdrawn_by
            );
            $transaction_stmt->execute();
            
            $conn->commit();
            
            $message = "خروج موردی ثبت شد - $item_name";
            if ($remaining_quantity < 0) {
                $message .= " (هشدار: موجودی منفی شد)";
            }
            
            set_flash_message($message, $remaining_quantity < 0 ? 'warning' : 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('خطا در ثبت خروج موردی: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: manual_withdrawals.php');
        exit;
    }
    
    if (isset($_POST['cancel_withdrawal'])) {
        $withdrawal_id = (int)$_POST['withdrawal_id'];
        
        try {
            $conn->begin_transaction();
            
            // Get withdrawal details
            $withdrawal_check = $conn->prepare("
                SELECT mw.*, i.item_name 
                FROM manual_withdrawals mw 
                JOIN inventory i ON mw.inventory_code = i.inventory_code 
                WHERE mw.withdrawal_id = ? AND mw.status = 'active'
            ");
            $withdrawal_check->bind_param('i', $withdrawal_id);
            $withdrawal_check->execute();
            $withdrawal_result = $withdrawal_check->get_result();
            
            if ($withdrawal_result->num_rows === 0) {
                throw new Exception('خروج موردی یافت نشد یا قبلاً لغو شده است');
            }
            
            $withdrawal_data = $withdrawal_result->fetch_assoc();
            
            // Cancel withdrawal
            $cancel_stmt = $conn->prepare("UPDATE manual_withdrawals SET status = 'cancelled', cancelled_at = NOW() WHERE withdrawal_id = ?");
            $cancel_stmt->bind_param('i', $withdrawal_id);
            $cancel_stmt->execute();
            
            // Restore inventory
            $restore_stmt = $conn->prepare("UPDATE inventory SET current_inventory = current_inventory + ? WHERE inventory_code = ?");
            $restore_stmt->bind_param('is', $withdrawal_data['quantity'], $withdrawal_data['inventory_code']);
            $restore_stmt->execute();
            
            // Record reversal transaction
            $current_stock_result = $conn->query("SELECT current_inventory FROM inventory WHERE inventory_code = '{$withdrawal_data['inventory_code']}'");
            $current_stock = $current_stock_result->fetch_assoc()['current_inventory'];
            $previous_stock = $current_stock - $withdrawal_data['quantity'];
            
            $transaction_stmt = $conn->prepare("
                INSERT INTO inventory_transactions 
                (inventory_code, transaction_type, quantity_change, previous_quantity, new_quantity, reference_type, reference_id, notes, created_by) 
                VALUES (?, 'withdrawal_cancellation', ?, ?, ?, 'manual_withdrawal', ?, ?, 'system')
            ");
            
            $reversal_notes = "لغو خروج موردی - " . $withdrawal_data['reason'];
            
            $transaction_stmt->bind_param('siiiiss', 
                $withdrawal_data['inventory_code'], 
                $withdrawal_data['quantity'], 
                $previous_stock, 
                $current_stock, 
                $withdrawal_id, 
                $reversal_notes, 
                'system'
            );
            $transaction_stmt->execute();
            
            $conn->commit();
            
            set_flash_message('خروج موردی لغو شد و موجودی بازگردانده شد - ' . $withdrawal_data['item_name'], 'success');
            
        } catch (Exception $e) {
            $conn->rollback();
            set_flash_message('خطا در لغو خروج موردی: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: manual_withdrawals.php');
        exit;
    }
}

// Get withdrawal reasons for dropdown
$withdrawal_reasons = [
    'نمونه‌گیری کیفیت',
    'تست و آزمایش',
    'آسیب و ضایعات',
    'ارسال فوری',
    'تعمیر و نگهداری',
    'مصرف داخلی',
    'سایر موارد'
];

// Get manual withdrawals
$withdrawals = [];
$sql = "
    SELECT mw.*, i.item_name, ic.category_name,
           DATE_FORMAT(mw.withdrawal_date, '%Y/%m/%d %H:%i') as formatted_date
    FROM manual_withdrawals mw
    JOIN inventory i ON mw.inventory_code COLLATE utf8mb4_unicode_ci = i.inventory_code COLLATE utf8mb4_unicode_ci
    LEFT JOIN inventory_categories ic ON i.category_id = ic.category_id
    ORDER BY mw.withdrawal_date DESC
    LIMIT 100
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $withdrawals[] = $row;
    }
}

// Get statistics
$stats = [];
$stats['total_withdrawals'] = $conn->query("SELECT COUNT(*) as count FROM manual_withdrawals WHERE status = 'active'")->fetch_assoc()['count'];
$stats['total_quantity'] = $conn->query("SELECT SUM(quantity) as total FROM manual_withdrawals WHERE status = 'active'")->fetch_assoc()['total'] ?? 0;
$stats['today_withdrawals'] = $conn->query("SELECT COUNT(*) as count FROM manual_withdrawals WHERE DATE(withdrawal_date) = CURDATE() AND status = 'active'")->fetch_assoc()['count'];
$stats['negative_stock_items'] = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE current_inventory < 0")->fetch_assoc()['count'];

$page_title = 'خروج موردی کالا';
$page_description = 'ثبت و مدیریت خروج موردی اقلام از انبار';

get_header();
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 mb-0"><?php echo $page_title; ?></h2>
            <p class="text-muted mb-0"><?php echo $page_description; ?></p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
            <i class="fas fa-minus-circle me-2"></i>ثبت خروج موردی
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
                            <h4 class="mb-0"><?php echo number_format($stats['total_withdrawals']); ?></h4>
                            <small>کل خروج‌های موردی</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-list-ol"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_quantity']); ?></h4>
                            <small>کل تعداد خروج</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-boxes"></i>
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
                            <h4 class="mb-0"><?php echo number_format($stats['today_withdrawals']); ?></h4>
                            <small>خروج امروز</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-calendar-day"></i>
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
                            <h4 class="mb-0"><?php echo number_format($stats['negative_stock_items']); ?></h4>
                            <small>کالای با موجودی منفی</small>
                        </div>
                        <div class="fs-2">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawals Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تاریخچه خروج‌های موردی</h5>
            <div>
                <button class="btn btn-outline-primary btn-sm" onclick="exportTable('withdrawalsTable', 'manual_withdrawals')">
                    <i class="fas fa-file-excel me-1"></i>Excel
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="printTable('withdrawalsTable')">
                    <i class="fas fa-print me-1"></i>پرینت
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($withdrawals)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5>هیچ خروج موردی یافت نشد</h5>
                    <p class="text-muted">برای ثبت خروج موردی کالا از دکمه بالا استفاده کنید</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                        ثبت خروج موردی
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="withdrawalsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>شماره</th>
                                <th>تاریخ و زمان</th>
                                <th>کد کالا</th>
                                <th>نام کالا</th>
                                <th>گروه</th>
                                <th>تعداد خروج</th>
                                <th>دلیل خروج</th>
                                <th>خروج‌گیرنده</th>
                                <th>موجودی قبل</th>
                                <th>موجودی بعد</th>
                                <th>وضعیت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr>
                                    <td>
                                        <code>#<?php echo $withdrawal['withdrawal_id']; ?></code>
                                    </td>
                                    <td>
                                        <small><?php echo $withdrawal['formatted_date']; ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($withdrawal['inventory_code']); ?></code>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($withdrawal['item_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($withdrawal['category_name']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($withdrawal['category_name']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger"><?php echo number_format($withdrawal['quantity']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($withdrawal['reason']); ?>
                                        <?php if ($withdrawal['notes']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($withdrawal['notes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($withdrawal['withdrawn_by']); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo number_format($withdrawal['previous_quantity']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $remaining = $withdrawal['remaining_quantity'];
                                        $badge_class = $remaining < 0 ? 'bg-danger' : ($remaining == 0 ? 'bg-warning' : 'bg-success');
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo number_format($remaining); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($withdrawal['status'] == 'active'): ?>
                                            <span class="badge bg-success">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">لغو شده</span>
                                            <?php if ($withdrawal['cancelled_at']): ?>
                                                <br><small class="text-muted"><?php echo jdate('Y/m/d H:i', strtotime($withdrawal['cancelled_at'])); ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($withdrawal['status'] == 'active'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="cancelWithdrawal(<?php echo $withdrawal['withdrawal_id']; ?>, '<?php echo htmlspecialchars($withdrawal['item_name']); ?>')" 
                                                    title="لغو خروج">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
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

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="withdrawalForm">
                <div class="modal-header">
                    <h5 class="modal-title">ثبت خروج موردی کالا</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="inventory_code" class="form-label">کد کالا <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="inventory_code" name="inventory_code" required
                                       placeholder="کد کالا را وارد کنید">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchItem()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="itemInfo" class="mt-2" style="display: none;">
                                <!-- Item info will be displayed here -->
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">تعداد خروج <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required min="1"
                                   placeholder="تعداد مورد نظر برای خروج">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reason" class="form-label">دلیل خروج <span class="text-danger">*</span></label>
                            <select class="form-control" id="reason" name="reason" required>
                                <option value="">انتخاب دلیل خروج</option>
                                <?php foreach ($withdrawal_reasons as $reason): ?>
                                    <option value="<?php echo htmlspecialchars($reason); ?>"><?php echo htmlspecialchars($reason); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="withdrawn_by" class="form-label">خروج‌گیرنده <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="withdrawn_by" name="withdrawn_by" required
                                   placeholder="نام شخص خروج‌گیرنده">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">یادداشت</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                                  placeholder="توضیحات اضافی در مورد این خروج"></textarea>
                    </div>
                    
                    <div class="alert alert-warning" id="negativeStockWarning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هشدار: با این خروج، موجودی کالا منفی خواهد شد.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="withdraw_item" class="btn btn-danger" id="withdrawBtn">ثبت خروج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Withdrawal Modal -->
<div class="modal fade" id="cancelWithdrawalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">لغو خروج موردی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="withdrawal_id" id="cancel_withdrawal_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        آیا مطمئن هستید که می‌خواهید این خروج موردی را لغو کنید؟
                    </div>
                    
                    <p>با لغو این خروج، موجودی کالا <strong id="cancel_item_name"></strong> بازگردانده خواهد شد.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="cancel_withdrawal" class="btn btn-danger">لغو خروج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function searchItem() {
    const inventoryCode = document.getElementById('inventory_code').value.trim();
    if (!inventoryCode) {
        alert('لطفاً کد کالا را وارد کنید');
        return;
    }
    
    // Make AJAX request to get item info
    fetch('ajax/get_item_info.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'inventory_code=' + encodeURIComponent(inventoryCode)
    })
    .then(response => response.json())
    .then(data => {
        const itemInfo = document.getElementById('itemInfo');
        if (data.success) {
            itemInfo.innerHTML = `
                <div class="alert alert-info">
                    <strong>${data.item.item_name}</strong><br>
                    موجودی فعلی: <span class="badge ${data.item.current_inventory < 0 ? 'bg-danger' : 'bg-success'}">${data.item.current_inventory.toLocaleString()}</span>
                    ${data.item.category_name ? '<br>گروه: ' + data.item.category_name : ''}
                </div>
            `;
            itemInfo.style.display = 'block';
            
            // Set up quantity validation
            const quantityInput = document.getElementById('quantity');
            quantityInput.addEventListener('input', function() {
                checkNegativeStock(data.item.current_inventory);
            });
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

function checkNegativeStock(currentInventory) {
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    const warning = document.getElementById('negativeStockWarning');
    const withdrawBtn = document.getElementById('withdrawBtn');
    
    if (quantity > currentInventory) {
        warning.style.display = 'block';
        withdrawBtn.className = 'btn btn-warning';
        withdrawBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>ثبت خروج (موجودی منفی)';
    } else {
        warning.style.display = 'none';
        withdrawBtn.className = 'btn btn-danger';
        withdrawBtn.innerHTML = 'ثبت خروج';
    }
}

function cancelWithdrawal(withdrawalId, itemName) {
    document.getElementById('cancel_withdrawal_id').value = withdrawalId;
    document.getElementById('cancel_item_name').textContent = itemName;
    new bootstrap.Modal(document.getElementById('cancelWithdrawalModal')).show();
}

// Initialize enhanced table
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#withdrawalsTable').DataTable({
            language: {
                url: 'assets/js/dataTables.persian.json'
            },
            order: [[0, 'desc']], // Sort by withdrawal ID
            pageLength: 25,
            responsive: true
        });
    }
});
</script>

<?php get_footer(); ?>
