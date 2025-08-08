<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-box-seam"></i> موجودی انبار
                </h5>
                <a href="index.php?controller=inventory&action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> افزودن قطعه جدید
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($inventory)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-box" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3">هیچ قطعه‌ای در انبار موجود نیست.</p>
                        <a href="index.php?controller=inventory&action=add" class="btn btn-primary">
                            افزودن اولین قطعه
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>شماره قطعه</th>
                                    <th>نام قطعه</th>
                                    <th>موجودی</th>
                                    <th>قیمت</th>
                                    <th>تأمین‌کننده</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['part_number']); ?></td>
                                    <td><?php echo htmlspecialchars($item['part_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $item['stock'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                            <?php echo number_format($item['stock']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($item['price']); ?> تومان</td>
                                    <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'نامشخص'); ?></td>
                                    <td>
                                        <a href="index.php?controller=inventory&action=edit&id=<?php echo $item['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?controller=inventory&action=delete&id=<?php echo $item['id']; ?>" 
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
