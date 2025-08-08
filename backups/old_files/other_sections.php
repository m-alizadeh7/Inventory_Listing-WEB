<?php
require_once 'config.php';
require_once 'includes/functions.php';

// سایر بخش‌ها
$page = $_GET['page'] ?? '';

?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سایر بخش‌ها - سیستم انبارداری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><i class="bi bi-grid"></i> سایر بخش‌ها</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> بازگشت به داشبورد
                </a>
            </div>
            <div class="list-group mb-4">
                <a href="suppliers.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-truck"></i> مدیریت تامین‌کنندگان
                </a>
                <!-- سایر بخش‌های قابل افزودن -->
            </div>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> بخش تامین‌کنندگان و سایر بخش‌های جانبی اینجا منتقل شدند.
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
