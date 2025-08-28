<?php
require_once 'bootstrap.php';

// Simple backup listing page to avoid 404. Lists files in backups/ directory.
$backupDir = __DIR__ . '/backups';
$files = [];
if (is_dir($backupDir)) {
    $all = scandir($backupDir);
    foreach ($all as $f) {
        if (in_array($f, ['.', '..'])) continue;
        $files[] = $f;
    }
}

get_template_part('header');
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-header"><h4 class="mb-0">فایل‌های پشتیبان</h4></div>
        <div class="card-body">
            <?php if (empty($files)): ?>
                <div class="alert alert-info">هیچ فایل پشتیبانی یافت نشد.</div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($files as $f): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($f) ?>
                            <a href="backups/<?= rawurlencode($f) ?>" class="btn btn-sm btn-outline-secondary">دانلود</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php get_template_part('footer');
