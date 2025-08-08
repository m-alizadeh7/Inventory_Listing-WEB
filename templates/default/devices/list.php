<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-hdd-stack"></i> مدیریت دستگاه‌ها
                </h5>
                <a href="index.php?controller=device&action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> افزودن دستگاه جدید
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($devices)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-hdd-stack" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3">هیچ دستگاهی تعریف نشده است.</p>
                        <a href="index.php?controller=device&action=add" class="btn btn-primary">
                            افزودن اولین دستگاه
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نام دستگاه</th>
                                    <th>مدل</th>
                                    <th>توضیحات</th>
                                    <th>تاریخ ایجاد</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devices as $device): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($device['name']); ?></td>
                                    <td><?php echo htmlspecialchars($device['model'] ?? 'نامشخص'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($device['description'], 0, 50)) . (strlen($device['description']) > 50 ? '...' : ''); ?></td>
                                    <td><?php echo date('Y/m/d', strtotime($device['created_at'])); ?></td>
                                    <td>
                                        <a href="index.php?controller=device&action=view&id=<?php echo $device['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="index.php?controller=device&action=edit&id=<?php echo $device['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?controller=device&action=delete&id=<?php echo $device['id']; ?>" 
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
