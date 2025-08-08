<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-person"></i> مدیریت کاربران
                </h5>
                <a href="index.php?controller=user&action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> افزودن کاربر جدید
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($users)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-person" style="font-size: 3rem; opacity: 0.5;"></i>
                        <p class="mt-3">هیچ کاربری موجود نیست.</p>
                        <a href="index.php?controller=user&action=add" class="btn btn-primary">
                            ایجاد اولین کاربر
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نام کاربری</th>
                                    <th>نام و نام خانوادگی</th>
                                    <th>ایمیل</th>
                                    <th>نقش</th>
                                    <th>وضعیت</th>
                                    <th>آخرین ورود</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $role_labels = [
                                            'admin' => '<span class="badge bg-danger">مدیر کل</span>',
                                            'manager' => '<span class="badge bg-warning">مدیر</span>',
                                            'user' => '<span class="badge bg-info">کاربر عادی</span>'
                                        ];
                                        echo $role_labels[$user['role']] ?? '<span class="badge bg-secondary">نامشخص</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غیرفعال</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $user['last_login'] ? date('Y/m/d H:i', strtotime($user['last_login'])) : 'هرگز'; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?controller=user&action=edit&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['user_data']['id']): ?>
                                        <a href="index.php?controller=user&action=delete&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('آیا مطمئن هستید؟')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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
</div>
