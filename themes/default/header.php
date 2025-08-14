<?php
// Default theme header
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business_info['business_name'] ?? 'سیستم انبارداری'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="themes/default/assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="main-menu navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary d-lg-none me-2" id="mobileMenuBtn" aria-label="باز کردن منو">
                                <i class="bi bi-list"></i>
                        </button>
                        <a class="navbar-brand fw-bold d-flex align-items-center" href="/">
                                <span class="brand-icon">📦</span>
                                <span class="ms-2 d-none d-sm-inline-block"><?php echo htmlspecialchars($business_info['business_name'] ?? 'سیستم انبارداری'); ?></span>
                        </a>
                </div>

                <div class="d-none d-lg-flex align-items-center desktop-actions">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary home-btn">صفحه اصلی</a>
                        <a href="inventory_records.php" class="btn btn-sm btn-outline-primary me-2">موجودی</a>
                        <a href="production_orders.php" class="btn btn-sm btn-outline-success">سفارشات</a>
                </div>
        </div>
</nav>

<!-- mobile offcanvas menu -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileMenuLabel"><?php echo htmlspecialchars($business_info['business_name'] ?? 'منو'); ?></h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="list-unstyled">
            <li><a href="new_inventory.php" class="d-block py-2">انبارگردانی جدید</a></li>
            <li><a href="inventory_records.php" class="d-block py-2">مدیریت موجودی</a></li>
            <li><a href="view_inventories.php" class="d-block py-2">گزارش‌ها</a></li>
            <li><a href="production_orders.php" class="d-block py-2">سفارشات تولید</a></li>
            <li><a href="devices.php" class="d-block py-2">دستگاه‌ها</a></li>
            <li><a href="settings.php" class="d-block py-2">تنظیمات</a></li>
        </ul>
    </div>
</div>
<div class="content-wrapper pt-5">
    <div class="container pt-4">
