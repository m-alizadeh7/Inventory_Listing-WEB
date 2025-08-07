<?php
require_once 'config.php';
require_once 'includes/functions.php';

// دریافت اطلاعات کسب و کار
$business_info = getBusinessInfo();

// بررسی و ایجاد جدول production_orders اگر وجود ندارد
$res = $conn->query("SHOW TABLES LIKE 'production_orders'");
if ($res && $res->num_rows === 0) {
    $createTable = "CREATE TABLE production_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) NOT NULL,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME NULL,
        notes TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    if (!$conn->query($createTable)) {
        die('خطا در ایجاد جدول production_orders: ' . $conn->error);
    }
}

// بررسی و اضافه کردن ستون item_code به جدول‌های مرتبط
$res = $conn->query("SHOW COLUMNS FROM device_bom LIKE 'item_code'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE device_bom ADD COLUMN item_code VARCHAR(50) NULL");
}

$res = $conn->query("SHOW COLUMNS FROM production_order_items LIKE 'item_code'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE production_order_items ADD COLUMN item_code VARCHAR(50) NULL");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = clean($_POST['order_id']);

    try {
        $conn->begin_transaction();

        // بررسی وضعیت فعلی سفارش
        $order = $conn->query("SELECT status FROM production_orders WHERE order_id = $order_id")->fetch_assoc();
        if ($order['status'] !== 'draft') {
            throw new Exception('این سفارش قبلاً تایید شده است.');
        }

        // محاسبه کالای مورد نیاز و تهیه لیست تامین‌کننده
        $result = $conn->query("
            SELECT b.item_code, b.item_name, SUM(b.quantity_needed * i.quantity) as total_needed, s.supplier_name
            FROM production_order_items i
            JOIN device_bom b ON i.device_id = b.device_id
            LEFT JOIN suppliers s ON b.supplier_id = s.supplier_id
            WHERE i.order_id = $order_id
            GROUP BY b.item_code, b.item_name, s.supplier_name
        ");

        $supplier_list = [];
        while ($row = $result->fetch_assoc()) {
            $supplier_list[] = $row;
        }

        // تایید سفارش
        $sql = "UPDATE production_orders SET status = 'confirmed' WHERE order_id = $order_id";
        if (!$conn->query($sql)) {
            throw new Exception('خطا در تایید سفارش.');
        }

        $conn->commit();

        // نمایش لیست تامین‌کننده
        echo "<html lang='fa' dir='rtl'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title>لیست تامین‌کننده - " . htmlspecialchars($business_info['business_name']) . "</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>";
        echo "<style>@media print { .btn { display: none; } .business-header { margin-bottom: 20px; } }</style>";
        echo "</head>";
        echo "<body>";
        echo "<div class='container mt-4'>";
        
        // هدر اطلاعات کسب و کار
        echo "<div class='business-header text-center mb-4'>";
        echo "<h2>" . htmlspecialchars($business_info['business_name']) . "</h2>";
        if ($business_info['business_address']) {
            echo "<p class='mb-1'><strong>آدرس:</strong> " . htmlspecialchars($business_info['business_address']) . "</p>";
        }
        if ($business_info['business_phone']) {
            echo "<p class='mb-1'><strong>تلفن:</strong> " . htmlspecialchars($business_info['business_phone']) . "</p>";
        }
        if ($business_info['business_email']) {
            echo "<p class='mb-1'><strong>ایمیل:</strong> " . htmlspecialchars($business_info['business_email']) . "</p>";
        }
        echo "<hr>";
        echo "</div>";
        
        echo "<h3 class='text-center'>لیست تامین‌کننده</h3>";
        echo "<p class='text-center text-muted'>تاریخ: " . date('Y/m/d H:i') . "</p>";
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>کد کالا</th><th>نام کالا</th><th>تعداد مورد نیاز</th><th>تامین‌کننده</th></tr></thead>";
        echo "<tbody>";
        foreach ($supplier_list as $item) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($item['item_code']) . "</td>";
            echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['total_needed']) . "</td>";
            echo "<td>" . htmlspecialchars($item['supplier_name'] ?? 'نامشخص') . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "<div class='text-center mt-4'>";
        echo "<button class='btn btn-primary' onclick='window.print()'>چاپ</button>";
        echo "</div>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("خطا: " . $e->getMessage());
    }
}

// اضافه کردن قابلیت حذف یا کنسل کردن سفارش
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = clean($_POST['order_id']);
    $conn->query("UPDATE production_orders SET status = 'cancelled' WHERE order_id = $order_id");
    header("Location: production_orders.php?msg=cancelled");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id = clean($_POST['order_id']);
    $conn->query("DELETE FROM production_orders WHERE order_id = $order_id");
    $conn->query("DELETE FROM production_order_items WHERE order_id = $order_id");
    header("Location: production_orders.php?msg=deleted");
    exit;
}

// نمایش برگه سفارش با قالب مناسب
if (!empty($missing_suppliers)) {
    // اصلاح نمایش پیغام تایید قبلی
    $order = $conn->query("SELECT status, completed_at FROM production_orders WHERE order_id = $order_id")->fetch_assoc();
    if ($order['status'] !== 'draft') {
        $confirmation_date = $order['completed_at'];
        $confirmation_message = "این سفارش قبلاً تایید شده است.";
    } else {
        $confirmation_message = "";
        $confirmation_date = "";
    }

    echo "<html lang='fa' dir='rtl'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<title>برگه سفارش</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css' rel='stylesheet'>";
    echo "<style>@media print { .btn { display: none; } }</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='container mt-4'>";
    echo "<h3 class='text-center'>برگه سفارش</h3>";
    echo "<table class='table table-bordered'>";
    echo "<thead><tr><th>شماره ردیف</th><th>کد انبار</th><th>نام کالا</th><th>واحد</th><th>تعداد سفارش</th><th>تاریخ سفارش</th></tr></thead>";
    echo "<tbody>";
    $row_number = 1;
    foreach ($missing_suppliers as $supplier) {
        echo "<tr>";
        echo "<td>" . $row_number++ . "</td>";
        echo "<td>" . htmlspecialchars($supplier) . "</td>";
        echo "<td>نام کالا</td>";
        echo "<td>واحد</td>";
        echo "<td>تعداد سفارش</td>";
        echo "<td>" . date('Y-m-d') . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<div class='text-center mt-4'>";
    echo "<p>امضا درخواست کننده: ______________________</p>";
    echo "<p>آدرس سایت: www.example.com</p>";
    echo "<button class='btn btn-primary' onclick='window.print()'>چاپ</button>";
    echo "</div>";
    if (!empty($confirmation_message)) {
        echo "<div class='alert alert-warning text-center mt-4'>";
        echo htmlspecialchars($confirmation_message);
        if (!empty($confirmation_date)) {
            echo "<br>تاریخ تایید: " . htmlspecialchars($confirmation_date);
        }
        echo "</div>";
    }
    echo "</div>";
    echo "</body>";
    echo "</html>";
    exit;
}
