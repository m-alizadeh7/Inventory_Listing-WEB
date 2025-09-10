<?php
/**
 * Inventory Categories Template with sidebar layout
 * Professional management for product categories
 */

// Make database connection available
global $conn;

// Load alerts component
get_theme_part('alerts');

// Check for pending migrations
checkMigrationsPrompt();
?>

<div class="dashboard-layout">
    <!-- Sidebar Navigation -->
    <?php get_theme_part('sidebar'); ?>

    <!-- Main Content -->
    <div class="dashboard-main">
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1">
                        <i class="bi bi-collection text-info me-2"></i>
                        مدیریت گروه‌های کالا
                    </h1>
                    <p class="text-muted mb-0">ایجاد و مدیریت گروه‌بندی کالاها برای شمارش و گزارش‌گیری دسته‌ای</p>
                </div>
                <div>
                    <a href="physical_count.php" class="btn btn-primary me-2">
                        <i class="bi bi-calculator"></i>
                        شمارش دسته‌ای
                    </a>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i>
                        بازگشت به داشبرد
                    </a>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="../index.php">
                            <i class="bi bi-house"></i>
                            داشبرد
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">گروه‌های کالا</li>
                </ol>
            </nav>

            <!-- Statistics Cards -->
            <?php
            $total_categories = count($categories);
            $total_items = array_sum(array_column($categories, 'items_count'));
            $empty_categories = count(array_filter($categories, function($cat) { return $cat['items_count'] == 0; }));

            $stats = array(
                array(
                    'icon' => 'bi bi-collection',
                    'value' => number_format($total_categories),
                    'label' => 'کل گروه‌ها',
                    'bg_class' => 'bg-primary',
                    'text_class' => 'text-white'
                ),
                array(
                    'icon' => 'bi bi-box-seam',
                    'value' => number_format($total_items),
                    'label' => 'کل کالاها',
                    'bg_class' => 'bg-success',
                    'text_class' => 'text-white'
                ),
                array(
                    'icon' => 'bi bi-dash-circle',
                    'value' => number_format($empty_categories),
                    'label' => 'گروه‌های خالی',
                    'bg_class' => 'bg-warning',
                    'text_class' => 'text-dark'
                )
            );
            ?>

            <div class="dashboard-cards mb-4">
                <?php foreach ($stats as $stat): ?>
                <div class="dashboard-card">
                    <div class="card-icon <?php echo $stat['bg_class']; ?> <?php echo $stat['text_class']; ?>">
                        <i class="<?php echo $stat['icon']; ?>"></i>
                    </div>
                    <div class="card-value"><?php echo $stat['value']; ?></div>
                    <div class="card-label"><?php echo $stat['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Alerts -->
            <?php get_theme_part('alerts'); ?>

            <!-- Categories Management -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        لیست گروه‌های کالا
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg"></i>
                        افزودن گروه جدید
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-collection fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">هیچ گروه کالایی یافت نشد</h5>
                            <p class="text-muted">برای شروع، گروه کالایی جدید ایجاد کنید</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="bi bi-plus-lg"></i>
                                ایجاد گروه اول
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>نام گروه</th>
                                        <th>توضیحات</th>
                                        <th>تعداد کالا</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $index => $category): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['category_description'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo $category['items_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary" onclick="editCategory(<?php echo $category['category_id']; ?>, '<?php echo addslashes($category['category_name']); ?>', '<?php echo addslashes($category['category_description'] ?? ''); ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo addslashes($category['category_name']); ?>')">
                                                    <i class="bi bi-trash"></i>
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
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-lg me-2"></i>
                    افزودن گروه کالایی جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">نام گروه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        افزودن گروه
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>
                    ویرایش گروه کالایی
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">نام گروه <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">توضیحات</label>
                        <textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="bi bi-save"></i>
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Toggle -->
<button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Mobile Overlay -->
<div class="sidebar-overlay d-lg-none" onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close sidebar when clicking on a link (mobile)
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 992) {
            toggleSidebar();
        }
    });
});

function editCategory(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_description').value = description;

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function deleteCategory(id, name) {
    if (confirm(`آیا مطمئن هستید که می‌خواهید گروه "${name}" را حذف کنید؟`)) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="category_id" value="${id}">
            <input type="hidden" name="delete_category" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
