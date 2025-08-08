<?php
/**
 * قالب لیست کاربران
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">مدیریت کاربران</h1>
        <a href="index.php?controller=user&action=show_add_user" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> افزودن کاربر جدید
        </a>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted mb-3"></i>
                <h5>هیچ کاربری یافت نشد</h5>
                <p class="text-muted">برای افزودن کاربر جدید روی دکمه "افزودن کاربر جدید" کلیک کنید.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">نام کاربری</th>
                            <th scope="col">نام و نام خانوادگی</th>
                            <th scope="col">ایمیل</th>
                            <th scope="col">نقش</th>
                            <th scope="col">آخرین ورود</th>
                            <th scope="col">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $user_item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($user_item['username']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['name']); ?></td>
                            <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                            <td>
                                <?php 
                                $role_badges = [
                                    'admin' => '<span class="badge bg-danger">مدیر سیستم</span>',
                                    'manager' => '<span class="badge bg-primary">مدیر</span>',
                                    'inventory' => '<span class="badge bg-success">کارشناس انبار</span>',
                                    'production' => '<span class="badge bg-warning">کارشناس تولید</span>'
                                ];
                                echo $role_badges[$user_item['role']] ?? '<span class="badge bg-secondary">' . $user_item['role'] . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($user_item['last_login']): ?>
                                <small><?php echo date('Y/m/d H:i', strtotime($user_item['last_login'])); ?></small>
                                <?php else: ?>
                                <span class="text-muted">هیچ‌وقت</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="index.php?controller=user&action=show_edit_user&id=<?php echo $user_item['id']; ?>" class="btn btn-sm btn-outline-primary" title="ویرایش">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user_item['id'] != $user['id']): ?>
                                    <a href="index.php?controller=user&action=delete_user&id=<?php echo $user_item['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('آیا از حذف این کاربر اطمینان دارید؟');" 
                                       title="حذف">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
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
