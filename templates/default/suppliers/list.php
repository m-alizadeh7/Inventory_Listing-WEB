<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-people"></i> مدیریت تأمین‌کنندگان
                </h5>
                <a href="index.php?controller=supplier&action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> افزودن تأمین‌کننده جدید
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($suppliers)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3">هیچ تأمین‌کننده‌ای تعریف نشده است.</p>
                        <a href="index.php?controller=supplier&action=add" class="btn btn-primary">
                            افزودن اولین تأمین‌کننده
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نام تأمین‌کننده</th>
                                    <th>شخص تماس</th>
                                    <th>تلفن</th>
                                    <th>ایمیل</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suppliers as $supplier): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['contact_person'] ?? 'نامشخص'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['phone'] ?? 'نامشخص'); ?></td>
                                    <td><?php echo htmlspecialchars($supplier['email'] ?? 'نامشخص'); ?></td>
                                    <td><?php echo date('Y/m/d', strtotime($supplier['created_at'])); ?></td>
                                    <td>
                                        <a href="index.php?controller=supplier&action=view&id=<?php echo $supplier['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="index.php?controller=supplier&action=edit&id=<?php echo $supplier['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?controller=supplier&action=delete&id=<?php echo $supplier['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('آیا مطمئن هستید؟')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
