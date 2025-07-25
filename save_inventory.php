<?php
require_once 'config.php';
session_start();

$item_id = $_POST['item_id'];
$current_inventory = floatval($_POST['current_inventory']);
$notes = $_POST['notes'];
$session = $_SESSION['inventory_session'];

// دریافت اطلاعات کالا
$stmt = $conn->prepare("SELECT `min_inventory` FROM `inventory` WHERE `id` = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

// محاسبه مورد نیاز
$required = ($item['min_inventory'] && $current_inventory < $item['min_inventory']) 
    ? $item['min_inventory'] - $current_inventory 
    : 0;

// ذخیره در جدول inventory_records
$stmt = $conn->prepare("INSERT INTO `inventory_records` (`inventory_id`, `inventory_session`, `current_inventory`, `required`, `notes`, `updated_at`) 
                        VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("isdds", $item_id, $session, $current_inventory, $required, $notes);
$stmt->execute();

$stmt->close();
$conn->close();

header("Location: new_inventory.php");
exit();
?>