<?php
/**
 * قالب فوتر پیش‌فرض
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

global $config;
$business_info = getBusinessInfo();
?>
    </div>
</div>

<footer class="footer mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">© <?php echo date('Y'); ?> سیستم مدیریت انبار و تولید <?php echo htmlspecialchars($business_info['business_name']); ?></p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="footer-links">
                    <?php if (isset($_SESSION['user_data']) && $_SESSION['user_data']['role'] === 'admin'): ?>
                    <a href="index.php?controller=main&action=settings">
                        <i class="bi bi-gear"></i> تنظیمات
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $config['github']; ?>" target="_blank">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                    <a href="<?php echo $config['telegram']; ?>" target="_blank">
                        <i class="bi bi-telegram"></i> Telegram
                    </a>
                </div>
                <div class="mt-2 small text-muted">
                    توسعه‌دهنده: <a href="<?php echo $config['website']; ?>" target="_blank"><?php echo $config['author']; ?></a>
                    | <a href="mailto:<?php echo $config['email']; ?>"><?php echo $config['email']; ?></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>/js/script.js"></script>

<?php if (isset($custom_js)): ?>
<script src="<?php echo ASSETS_URL; ?>/js/<?php echo $custom_js; ?>.js"></script>
<?php endif; ?>

</body>
</html>
