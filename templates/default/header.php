<?php
/**
 * قالب هدر پیش‌فرض
 * 
 * @author Mahdi Alizadeh <m.alizadeh7@live.com>
 * @website https://alizadehx.ir
 */

// دریافت عنوان صفحه
$page_title = $page_title ?? 'سیستم مدیریت انبار';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- استایل‌ها -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
    
    <?php if (isset($custom_css)): ?>
    <link href="<?php echo ASSETS_URL; ?>/css/<?php echo $custom_css; ?>.css" rel="stylesheet">
    <?php endif; ?>
    
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }
        .content-wrapper {
            flex: 1 0 auto;
            padding: 2rem 0;
        }
        .footer {
            flex-shrink: 0;
            background-color: #fff;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem 0;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
<nav class="main-menu navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold">📦 <?php echo htmlspecialchars(isset($business_info['business_name']) ? $business_info['business_name'] : 'سیستم مدیریت انبار و تولید'); ?></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house"></i> داشبورد
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?controller=inventory&action=list">
                        <i class="bi bi-box-seam"></i> موجودی انبار
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?controller=production&action=list">
                        <i class="bi bi-gear"></i> سفارشات تولید
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?controller=device&action=list">
                        <i class="bi bi-hdd-stack"></i> دستگاه‌ها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?controller=supplier&action=list">
                        <i class="bi bi-people"></i> تأمین‌کنندگان
                    </a>
                </li>
                <?php if (isset($_SESSION['user_data']) && $_SESSION['user_data']['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?controller=user&action=listUsers">
                        <i class="bi bi-person"></i> کاربران
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo isset($_SESSION['user_data']) ? htmlspecialchars($_SESSION['user_data']['full_name']) : 'کاربر'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="index.php?controller=main&action=show_profile">
                            <i class="bi bi-person"></i> پروفایل
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="index.php?controller=user&action=logout">
                            <i class="bi bi-box-arrow-right"></i> خروج
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="content-wrapper">
    <div class="container">
        <?php checkMigrationsPrompt(); ?>
