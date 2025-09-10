<?php
/**
 * Inventory Categories Template
 * Professional management for product categories
 */

// Make database connection available
global $conn;

// Page header data
$header_args = array(
    'title' => 'مدیریت گروه‌های کالا',
    'subtitle' => 'ایجاد و مدیریت گروه‌بندی کالاها برای شمارش و گزارش‌گیری دسته‌ای',
    'icon' => 'bi bi-collection',
    'breadcrumbs' => array(
        array('text' => 'خانه', 'url' => 'index.php'),
        array('text' => 'مدیریت گروه‌های کالا')
    ),
    'actions' => array(
        array(
            'text' => 'شمارش دسته‌ای',
            'url' => 'physical_count.php',
            'class' => 'btn-primary',
            'icon' => 'bi bi-calculator'
        ),
        array(
            'text' => 'بازگشت',
            'url' => 'index.php',
            'class' => 'btn-secondary',
            'icon' => 'bi bi-house'
        )
    )
);

get_theme_part('page-header', $header_args);

// Load alerts
get_theme_part('alerts');

// Statistics cards
$total_categories = count($categories);
$total_items = array_sum(array_column($categories, 'items_count'));
$empty_categories = count(array_filter($categories, function($cat) { return $cat['items_count'] == 0; }));

$stats = array(
    array(
        'icon' => 'bi bi-collection',
        'value' => number_format($total_categories),
        'label' => 'کل گروه‌ها',
        'icon_color' => 'text-primary'
    ),
    array(
        'icon' => 'bi bi-boxes',
        'value' => number_format($total_items),
        'label' => 'کل کالاها',
        'icon_color' => 'text-success'
    ),
    array(
        'icon' => 'bi bi-exclamation-circle',
        'value' => number_format($empty_categories),
        'label' => 'گروه‌های خالی',
        'icon_color' => 'text-warning'
    ),
    array(
        'icon' => 'bi bi-percent',
        'value' => $total_categories > 0 ? number_format(($total_categories - $empty_categories) / $total_categories * 100, 1) . '%' : '0%',
        'label' => 'پر بودن',
        'icon_color' => 'text-info'
    )
);

include ACTIVE_THEME_PATH . '/template-parts/stats-cards.php';
?>

<div class="row">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="card form-card fade-in hover-lift">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-plus-lg me-2"></i>افزودن گروه جدید
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">نام گروه</label>
                        <input type="text" name="category_name" id="category_name" 
                               class="form-control" required maxlength="100"
                               placeholder="مثال: قطعات الکترونیکی">
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_description" class="form-label">توضیحات</label>
                        <textarea name="category_description" id="category_description" 
                                  class="form-control" rows="3"
                                  placeholder="توضیحات اختیاری در مورد این گروه..."></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="add_category" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>افزودن گروه
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card fade-in-delay-1 hover-lift">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>لیست گروه‌های کالا
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-collection display-4 text-muted mb-3"></i>
                        <h5 class="text-muted">هیچ گروهی تعریف نشده است</h5>
                        <p class="text-muted">برای شروع، یک گروه کالا جدید ایجاد کنید</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <?php enhanced_table_start('categoriesTable', 'گزارش گروه‌های کالا', 'سیستم مدیریت انبار'); ?>
                            <thead class="table-light">
                                <tr>
                                    <th>نام گروه</th>
                                    <th>توضیحات</th>
                                    <th>تعداد کالا</th>
                                    <th>تاریخ ایجاد</th>
                                    <th width="150">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr class="fade-in" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                        <td>
                                            <strong class="text-primary">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($category['category_description'])): ?>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                      title="<?php echo htmlspecialchars($category['category_description']); ?>">
                                                    <?php echo htmlspecialchars($category['category_description']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($category['items_count'] > 0): ?>
                                                <span class="badge bg-success">
                                                    <?php echo number_format($category['items_count']); ?> کالا
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">خالی</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo !empty($category['created_at']) ? gregorianToJalali($category['created_at']) : '-'; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" 
                                                        onclick="editCategory(<?php echo $category['category_id']; ?>)"
                                                        title="ویرایش گروه">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="physical_count.php?category_id=<?php echo $category['category_id']; ?>" 
                                                   class="btn btn-outline-info" title="شمارش این گروه">
                                                    <i class="bi bi-calculator"></i>
                                                </a>
                                                <?php if ($category['items_count'] == 0): ?>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteCategory(<?php echo $category['category_id']; ?>)"
                                                            title="حذف گروه">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        <?php enhanced_table_end(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>ویرایش گروه کالا
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCategoryForm">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">نام گروه</label>
                        <input type="text" name="category_name" id="edit_category_name" 
                               class="form-control" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">توضیحات</label>
                        <textarea name="category_description" id="edit_category_description" 
                                  class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>تأیید حذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>آیا از حذف این گروه کالا اطمینان دارید؟</p>
                <p class="text-muted small">این عمل قابل بازگشت نیست.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <button type="submit" name="delete_category" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>حذف
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Categories data for JavaScript operations
const categoriesData = <?php echo json_encode($categories); ?>;

function editCategory(categoryId) {
    const category = categoriesData.find(c => c.category_id == categoryId);
    if (category) {
        document.getElementById('edit_category_id').value = categoryId;
        document.getElementById('edit_category_name').value = category.category_name;
        document.getElementById('edit_category_description').value = category.category_description || '';
        
        const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }
}

function deleteCategory(categoryId) {
    document.getElementById('delete_category_id').value = categoryId;
    const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}
</script>
