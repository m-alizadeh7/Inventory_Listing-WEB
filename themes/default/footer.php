<?php
// Default theme footer
?>
    </div>
</div>

<!-- mobile sticky footer -->
<div class="mobile-footer d-lg-none">
    <a href="new_inventory.php"><i class="bi bi-plus-circle"></i><div>جدید</div></a>
    <a href="inventory_records.php" class="active"><i class="bi bi-box-seam"></i><div>موجودی</div></a>
    <a href="production_orders.php"><i class="bi bi-list-check"></i><div>سفارشات</div></a>
    <a href="settings.php"><i class="bi bi-gear"></i><div>تنظیمات</div></a>
</div>

<footer class="footer d-none d-lg-block mt-5 py-4 bg-white border-top">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-start">
                <strong>مالک و سازنده:</strong> m-alizadeh7<br>
                <a href="https://alizadehx.ir" target="_blank">alizadehx.ir</a> •
                <a href="https://github.com/m-alizadeh7" target="_blank">GitHub</a>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">© <?php echo date('Y'); ?> سیستم انبارداری - همه حقوق محفوظ است</small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('mobileMenuBtn')?.addEventListener('click', function(){
    var off = new bootstrap.Offcanvas(document.getElementById('mobileMenu'));
    off.show();
});
</script>
</body>
</html>
