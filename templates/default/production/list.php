<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-gear"></i> سفارشات تولید
                </h5>
                <a href="index.php?controller=production&action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> ایجاد سفارش جدید
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-gear" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3">هیچ سفارش تولیدی موجود نیست.</p>
                        <a href="index.php?controller=production&action=add" class="btn btn-primary">
                            ایجاد اولین سفارش
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>شماره سفارش</th>
                                    <th>دستگاه</th>
                                    <th>تعداد</th>
                                    <th>وضعیت</th>
                                    <th>تاریخ شروع</th>
                                    <th>تاریخ پایان</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['device_name'] ?? 'نامشخص'); ?></td>
                                    <td><?php echo number_format($order['quantity']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'pending' => 'bg-warning',
                                            'in_progress' => 'bg-info',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger'
                                        ];
                                        $status_text = [
                                            'pending' => 'در انتظار',
                                            'in_progress' => 'در حال انجام',
                                            'completed' => 'تکمیل شده',
                                            'cancelled' => 'لغو شده'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $status_class[$order['status']] ?? 'bg-secondary'; ?>">
                                            <?php echo $status_text[$order['status']] ?? $order['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['start_date'] ? date('Y/m/d', strtotime($order['start_date'])) : 'تعیین نشده'; ?></td>
                                    <td><?php echo $order['end_date'] ? date('Y/m/d', strtotime($order['end_date'])) : 'تعیین نشده'; ?></td>
                                    <td>
                                        <a href="index.php?controller=production&action=view&id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="index.php?controller=production&action=edit&id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?controller=production&action=delete&id=<?php echo $order['id']; ?>" 
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
